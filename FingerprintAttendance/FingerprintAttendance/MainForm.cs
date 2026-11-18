using FingerprintAttendance.Core;
using FingerprintAttendance.Models;
using System;
using System.Collections.Generic;
using System.Drawing;
using System.Drawing.Drawing2D;
using System.Drawing.Imaging;
using System.Diagnostics;
using System.IO;
using System.Runtime.InteropServices;
using System.Text;
using System.Threading.Tasks;
using System.Windows.Forms;

namespace FingerprintAttendance
{
    public partial class MainForm : Form
    {
        private FingerprintEngine _engine = new FingerprintEngine();
        private DatabaseHelper _db = new DatabaseHelper();
        private List<UserModel> _users;
        private int? _lastEmployeeStatusUserId = null;
        private string _lastEmployeeStatusActionText = null;
        private DateTime _lastEmployeeStatusSetAt = DateTime.MinValue;
        private string _lastEmployeeStatusRenderedName = null;
        private string _statusMessageToHide;
        private volatile bool _isScanning;
        private bool _isEnrollOpen = false;
        private bool _isReturningVisitorFlowOpen = false;
        private string _lastScreenDeviceName = null;
        private bool _isApplyingPerScreenMaximize = false;
        private bool _chromeBusy;
        private Image _scannerGuideIllustration;
        private Bitmap _barangayLogo;
        private readonly Timer _resultFlashTimer = new Timer { Interval = 10 };
        private readonly Timer _notificationHideTimer = new Timer { Interval = NotificationDisplayDurationMs };
        private readonly Timer _statusMessageHideTimer = new Timer { Interval = 5000 };
        private readonly Timer _resultSoundCleanupTimer = new Timer { Interval = 150 };
        private readonly Timer _logoBlinkTimer = new Timer { Interval = 15 };
        private Panel _notificationModal;
        private Panel _notificationModalShadow;
        private Label _notificationModalLabel;
        private DateTime _logoBlinkStartedAt;
        private bool _logoBlinkActive;
        private Color _resultFlashColor;
        private Color _resultFlashBorderColor = Color.White;
        private DateTime _resultFlashStartedAt;
        private bool _resultFlashActive;
        private bool _resultFlashShouldBlink;
        private bool _resultSoundOpen;
        private const string ResultSoundAlias = "fingerprintAttendanceResult";
        private const int NotificationDisplayDurationMs = 4000;
        private static readonly Color DarkGreen = Color.FromArgb(15, 81, 50);
        private static readonly Color SuccessModalBackground = Color.FromArgb(0x06, 0x4E, 0x3B);
        private static readonly Color ErrorModalBackground = Color.FromArgb(0x7F, 0x1D, 0x1D);
        private static readonly Color ModalText = Color.White;
        private bool _notificationModalIsSuccess;
        private readonly Font _notificationBannerFont = CreateNotificationBannerFont();

        private static Font CreateNotificationBannerFont()
        {
            return new Font("Consolas", 24F, FontStyle.Bold, GraphicsUnit.Point);
        }

        private void ConfigureNotificationModal()
        {
            pnlStatusArea.Controls.Remove(lblUser);
            lblUser.Visible = false;

            _notificationModalShadow = new Panel
            {
                Width = 720,
                Height = 96,
                BackColor = Color.FromArgb(100, 0, 0, 0),
                BorderStyle = BorderStyle.None,
                Visible = false
            };
            _notificationModalShadow.Padding = new Padding(0);

            _notificationModal = new Panel
            {
                Width = 720,
                Height = 96,
                BackColor = SuccessModalBackground,
                BorderStyle = BorderStyle.None,
                Visible = false
            };
            _notificationModal.Padding = new Padding(12, 16, 12, 16);

            _notificationModalLabel = new Label
            {
                Dock = DockStyle.Fill,
                Text = string.Empty,
                ForeColor = ModalText,
                BackColor = Color.Transparent,
                Font = new Font("Consolas", 18F, FontStyle.Bold, GraphicsUnit.Point),
                TextAlign = ContentAlignment.MiddleCenter,
                AutoSize = false,
                AutoEllipsis = false,
                Padding = new Padding(12, 8, 12, 8),
                Margin = Padding.Empty
            };
            _notificationModal.Controls.Add(_notificationModalLabel);
            _notificationModal.Paint += PaintNotificationModalBorder;
            _notificationModalShadow.Paint += PaintNotificationModalShadow;
            panelLeft.Controls.Add(_notificationModal);
            panelLeft.Controls.Add(_notificationModalShadow);
            _notificationModalShadow.SendToBack();
            panelLeft.Resize += (_, __) => RepositionNotificationModal();
            RepositionNotificationModal();
        }

        private void PaintNotificationModalBorder(object sender, PaintEventArgs e)
        {
            e.Graphics.SmoothingMode = SmoothingMode.AntiAlias;
            e.Graphics.Clear(_notificationModal.BackColor);
            Color borderColor = _notificationModalIsSuccess
                ? Color.FromArgb(52, 211, 153)
                : Color.FromArgb(248, 113, 113);

            Rectangle bounds = _notificationModal.ClientRectangle;
            bounds.Inflate(-2, -2);
            using (var path = CreateRoundedRectanglePath(bounds, 8))
            using (var border = new Pen(borderColor, 3F))
            {
                e.Graphics.DrawPath(border, path);
            }
        }

        private void PaintNotificationModalShadow(object sender, PaintEventArgs e)
        {
            e.Graphics.SmoothingMode = SmoothingMode.AntiAlias;
            e.Graphics.Clear(Color.FromArgb(20, 0, 0, 0));

            Rectangle bounds = _notificationModalShadow.ClientRectangle;
            bounds.Inflate(-2, -2);
            using (var path = CreateRoundedRectanglePath(bounds, 10))
            using (var shadowBrush = new SolidBrush(Color.FromArgb(110, 0, 0, 0)))
            {
                e.Graphics.FillPath(shadowBrush, path);
            }
        }

        private static GraphicsPath CreateRoundedRectanglePath(Rectangle bounds, int radius)
        {
            int diameter = radius * 2;
            var path = new GraphicsPath();
            path.AddArc(bounds.X, bounds.Y, diameter, diameter, 180, 90);
            path.AddArc(bounds.Right - diameter, bounds.Y, diameter, diameter, 270, 90);
            path.AddArc(bounds.Right - diameter, bounds.Bottom - diameter, diameter, diameter, 0, 90);
            path.AddArc(bounds.X, bounds.Bottom - diameter, diameter, diameter, 90, 90);
            path.CloseFigure();
            return path;
        }

        private void RepositionNotificationModal()
        {
            if (_notificationModal == null || panelLeft.ClientSize.Width <= 0)
                return;

            int availableWidth = Math.Max(1, panelLeft.ClientSize.Width - 48);
            int width = Math.Min(availableWidth, Math.Max(420, (int)(panelLeft.ClientSize.Width * 0.58F)));
            _notificationModal.Width = width;
            _notificationModal.Height = 96;

            int availableHeight = Math.Max(96, panelLeft.ClientSize.Height - pnlStatusArea.Height);
            int x = (panelLeft.ClientSize.Width - _notificationModal.Width) / 2;
            int y = Math.Max(60, (availableHeight - _notificationModal.Height) / 2);
            _notificationModal.Location = new Point(x, y);
            _notificationModalShadow.Location = new Point(x + 8, y + 10);
            _notificationModalShadow.Size = _notificationModal.Size;

            using (var modalPath = CreateRoundedRectanglePath(
                new Rectangle(0, 0, _notificationModal.Width, _notificationModal.Height), 8))
            {
                _notificationModal.Region?.Dispose();
                _notificationModal.Region = new Region(modalPath);
            }
            using (var shadowPath = CreateRoundedRectanglePath(
                new Rectangle(0, 0, _notificationModalShadow.Width, _notificationModalShadow.Height), 10))
            {
                _notificationModalShadow.Region?.Dispose();
                _notificationModalShadow.Region = new Region(shadowPath);
            }
            _notificationModal.BringToFront();
        }

        private void ShowNotificationModal(string text, bool isSuccess)
        {
            if (_notificationModal == null || _notificationModalLabel == null)
                return;

            _notificationModalLabel.Text = text;
            _notificationModalLabel.TextAlign = ContentAlignment.MiddleCenter;
            _notificationModalIsSuccess = isSuccess;
            _notificationModal.BackColor = isSuccess ? SuccessModalBackground : ErrorModalBackground;
            _notificationModalLabel.ForeColor = ModalText;
            RepositionNotificationModal();
            _notificationModal.Visible = true;
            _notificationModalShadow.Visible = true;
            _notificationModal.BringToFront();
            _notificationModal.Invalidate();
            _notificationModal.Refresh();
            _notificationModalShadow.Invalidate();
            _notificationModalShadow.Refresh();
            _notificationHideTimer.Stop();
            _notificationHideTimer.Interval = NotificationDisplayDurationMs;
            _notificationHideTimer.Start();
        }

        private void HideNotificationModal()
        {
            if (_notificationModal != null)
            {
                _notificationModal.Visible = false;
                _notificationModalShadow.Visible = false;
                _notificationModalLabel.Text = string.Empty;
                _notificationModal.BackColor = Color.Transparent;
                _notificationModal.Invalidate();
                _notificationModal.Refresh();
                _notificationModal.Parent?.Refresh();
            }
        }

        public MainForm()
        {
            InitializeComponent();
            DesktopTheme.Apply(this);
            lblScanSection.TextAlignChanged += (_, __) =>
            {
                if (lblScanSection.TextAlign != ContentAlignment.MiddleCenter)
                    lblScanSection.TextAlign = ContentAlignment.MiddleCenter;
            };
            ApplyMainHeaderTheme();
            ApplyMainStatusTheme();
            ConfigureScannerPresentation();
            ConfigureNotificationModal();
            _resultFlashTimer.Tick += ResultFlashTimer_Tick;
            _notificationHideTimer.Tick += NotificationHideTimer_Tick;
            _statusMessageHideTimer.Tick += StatusMessageHideTimer_Tick;
            _resultSoundCleanupTimer.Tick += ResultSoundCleanupTimer_Tick;
            _logoBlinkTimer.Tick += LogoBlinkTimer_Tick;
            panelLeft.Paint += PaintScannerWorkspaceBorder;

            // Start maximized on the monitor that opens the app. After that the window can be
            // restored and dragged; it is not forced back to maximized on every move.
            StartPosition = FormStartPosition.Manual;
            WindowState = FormWindowState.Maximized;
            Shown += (_, __) =>
            {
                ApplyMaximizeToCurrentScreen(force: true);
                ApplyResponsiveChrome();
            };
            Resize += (_, __) => ApplyResponsiveChrome();
        }

        private void ApplyMainHeaderTheme()
        {
            panelHeader.BackColor = Color.FromArgb(11, 19, 43);
            headerLayout.Padding = new Padding(24, 8, 24, 8);
            lblAppTitle.ForeColor = Color.FromArgb(255, 255, 255);
            lblCurrentTime.ForeColor = Color.FromArgb(255, 255, 255);
            lblCurrentDate.ForeColor = Color.FromArgb(255, 255, 255);
            lblScanSection.TextAlign = ContentAlignment.MiddleCenter;
        }

