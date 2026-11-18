using System;
using System.Drawing;
using System.Windows.Forms;
using FingerprintAttendance.Core;

namespace FingerprintAttendance
{
    public partial class AttendanceLogForm : Form
    {
        private DatabaseHelper _db;
        private readonly Timer _autoRefreshTimer = new Timer { Interval = 5000 };
        private readonly Timer _liveHoursTimer = new Timer { Interval = 1000 };
        private bool _isLoadingLogs;

        public AttendanceLogForm(DatabaseHelper db)
        {
            InitializeComponent();
            DesktopTheme.Apply(this);
            _db = db;
            _autoRefreshTimer.Tick += (_, __) => LoadLogs();
            _liveHoursTimer.Tick += (_, __) => UpdateLiveDurationCells();
            ApplyLightTheme();
            ResponsiveLayout.FitClientToWorkingArea(this, 1000, 680, 720, 480);
            pnlFilterInner.Resize += (_, __) => PlaceFilterButton();
            PlaceFilterButton();

            KeyPreview = true;
            KeyDown += (_, e) =>
            {
                if (e.KeyCode != Keys.Escape) return;
                Close();
            };
            FormClosed += (_, __) =>
            {
                _autoRefreshTimer.Stop();
                _liveHoursTimer.Stop();
                _autoRefreshTimer.Dispose();
                _liveHoursTimer.Dispose();
            };
        }

        private void AttendanceLogForm_Load(object sender, EventArgs e)
        {
            datePicker.Value = DateTime.Today;
            PlaceFilterButton();
            ConfigureGridColumns();
            LoadLogs();

            // Make grid look clean
            dgvLogs.AutoSizeColumnsMode = DataGridViewAutoSizeColumnsMode.Fill;
            dgvLogs.ReadOnly = true;
            dgvLogs.AllowUserToAddRows = false;
            ApplyGridTheme();
            dgvLogs.CellClick += DgvLogs_CellClick;
            _autoRefreshTimer.Start();
            _liveHoursTimer.Start();
        }

        private void ApplyLightTheme()
        {
            Color page = Color.FromArgb(244, 247, 250);
            Color slateBlue = Color.FromArgb(13, 20, 38);
            Color electricBlue = Color.FromArgb(29, 97, 231);
            Color cyan = Color.FromArgb(0, 194, 230);
            Color darkBlue = Color.FromArgb(23, 55, 105);
            Color text = Color.White;
            Color mutedText = Color.FromArgb(226, 232, 240);

            BackColor = page;
            panelHeader.BackColor = slateBlue;
            pnlHeaderLeft.BackColor = electricBlue;
            pnlHeaderAccent.BackColor = cyan;
            lblTitle.ForeColor = text;
            lblTitle.Font = new Font("Segoe UI", 16F, FontStyle.Bold);
            lblTitleSub.ForeColor = mutedText;
            lblTitleSub.Font = new Font("Segoe UI", 9.5F, FontStyle.Bold);

            panelFilter.BackColor = Color.White;
            panelFilter.Padding = new Padding(1);
            pnlFilterAccent.BackColor = cyan;
            pnlFilterInner.BackColor = Color.White;
            lblDate.ForeColor = Color.FromArgb(10, 17, 40);
            lblDate.Font = new Font("Segoe UI", 9F, FontStyle.Bold);
            datePicker.Font = new Font("Segoe UI", 10F, FontStyle.Bold);
            datePicker.CalendarTitleBackColor = electricBlue;
            datePicker.CalendarTitleForeColor = Color.White;
            datePicker.CalendarMonthBackground = Color.White;
            datePicker.CalendarForeColor = text;

            btnLoad.Text = "Load Records";
            btnLoad.Font = new Font("Segoe UI", 10F, FontStyle.Bold);
            btnLoad.BackColor = darkBlue;
            btnLoad.ForeColor = Color.White;
            btnLoad.FlatStyle = FlatStyle.Flat;
            btnLoad.UseVisualStyleBackColor = false;
            btnLoad.FlatAppearance.BorderSize = 0;
            btnLoad.FlatAppearance.MouseOverBackColor = Color.FromArgb(32, 75, 138);
            btnLoad.FlatAppearance.MouseDownBackColor = Color.FromArgb(15, 41, 82);
            btnLoad.Cursor = Cursors.Hand;
            btnLoad.Padding = new Padding(12, 4, 12, 4);

            pnlGridSection.BackColor = page;
            lblGridSection.ForeColor = Color.FromArgb(29, 80, 157);
            lblGridSection.Font = new Font("Segoe UI", 9F, FontStyle.Bold);

            panelFilter.Paint += (_, e) => DrawCardBorder(e.Graphics, panelFilter.ClientRectangle, cyan);
            dgvLogs.Paint += (_, e) => DrawCardBorder(e.Graphics, dgvLogs.ClientRectangle, electricBlue);
            ApplyGridTheme();
        }

