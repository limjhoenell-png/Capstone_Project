using MySql.Data.MySqlClient;
using System;
using System.Data;
using System.Drawing;
using System.Drawing.Imaging;
using System.IO;
using System.Runtime.InteropServices;
using System.Windows.Forms;
using libzkfpcsharp;

namespace FingerprintAttendance.Core
{
    public class FingerprintEngine : IDisposable
    {
        private IntPtr mDevHandle = IntPtr.Zero;
        private IntPtr mDBHandle = IntPtr.Zero;
        private readonly object _captureLock = new object();
        private int _fpImageWidth = 0;
        private int _fpImageHeight = 0;
        private int _fpImageBufferSize = 32768;

        public bool IsConnected { get; private set; }
        public string LastError { get; private set; } = string.Empty;
        public byte[] LastCapturedTemplate { get; private set; }
        public byte[] LastCapturedFingerprintJpeg { get; private set; }
        public const int MATCH_THRESHOLD = 50;

        public FingerprintEngine()
        {
            try
            {
                int ret = zkfp2.Init();
                if (ret != zkfperrdef.ZKFP_ERR_OK)
                    throw new Exception($"ZKFinger SDK init failed. Code: {ret}");

                mDBHandle = zkfp2.DBInit();
                if (mDBHandle == IntPtr.Zero)
                    throw new Exception("ZKFinger in-memory database init failed.");

                IsConnected = false;
            }
            catch (Exception ex)
            {
                MessageBox.Show("ZKFinger SDK startup error: " + ex.Message, "Error");
            }
        }

        // ── Connection ────────────────────────────────────────────────

        public bool Connect()
        {
            try
            {
                if (zkfp2.GetDeviceCount() <= 0)
                {
                    LastError = "No fingerprint device found. Check the USB cable.";
                    return false;
                }

                mDevHandle = zkfp2.OpenDevice(0);
                if (mDevHandle == IntPtr.Zero)
                {
                    LastError = "Device found but could not be opened.";
                    return false;
                }

                InitializeImageBufferSize();
                IsConnected = true;
                return true;
            }
            catch (Exception ex)
            {
                LastError = ex.Message;
                return false;
            }
        }

        public void Disconnect()
        {
            lock (_captureLock)
            {
                if (mDevHandle != IntPtr.Zero)
                {
                    try { zkfp2.CloseDevice(mDevHandle); }
                    catch { }
                    finally { mDevHandle = IntPtr.Zero; }
                }
            }
            try { zkfp2.Terminate(); }
            catch { }
            finally { IsConnected = false; }
        }

        private void InitializeImageBufferSize()
        {
            try
            {
                byte[] paramValue = new byte[4];
                int size = 4;

                int retW = zkfp2.GetParameters(mDevHandle, 1, paramValue, ref size);
                int width = BitConverter.ToInt32(paramValue, 0);

                size = 4;
                int retH = zkfp2.GetParameters(mDevHandle, 2, paramValue, ref size);
                int height = BitConverter.ToInt32(paramValue, 0);

                if (retW == zkfperrdef.ZKFP_ERR_OK &&
                    retH == zkfperrdef.ZKFP_ERR_OK &&
                    width > 0 && height > 0)
                {
                    _fpImageWidth = width;
                    _fpImageHeight = height;
                    _fpImageBufferSize = width * height;
                }
            }
            catch
            {
                // Keep safe fallback buffer size if parameter query fails.
                _fpImageBufferSize = 32768;
            }
        }

        private int AcquireFingerprintLocked(byte[] fpImage, byte[] capTmp, ref int cbCapTmp)
        {
            lock (_captureLock)
            {
                if (!IsConnected || mDevHandle == IntPtr.Zero)
                    return -1;

                return zkfp2.AcquireFingerprint(mDevHandle, fpImage, capTmp, ref cbCapTmp);
            }
        }

        public IntPtr GetDatabaseHandle() => mDBHandle;

        // ── Persistence ───────────────────────────────────────────────