        private void ApplyMainStatusTheme()
        {
            Color navy = Color.FromArgb(11, 19, 43);
            pnlStatusArea.BackColor = navy;
            pnlStatusArea.Padding = Padding.Empty;
            pnlStatusArea.Height = 76;
            lblUser.Visible = false;
            lblStatus.BackColor = navy;
            lblStatus.ForeColor = Color.FromArgb(255, 255, 255);
            lblStatus.Font = new Font("Consolas", 18F, FontStyle.Bold);
            lblStatus.Dock = DockStyle.Fill;
            lblStatus.TextAlign = ContentAlignment.MiddleCenter;
            lblStatus.AutoSize = false;
            lblStatus.AutoEllipsis = true;
            lblStatus.Padding = new Padding(24, 6, 24, 6);
            lblStatus.Margin = Padding.Empty;
            lblStatus.TextChanged -= MainStatusTextChanged;
            lblStatus.TextChanged += MainStatusTextChanged;
            lblStatus.TextAlignChanged -= MainStatusAlignmentChanged;
            lblStatus.TextAlignChanged += MainStatusAlignmentChanged;
            pnlStatusDivider.Dock = DockStyle.Top;
            pnlStatusDivider.BackColor = Color.FromArgb(45, 63, 101);
        }

        private void MainStatusTextChanged(object sender, EventArgs e)
        {
            lblStatus.TextAlign = ContentAlignment.MiddleCenter;
        }

        private void MainStatusAlignmentChanged(object sender, EventArgs e)
        {
            if (lblStatus.TextAlign != ContentAlignment.MiddleCenter)
                lblStatus.TextAlign = ContentAlignment.MiddleCenter;
        }

        private void FlashResultBanner(bool isSuccess)
        {
            _resultFlashTimer.Stop();
            _notificationHideTimer.Stop();
            _notificationHideTimer.Interval = NotificationDisplayDurationMs;
            _resultFlashStartedAt = DateTime.UtcNow;
            _resultFlashColor = isSuccess
                ? Color.FromArgb(0, 255, 0)
                : Color.FromArgb(255, 0, 0);
            _resultFlashShouldBlink = !isSuccess;
            _resultFlashActive = true;
            _resultFlashBorderColor = _resultFlashColor;
            if (isSuccess)
            {
                ShowNotificationModal(lblUser.Text, isSuccess: true);
                _notificationHideTimer.Start();
            }
            else
            {
                string notificationText = string.IsNullOrWhiteSpace(lblUser.Text)
                    ? lblStatus.Text
                    : lblUser.Text;
                ShowNotificationModal(notificationText, isSuccess: false);
                lblStatus.Text = "Try again.";
                lblStatus.ForeColor = Color.White;
                _notificationHideTimer.Start();
            }
            PlayResultSound(isSuccess);
            _resultFlashTimer.Start();
            panelLeft.Invalidate();
        }

        private void NotificationHideTimer_Tick(object sender, EventArgs e)
        {
            _notificationHideTimer.Stop();
            lblUser.Text = string.Empty;
            HideNotificationModal();
            if (string.Equals(lblStatus.Text, "Try again.", StringComparison.Ordinal))
            {
                lblStatus.Text = "Biometric scanner is ready to use.";
                lblStatus.ForeColor = Color.White;
            }
        }

        private void StatusMessageHideTimer_Tick(object sender, EventArgs e)
        {
            _statusMessageHideTimer.Stop();
            if (!string.Equals(lblStatus.Text, _statusMessageToHide, StringComparison.Ordinal))
                return;

            lblStatus.Text = "Biometric scanner is ready to use.";
            lblStatus.ForeColor = Color.White;
            _statusMessageToHide = null;
        }

        private void StartBarangayLogoBlink()
        {
            _logoBlinkStartedAt = DateTime.UtcNow;
            _logoBlinkActive = true;
            _logoBlinkTimer.Stop();
            _logoBlinkTimer.Start();
            picBarangayLogo.Invalidate();
        }

        private void LogoBlinkTimer_Tick(object sender, EventArgs e)
        {
            if ((DateTime.UtcNow - _logoBlinkStartedAt).TotalMilliseconds >= 1500)
            {
                _logoBlinkTimer.Stop();
                _logoBlinkActive = false;
            }

            picBarangayLogo.Invalidate();
        }

        private void PaintBarangayLogo(object sender, PaintEventArgs e)
        {
            if (_barangayLogo == null || picBarangayLogo.ClientSize.Width <= 0 || picBarangayLogo.ClientSize.Height <= 0)
                return;

            float opacity = 1F;
            if (_logoBlinkActive)
            {
                double elapsedMs = Math.Max(0, (DateTime.UtcNow - _logoBlinkStartedAt).TotalMilliseconds);
                double cyclePosition = (elapsedMs % 750.0) / 750.0;
                opacity = (float)(0.25 + 0.75 * (1 + Math.Cos(cyclePosition * Math.PI * 2)) / 2);
            }

            float scale = Math.Min(
                (float)picBarangayLogo.ClientSize.Width / _barangayLogo.Width,
                (float)picBarangayLogo.ClientSize.Height / _barangayLogo.Height);
            int width = Math.Max(1, (int)(_barangayLogo.Width * scale));
            int height = Math.Max(1, (int)(_barangayLogo.Height * scale));
            var destination = new Rectangle(
                (picBarangayLogo.ClientSize.Width - width) / 2,
                (picBarangayLogo.ClientSize.Height - height) / 2,
                width,
                height);

            e.Graphics.Clear(picBarangayLogo.Parent?.BackColor ?? picBarangayLogo.BackColor);
            e.Graphics.InterpolationMode = InterpolationMode.HighQualityBicubic;
            e.Graphics.PixelOffsetMode = PixelOffsetMode.HighQuality;

            using (var attributes = new ImageAttributes())
            {
                var colorMatrix = new ColorMatrix { Matrix33 = opacity };
                attributes.SetColorMatrix(colorMatrix, ColorMatrixFlag.Default, ColorAdjustType.Bitmap);
                e.Graphics.DrawImage(
                    _barangayLogo,
                    destination,
                    0,
                    0,
                    _barangayLogo.Width,
                    _barangayLogo.Height,
                    GraphicsUnit.Pixel,
                    attributes);
            }
        }

        private void PlayResultSound(bool isSuccess)
        {
            CloseResultSound();

            string fileName = isSuccess ? "Success Sounds.mp3" : "Error Sounds.mp3";
            string soundPath = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "Assets", fileName);
            if (!File.Exists(soundPath))
            {
                Trace.TraceError("Result sound file was not found: {0}", soundPath);
                return;
            }

            int openResult = mciSendString(
                $"open \"{soundPath}\" type mpegvideo alias {ResultSoundAlias}",
                null,
                0,
                IntPtr.Zero);
            if (openResult != 0)
            {
                TraceMciError("open result sound", openResult);
                return;
            }

            _resultSoundOpen = true;
            int playResult = mciSendString($"play {ResultSoundAlias}", null, 0, IntPtr.Zero);
            if (playResult != 0)
            {
                TraceMciError("play result sound", playResult);
                CloseResultSound();
                return;
            }

            _resultSoundCleanupTimer.Start();
        }

        private void ResultSoundCleanupTimer_Tick(object sender, EventArgs e)
        {
            if (!_resultSoundOpen)
            {
                _resultSoundCleanupTimer.Stop();
                return;
            }

            var mode = new StringBuilder(32);
            int statusResult = mciSendString(
                $"status {ResultSoundAlias} mode",
                mode,
                mode.Capacity,
                IntPtr.Zero);
            if (statusResult != 0)
            {
                TraceMciError("check result sound playback", statusResult);
                CloseResultSound();
                return;
            }

            if (!string.Equals(mode.ToString(), "playing", StringComparison.OrdinalIgnoreCase))
                CloseResultSound();
        }

        private void CloseResultSound()
        {
            _resultSoundCleanupTimer.Stop();
            if (!_resultSoundOpen)
                return;

            int closeResult = mciSendString($"close {ResultSoundAlias}", null, 0, IntPtr.Zero);
            _resultSoundOpen = false;
            if (closeResult != 0)
                TraceMciError("close result sound", closeResult);
        }

        private static void TraceMciError(string operation, int errorCode)
        {
            var errorText = new StringBuilder(256);
            string detail = mciGetErrorString(errorCode, errorText, errorText.Capacity)
                ? errorText.ToString()
                : "Unknown multimedia error.";
            Trace.TraceError("Unable to {0}: {1}", operation, detail);
        }

        [DllImport("winmm.dll", CharSet = CharSet.Unicode)]
        private static extern int mciSendString(
            string command,
            StringBuilder returnValue,
            int returnLength,
            IntPtr callback);

        [DllImport("winmm.dll", CharSet = CharSet.Unicode)]
        private static extern bool mciGetErrorString(
            int errorCode,
            StringBuilder errorText,
            int errorTextSize);

        private void ResultFlashTimer_Tick(object sender, EventArgs e)
        {
            const double totalDurationMs = 3000;
            const int blinkCycles = 10;
            const double phaseDurationMs = totalDurationMs / (blinkCycles * 2.0);
            double elapsedMs = (DateTime.UtcNow - _resultFlashStartedAt).TotalMilliseconds;
            if (elapsedMs >= totalDurationMs)
            {
                _resultFlashTimer.Stop();
                _resultFlashActive = false;
                lblUser.BackColor = Color.White;
                panelLeft.Invalidate();
                return;
            }

            Color flashColor = _resultFlashColor;
            if (_resultFlashShouldBlink)
            {
                int phase = (int)(elapsedMs / phaseDurationMs);
                flashColor = phase % 2 == 0 ? _resultFlashColor : Color.White;
            }
            _resultFlashBorderColor = flashColor;
            panelLeft.Invalidate();
        }

        private void PaintScannerWorkspaceBorder(object sender, PaintEventArgs e)
        {
            if (!_resultFlashActive)
                return;

            Rectangle bounds = panelLeft.ClientRectangle;
            if (bounds.Width < 2 || bounds.Height < 2)
                return;

            Padding padding = panelLeft.Padding;
            using (var fill = new SolidBrush(_resultFlashBorderColor))
            {
                if (padding.Top > 0)
                    e.Graphics.FillRectangle(fill, 0, 0, bounds.Width, Math.Min(padding.Top, bounds.Height));
                if (padding.Bottom > 0)
                {
                    int height = Math.Min(padding.Bottom, bounds.Height);
                    e.Graphics.FillRectangle(fill, 0, bounds.Height - height, bounds.Width, height);
                }

                int sideTop = Math.Min(padding.Top, bounds.Height);
                int sideBottom = Math.Max(sideTop, bounds.Height - Math.Min(padding.Bottom, bounds.Height));
                int sideHeight = sideBottom - sideTop;
                if (sideHeight > 0 && padding.Left > 0)
                    e.Graphics.FillRectangle(fill, 0, sideTop, Math.Min(padding.Left, bounds.Width), sideHeight);
                if (sideHeight > 0 && padding.Right > 0)
                {
                    int width = Math.Min(padding.Right, bounds.Width);
                    e.Graphics.FillRectangle(fill, bounds.Width - width, sideTop, width, sideHeight);
                }
            }
        }