        private void ApplyGridTheme()
        {
            Color text = Color.FromArgb(30, 41, 59);
            Color surface = Color.White;
            Color alternate = Color.FromArgb(246, 249, 253);
            Color header = Color.FromArgb(235, 243, 253);
            Color border = Color.FromArgb(218, 228, 241);

            dgvLogs.BackgroundColor = surface;
            dgvLogs.BorderStyle = BorderStyle.None;
            dgvLogs.GridColor = border;
            dgvLogs.EnableHeadersVisualStyles = false;
            dgvLogs.ColumnHeadersHeight = 44;
            dgvLogs.ColumnHeadersBorderStyle = DataGridViewHeaderBorderStyle.Single;
            dgvLogs.ColumnHeadersDefaultCellStyle.BackColor = header;
            dgvLogs.ColumnHeadersDefaultCellStyle.ForeColor = Color.FromArgb(29, 80, 157);
            dgvLogs.ColumnHeadersDefaultCellStyle.SelectionBackColor = header;
            dgvLogs.ColumnHeadersDefaultCellStyle.SelectionForeColor = Color.FromArgb(29, 80, 157);
            dgvLogs.ColumnHeadersDefaultCellStyle.Font = new Font("Segoe UI", 9F, FontStyle.Bold);
            dgvLogs.ColumnHeadersDefaultCellStyle.Padding = new Padding(12, 0, 8, 0);
            dgvLogs.DefaultCellStyle.BackColor = surface;
            dgvLogs.DefaultCellStyle.ForeColor = text;
            dgvLogs.DefaultCellStyle.Font = new Font("Segoe UI", 10F, FontStyle.Bold);
            dgvLogs.DefaultCellStyle.SelectionBackColor = Color.FromArgb(29, 97, 231);
            dgvLogs.DefaultCellStyle.SelectionForeColor = Color.White;
            dgvLogs.DefaultCellStyle.Padding = new Padding(12, 3, 8, 3);
            dgvLogs.AlternatingRowsDefaultCellStyle.BackColor = alternate;
            dgvLogs.AlternatingRowsDefaultCellStyle.ForeColor = text;
            dgvLogs.AlternatingRowsDefaultCellStyle.Font = new Font("Segoe UI", 10F, FontStyle.Bold);
            dgvLogs.AlternatingRowsDefaultCellStyle.SelectionBackColor = Color.FromArgb(29, 97, 231);
            dgvLogs.AlternatingRowsDefaultCellStyle.SelectionForeColor = Color.White;
            dgvLogs.Font = new Font("Segoe UI", 10F, FontStyle.Bold);
            dgvLogs.RowTemplate.Height = 42;
            dgvLogs.CellBorderStyle = DataGridViewCellBorderStyle.SingleHorizontal;
        }

        private static void DrawCardBorder(Graphics graphics, Rectangle bounds, Color accent)
        {
            if (bounds.Width < 4 || bounds.Height < 4)
                return;

            using (var shadow = new Pen(Color.FromArgb(20, 32, 56, 88)))
                graphics.DrawRectangle(shadow, 1, 1, bounds.Width - 3, bounds.Height - 3);
            using (var border = new Pen(Color.FromArgb(207, 220, 237)))
                graphics.DrawRectangle(border, 0, 0, bounds.Width - 1, bounds.Height - 1);
            using (var accentLine = new Pen(Color.FromArgb(110, accent)))
                graphics.DrawLine(accentLine, 1, 1, bounds.Width - 2, 1);
        }

        private void PlaceFilterButton()
        {
            if (pnlFilterInner == null || btnLoad == null || datePicker == null)
                return;
            int right = Math.Max(datePicker.Right + 12, pnlFilterInner.ClientSize.Width - btnLoad.Width - 16);
            btnLoad.Left = right;
            btnLoad.Top = Math.Max(8, (pnlFilterInner.ClientSize.Height - btnLoad.Height) / 2);
        }

        private void btnLoad_Click(object sender, EventArgs e) => LoadLogs();