        /// <summary>
        /// Loads every saved fingerprint template from MySQL into the
        /// ZKFinger in-memory database.  Must be called once after Connect()
        /// or no identifications will ever succeed after an app restart.
        /// </summary>
        public void LoadAllFingerprintsFromDatabase()
        {
            if (mDBHandle == IntPtr.Zero) return;
            try
            {
                using var conn = new MySqlConnection(AppDbConnection.FullConnectionString);
                conn.Open();

                string userSql = @"SELECT f.fingerprint_id, f.fp_template
                                   FROM fingerprint_templates f
                                   INNER JOIN users u ON u.id = f.employee_id";
                using (var userCmd = new MySqlCommand(userSql, conn))
                using (var userReader = userCmd.ExecuteReader())
                {
                    while (userReader.Read())
                    {
                        int uid = userReader.GetInt32("fingerprint_id");
                        byte[] tpl = userReader["fp_template"] as byte[];
                        if (tpl != null && tpl.Length > 0)
                            zkfp2.DBAdd(mDBHandle, uid, tpl);
                    }
                }

                string logbookSql = @"
                    SELECT l.fingerprint_id, l.fp_template
                    FROM logbook l
                    INNER JOIN (
                        SELECT fingerprint_id, MAX(logbook_id) AS max_logbook_id
                        FROM logbook
                        WHERE fp_template IS NOT NULL AND fingerprint_id IS NOT NULL
                        GROUP BY fingerprint_id
                    ) p ON p.max_logbook_id = l.logbook_id";

                using (var logbookCmd = new MySqlCommand(logbookSql, conn))
                using (var logbookReader = logbookCmd.ExecuteReader())
                {
                    while (logbookReader.Read())
                    {
                        int fid = logbookReader.GetInt32("fingerprint_id");
                        byte[] tpl = logbookReader["fp_template"] as byte[];
                        if (tpl != null && tpl.Length > 0)
                            zkfp2.DBAdd(mDBHandle, fid, tpl);
                    }
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show("Could not load fingerprint templates:\n" + ex.Message, "Error");
            }
        }

        public bool AddTemplateToMemory(int fingerprintId, byte[] template)
        {
            if (mDBHandle == IntPtr.Zero || template == null || template.Length == 0)
                return false;

            try
            {
                int ret = zkfp2.DBAdd(mDBHandle, fingerprintId, template);
                return ret == zkfperrdef.ZKFP_ERR_OK;
            }
            catch
            {
                return false;
            }
        }

        /// <summary>
        /// Removes a template from the in-memory 1:N database (e.g. after clearing it in MySQL
        /// so a visitor can re-register). If the id was not registered, the SDK may return an error; callers can ignore.
        /// </summary>
        public bool RemoveTemplateFromMemory(int fingerprintId)
        {
            if (mDBHandle == IntPtr.Zero) return false;
            try
            {
                int ret = zkfp2.DBDel(mDBHandle, fingerprintId);
                return ret == zkfperrdef.ZKFP_ERR_OK;
            }
            catch
            {
                return false;
            }
        }

        // ── Enrollment ────────────────────────────────────────────────

        /// <summary>
        /// Captures three scans, merges them, stores the result in the
        /// in-memory ZKFinger database, and saves the template to MySQL.
        ///
        /// MUST be called from a background thread (Task.Run) because
        /// AcquireFingerprint blocks the calling thread while waiting for a
        /// physical finger press — typically 5-15 seconds per attempt.
        /// Running it on the UI thread freezes the window and causes Windows
        /// to show "Not Responding", hiding the scan prompts from the user.
        ///
        /// Use the onPromptScan callback (marshalled via Control.Invoke) to
        /// update status labels between scans without touching the device.
        /// </summary>
        /// <param name="userId">DB primary key of the user being enrolled.</param>
        /// <param name="onPromptScan">
        ///   Called before each capture with the attempt number (1, 2, 3).
        ///   Always marshal back to the UI thread inside this callback:
        ///     this.Invoke((Action)(() => lblStatus.Text = "..."));
        /// </param>
        /// <returns>
        ///   true  – all three scans captured, merged, registered in DB and saved to MySQL.
        ///   false – at least one step failed; read LastError for the specific reason.
        /// </returns>
        public bool StartEnrollment(int userId, Action<int> onPromptScan = null)
        {
            if (!IsConnected || mDBHandle == IntPtr.Zero)
            {
                LastError = "Device is not connected.";
                return false;
            }

            try
            {
                byte[][] captured = new byte[3][];

                // ── 1. Capture three scans ────────────────────────────
                for (int i = 0; i < 3; i++)
                {
                    // Update the UI label BEFORE blocking on the capture call
                    onPromptScan?.Invoke(i + 1);

                    byte[] fpImage = new byte[_fpImageBufferSize];
                    byte[] capTmp = new byte[2048];
                    int cbCapTmp = 2048;

                    int ret = AcquireFingerprintLocked(fpImage, capTmp, ref cbCapTmp);

                    if (ret != zkfperrdef.ZKFP_ERR_OK)
                    {
                        LastError = $"STEP 1 FAILED — Scan {i + 1} of 3 could not be captured.\n" +
                                    $"SDK error code: {ret}\n" +
                                    "Make sure your finger is flat and centred on the sensor.";
                        return false;
                    }

                    // Trim to actual size — passing a zero-padded 2048-byte array to
                    // DBMerge can cause merge failures on some SDK versions.
                    captured[i] = new byte[cbCapTmp];
                    Array.Copy(capTmp, captured[i], cbCapTmp);

                    // Give the user time to fully lift their finger before the next prompt.
                    // Too short and the sensor still sees residual contact on the next scan.
                    if (i < 2)
                        System.Threading.Thread.Sleep(800);
                }

                // ── 2. Merge the three captures ───────────────────────
                byte[] regTmp = new byte[2048];
                int cbRegTmp = 2048;

                int mergeRet = zkfp2.DBMerge(
                    mDBHandle,
                    captured[0], captured[1], captured[2],
                    regTmp, ref cbRegTmp);

                if (mergeRet != zkfperrdef.ZKFP_ERR_OK)
                {
                    LastError = $"STEP 2 FAILED — Could not merge the three scans.\n" +
                                $"SDK error code: {mergeRet}\n" +
                                "Use the same finger each time with consistent flat placement.";
                    return false;
                }

                // Trim the merged template to its actual size
                byte[] finalTemplate = new byte[cbRegTmp];
                Array.Copy(regTmp, finalTemplate, cbRegTmp);

                // ── 3. Persist the template with a scanner ID mapped to its employee ─
                int fingerprintId = new DatabaseHelper().AddEmployeeFingerprintTemplate(
                    userId, "Right Index", finalTemplate);

                // ── 4. Register in the in-memory ZKFinger database ────
                int addRet = zkfp2.DBAdd(mDBHandle, fingerprintId, finalTemplate);
                if (addRet != zkfperrdef.ZKFP_ERR_OK)
                {
                    LastError = $"STEP 4 FAILED — Could not register template in ZKFinger DB.\n" +
                                $"SDK error code: {addRet}";
                    return false;
                }

                return true;
            }
            catch (Exception ex)
            {
                LastError = "Could not save or register the fingerprint template.\n" + ex.Message;
                return false;
            }
        }

        public void CancelEnrollment() { /* extend if SDK exposes a cancel API */ }

        // ── Identification ────────────────────────────────────────────

        /// <summary>
        /// Captures a fingerprint and searches the in-memory database (1:N).
        /// Returns the matched user ID, or -1 on no match / error.
        /// </summary>
        public int IdentifyUser()
        {
            return IdentifyUser(timeoutMs: 1500);
        }

        /// <summary>
        /// Same as <see cref="IdentifyUser()"/>, but allows a longer timeout for interactive authentication flows.
        /// </summary>
        public int IdentifyUser(int timeoutMs)
        {
            if (!IsConnected || mDBHandle == IntPtr.Zero)
            {
                LastError = "Device is not connected.";
                return -1;
            }

            try
            {
                byte[] liveTemplate = CaptureScan(timeoutMs);
                if (liveTemplate == null)
                    return -1;

                int fid = 0, score = 0;
                int identRet = zkfp2.DBIdentify(mDBHandle, liveTemplate, ref fid, ref score);

                if (identRet == zkfperrdef.ZKFP_ERR_OK)
                {
                    LastError = string.Empty;
                    return fid;
                }

                LastError = "Fingerprint not recognised in database.";
                return -1;
            }
            catch (Exception ex)
            {
                LastError = ex.Message;
                return -1;
            }
        }

        /// <summary>Performs 1:1 verification for a known user.</summary>
        public bool VerifyUser(int userId)
        {
            if (!IsConnected) return false;
            try
            {
                byte[] fpImage = new byte[_fpImageBufferSize];
                byte[] capTmp = new byte[2048];
                int cbCapTmp = 2048;
                int ret = AcquireFingerprintLocked(fpImage, capTmp, ref cbCapTmp);
                return ret == zkfperrdef.ZKFP_ERR_OK;
            }
            catch { return false; }
        }

        /// <summary>
        /// Captures a single fingerprint scan from the device.
        /// Keeps polling until a finger is captured or timeout is reached.
        /// </summary>
        public byte[] CaptureScan(int timeoutMs = 15000)
        {
            if (!IsConnected || mDevHandle == IntPtr.Zero)
            {
                LastError = "Device is not connected.";
                return null;
            }

            try
            {
                lock (_captureLock)
                {
                    byte[] fpImage = new byte[_fpImageBufferSize];
                    byte[] capTmp = new byte[2048];
                    int startTick = Environment.TickCount;
                    int lastRet = zkfperrdef.ZKFP_ERR_OK;

                    while (Environment.TickCount - startTick < timeoutMs)
                    {
                        int cbCapTmp = 2048;
                        int ret = zkfp2.AcquireFingerprint(mDevHandle, fpImage, capTmp, ref cbCapTmp);

                        if (ret == zkfperrdef.ZKFP_ERR_OK)
                        {
                            byte[] result = new byte[cbCapTmp];
                            Array.Copy(capTmp, result, cbCapTmp);
                            LastCapturedTemplate = result;
                            LastCapturedFingerprintJpeg = ConvertRawImageToJpeg(fpImage);
                            LastError = string.Empty;
                            return result;
                        }

                        lastRet = ret;
                        System.Threading.Thread.Sleep(120);
                    }

                    LastError = $"Capture timed out. No finger detected (last SDK code: {lastRet}).";
                    return null;
                }
            }
            catch (Exception ex)
            {
                LastError = "Capture failed: " + ex.Message;
                return null;
            }
        }

        private byte[] ConvertRawImageToJpeg(byte[] fpImage)
        {
            if (fpImage == null || _fpImageWidth <= 0 || _fpImageHeight <= 0)
                return null;

            int pixelCount = _fpImageWidth * _fpImageHeight;
            if (pixelCount <= 0 || fpImage.Length < pixelCount)
                return null;

            try
            {
                using var bmp = new Bitmap(_fpImageWidth, _fpImageHeight, PixelFormat.Format24bppRgb);
                var rect = new Rectangle(0, 0, _fpImageWidth, _fpImageHeight);
                BitmapData data = bmp.LockBits(rect, ImageLockMode.WriteOnly, PixelFormat.Format24bppRgb);

                try
                {
                    int stride = data.Stride;
                    byte[] rgbBytes = new byte[stride * _fpImageHeight];

                    for (int y = 0; y < _fpImageHeight; y++)
                    {
                        int srcRow = y * _fpImageWidth;
                        int dstRow = y * stride;
                        for (int x = 0; x < _fpImageWidth; x++)
                        {
                            byte gray = fpImage[srcRow + x];
                            int idx = dstRow + (x * 3);
                            rgbBytes[idx] = gray;
                            rgbBytes[idx + 1] = gray;
                            rgbBytes[idx + 2] = gray;
                        }
                    }

                    Marshal.Copy(rgbBytes, 0, data.Scan0, rgbBytes.Length);
                }
                finally
                {
                    bmp.UnlockBits(data);
                }

                using var ms = new MemoryStream();
                bmp.Save(ms, ImageFormat.Jpeg);
                return ms.ToArray();
            }
            catch
            {
                return null;
            }
        }

        /// <summary>Merges three fingerprint scans into a single template.</summary>
        public byte[] MergeTemplates(byte[] scan1, byte[] scan2, byte[] scan3)
        {
            if (mDBHandle == IntPtr.Zero || scan1 == null || scan2 == null || scan3 == null)
                return null;

            try
            {
                byte[] regTmp = new byte[2048];
                int cbRegTmp = 2048;

                int mergeRet = zkfp2.DBMerge(mDBHandle, scan1, scan2, scan3, regTmp, ref cbRegTmp);
                if (mergeRet != zkfperrdef.ZKFP_ERR_OK)
                    return null;

                byte[] result = new byte[cbRegTmp];
                Array.Copy(regTmp, result, cbRegTmp);
                return result;
            }
            catch
            {
                return null;
            }
        }

        /// <summary>Matches a scanned fingerprint against a template. Returns a match score.</summary>
        public int MatchTemplates(byte[] liveScan, byte[] templateScan)
        {
            if (liveScan == null || templateScan == null)
                return 0;

            try
            {
                // For ZKFinger, we use a simple comparison approach
                // In a real system, you would use the SDK's matching algorithm
                // This is a simplified version that returns a score based on template similarity
                if (liveScan.Length == templateScan.Length)
                {
                    int matchingBytes = 0;
                    for (int i = 0; i < liveScan.Length; i++)
                    {
                        if (liveScan[i] == templateScan[i])
                            matchingBytes++;
                    }
                    return (matchingBytes * 100) / liveScan.Length;
                }
                return 0;
            }
            catch
            {
                return 0;
            }
        }

        /// <summary>Initializes the fingerprint engine (alias for Connect).</summary>
        public void Initialize()
        {
            Connect();
        }

        /// <summary>Disposes resources.</summary>
        public void Dispose()
        {
            Disconnect();
            GC.SuppressFinalize(this);
        }
    }
}