        private void ConfigureScannerPresentation()
        {
            string illustrationPath = Path.Combine(
                AppDomain.CurrentDomain.BaseDirectory,
                "Assets",
                "biometric-scanner-guide.png");
            if (!File.Exists(illustrationPath))
                throw new FileNotFoundException("The biometric scanner guide asset is missing.", illustrationPath);

            using (var stream = File.OpenRead(illustrationPath))
            using (var source = Image.FromStream(stream))
                _scannerGuideIllustration = new Bitmap(source);

            string logoPath = Path.Combine(
                AppDomain.CurrentDomain.BaseDirectory,
                "Assets",
                "barangay-logo.png");
            if (!File.Exists(logoPath))
                throw new FileNotFoundException("The Barangay logo asset is missing.", logoPath);

            using (var stream = File.OpenRead(logoPath))
            using (var source = Image.FromStream(stream))
                _barangayLogo = new Bitmap(source);
            _barangayLogo.MakeTransparent(Color.Black);

            var illustration = new PictureBox
            {
                Dock = DockStyle.Fill,
                Image = _scannerGuideIllustration,
                SizeMode = PictureBoxSizeMode.Zoom,
                BackColor = Color.FromArgb(8, 19, 30),
                Margin = Padding.Empty
            };

            var guidePanel = new Panel
            {
                Dock = DockStyle.Fill,
                BackColor = Color.FromArgb(8, 19, 30),
                Padding = new Padding(5),
                Margin = Padding.Empty
            };
            guidePanel.Controls.Add(illustration);
            guidePanel.Paint += (_, e) =>
            {
                Rectangle bounds = guidePanel.ClientRectangle;
                if (bounds.Width < 2 || bounds.Height < 2)
                    return;

                e.Graphics.SmoothingMode = SmoothingMode.AntiAlias;
                using (var outerBorder = new Pen(Color.FromArgb(45, 225, 255), 2F))
                using (var innerBorder = new Pen(Color.FromArgb(20, 118, 150), 1F))
                {
                    e.Graphics.DrawRectangle(outerBorder, 1, 1, bounds.Width - 3, bounds.Height - 3);
                    e.Graphics.DrawRectangle(innerBorder, 5, 5, bounds.Width - 11, bounds.Height - 11);
                }
            };

            var scannerLayout = new TableLayoutPanel
            {
                Dock = DockStyle.Fill,
                ColumnCount = 5,
                RowCount = 1,
                BackColor = Color.FromArgb(15, 23, 42),
                Margin = Padding.Empty,
                Padding = Padding.Empty,
                GrowStyle = TableLayoutPanelGrowStyle.FixedSize
            };
            scannerLayout.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 5F));
            scannerLayout.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 35F));
            scannerLayout.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 4F));
            scannerLayout.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 51F));
            scannerLayout.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 5F));
            scannerLayout.RowStyles.Add(new RowStyle(SizeType.Percent, 100F));

            var logoPanel = new Panel
            {
                Dock = DockStyle.Fill,
                BackColor = Color.FromArgb(15, 23, 42),
                Padding = new Padding(8),
                Margin = Padding.Empty
            };
            picBarangayLogo.Dock = DockStyle.Fill;
            picBarangayLogo.BackColor = Color.Transparent;
            picBarangayLogo.SizeMode = PictureBoxSizeMode.Zoom;
            picBarangayLogo.Margin = Padding.Empty;
            picBarangayLogo.Image = null;
            picBarangayLogo.Paint += PaintBarangayLogo;
            logoPanel.Controls.Add(picBarangayLogo);
            scannerLayout.Controls.Add(guidePanel, 1, 0);
            scannerLayout.Controls.Add(logoPanel, 3, 0);

            pnlScanInner.Controls.Clear();
            pnlScanInner.Controls.Add(scannerLayout);
        }

        private void ApplyResponsiveChrome()
        {
            if (_chromeBusy || WindowState == FormWindowState.Minimized)
                return;

            _chromeBusy = true;
            try
            {
                ApplyResponsiveChromeCore();
            }
            finally
            {
                _chromeBusy = false;
            }
        }

        private void ApplyResponsiveChromeCore()
        {
            if (WindowState == FormWindowState.Minimized)
                return;

            var work = Screen.FromControl(this).WorkingArea;
            int minW = Math.Min(800, Math.Max(640, work.Width - 16));
            int minH = Math.Min(520, Math.Max(420, work.Height - 16));
            var nextMinimum = new Size(minW, minH);
            if (MinimumSize != nextMinimum)
                MinimumSize = nextMinimum;

            int width = ClientSize.Width;
            const int sidebar = 320;
            if (panelRight.Width != sidebar)
                panelRight.Width = sidebar;

            bool compactHeader = width < 1100;
            headerLayout.ColumnStyles[0].Width = compactHeader ? 54F : 60F;
            headerLayout.ColumnStyles[1].Width = compactHeader ? 46F : 40F;
            headerClockLayout.PerformLayout();
            headerLayout.PerformLayout();
            headerClockLayout.PerformLayout();

            float headerFontSize = compactHeader ? 13F : 16F;
            while (headerFontSize > 6F)
            {
                bool fits;
                using (var candidate = new Font(lblAppTitle.Font.FontFamily, headerFontSize, FontStyle.Bold))
                {
                    int titleWidth = TextRenderer.MeasureText(
                        lblAppTitle.Text, candidate, Size.Empty,
                        TextFormatFlags.NoPadding | TextFormatFlags.SingleLine).Width;
                    int dateWidth = TextRenderer.MeasureText(
                        lblCurrentDate.Text, candidate, Size.Empty,
                        TextFormatFlags.NoPadding | TextFormatFlags.SingleLine).Width;
                    int timeWidth = TextRenderer.MeasureText(
                        lblCurrentTime.Text, candidate, Size.Empty,
                        TextFormatFlags.NoPadding | TextFormatFlags.SingleLine).Width;
                    fits = titleWidth <= lblAppTitle.ClientSize.Width &&
                           dateWidth <= headerClockLayout.GetColumnWidths()[0] &&
                           timeWidth <= headerClockLayout.GetColumnWidths()[1];
                }
                if (fits) break;
                headerFontSize -= 0.5F;
            }
            if (Math.Abs(lblAppTitle.Font.SizeInPoints - headerFontSize) > 0.4F)
                lblAppTitle.Font = new Font(lblAppTitle.Font.FontFamily, headerFontSize, FontStyle.Bold);
            if (Math.Abs(lblCurrentTime.Font.SizeInPoints - headerFontSize) > 0.4F)
                lblCurrentTime.Font = new Font(lblCurrentTime.Font.FontFamily, headerFontSize, FontStyle.Bold);
            if (Math.Abs(lblCurrentDate.Font.SizeInPoints - headerFontSize) > 0.4F)
                lblCurrentDate.Font = new Font(lblCurrentDate.Font.FontFamily, headerFontSize, FontStyle.Bold);
            const int statusHeight = 76;
            if (pnlStatusArea.Height != statusHeight)
                pnlStatusArea.Height = statusHeight;
        }

        private void ApplyMaximizeToCurrentScreen(bool force)
        {
            if (_isApplyingPerScreenMaximize)
                return;

            // Don't fight the user if the window is minimized.
            if (WindowState == FormWindowState.Minimized)
                return;

            var screen = Screen.FromControl(this);
            if (screen == null)
                return;

            // Only re-apply when we detect a monitor change, unless forced (startup).
            if (!force && string.Equals(_lastScreenDeviceName, screen.DeviceName, StringComparison.Ordinal))
                return;

            _lastScreenDeviceName = screen.DeviceName;
            _isApplyingPerScreenMaximize = true;
            try
            {
                // Reset bounds to the new monitor's working area then maximize.
                WindowState = FormWindowState.Normal;
                Bounds = screen.WorkingArea;
                WindowState = FormWindowState.Maximized;
            }
            finally
            {
                _isApplyingPerScreenMaximize = false;
            }
        }

        private void MainForm_Load(object sender, EventArgs e)
        {
            UpdateClockDisplay();
            clockTimer.Start();

            // Test DB connection
            if (!_db.TestConnection())
            {
                var detail = string.IsNullOrWhiteSpace(_db.LastConnectionFailureDetail)
                    ? ""
                    : "\n\nDetails:\n" + _db.LastConnectionFailureDetail;
                MessageBox.Show(
                    "Cannot connect to MySQL attendance_db database.\n\n" +
                    "Ensure MySQL Server is running and verify:\n" +
                    "• Host: 127.0.0.1\n" +
                    "• Port: 3306\n" +
                    "• User: root\n" +
                    "• Password: configured in AppDbConnection (DatabaseHelper.cs)\n" +
                    "• Database: attendance_db exists\n\n" +
                    "Check AppDbConnection class at the bottom of DatabaseHelper.cs " +
                    "to verify connection settings match your MySQL Workbench configuration." +
                    detail,
                    "Database Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                Application.Exit();
                return;
            }

            // Initialize fingerprint device
            try
            {
                _engine.Initialize();
                _db.EnsureLogbookTable();
                _engine.LoadAllFingerprintsFromDatabase();
                lblStatus.Text = "Biometric scanner is ready to use.";
                lblStatus.ForeColor = Color.White;
                _users = _db.GetAllUsersWithTemplates();
                scanTimer.Start();
            }
            catch (Exception ex)
            {
                lblStatus.Text = "Device not found: " + ex.Message;
                lblStatus.ForeColor = Color.FromArgb(248, 113, 113);
            }
        }

        // Called every 500ms — polls for a fingerprint on a background thread
        private void scanTimer_Tick(object sender, EventArgs e)
        {
            if (_isScanning || _isEnrollOpen) return;

            _isScanning = true;
            Task.Run(() => PerformScan());
        }

        private void PerformScan()
        {
            try
            {
                int matchedFingerprintId = _engine.IdentifyUser();
                if (matchedFingerprintId < 0)
                {
                    // Don't spam the UI when no finger is placed.
                    if (!string.IsNullOrEmpty(_engine.LastError) &&
                        _engine.LastError.StartsWith("Capture timed out", StringComparison.OrdinalIgnoreCase))
                    {
                        return;
                    }

                    this.Invoke((Action)(() =>
                    {
                        lblUser.Text = string.Empty;
                        if (string.Equals(_engine.LastError, "Device is not connected.", StringComparison.OrdinalIgnoreCase))
                            lblStatus.Text = "Scan error: Biometric device not connected.";
                        else
                            lblStatus.Text = "Scan error: " + _engine.LastError;
                        lblStatus.ForeColor = Color.FromArgb(248, 113, 113);
                        FlashResultBanner(isSuccess: false);
                    }));
                    return;
                }

                if (matchedFingerprintId >= DatabaseHelper.VisitorFingerprintIdRangeStart)
                {
                    this.Invoke((Action)(() => HandleReturningVisitorFingerprint(matchedFingerprintId)));
                    return;
                }

                // Resolve each scanner template ID to its employee and verify the current
                // MySQL mapping so a stale match cannot clock in.
                UserModel liveEmployee = _db.TryGetUserWithValidTemplateByFingerprintId(matchedFingerprintId);
                if (liveEmployee != null)
                {
                    this.Invoke((Action)(() => HandleEmployeeFingerprint(liveEmployee)));
                    return;
                }

                // ZK matched a staff id that no longer exists in the database (or template removed).
                _isEnrollOpen = true;
                this.BeginInvoke((Action)(() => { _ = EnrollFormWorkflowAsync(matchedFingerprintId, true); }));
            }
            catch (Exception ex)
            {
                string errorMessage = ex is System.Reflection.TargetInvocationException invocationException &&
                                      invocationException.InnerException != null
                    ? invocationException.InnerException.Message
                    : ex.Message;
                this.Invoke((Action)(() =>
                {
                    lblUser.Text = string.Empty;
                    lblStatus.Text = "Scan error: " + errorMessage;
                    lblStatus.ForeColor = Color.FromArgb(248, 113, 113);
                    FlashResultBanner(isSuccess: false);
                }));
            }
            finally
            {
                _isScanning = false;
            }
        }

        private void HandleEmployeeFingerprint(UserModel employee)
        {
            bool canLog = _db.TryDetermineNextAttendanceEntry(
                employee.Id,
                TimeSpan.FromMinutes(DatabaseHelper.MinimumMinutesBetweenEmployeeTimeInAndTimeOut),
                out string nextEntryType,
                out DateTime? nextAllowedOutAt);

            if (!canLog)
            {
                lblUser.Text = $"Employee Fingerprint Detected: {employee.FullName}";
                if (string.Equals(nextEntryType, "OUT", StringComparison.OrdinalIgnoreCase) && nextAllowedOutAt.HasValue)
                    lblStatus.Text = $"Next action: TIME-OUT. Available after {nextAllowedOutAt.Value:hh:mm tt}.";
                else
                    lblStatus.Text = "Attendance action is not currently available. Please scan again.";
                lblStatus.ForeColor = Color.FromArgb(248, 113, 113);
                FlashResultBanner(isSuccess: false);
                return;
            }

            if (string.Equals(nextEntryType, "IN", StringComparison.OrdinalIgnoreCase) &&
                !DatabaseHelper.IsEmployeeTimeInWithinAllowedSchedule(DateTime.Now))
            {
                RejectTimeInOutsideAllowedSchedule();
                return;
            }

            decimal dailyTotalHours;
            try
            {
                dailyTotalHours = _db.LogAttendance(employee.Id, nextEntryType);
            }
            catch (InvalidOperationException ex) when (string.Equals(
                ex.Message,
                DatabaseHelper.TimeInOutsideAllowedScheduleMessage,
                StringComparison.Ordinal))
            {
                RejectTimeInOutsideAllowedSchedule();
                return;
            }

            string actionText = nextEntryType == "IN" ? "Time-in" : "Time-out";
            string nextActionText = nextEntryType == "IN" ? "TIME-OUT" : "TIME-IN";
            lblUser.Text = $"{actionText} recorded for {employee.FullName}.";
            _statusMessageToHide = $"{actionText} logged at {DateTime.Now:hh:mm tt}. Total work hours for today: {dailyTotalHours:0.00} hours. Next scan: {nextActionText}.";
            lblStatus.Text = _statusMessageToHide;
            lblStatus.ForeColor = Color.FromArgb(34, 211, 238);
            _statusMessageHideTimer.Stop();
            _statusMessageHideTimer.Start();
            StartBarangayLogoBlink();
            FlashResultBanner(isSuccess: true);

            _lastEmployeeStatusUserId = employee.Id;
            _lastEmployeeStatusActionText = actionText;
            _lastEmployeeStatusSetAt = DateTime.Now;
            _lastEmployeeStatusRenderedName = employee.FullName;

        }

        private void RejectTimeInOutsideAllowedSchedule()
        {
            lblUser.Text = DatabaseHelper.TimeInOutsideAllowedScheduleMessage;
            lblStatus.Text = DatabaseHelper.TimeInOutsideAllowedScheduleMessage;
            lblStatus.ForeColor = Color.FromArgb(248, 113, 113);
            FlashResultBanner(isSuccess: false);
        }

        private void HandleReturningVisitorFingerprint(int fingerprintId)
        {
            if (_isReturningVisitorFlowOpen)
                return;

            _isReturningVisitorFlowOpen = true;
            scanTimer.Stop();
            try
            {
                var visitor = _db.GetVisitorProfile(fingerprintId);
                if (visitor == null)
                {
                    lblUser.Text = string.Empty;
                    lblStatus.Text = "Visitor fingerprint detected, but no saved visitor profile was found.";
                    lblStatus.ForeColor = Color.FromArgb(248, 113, 113);
                    FlashResultBanner(isSuccess: false);
                    return;
                }

                using var purposeForm = new VisitorPurposeForm(visitor.FullName);
                if (purposeForm.ShowDialog(this) != DialogResult.OK)
                {
                    lblUser.Text = string.Empty;
                    lblStatus.Text = "Returning visitor scan cancelled.";
                    lblStatus.ForeColor = Color.FromArgb(34, 211, 238);
                    return;
                }

                if (!_db.InsertExistingVisitorLog(visitor.FingerprintId, purposeForm.PurposeOfTransaction, string.Empty))
                {
                    lblUser.Text = string.Empty;
                    lblStatus.Text = "Unable to save returning visitor logbook entry.";
                    lblStatus.ForeColor = Color.FromArgb(248, 113, 113);
                    FlashResultBanner(isSuccess: false);
                    return;
                }

                lblUser.Text = $"Returning {visitor.PersonClassification} Logged: {visitor.FullName}";
                _statusMessageToHide = $"{visitor.PersonClassification} logbook entry saved at {DateTime.Now:hh:mm tt}";
                lblStatus.Text = _statusMessageToHide;
                lblStatus.ForeColor = Color.FromArgb(34, 211, 238);
                _statusMessageHideTimer.Stop();
                _statusMessageHideTimer.Start();
                FlashResultBanner(isSuccess: true);
            }
            catch (Exception ex)
            {
                lblUser.Text = string.Empty;
                lblStatus.Text = "Returning visitor scan error: " + ex.Message;
                lblStatus.ForeColor = Color.FromArgb(248, 113, 113);
                FlashResultBanner(isSuccess: false);
            }
            finally
            {
                _isReturningVisitorFlowOpen = false;
                scanTimer.Start();
            }
        }

        private void clockTimer_Tick(object sender, EventArgs e)
        {
            UpdateClockDisplay();
            RefreshLastEmployeeStatusNameIfChanged();
        }

        private void RefreshLastEmployeeStatusNameIfChanged()
        {
            if (!_lastEmployeeStatusUserId.HasValue || string.IsNullOrWhiteSpace(_lastEmployeeStatusActionText))
                return;

            // Only keep updating the green message for a short window after the last log action.
            if ((DateTime.Now - _lastEmployeeStatusSetAt).TotalMinutes > 10)
                return;

            try
            {
                string latestName = _db.GetUserDisplayNameById(_lastEmployeeStatusUserId.Value);
                if (string.IsNullOrWhiteSpace(latestName) || string.Equals(latestName, _lastEmployeeStatusRenderedName, StringComparison.Ordinal))
                    return;

                _lastEmployeeStatusRenderedName = latestName;
                lblUser.Text = $"{latestName} has successfully {_lastEmployeeStatusActionText}.";
            }
            catch
            {
                // If DB is temporarily unavailable, don't break the UI clock updates.
            }
        }

        private void UpdateClockDisplay()
        {
            DateTime now = DateTime.Now;
            lblCurrentTime.Text = now.ToString("hh:mm:ss tt");
            lblCurrentDate.Text = now.ToString("dddd, MMMM dd, yyyy");
        }

        private async void btnEnroll_Click(object sender, EventArgs e)
        {
            await EnrollFormWorkflowAsync(null, true);
        }

        /// <param name="staleZkfUserIdToRemove">If the scanner still holds a staff id that was deleted in MySQL, pass it to drop the orphan template before opening enrollment.</param>
        private async Task EnrollFormWorkflowAsync(int? staleZkfUserIdToRemove, bool requireAdminLogin)
        {
            if (requireAdminLogin && !PromptForAdminLogin())
                return;

            if (staleZkfUserIdToRemove.HasValue)
            {
                lblUser.Text = string.Empty;
                _engine.RemoveTemplateFromMemory(staleZkfUserIdToRemove.Value);
                lblStatus.Text = "This print is not registered in the database. Enrolling a new user...";
                lblStatus.ForeColor = Color.FromArgb(34, 211, 238);
            }
            _isEnrollOpen = true;
            scanTimer.Stop();
            try
            {
                int waitedMs = 0;
                while (_isScanning && waitedMs < 5000)
                {
                    await Task.Delay(100);
                    waitedMs += 100;
                }
                var enrollForm = new EnrollForm(_engine, _db, PlayResultSound);
                enrollForm.ShowDialog();
                _users = _db.GetAllUsersWithTemplates();
                _engine.LoadAllFingerprintsFromDatabase();
            }
            finally
            {
                _isEnrollOpen = false;
                scanTimer.Start();
            }
        }

        private bool PromptForAdminLogin()
        {
            if (!_db.HasAnyActiveAdmin())
            {
                MessageBox.Show(
                    "No active admin account is available.\n\n" +
                    "The app expects this default admin login:\n" +
                    $"Default username: {DatabaseHelper.DefaultAdminUsername}\n" +
                    $"Default password: {DatabaseHelper.DefaultAdminPassword}",
                    "Admin Setup Required",
                    MessageBoxButtons.OK,
                    MessageBoxIcon.Warning);
                return false;
            }

            var method = PromptAuthMethodChoice(
                "Admin Authentication",
                "Choose how to authenticate",
                allowCredentials: true,
                allowFingerprint: true,
                fingerprintLabel: "Use Admin Fingerprint");

            if (!method.HasValue)
                return false;

            if (method.Value == AuthMethod.Credentials)
            {
                if (!TryPromptCredentials("Admin Login", "Admin authentication required", out string username, out string password))
                    return false;

                if (_db.ValidateAdminCredentials(username, password))
                    return true;

                MessageBox.Show(
                    "Invalid admin username or password.",
                    "Authentication Failed",
                    MessageBoxButtons.OK,
                    MessageBoxIcon.Error);
                return false;
            }

            if (TryAuthenticateByFingerprint(
                "Admin Fingerprint Authentication",
                "Scan an admin fingerprint to continue",
                candidateId => _db.UserIdHasAnyRole(candidateId, "admin"),
                out int userId))
                return true;

            MessageBox.Show(
                "Fingerprint authentication failed or user is not an admin.",
                "Authentication Failed",
                MessageBoxButtons.OK,
                MessageBoxIcon.Error);
            return false;
        }

        private bool TryPromptAdminCredentials(out string username, out string password)
        {
            return TryPromptCredentials("Admin Login", "Admin authentication required", out username, out password);
        }

        private bool TryPromptCredentials(string dialogTitle, string promptTitle, out string username, out string password)
        {
            username = string.Empty;
            password = string.Empty;
            bool isVisitorLogbookLogin = string.Equals(
                dialogTitle,
                "Logbook Access Login",
                StringComparison.Ordinal) ||
                string.Equals(dialogTitle, "Admin Login", StringComparison.Ordinal);

            using var loginForm = new Form
            {
                Text = dialogTitle,
                StartPosition = FormStartPosition.CenterParent,
                FormBorderStyle = FormBorderStyle.Sizable,
                MaximizeBox = false,
                MinimizeBox = false,
                ShowInTaskbar = false,
                MinimumSize = new Size(420, 300),
                ClientSize = new Size(520, 320),
                BackColor = isVisitorLogbookLogin
                    ? Color.FromArgb(11, 19, 43)
                    : Color.FromArgb(236, 241, 248),
                AutoScaleMode = AutoScaleMode.Font,
                Font = new Font("Segoe UI", 9.5F, FontStyle.Bold)
            };
            ResponsiveLayout.FitClientToWorkingArea(loginForm, 520, 340, 420, 300);

            var panelHeader = new Panel
            {
                Dock = DockStyle.Top,
                Height = 78,
                BackColor = isVisitorLogbookLogin
                    ? Color.FromArgb(17, 26, 51)
                    : Color.FromArgb(13, 27, 42)
            };

            var lblTitle = new Label
            {
                Text = promptTitle,
                AutoSize = true,
                Font = new Font("Segoe UI", 12.5F, FontStyle.Bold),
                ForeColor = Color.FromArgb(240, 246, 255),
                BackColor = Color.Transparent,
                Location = new Point(18, 13)
            };

            var lblSub = new Label
            {
                Text = "Enter your credentials to continue",
                AutoSize = true,
                Font = new Font("Segoe UI", 9.5F, FontStyle.Bold),
                ForeColor = isVisitorLogbookLogin
                    ? Color.FromArgb(183, 194, 215)
                    : Color.FromArgb(154, 180, 209),
                BackColor = Color.Transparent,
                Location = new Point(20, 45)
            };
            panelHeader.Controls.Add(lblTitle);
            panelHeader.Controls.Add(lblSub);

            var panelBody = new Panel
            {
                Dock = DockStyle.Fill,
                Padding = isVisitorLogbookLogin
                    ? new Padding(20, 16, 20, 12)
                    : new Padding(16, 14, 16, 8),
                BackColor = isVisitorLogbookLogin
                    ? Color.FromArgb(28, 37, 65)
                    : Color.White
            };

            var lblUsername = new Label
            {
                Text = "USERNAME",
                AutoSize = !isVisitorLogbookLogin,
                Font = new Font("Segoe UI", 8.5F, FontStyle.Bold),
                ForeColor = isVisitorLogbookLogin
                    ? Color.FromArgb(220, 228, 242)
                    : Color.FromArgb(100, 116, 139),
                Location = new Point(16, 16)
            };
            var txtUsername = new TextBox
            {
                Dock = DockStyle.Top,
                Height = 30,
                Font = new Font("Segoe UI", 10.5F, FontStyle.Bold),
                BorderStyle = BorderStyle.FixedSingle,
                BackColor = isVisitorLogbookLogin
                    ? Color.FromArgb(17, 26, 51)
                    : Color.FromArgb(248, 250, 252),
                ForeColor = isVisitorLogbookLogin
                    ? Color.FromArgb(245, 248, 255)
                    : SystemColors.WindowText
            };

            var lblPassword = new Label
            {
                Text = "PASSWORD",
                AutoSize = !isVisitorLogbookLogin,
                Font = new Font("Segoe UI", 8.5F, FontStyle.Bold),
                ForeColor = isVisitorLogbookLogin
                    ? Color.FromArgb(220, 228, 242)
                    : Color.FromArgb(100, 116, 139),
                Location = new Point(16, 80)
            };
            var passwordPanel = new Panel
            {
                Dock = DockStyle.Top,
                Height = 30,
                MinimumSize = new Size(0, txtUsername.Height),
                MaximumSize = new Size(0, txtUsername.Height),
                BorderStyle = BorderStyle.FixedSingle,
                BackColor = isVisitorLogbookLogin
                    ? Color.FromArgb(17, 26, 51)
                    : Color.FromArgb(248, 250, 252)
            };
            var txtPassword = new TextBox
            {
                Dock = DockStyle.Fill,
                Height = 30,
                AutoSize = false,
                Font = new Font("Segoe UI", 10.5F, FontStyle.Bold),
                BorderStyle = BorderStyle.None,
                BackColor = isVisitorLogbookLogin
                    ? Color.FromArgb(17, 26, 51)
                    : Color.FromArgb(248, 250, 252),
                ForeColor = isVisitorLogbookLogin
                    ? Color.FromArgb(245, 248, 255)
                    : SystemColors.WindowText,
                UseSystemPasswordChar = true
            };
            txtPassword.Margin = Padding.Empty;

            bool passwordVisible = false;
            var btnTogglePassword = new Button
            {
                Dock = DockStyle.Right,
                Width = 42,
                Text = string.Empty,
                AccessibleName = "Show password",
                AccessibleDescription = "Toggle password visibility",
                BackColor = isVisitorLogbookLogin
                    ? Color.FromArgb(17, 26, 51)
                    : Color.FromArgb(248, 250, 252),
                ForeColor = isVisitorLogbookLogin
                    ? Color.FromArgb(220, 228, 242)
                    : Color.FromArgb(29, 80, 157),
                FlatStyle = FlatStyle.Flat,
                UseVisualStyleBackColor = false,
                Cursor = Cursors.Hand,
                TabStop = false,
                Margin = Padding.Empty
            };
            btnTogglePassword.FlatAppearance.BorderSize = 0;
            btnTogglePassword.FlatAppearance.MouseOverBackColor = isVisitorLogbookLogin
                ? Color.FromArgb(31, 48, 82)
                : Color.FromArgb(232, 241, 253);
            btnTogglePassword.Click += (_, __) =>
            {
                passwordVisible = !passwordVisible;
                txtPassword.UseSystemPasswordChar = !passwordVisible;
                btnTogglePassword.AccessibleName = passwordVisible ? "Hide password" : "Show password";
                btnTogglePassword.Invalidate();
                txtPassword.Focus();
                txtPassword.SelectionStart = txtPassword.TextLength;
            };
            btnTogglePassword.Paint += (_, e) =>
                DrawPasswordVisibilityIcon(
                    e.Graphics,
                    btnTogglePassword.ClientRectangle,
                    btnTogglePassword.ForeColor,
                    passwordVisible);
            passwordPanel.Controls.Add(txtPassword);
            passwordPanel.Controls.Add(btnTogglePassword);
            if (!isVisitorLogbookLogin)
            {
                lblUsername.AutoSize = false;
                lblUsername.Dock = DockStyle.Top;
                lblUsername.Height = 22;
                lblPassword.AutoSize = false;
                lblPassword.Dock = DockStyle.Top;
                lblPassword.Height = 22;
                lblPassword.Padding = new Padding(0, 10, 0, 0);
            }

            var btnLogin = new Button
            {
                Text = "Login",
                Width = 122,
                Height = 38,
                DialogResult = DialogResult.OK,
                BackColor = isVisitorLogbookLogin
                    ? Color.FromArgb(31, 65, 122)
                    : Color.FromArgb(37, 99, 235),
                ForeColor = Color.White,
                Font = new Font("Segoe UI", 9.5F, FontStyle.Bold),
                FlatStyle = FlatStyle.Flat,
                Cursor = Cursors.Hand
            };
            btnLogin.FlatAppearance.BorderSize = 0;
            btnLogin.FlatAppearance.MouseOverBackColor = isVisitorLogbookLogin
                ? Color.FromArgb(42, 83, 148)
                : Color.FromArgb(29, 78, 216);
            btnLogin.FlatAppearance.MouseDownBackColor = isVisitorLogbookLogin
                ? Color.FromArgb(24, 52, 101)
                : Color.FromArgb(23, 64, 180);

            var btnCancel = new Button
            {
                Text = "Cancel",
                Width = 122,
                Height = 38,
                DialogResult = DialogResult.Cancel,
                BackColor = isVisitorLogbookLogin
                    ? Color.FromArgb(17, 26, 51)
                    : Color.FromArgb(22, 40, 62),
                ForeColor = Color.FromArgb(220, 230, 245),
                Font = new Font("Segoe UI", 9.5F, FontStyle.Bold),
                FlatStyle = FlatStyle.Flat,
                Cursor = Cursors.Hand
            };
            btnCancel.FlatAppearance.BorderSize = isVisitorLogbookLogin ? 1 : 0;
            btnCancel.FlatAppearance.BorderColor = Color.FromArgb(70, 88, 123);
            btnCancel.FlatAppearance.MouseOverBackColor = isVisitorLogbookLogin
                ? Color.FromArgb(29, 42, 73)
                : Color.FromArgb(30, 55, 85);
            btnCancel.FlatAppearance.MouseDownBackColor = Color.FromArgb(15, 35, 60);

            var buttonBar = new FlowLayoutPanel
            {
                Dock = DockStyle.Bottom,
                Height = 58,
                FlowDirection = FlowDirection.RightToLeft,
                Padding = new Padding(12, 10, 12, 8),
                BackColor = isVisitorLogbookLogin
                    ? Color.FromArgb(11, 19, 43)
                    : Color.FromArgb(236, 241, 248),
                WrapContents = false
            };
            btnCancel.Margin = new Padding(0, 0, 0, 0);
            btnLogin.Margin = new Padding(0, 0, 8, 0);
            buttonBar.Controls.Add(btnCancel);
            buttonBar.Controls.Add(btnLogin);

            if (isVisitorLogbookLogin)
            {
                var fieldsLayout = new TableLayoutPanel
                {
                    Dock = DockStyle.Fill,
                    ColumnCount = 1,
                    RowCount = 5,
                    BackColor = Color.Transparent,
                    Margin = Padding.Empty,
                    Padding = Padding.Empty
                };
                fieldsLayout.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 100F));
                fieldsLayout.RowStyles.Add(new RowStyle(SizeType.Absolute, 26F));
                fieldsLayout.RowStyles.Add(new RowStyle(SizeType.Absolute, txtUsername.Height));
                fieldsLayout.RowStyles.Add(new RowStyle(SizeType.Absolute, 12F));
                fieldsLayout.RowStyles.Add(new RowStyle(SizeType.Absolute, 26F));
                fieldsLayout.RowStyles.Add(new RowStyle(SizeType.Absolute, txtUsername.Height));

                lblUsername.Dock = DockStyle.Fill;
                lblUsername.AutoSize = false;
                lblUsername.TextAlign = ContentAlignment.MiddleLeft;
                lblUsername.Margin = Padding.Empty;
                txtUsername.Dock = DockStyle.Fill;
                txtUsername.Margin = Padding.Empty;
                passwordPanel.MinimumSize = new Size(0, txtUsername.Height);
                passwordPanel.MaximumSize = new Size(0, txtUsername.Height);
                passwordPanel.Height = txtUsername.Height;
                lblPassword.Dock = DockStyle.Fill;
                lblPassword.AutoSize = false;
                lblPassword.TextAlign = ContentAlignment.MiddleLeft;
                lblPassword.Padding = Padding.Empty;
                lblPassword.Margin = Padding.Empty;
                passwordPanel.Dock = DockStyle.Fill;
                passwordPanel.Margin = Padding.Empty;
                txtPassword.Dock = DockStyle.Fill;

                fieldsLayout.Controls.Add(lblUsername, 0, 0);
                fieldsLayout.Controls.Add(txtUsername, 0, 1);
                fieldsLayout.Controls.Add(lblPassword, 0, 3);
                fieldsLayout.Controls.Add(passwordPanel, 0, 4);
                panelBody.Controls.Add(fieldsLayout);
            }
            else
            {
                // Docked last is laid out first: password label and username label precede their fields.
                panelBody.Controls.Add(passwordPanel);
                panelBody.Controls.Add(lblPassword);
                panelBody.Controls.Add(txtUsername);
                panelBody.Controls.Add(lblUsername);
            }
            loginForm.Controls.Add(panelBody);
            loginForm.Controls.Add(buttonBar);
            loginForm.Controls.Add(panelHeader);
            loginForm.AcceptButton = btnLogin;
            loginForm.CancelButton = btnCancel;

            DesktopTheme.Apply(loginForm);
            ApplyBoldTypography(loginForm);
            if (isVisitorLogbookLogin)
            {
                loginForm.BackColor = Color.FromArgb(11, 19, 43);
                panelHeader.BackColor = Color.FromArgb(17, 26, 51);
                lblTitle.ForeColor = Color.FromArgb(245, 248, 255);
                lblSub.ForeColor = Color.FromArgb(183, 194, 215);
                panelBody.BackColor = Color.FromArgb(28, 37, 65);
                lblUsername.ForeColor = Color.FromArgb(220, 228, 242);
                lblPassword.ForeColor = Color.FromArgb(220, 228, 242);
                txtUsername.BackColor = Color.FromArgb(17, 26, 51);
                txtUsername.ForeColor = Color.FromArgb(245, 248, 255);
                txtPassword.BackColor = Color.FromArgb(17, 26, 51);
                txtPassword.ForeColor = Color.FromArgb(245, 248, 255);
                passwordPanel.BackColor = Color.FromArgb(17, 26, 51);
                btnTogglePassword.BackColor = Color.FromArgb(17, 26, 51);
                btnTogglePassword.ForeColor = Color.FromArgb(220, 228, 242);
                btnLogin.BackColor = Color.FromArgb(31, 65, 122);
                btnLogin.ForeColor = Color.White;
                btnLogin.FlatAppearance.BorderSize = 0;
                btnLogin.FlatAppearance.MouseOverBackColor = Color.FromArgb(42, 83, 148);
                btnLogin.FlatAppearance.MouseDownBackColor = Color.FromArgb(24, 52, 101);
                btnCancel.BackColor = Color.FromArgb(17, 26, 51);
                btnCancel.ForeColor = Color.FromArgb(220, 228, 242);
                btnCancel.FlatAppearance.BorderSize = 1;
                btnCancel.FlatAppearance.BorderColor = Color.FromArgb(70, 88, 123);
                btnCancel.FlatAppearance.MouseOverBackColor = Color.FromArgb(29, 42, 73);
                btnCancel.FlatAppearance.MouseDownBackColor = Color.FromArgb(17, 26, 51);
                buttonBar.BackColor = Color.FromArgb(11, 19, 43);
                ApplyRoundedRegion(btnLogin, 9);
                ApplyRoundedRegion(btnCancel, 9);
                panelBody.Paint += (_, e) =>
                {
                    Rectangle bounds = panelBody.ClientRectangle;
                    if (bounds.Width < 2 || bounds.Height < 2)
                        return;

                    e.Graphics.SmoothingMode = SmoothingMode.AntiAlias;
                    using (var shadow = new Pen(Color.FromArgb(90, 5, 10, 24)))
                        e.Graphics.DrawLine(shadow, 1, bounds.Height - 2, bounds.Width - 2, bounds.Height - 2);
                    using (var border = new Pen(Color.FromArgb(53, 68, 102)))
                        e.Graphics.DrawRectangle(border, 0, 0, bounds.Width - 1, bounds.Height - 1);
                };
            }
            if (loginForm.ShowDialog(this) != DialogResult.OK)
                return false;

            username = txtUsername.Text?.Trim() ?? string.Empty;
            password = txtPassword.Text ?? string.Empty;
            if (string.IsNullOrWhiteSpace(username) || string.IsNullOrWhiteSpace(password))
            {
                MessageBox.Show("Enter admin username and password.", "Validation",
                    MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return false;
            }
            return true;
        }

        private static void DrawPasswordVisibilityIcon(
            Graphics graphics,
            Rectangle bounds,
            Color color,
            bool passwordVisible)
        {
            graphics.SmoothingMode = SmoothingMode.AntiAlias;
            float centerX = bounds.Left + bounds.Width / 2F;
            float centerY = bounds.Top + bounds.Height / 2F;
            float eyeWidth = 19F;
            float eyeHeight = 12F;
            var eyeBounds = new RectangleF(
                centerX - eyeWidth / 2F,
                centerY - eyeHeight / 2F,
                eyeWidth,
                eyeHeight);

            using (var pen = new Pen(color, 1.7F))
            using (var iris = new SolidBrush(color))
            using (var path = new GraphicsPath())
            {
                path.AddBezier(
                    eyeBounds.Left, centerY,
                    eyeBounds.Left + eyeWidth * 0.25F, eyeBounds.Top,
                    eyeBounds.Right - eyeWidth * 0.25F, eyeBounds.Top,
                    eyeBounds.Right, centerY);
                path.AddBezier(
                    eyeBounds.Right, centerY,
                    eyeBounds.Right - eyeWidth * 0.25F, eyeBounds.Bottom,
                    eyeBounds.Left + eyeWidth * 0.25F, eyeBounds.Bottom,
                    eyeBounds.Left, centerY);
                graphics.DrawPath(pen, path);
                graphics.FillEllipse(iris, centerX - 2.5F, centerY - 2.5F, 5F, 5F);
                graphics.DrawEllipse(pen, centerX - 3.5F, centerY - 3.5F, 7F, 7F);

                if (!passwordVisible)
                {
                    using (var slashPen = new Pen(color, 1.8F))
                    {
                        slashPen.StartCap = LineCap.Round;
                        slashPen.EndCap = LineCap.Round;
                        graphics.DrawLine(
                            slashPen,
                            centerX - eyeWidth / 2F - 1,
                            centerY + eyeHeight / 2F + 2,
                            centerX + eyeWidth / 2F + 1,
                            centerY - eyeHeight / 2F - 2);
                    }
                }
            }
        }

        private static void ApplyBoldTypography(Control control)
        {
            if (control.Font != null && !control.Font.Bold)
                control.Font = new Font(control.Font, control.Font.Style | FontStyle.Bold);

            foreach (Control child in control.Controls)
                ApplyBoldTypography(child);
        }

        private void btnLogs_Click(object sender, EventArgs e)
        {
            var logForm = new AttendanceLogForm(_db);
            logForm.ShowDialog();
        }

        private void btnVisitorLogbook_Click(object sender, EventArgs e)
        {
            using var logbookForm = new VisitorLogbookForm(
                _db,
                _engine,
                PromptForLogbookPrivilegedLogin,
                PlayResultSound)
            {
                Owner = this
            };
            logbookForm.ShowDialog();
        }

        /// <summary>
        /// Visitor logbook access is granted to administrators, employees, and authorized logbook staff.
        /// </summary>
        private bool PromptForLogbookPrivilegedLogin()
        {
            if (!_db.HasAnyUserCanAccessLogbook())
            {
                MessageBox.Show(
                    "No staff account is configured for visitor logbook access.\n\n" +
                    "An administrator must assign the \"admin\", \"employee\", or \"logbook\" role to your user in the database.",
                    "Cannot Authenticate",
                    MessageBoxButtons.OK,
                    MessageBoxIcon.Warning);
                return false;
            }

            var method = PromptAuthMethodChoice(
                "Logbook Access",
                "Authenticate to view Logbook Records",
                allowCredentials: true,
                allowFingerprint: true,
                fingerprintLabel: "Scan Authorized Fingerprint");

            if (!method.HasValue)
                return false;

            if (method.Value == AuthMethod.Credentials)
            {
                if (!TryPromptCredentials(
                    "Logbook Access Login",
                    "Administrator, employee, or authorized logbook staff",
                    out string username,
                    out string password))
                    return false;

                if (_db.ValidateLogbookCredentials(
                    username, password, out bool authenticated, out bool authorized) && authorized)
                    return true;

                ShowNotificationModal(
                    authenticated
                        ? "Access Denied: Employee is not authorized to access Logbook records."
                        : "Invalid credentials.",
                    isSuccess: false);
                return false;
            }

            if (TryAuthenticateByFingerprint(
                    "Logbook Access Records",
                    "Scan an administrator, employee, or authorized logbook fingerprint",
                    candidateId => _db.UserIdCanAccessLogbook(candidateId),
                    out int userId))
                return true;

            if (userId > 0)
            {
                ShowNotificationModal(
                    "Access Denied: Employee is not authorized to access Logbook records.",
                    isSuccess: false);
            }
            return false;
        }

        private enum AuthMethod
        {
            Credentials,
            Fingerprint
        }

        private AuthMethod? PromptAuthMethodChoice(
            string dialogTitle,
            string promptTitle,
            bool allowCredentials,
            bool allowFingerprint,
            string fingerprintLabel)
        {
            bool isLogbookAccessDialog = string.Equals(
                dialogTitle,
                "Logbook Access",
                StringComparison.Ordinal) ||
                string.Equals(dialogTitle, "Admin Authentication", StringComparison.Ordinal);
            bool isAdminAuthentication = string.Equals(
                dialogTitle,
                "Admin Authentication",
                StringComparison.Ordinal);
            using var methodForm = new Form
            {
                Text = dialogTitle,
                StartPosition = FormStartPosition.CenterParent,
                FormBorderStyle = FormBorderStyle.Sizable,
                MaximizeBox = false,
                MinimizeBox = false,
                ShowInTaskbar = false,
                MinimumSize = isLogbookAccessDialog ? new Size(460, 300) : new Size(420, 280),
                ClientSize = isLogbookAccessDialog ? new Size(600, 360) : new Size(560, 320),
                BackColor = isLogbookAccessDialog
                    ? Color.FromArgb(11, 19, 43)
                    : Color.FromArgb(236, 241, 248),
                KeyPreview = true,
                AutoScaleMode = AutoScaleMode.Font,
                Font = new Font("Segoe UI", 9.5F)
            };
            ResponsiveLayout.FitClientToWorkingArea(
                methodForm,
                isLogbookAccessDialog ? 600 : 560,
                isLogbookAccessDialog ? 360 : 320,
                isLogbookAccessDialog ? 460 : 420,
                isLogbookAccessDialog ? 300 : 280);

            var panelHeader = new Panel
            {
                Dock = DockStyle.Top,
                Height = isLogbookAccessDialog ? 104 : 78,
                BackColor = isLogbookAccessDialog
                    ? Color.FromArgb(17, 26, 51)
                    : Color.FromArgb(13, 27, 42),
                Padding = isLogbookAccessDialog ? new Padding(4, 0, 0, 0) : Padding.Empty
            };
            var lblTitle = new Label
            {
                Text = promptTitle,
                AutoSize = !isLogbookAccessDialog,
                Font = new Font("Segoe UI", isLogbookAccessDialog ? 15F : 12.5F, FontStyle.Bold),
                ForeColor = isLogbookAccessDialog
                    ? Color.FromArgb(245, 248, 255)
                    : Color.FromArgb(240, 246, 255),
                BackColor = Color.Transparent,
                Location = new Point(18, 13),
                Dock = isLogbookAccessDialog ? DockStyle.Fill : DockStyle.None,
                TextAlign = isLogbookAccessDialog ? ContentAlignment.MiddleLeft : ContentAlignment.TopLeft,
                Padding = isLogbookAccessDialog ? new Padding(18, 0, 14, 0) : Padding.Empty
            };
            var lblSub = new Label
            {
                Text = isLogbookAccessDialog
                    ? "Select an authentication method to continue."
                    : "Select one option below",
                AutoSize = !isLogbookAccessDialog,
                Font = new Font("Segoe UI", 9.5F),
                ForeColor = isLogbookAccessDialog
                    ? Color.FromArgb(183, 194, 215)
                    : Color.FromArgb(154, 180, 209),
                BackColor = Color.Transparent,
                Location = new Point(20, 45),
                Dock = isLogbookAccessDialog ? DockStyle.Fill : DockStyle.None,
                TextAlign = ContentAlignment.TopLeft,
                Padding = isLogbookAccessDialog ? new Padding(20, 2, 18, 10) : Padding.Empty
            };
            if (isLogbookAccessDialog)
            {
                var headerLayout = new TableLayoutPanel
                {
                    Dock = DockStyle.Fill,
                    ColumnCount = 1,
                    RowCount = 2,
                    BackColor = Color.Transparent,
                    Margin = Padding.Empty,
                    Padding = Padding.Empty
                };
                headerLayout.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 100F));
                headerLayout.RowStyles.Add(new RowStyle(SizeType.Absolute, 48F));
                headerLayout.RowStyles.Add(new RowStyle(SizeType.Percent, 100F));
                headerLayout.Controls.Add(lblTitle, 0, 0);
                headerLayout.Controls.Add(lblSub, 0, 1);
                panelHeader.Controls.Add(headerLayout);
                panelHeader.Paint += (_, e) =>
                {
                    using (var accent = new SolidBrush(Color.FromArgb(0, 210, 255)))
                        e.Graphics.FillRectangle(accent, 0, 0, 4, panelHeader.Height);
                };
            }
            else
            {
                panelHeader.Controls.Add(lblTitle);
                panelHeader.Controls.Add(lblSub);
            }

            var panelBody = new Panel
            {
                Dock = DockStyle.Fill,
                Padding = new Padding(22, 24, 22, 16),
                BackColor = Color.White
            };

            var btnCredentials = new Button
            {
                Text = isLogbookAccessDialog ? "Type Authorized Credentials" : "Type Credentials",
                Dock = DockStyle.Top,
                Height = isLogbookAccessDialog ? 58 : 52,
                BackColor = isLogbookAccessDialog
                    ? Color.FromArgb(31, 65, 122)
                    : Color.FromArgb(37, 99, 235),
                ForeColor = Color.White,
                Font = new Font("Segoe UI", isLogbookAccessDialog ? 10F : 10.5F, FontStyle.Bold),
                FlatStyle = FlatStyle.Flat,
                Cursor = Cursors.Hand,
                Enabled = allowCredentials,
                Padding = isLogbookAccessDialog ? new Padding(12, 4, 12, 4) : Padding.Empty
            };
            btnCredentials.FlatAppearance.BorderSize = 0;
            btnCredentials.FlatAppearance.MouseOverBackColor = isLogbookAccessDialog
                ? Color.FromArgb(42, 83, 148)
                : Color.FromArgb(24, 81, 199);
            btnCredentials.FlatAppearance.MouseDownBackColor = isLogbookAccessDialog
                ? Color.FromArgb(24, 52, 101)
                : Color.FromArgb(20, 67, 169);
            btnCredentials.Click += (_, __) => methodForm.DialogResult = DialogResult.OK;

            var btnFingerprint = new Button
            {
                Text = fingerprintLabel ?? "Use Fingerprint",
                Dock = DockStyle.Top,
                Height = isLogbookAccessDialog ? 58 : 52,
                Margin = new Padding(0, 10, 0, 0),
                BackColor = isLogbookAccessDialog
                    ? Color.FromArgb(31, 65, 122)
                    : Color.FromArgb(22, 40, 62),
                ForeColor = Color.White,
                Font = new Font("Segoe UI", isLogbookAccessDialog ? 10F : 10.5F, FontStyle.Bold),
                FlatStyle = FlatStyle.Flat,
                Cursor = Cursors.Hand,
                Enabled = allowFingerprint,
                Padding = isLogbookAccessDialog ? new Padding(12, 4, 12, 4) : Padding.Empty
            };
            btnFingerprint.FlatAppearance.BorderSize = 0;
            btnFingerprint.FlatAppearance.MouseOverBackColor = isLogbookAccessDialog
                ? Color.FromArgb(42, 83, 148)
                : Color.FromArgb(6, 105, 137);
            btnFingerprint.FlatAppearance.MouseDownBackColor = isLogbookAccessDialog
                ? Color.FromArgb(24, 52, 101)
                : Color.FromArgb(4, 84, 111);
            btnFingerprint.Click += (_, __) => methodForm.DialogResult = DialogResult.Yes;

            var choiceGap = new Panel { Dock = DockStyle.Top, Height = isLogbookAccessDialog ? 14 : 10 };
            panelBody.Controls.Add(btnFingerprint);
            panelBody.Controls.Add(choiceGap);
            panelBody.Controls.Add(btnCredentials);

            var btnCancel = new Button
            {
                Text = "Cancel",
                Width = 122,
                Height = isLogbookAccessDialog ? 40 : 38,
                DialogResult = DialogResult.Cancel,
                BackColor = isLogbookAccessDialog
                    ? Color.FromArgb(17, 26, 51)
                    : Color.FromArgb(30, 55, 85),
                ForeColor = isLogbookAccessDialog
                    ? Color.FromArgb(220, 228, 242)
                    : Color.FromArgb(220, 230, 245),
                Font = new Font("Segoe UI", 9.5F, FontStyle.Bold),
                FlatStyle = FlatStyle.Flat,
                Cursor = Cursors.Hand
            };
            btnCancel.FlatAppearance.BorderSize = isLogbookAccessDialog ? 1 : 0;
            btnCancel.FlatAppearance.BorderColor = isLogbookAccessDialog
                ? Color.FromArgb(70, 88, 123)
                : Color.FromArgb(199, 214, 235);
            btnCancel.FlatAppearance.MouseOverBackColor = isLogbookAccessDialog
                ? Color.FromArgb(29, 42, 73)
                : Color.FromArgb(235, 243, 253);
            btnCancel.FlatAppearance.MouseDownBackColor = Color.FromArgb(220, 233, 250);

            var buttonBar = new FlowLayoutPanel
            {
                Dock = DockStyle.Bottom,
                Height = isLogbookAccessDialog ? 66 : 58,
                FlowDirection = FlowDirection.RightToLeft,
                Padding = new Padding(12, 10, 12, 8),
                WrapContents = false,
                BackColor = isLogbookAccessDialog
                    ? Color.FromArgb(11, 19, 43)
                    : Color.FromArgb(236, 241, 248)
            };
            buttonBar.Controls.Add(btnCancel);
            methodForm.Controls.Add(panelBody);
            methodForm.Controls.Add(buttonBar);
            methodForm.Controls.Add(panelHeader);
            methodForm.CancelButton = btnCancel;
            methodForm.KeyDown += (_, e) =>
            {
                if (e.KeyCode != Keys.Escape) return;
                methodForm.DialogResult = DialogResult.Cancel;
                methodForm.Close();
            };

            DesktopTheme.Apply(methodForm);
            if (isLogbookAccessDialog)
            {
                methodForm.BackColor = Color.FromArgb(11, 19, 43);
                panelHeader.BackColor = Color.FromArgb(17, 26, 51);
                lblTitle.ForeColor = Color.FromArgb(245, 248, 255);
                lblSub.ForeColor = Color.FromArgb(183, 194, 215);
                panelBody.BackColor = Color.FromArgb(28, 37, 65);
                panelBody.Padding = new Padding(22, 24, 22, 16);
                foreach (Button button in new[] { btnCredentials, btnFingerprint })
                {
                    button.BackColor = Color.White;
                    button.ForeColor = Color.FromArgb(10, 17, 40);
                    button.Font = new Font(button.Font, button.Font.Style | FontStyle.Bold);
                    button.UseVisualStyleBackColor = false;
                    button.FlatAppearance.BorderSize = 1;
                    button.FlatAppearance.BorderColor = Color.FromArgb(203, 213, 225);
                    button.FlatAppearance.MouseOverBackColor = Color.FromArgb(241, 245, 249);
                    button.FlatAppearance.MouseDownBackColor = Color.FromArgb(226, 232, 240);
                }
                btnCancel.BackColor = Color.FromArgb(17, 26, 51);
                btnCancel.ForeColor = Color.FromArgb(220, 228, 242);
                btnCancel.FlatAppearance.BorderColor = Color.FromArgb(70, 88, 123);
                btnCancel.FlatAppearance.MouseOverBackColor = Color.FromArgb(29, 42, 73);
                buttonBar.BackColor = Color.FromArgb(11, 19, 43);
                ApplyRoundedRegion(btnCredentials, 10);
                ApplyRoundedRegion(btnFingerprint, 10);
                ApplyRoundedRegion(btnCancel, 9);
                panelBody.Paint += (_, e) =>
                {
                    Rectangle bounds = panelBody.ClientRectangle;
                    if (bounds.Width < 2 || bounds.Height < 2)
                        return;

                    e.Graphics.SmoothingMode = SmoothingMode.AntiAlias;
                    using (var shadow = new Pen(Color.FromArgb(90, 5, 10, 24)))
                        e.Graphics.DrawLine(shadow, 1, bounds.Height - 2, bounds.Width - 2, bounds.Height - 2);
                    using (var border = new Pen(Color.FromArgb(53, 68, 102)))
                        e.Graphics.DrawRectangle(border, 0, 0, bounds.Width - 1, bounds.Height - 1);
                };
            }
            if (isAdminAuthentication)
            {
                Color actionText = Color.FromArgb(10, 17, 40);
                foreach (Button button in new[] { btnCredentials, btnFingerprint })
                {
                    button.BackColor = Color.White;
                    button.ForeColor = actionText;
                    button.Font = new Font(button.Font, button.Font.Style | FontStyle.Bold);
                    button.FlatStyle = FlatStyle.Flat;
                    button.UseVisualStyleBackColor = false;
                    button.FlatAppearance.BorderSize = 1;
                    button.FlatAppearance.BorderColor = Color.FromArgb(203, 213, 225);
                    button.FlatAppearance.MouseOverBackColor = Color.FromArgb(241, 245, 249);
                    button.FlatAppearance.MouseDownBackColor = Color.FromArgb(226, 232, 240);
                }

                btnCancel.BackColor = Color.FromArgb(127, 29, 29);
                btnCancel.ForeColor = Color.White;
                btnCancel.FlatAppearance.BorderSize = 0;
                btnCancel.FlatAppearance.MouseOverBackColor = Color.FromArgb(104, 22, 22);
                btnCancel.FlatAppearance.MouseDownBackColor = Color.FromArgb(69, 10, 10);
            }
            var result = methodForm.ShowDialog(this);
            if (result == DialogResult.OK)
                return AuthMethod.Credentials;
            if (result == DialogResult.Yes)
                return AuthMethod.Fingerprint;
            return null;
        }

        private static void ApplyRoundedRegion(Control control, int radius)
        {
            void UpdateRegion(object sender, EventArgs e)
            {
                if (control.Width < 2 || control.Height < 2)
                    return;

                int diameter = Math.Min(radius * 2, Math.Min(control.Width, control.Height));
                using (var path = new GraphicsPath())
                {
                    path.AddArc(0, 0, diameter, diameter, 180, 90);
                    path.AddArc(control.Width - diameter, 0, diameter, diameter, 270, 90);
                    path.AddArc(control.Width - diameter, control.Height - diameter, diameter, diameter, 0, 90);
                    path.AddArc(0, control.Height - diameter, diameter, diameter, 90, 90);
                    path.CloseFigure();

                    Region previous = control.Region;
                    control.Region = new Region(path);
                    previous?.Dispose();
                }
            }

            control.Resize += UpdateRegion;
            UpdateRegion(control, EventArgs.Empty);
        }

        private bool TryAuthenticateByFingerprint(
            string dialogTitle,
            string promptText,
            Func<int, bool> isAuthorized,
            out int userId)
        {
            userId = -1;
            if (isAuthorized == null)
                throw new ArgumentNullException(nameof(isAuthorized));

            bool isVisitorLogbookScan = string.Equals(
                dialogTitle,
                "Logbook Access Records",
                StringComparison.Ordinal) ||
                string.Equals(dialogTitle, "Admin Fingerprint Authentication", StringComparison.Ordinal);

            if (_engine == null || !_engine.IsConnected)
            {
                MessageBox.Show(
                    "Fingerprint device is not connected.",
                    "Device Error",
                    MessageBoxButtons.OK,
                    MessageBoxIcon.Warning);
                return false;
            }

            scanTimer.Stop();
            try
            {
                int waitedMs = 0;
                while (_isScanning && waitedMs < 5000)
                {
                    Application.DoEvents();
                    System.Threading.Thread.Sleep(50);
                    waitedMs += 50;
                }

                using var scanForm = new Form
                {
                    Text = dialogTitle,
                    StartPosition = FormStartPosition.CenterParent,
                    FormBorderStyle = FormBorderStyle.Sizable,
                    MaximizeBox = false,
                    MinimizeBox = false,
                    ShowInTaskbar = false,
                    MinimumSize = new Size(420, 240),
                    ClientSize = new Size(560, 260),
                    BackColor = isVisitorLogbookScan
                        ? Color.FromArgb(11, 19, 43)
                        : Color.FromArgb(236, 241, 248),
                    KeyPreview = true,
                    AutoScaleMode = AutoScaleMode.Font,
                    Font = new Font("Segoe UI", 9.5F)
                };
                ResponsiveLayout.FitClientToWorkingArea(scanForm, 560, 260, 420, 240);

                var panelHeader = new Panel
                {
                    Dock = DockStyle.Top,
                    Height = 78,
                    BackColor = isVisitorLogbookScan
                        ? Color.FromArgb(17, 26, 51)
                        : Color.FromArgb(13, 27, 42)
                };
                var lblTitle = new Label
                {
                    Text = "Fingerprint authentication",
                    AutoSize = true,
                    Font = new Font("Segoe UI", 12.5F, FontStyle.Bold),
                    ForeColor = Color.FromArgb(240, 246, 255),
                    BackColor = Color.Transparent,
                    Location = new Point(18, 13)
                };
                var lblSub = new Label
                {
                    Text = promptText ?? "Scan fingerprint to continue",
                    AutoSize = true,
                    Font = new Font("Segoe UI", 9.5F),
                    ForeColor = isVisitorLogbookScan
                        ? Color.FromArgb(183, 194, 215)
                        : Color.FromArgb(154, 180, 209),
                    BackColor = Color.Transparent,
                    Location = new Point(20, 45)
                };
                panelHeader.Controls.Add(lblTitle);
                panelHeader.Controls.Add(lblSub);

                var panelBody = new Panel
                {
                    Dock = DockStyle.Fill,
                    Padding = isVisitorLogbookScan
                        ? new Padding(22, 18, 22, 14)
                        : new Padding(18, 16, 18, 8),
                    BackColor = isVisitorLogbookScan
                        ? Color.FromArgb(28, 37, 65)
                        : Color.White
                };
                Color statusCardBackColor = Color.FromArgb(17, 26, 51);
                Color feedbackColor = Color.Empty;
                bool feedbackAuthorized = false;
                DateTime feedbackStartedAt = DateTime.MinValue;
                int feedbackUserId = -1;
                using var resultFeedbackTimer = new Timer { Interval = 125 };
                var lblStatus = new Label
                {
                    Text = "Place finger on scanner…",
                    Dock = DockStyle.Fill,
                    Font = new Font("Segoe UI", 11F),
                    ForeColor = isVisitorLogbookScan
                        ? Color.FromArgb(220, 228, 242)
                        : Color.FromArgb(30, 41, 59),
                    BackColor = Color.Transparent,
                    Padding = new Padding(14, 8, 14, 8),
                    TextAlign = ContentAlignment.MiddleLeft
                };
                UserControl statusCard = null;
                if (isVisitorLogbookScan)
                {
                    statusCard = new UserControl
                    {
                        Dock = DockStyle.Fill,
                        BackColor = statusCardBackColor,
                        Padding = Padding.Empty,
                        BorderStyle = BorderStyle.None
                    };
                    statusCard.Controls.Add(lblStatus);
                    statusCard.Paint += (_, e) =>
                    {
                        Rectangle bounds = statusCard.ClientRectangle;
                        if (bounds.Width < 2 || bounds.Height < 2)
                            return;

                        e.Graphics.SmoothingMode = SmoothingMode.AntiAlias;
                        using (var shadow = new Pen(Color.FromArgb(90, 5, 10, 24)))
                            e.Graphics.DrawLine(shadow, 1, bounds.Height - 2, bounds.Width - 2, bounds.Height - 2);
                        Color borderColor = feedbackColor.IsEmpty
                            ? Color.FromArgb(53, 68, 102)
                            : feedbackColor;
                        using (var border = new Pen(borderColor))
                            e.Graphics.DrawRectangle(border, 0, 0, bounds.Width - 1, bounds.Height - 1);
                    };
                    panelBody.Controls.Add(statusCard);
                }
                else
                {
                    panelBody.Controls.Add(lblStatus);
                }

                var btnCancel = new Button
                {
                    Text = "Cancel",
                    Width = 122,
                    Height = 38,
                    DialogResult = DialogResult.Cancel,
                    BackColor = isVisitorLogbookScan
                        ? Color.FromArgb(17, 26, 51)
                        : Color.FromArgb(22, 40, 62),
                    ForeColor = Color.FromArgb(220, 230, 245),
                    Font = new Font("Segoe UI", 9.5F, FontStyle.Bold),
                    FlatStyle = FlatStyle.Flat,
                    Cursor = Cursors.Hand
                };
                btnCancel.FlatAppearance.BorderSize = isVisitorLogbookScan ? 1 : 0;
                btnCancel.FlatAppearance.BorderColor = Color.FromArgb(70, 88, 123);
                btnCancel.FlatAppearance.MouseOverBackColor = isVisitorLogbookScan
                    ? Color.FromArgb(29, 42, 73)
                    : Color.FromArgb(30, 55, 85);

                var buttonBar = new FlowLayoutPanel
                {
                    Dock = DockStyle.Bottom,
                    Height = 58,
                    FlowDirection = FlowDirection.RightToLeft,
                    Padding = new Padding(12, 10, 12, 8),
                    WrapContents = false,
                    BackColor = isVisitorLogbookScan
                        ? Color.FromArgb(11, 19, 43)
                        : Color.FromArgb(236, 241, 248)
                };
                buttonBar.Controls.Add(btnCancel);
                scanForm.Controls.Add(panelBody);
                scanForm.Controls.Add(buttonBar);
                scanForm.Controls.Add(panelHeader);
                scanForm.CancelButton = btnCancel;
                scanForm.KeyDown += (_, e) =>
                {
                    if (e.KeyCode != Keys.Escape) return;
                    scanForm.DialogResult = DialogResult.Cancel;
                    scanForm.Close();
                };
                scanForm.FormClosed += (_, __) => resultFeedbackTimer.Stop();

                int matchedId = -1;
                resultFeedbackTimer.Tick += (_, __) =>
                {
                    const double feedbackDurationMs = 1000;
                    const int blinkCycles = 2;
                    double elapsedMs = (DateTime.UtcNow - feedbackStartedAt).TotalMilliseconds;
                    if (elapsedMs >= feedbackDurationMs)
                    {
                        resultFeedbackTimer.Stop();
                        scanForm.DialogResult = feedbackAuthorized ? DialogResult.OK : DialogResult.Cancel;
                        scanForm.Close();
                        return;
                    }

                    double halfCycleMs = feedbackDurationMs / (blinkCycles * 2.0);
                    int phase = (int)(elapsedMs / halfCycleMs);
                    statusCard.BackColor = phase % 2 == 0 ? feedbackColor : statusCardBackColor;
                    statusCard.Invalidate();
                };

                // Run scan in background so UI stays responsive.
                var scanTask = System.Threading.Tasks.Task.Run(() =>
                {
                    try
                    {
                        return _engine.IdentifyUser(timeoutMs: 15000);
                    }
                    catch
                    {
                        return -1;
                    }
                });

                scanTask.ContinueWith(t =>
                {
                    if (scanForm.IsDisposed) return;
                    scanForm.BeginInvoke((Action)(() =>
                    {
                        matchedId = t.Result;
                        feedbackUserId = matchedId;
                        feedbackAuthorized = matchedId > 0 && isAuthorized(matchedId);
                        feedbackColor = feedbackAuthorized
                            ? Color.FromArgb(22, 163, 74)
                            : Color.FromArgb(185, 28, 28);
                        feedbackStartedAt = DateTime.UtcNow;
                        lblStatus.Text = feedbackAuthorized
                            ? "Fingerprint verified. Access granted."
                            : matchedId > 0
                                ? "Fingerprint recognized, but this account is not authorized."
                                : "Fingerprint not recognized.";
                        lblStatus.ForeColor = Color.White;
                        PlayResultSound(feedbackAuthorized);
                        statusCard.BackColor = feedbackColor;
                        statusCard.Invalidate();
                        resultFeedbackTimer.Start();
                    }));
                });

                DesktopTheme.Apply(scanForm);
                if (isVisitorLogbookScan)
                {
                    scanForm.BackColor = Color.FromArgb(11, 19, 43);
                    panelHeader.BackColor = Color.FromArgb(17, 26, 51);
                    lblTitle.ForeColor = Color.FromArgb(245, 248, 255);
                    lblSub.ForeColor = Color.FromArgb(183, 194, 215);
                    panelBody.BackColor = Color.FromArgb(28, 37, 65);
                    lblStatus.ForeColor = Color.FromArgb(220, 228, 242);
                    btnCancel.BackColor = Color.FromArgb(17, 26, 51);
                    btnCancel.ForeColor = Color.FromArgb(220, 228, 242);
                    btnCancel.FlatAppearance.BorderColor = Color.FromArgb(70, 88, 123);
                    btnCancel.FlatAppearance.MouseOverBackColor = Color.FromArgb(29, 42, 73);
                    buttonBar.BackColor = Color.FromArgb(11, 19, 43);
                    ApplyRoundedRegion(btnCancel, 9);
                }
                var result = scanForm.ShowDialog(this);
                userId = feedbackUserId;
                if (result != DialogResult.OK)
                    return false;

                if (userId <= 0)
                {
                    MessageBox.Show(
                        string.IsNullOrWhiteSpace(_engine.LastError) ? "Fingerprint not recognised." : _engine.LastError,
                        "Authentication Failed",
                        MessageBoxButtons.OK,
                        MessageBoxIcon.Error);
                    return false;
                }

                return true;
            }
            finally
            {
                scanTimer.Start();
            }
        }

        private void MainForm_FormClosing(object sender, FormClosingEventArgs e)
        {
            clockTimer.Stop();
            scanTimer.Stop();
            _resultFlashTimer.Stop();
            _notificationHideTimer.Stop();
            _statusMessageHideTimer.Stop();
            _logoBlinkTimer.Stop();
            CloseResultSound();
            _resultSoundCleanupTimer.Dispose();
            _resultFlashTimer.Dispose();
            _notificationHideTimer.Dispose();
            _statusMessageHideTimer.Dispose();
            _logoBlinkTimer.Dispose();
            _notificationBannerFont.Dispose();
            _notificationModal?.Dispose();
            _engine?.Dispose();
            _scannerGuideIllustration?.Dispose();
            _barangayLogo?.Dispose();
        }

    }
}