        private void ConfigureGridColumns()
        {
            dgvLogs.AutoGenerateColumns = false;
            dgvLogs.Columns.Clear();
            dgvLogs.Columns.Add(new DataGridViewTextBoxColumn
            {
                Name = "FullName",
                DataPropertyName = "FullName",
                HeaderText = "Name",
                FillWeight = 40,
                MinimumWidth = 180
            });
            dgvLogs.Columns.Add(new DataGridViewTextBoxColumn
            {
                Name = "TimeIn",
                DataPropertyName = "TimeIn",
                HeaderText = "Time In",
                FillWeight = 30,
                MinimumWidth = 180,
                DefaultCellStyle = { Format = "yyyy-MM-dd hh:mm:ss tt" }
            });
            dgvLogs.Columns.Add(new DataGridViewTextBoxColumn
            {
                Name = "TimeOut",
                DataPropertyName = "TimeOut",
                HeaderText = "Time Out",
                FillWeight = 25,
                MinimumWidth = 160,
                DefaultCellStyle = { Format = "yyyy-MM-dd hh:mm:ss tt" }
            });
            dgvLogs.Columns.Add(new DataGridViewTextBoxColumn
            {
                Name = "DailyTotalHours",
                DataPropertyName = "DailyTotalHours",
                HeaderText = "Daily Total (Hours)",
                FillWeight = 18,
                MinimumWidth = 130,
                DefaultCellStyle = { Format = "0.00" }
            });
        }

        private void LoadLogs()
        {
            if (_isLoadingLogs || IsDisposed)
                return;

            _isLoadingLogs = true;
            try
            {
                int? selectedSessionId = dgvLogs.CurrentRow?.DataBoundItem is Models.AttendanceModel selected
                    ? selected.SessionId
                    : (int?)null;
                var logs = _db.GetAttendanceLogs(datePicker.Value);
                dgvLogs.DataSource = logs;
                if (selectedSessionId.HasValue)
                {
                    foreach (DataGridViewRow row in dgvLogs.Rows)
                    {
                        if (row.DataBoundItem is Models.AttendanceModel entry && entry.SessionId == selectedSessionId.Value)
                        {
                            row.Selected = true;
                            break;
                        }
                    }
                }

                lblGridSection.Text = "ATTENDANCE ENTRIES · LIVE REFRESH";
                UpdateLiveDurationCells();
            }
            catch (Exception ex)
            {
                lblGridSection.Text = "LIVE REFRESH ERROR: " + ex.Message;
            }
            finally
            {
                _isLoadingLogs = false;
            }
        }

        private void UpdateLiveDurationCells()
        {
            if (IsDisposed || dgvLogs.Rows.Count == 0)
                return;

            DateTime now = DateTime.Now;
            foreach (DataGridViewRow row in dgvLogs.Rows)
            {
                if (row.DataBoundItem is Models.AttendanceModel entry)
                    row.Cells["DailyTotalHours"].Value = entry.GetCurrentDailyTotalHours(now).ToString("0.00");
            }
        }

        private void DgvLogs_CellClick(object sender, DataGridViewCellEventArgs e)
        {
            if (e.RowIndex < 0) return;
            if (e.RowIndex >= dgvLogs.Rows.Count) return;

            if (!(dgvLogs.Rows[e.RowIndex].DataBoundItem is Models.AttendanceModel entry))
                return;

            ShowAttendanceRecordOverlay(entry);
        }

        private void ShowAttendanceRecordOverlay(Models.AttendanceModel entry)
        {
            using var detailForm = new Form
            {
                Text = "Attendance Record Details",
                StartPosition = FormStartPosition.CenterParent,
                FormBorderStyle = FormBorderStyle.FixedDialog,
                MaximizeBox = false,
                MinimizeBox = false,
                ShowInTaskbar = false,
                ClientSize = new Size(640, 440),
                BackColor = Color.FromArgb(239, 244, 250),
                Font = new Font("Segoe UI", 9F)
            };
            detailForm.KeyPreview = true;

            var header = new Panel
            {
                Dock = DockStyle.Top,
                Height = 76,
                BackColor = Color.FromArgb(231, 240, 251),
                Padding = new Padding(24, 12, 20, 10)
            };

            var headerAccent = new Panel
            {
                Dock = DockStyle.Left,
                Width = 5,
                BackColor = Color.FromArgb(0, 194, 230)
            };

            var lblTitle = new Label
            {
                Text = "Attendance Record Details",
                Dock = DockStyle.Top,
                Height = 38,
                Font = new Font("Segoe UI", 16F, FontStyle.Bold),
                ForeColor = Color.FromArgb(23, 55, 105),
                BackColor = Color.Transparent,
                TextAlign = ContentAlignment.MiddleLeft
            };

            header.Controls.Add(lblTitle);
            header.Controls.Add(headerAccent);

            var card = new Panel
            {
                Left = 24,
                Top = 100,
                Width = 592,
                Height = 266,
                BackColor = Color.FromArgb(252, 254, 255),
                Padding = new Padding(1)
            };
            card.Paint += (_, e) => DrawCardBorder(
                e.Graphics, card.ClientRectangle, Color.FromArgb(29, 97, 231));

            var details = new TableLayoutPanel
            {
                Dock = DockStyle.Fill,
                ColumnCount = 2,
                RowCount = 4,
                Padding = new Padding(22, 14, 22, 14),
                BackColor = Color.FromArgb(252, 254, 255),
                CellBorderStyle = TableLayoutPanelCellBorderStyle.None
            };
            details.ColumnStyles.Add(new ColumnStyle(SizeType.Absolute, 154F));
            details.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 100F));
            for (int row = 0; row < details.RowCount; row++)
                details.RowStyles.Add(new RowStyle(SizeType.Percent, 100F / details.RowCount));

            AddDetailRow(details, 0, "Employee Name", entry.FullName);
            AddDetailRow(details, 1, "Time In", FormatAttendanceTime(entry.TimeIn));
            AddDetailRow(details, 2, "Time Out", FormatAttendanceTime(entry.TimeOut));
            AddDetailRow(details, 3, "Daily Total (Hours)", entry.GetCurrentDailyTotalHours(DateTime.Now).ToString("0.00"));
            card.Controls.Add(details);

            var btnClose = new Button
            {
                Text = "Close",
                Width = 132,
                Height = 42,
                Left = 484,
                Top = 370,
                BackColor = Color.FromArgb(29, 97, 231),
                ForeColor = Color.White,
                Font = new Font("Segoe UI", 10F, FontStyle.Bold),
                FlatStyle = FlatStyle.Flat,
                Cursor = Cursors.Hand
            };
            btnClose.FlatAppearance.BorderSize = 0;
            btnClose.FlatAppearance.MouseOverBackColor = Color.FromArgb(20, 78, 190);
            btnClose.FlatAppearance.MouseDownBackColor = Color.FromArgb(15, 60, 150);
            btnClose.Click += (_, __) => detailForm.Close();
            detailForm.CancelButton = btnClose;
            detailForm.KeyDown += (_, e) =>
            {
                if (e.KeyCode != Keys.Escape) return;
                detailForm.Close();
            };

            detailForm.Controls.Add(header);
            detailForm.Controls.Add(card);
            detailForm.Controls.Add(btnClose);
            ResponsiveLayout.FitClientToWorkingArea(detailForm, detailForm.ClientSize.Width, detailForm.ClientSize.Height, 420, 320);
            DesktopTheme.Apply(detailForm);
            detailForm.ShowDialog(this);
        }

        private static string FormatAttendanceTime(DateTime? time)
        {
            return time.HasValue ? time.Value.ToString("yyyy-MM-dd hh:mm:ss tt") : "Not recorded";
        }

        private static void AddDetailRow(TableLayoutPanel container, int rowIndex, string label, string value)
        {
            var lblKey = new Label
            {
                Text = label,
                Dock = DockStyle.Fill,
                Font = new Font("Segoe UI", 9F, FontStyle.Bold),
                ForeColor = Color.FromArgb(29, 80, 157),
                TextAlign = ContentAlignment.MiddleLeft,
                Margin = new Padding(0, 2, 12, 2)
            };

            var lblValue = new Label
            {
                Text = string.IsNullOrWhiteSpace(value) ? "-" : value,
                Dock = DockStyle.Fill,
                Font = new Font("Segoe UI", 10F, FontStyle.Bold),
                ForeColor = Color.FromArgb(30, 41, 59),
                TextAlign = ContentAlignment.MiddleLeft,
                AutoEllipsis = true,
                Margin = new Padding(0, 2, 0, 2)
            };

            container.Controls.Add(lblKey, 0, rowIndex);
            container.Controls.Add(lblValue, 1, rowIndex);
        }
    }
}