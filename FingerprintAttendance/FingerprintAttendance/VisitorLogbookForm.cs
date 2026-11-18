using FingerprintAttendance.Core;
using System;
using System.Collections.Generic;
using System.Drawing;
using System.Drawing.Drawing2D;
using System.Threading.Tasks;
using System.Windows.Forms;

namespace FingerprintAttendance
{
    public class VisitorLogbookForm : Form
    {
        private readonly DatabaseHelper _db;
        private readonly FingerprintEngine _engine;
        private readonly Func<bool> _promptLogbookPrivileged;
        private readonly Action<bool> _playResultSound;
        private readonly DateTimePicker _datePicker = new DateTimePicker();
        private readonly Button _btnLoad = new Button();
        private readonly Button _btnNewEntry = new Button();
        private readonly Button _btnManualVisitorEntry = new Button();
        private readonly Button _btnDocumentClaim = new Button();
        private readonly Button _btnLogbookEntries = new Button();
        private readonly TextBox _txtSearchLog = new TextBox();
        private readonly DataGridView _grid = new DataGridView();
        private readonly Button _btnPreviousPage = new Button();
        private readonly Button _btnNextPage = new Button();
        private readonly Button _btnGoToPage = new Button();
        private readonly NumericUpDown _pageSelector = new NumericUpDown();
        private readonly ComboBox _pageSizeSelector = new ComboBox();
        private readonly Label _lblPageCount = new Label();
        private readonly Label _lblPageSize = new Label();
        private readonly Panel _panelSensitive = new Panel();
        private readonly Panel _panelActions;
        private readonly Label _lblRestrictedBanner;
        private readonly Label _lblSectionLabel;
        private readonly Timer _searchDebounce = new Timer();
        private bool _logbookSensitiveVisible;
        private int _currentPage = 1;
        private int _pageSize = 25;
        private int _totalPages = 1;
        private readonly Panel _panelHeader;
        private readonly TableLayoutPanel _layoutRoot;

        public VisitorLogbookForm(
            DatabaseHelper db,
            FingerprintEngine engine,
            Func<bool> promptLogbookPrivileged,
            Action<bool> playResultSound)
        {
            _db = db;
            _engine = engine;
            _promptLogbookPrivileged = promptLogbookPrivileged ?? (Func<bool>)(() => false);
            _playResultSound = playResultSound;

            Color page = Color.FromArgb(244, 247, 250);
            Color surface = Color.White;
            Color raisedSurface = Color.FromArgb(248, 250, 252);
            Color electricBlue = Color.FromArgb(29, 97, 231);
            Color cyan = Color.FromArgb(0, 174, 210);
            Color headerBlue = Color.FromArgb(10, 17, 40);
            Color primaryText = Color.FromArgb(30, 41, 59);
            Color secondaryText = Color.FromArgb(91, 109, 133);
            Color subtleBorder = Color.FromArgb(215, 225, 238);

            Text = "Logbook Records";
            StartPosition = FormStartPosition.CenterParent;
            FormBorderStyle = FormBorderStyle.Sizable;
            MaximizeBox = true;
            MinimizeBox = true;
            BackColor = page;
            AutoScaleMode = AutoScaleMode.Font;
            AutoScroll = true;
            Width = 1100;
            Height = 740;
            MinimumSize = new Size(720, 500);
            ResponsiveLayout.FitClientToWorkingArea(this, 1100, 740, 720, 500);

            const int headerBarHeight = 76;
            _panelHeader = new Panel
            {
                Dock = DockStyle.Fill,
                Height = headerBarHeight,
                BackColor = headerBlue
            };

            var pnlHeaderAccentBar = new Panel
            {
                Dock = DockStyle.Left,
                Width = 6,
                BackColor = electricBlue
            };

            var lblTitle = new Label
            {
                Text = "Logbook Transaction",
                Dock = DockStyle.Fill,
                AutoEllipsis = false,
                AutoSize = false,
                Padding = new Padding(12, 0, 12, 0),
                Font = new Font("Segoe UI", 19F, FontStyle.Bold),
                ForeColor = Color.White,
                BackColor = Color.Transparent,
                TextAlign = ContentAlignment.MiddleCenter
            };

            var pnlHeaderInner = new Panel { Dock = DockStyle.Fill, Height = headerBarHeight };
            pnlHeaderInner.Controls.Add(lblTitle);
            _panelHeader.Controls.Add(pnlHeaderAccentBar);
            _panelHeader.Controls.Add(pnlHeaderInner);

            var pnlHeaderAccent = new Panel
            {
                Dock = DockStyle.Fill,
                Height = 3,
                BackColor = cyan
            };

            // ── Primary actions (always visible) ───────────────────────────────
            _panelActions = new Panel
            {
                Dock = DockStyle.Fill,
                Padding = new Padding(32, 18, 32, 18),
                BackColor = page
            };

            var tableActions = new TableLayoutPanel
            {
                Dock = DockStyle.Fill,
                ColumnCount = 1,
                RowCount = 4,
                BackColor = page,
                Margin = Padding.Empty,
                Padding = new Padding(4)
            };
            tableActions.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 100F));
            for (int i = 0; i < 4; i++)
                tableActions.RowStyles.Add(new RowStyle(SizeType.Percent, 25F));

            _btnNewEntry.Text = "Biometric Transaction Entry";
            _btnNewEntry.BackColor = electricBlue;
            _btnNewEntry.ForeColor = Color.White;
            _btnNewEntry.FlatStyle = FlatStyle.Flat;
            _btnNewEntry.FlatAppearance.BorderSize = 0;
            _btnNewEntry.Dock = DockStyle.Fill;
            _btnNewEntry.Margin = new Padding(14);
            _btnNewEntry.Cursor = Cursors.Hand;
            _btnNewEntry.Click += (_, __) => StartNewVisitorEntry();

            _btnManualVisitorEntry.Text = "Manual Transaction Entry";
            _btnManualVisitorEntry.BackColor = raisedSurface;
            _btnManualVisitorEntry.ForeColor = primaryText;
            _btnManualVisitorEntry.FlatStyle = FlatStyle.Flat;
            _btnManualVisitorEntry.FlatAppearance.BorderSize = 1;
            _btnManualVisitorEntry.FlatAppearance.BorderColor = subtleBorder;
            _btnManualVisitorEntry.Dock = DockStyle.Fill;
            _btnManualVisitorEntry.Margin = new Padding(14);
            _btnManualVisitorEntry.Cursor = Cursors.Hand;
            _btnManualVisitorEntry.Click += (_, __) => StartManualVisitorEntry();

            _btnDocumentClaim.Text = "Claim Documents (On Behalf of an Individual)";
            _btnDocumentClaim.BackColor = raisedSurface;
            _btnDocumentClaim.ForeColor = primaryText;
            _btnDocumentClaim.FlatStyle = FlatStyle.Flat;
            _btnDocumentClaim.FlatAppearance.BorderSize = 0;
            _btnDocumentClaim.Dock = DockStyle.Fill;
            _btnDocumentClaim.Margin = new Padding(14);
            _btnDocumentClaim.Cursor = Cursors.Hand;
            _btnDocumentClaim.Click += (_, __) => StartDocumentClaimOnBehalfEntry();

            _btnLogbookEntries.Text = "Logbook Entries";
            _btnLogbookEntries.BackColor = raisedSurface;
            _btnLogbookEntries.ForeColor = primaryText;
            _btnLogbookEntries.FlatStyle = FlatStyle.Flat;
            _btnLogbookEntries.FlatAppearance.BorderSize = 1;
            _btnLogbookEntries.FlatAppearance.BorderColor = subtleBorder;
            _btnLogbookEntries.Dock = DockStyle.Fill;
            _btnLogbookEntries.Margin = new Padding(14);
            _btnLogbookEntries.Cursor = Cursors.Hand;
            _btnLogbookEntries.Click += (_, __) => TryOpenLogbookEntriesSection();

            tableActions.Controls.Add(_btnNewEntry, 0, 0);
            tableActions.Controls.Add(_btnManualVisitorEntry, 0, 1);
            tableActions.Controls.Add(_btnDocumentClaim, 0, 2);
            tableActions.Controls.Add(_btnLogbookEntries, 0, 3);

            _panelActions.Controls.Add(tableActions);
            _panelActions.Paint += (_, e) =>
                DrawGlowBorder(e.Graphics, _panelActions.ClientRectangle, cyan);

            // ── Restricted: full logbook grid + search (hidden until authorized) ─
            _panelSensitive.Dock = DockStyle.Fill;
            _panelSensitive.Visible = false;
            _panelSensitive.BackColor = page;
            _panelSensitive.Padding = new Padding(18, 14, 18, 18);

            _lblRestrictedBanner = new Label
            {
                Text = "Restricted — visitor personal data. Admin or authorized logbook personnel only.",
                Dock = DockStyle.Top,
                Height = 36,
                Font = new Font("Segoe UI", 9F, FontStyle.Bold),
                ForeColor = cyan,
                BackColor = surface,
                TextAlign = ContentAlignment.MiddleLeft,
                Padding = new Padding(12, 0, 12, 0)
            };

            _lblSectionLabel = new Label
            {
                Text = "TRANSACTION LOG ENTRIES",
                Dock = DockStyle.Fill,
                Font = new Font("Segoe UI", 9F, FontStyle.Bold),
                ForeColor = cyan,
                TextAlign = ContentAlignment.BottomLeft,
                Padding = new Padding(4, 0, 0, 4),
                Margin = Padding.Empty,
                AutoSize = false
            };

            // Filter bar with a subtle light surface, then section label + grid.
            Color accentBlue = electricBlue;

            var panelAttendanceFilterOuter = new Panel
            {
                Dock = DockStyle.Fill,
                BackColor = surface,
                Margin = Padding.Empty,
                Padding = Padding.Empty,
                BorderStyle = BorderStyle.FixedSingle
            };

            var pnlLogbookFilterAccent = new Panel
            {
                Dock = DockStyle.Left,
                Width = 4,
                BackColor = cyan
            };

            var pnlAttendanceFilterInner = new Panel { Dock = DockStyle.Fill };

            void ApplyPrimaryBlueButton(Button b)
            {
                b.FlatAppearance.MouseOverBackColor = Color.FromArgb(24, 81, 199);
                b.FlatAppearance.MouseDownBackColor = Color.FromArgb(20, 67, 169);
            }

            var lblDateLabel = new Label
            {
                Text = "FILTER DATE",
                AutoSize = false,
                Dock = DockStyle.Fill,
                Font = new Font("Segoe UI", 8.5F, FontStyle.Bold),
                ForeColor = secondaryText,
                TextAlign = ContentAlignment.MiddleLeft,
                Margin = Padding.Empty,
                Padding = new Padding(0, 0, 10, 0)
            };

            _datePicker.Format = DateTimePickerFormat.Short;
            _datePicker.Font = new Font("Segoe UI", 10F, FontStyle.Bold);
            _datePicker.Margin = new Padding(0, 0, 12, 0);
            _datePicker.TabIndex = 1;
            _datePicker.Size = new Size(186, 34);

            _btnLoad.Text = "\u27F3   Load Records";
            _btnLoad.Font = new Font("Segoe UI", 10F, FontStyle.Bold);
            _btnLoad.BackColor = accentBlue;
            _btnLoad.ForeColor = Color.White;
            _btnLoad.FlatStyle = FlatStyle.Flat;
            _btnLoad.FlatAppearance.BorderSize = 0;
            _btnLoad.Cursor = Cursors.Hand;
            _btnLoad.TabIndex = 2;
            _btnLoad.Size = new Size(168, 36);
            ApplyPrimaryBlueButton(_btnLoad);
            _btnLoad.Click += (_, __) => LoadPrivilegedLogs();

            var flowDateLoad = new FlowLayoutPanel
            {
                Dock = DockStyle.Fill,
                FlowDirection = FlowDirection.LeftToRight,
                WrapContents = false,
                AutoSize = false,
                Padding = new Padding(0, 6, 0, 10),
                Margin = Padding.Empty,
                BackColor = Color.Transparent
            };
            flowDateLoad.Controls.Add(_datePicker);
            flowDateLoad.Controls.Add(_btnLoad);

            var lblSearch = new Label
            {
                Text = "SEARCH",
                AutoSize = false,
                Dock = DockStyle.Fill,
                Font = new Font("Segoe UI", 8.5F, FontStyle.Bold),
                ForeColor = secondaryText,
                TextAlign = ContentAlignment.MiddleLeft,
                Margin = Padding.Empty,
                Padding = new Padding(0, 0, 10, 0)
            };

            _txtSearchLog.Font = new Font("Segoe UI", 10F, FontStyle.Bold);
            _txtSearchLog.BorderStyle = BorderStyle.FixedSingle;
            _txtSearchLog.Margin = Padding.Empty;
            _txtSearchLog.Height = 36;
            _txtSearchLog.MinimumSize = new Size(120, 36);
            _txtSearchLog.TabIndex = 3;

            var tlpFilter = new TableLayoutPanel
            {
                Dock = DockStyle.Fill,
                ColumnCount = 2,
                RowCount = 2,
                Margin = Padding.Empty,
                Padding = new Padding(20, 14, 20, 14),
                BackColor = Color.Transparent,
                GrowStyle = TableLayoutPanelGrowStyle.FixedSize
            };
            tlpFilter.ColumnStyles.Add(new ColumnStyle(SizeType.Absolute, 108F));
            tlpFilter.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 100F));
            tlpFilter.RowStyles.Add(new RowStyle(SizeType.Absolute, 52F));
            tlpFilter.RowStyles.Add(new RowStyle(SizeType.Absolute, 40F));

            tlpFilter.Controls.Add(lblDateLabel, 0, 0);
            tlpFilter.Controls.Add(flowDateLoad, 1, 0);
            tlpFilter.Controls.Add(lblSearch, 0, 1);
            tlpFilter.Controls.Add(_txtSearchLog, 1, 1);

            _txtSearchLog.Dock = DockStyle.Fill;

            _searchDebounce.Interval = 350;
            _searchDebounce.Tick += (_, __) =>
            {
                _searchDebounce.Stop();
                LoadPrivilegedLogs();
            };

            _txtSearchLog.TextChanged += (_, __) =>
            {
                if (!_logbookSensitiveVisible) return;
                _currentPage = 1;
                _searchDebounce.Stop();
                _searchDebounce.Start();
            };

            _datePicker.ValueChanged += (_, __) =>
            {
                if (_logbookSensitiveVisible)
                {
                    _currentPage = 1;
                    LoadPrivilegedLogs();
                }
            };

            _grid.Dock = DockStyle.Fill;
            _grid.ReadOnly = true;
            _grid.AllowUserToAddRows = false;
            // Many columns exist; allow horizontal scrolling instead of squeezing columns to unreadable widths.
            _grid.AutoSizeColumnsMode = DataGridViewAutoSizeColumnsMode.None;
            // Vertical scroll for many rows; horizontal when columns exceed width.
            _grid.ScrollBars = ScrollBars.Both;
            _grid.BackgroundColor = surface;
            _grid.BorderStyle = BorderStyle.None;
            _grid.GridColor = subtleBorder;
            _grid.Font = new Font("Segoe UI", 9.5F, FontStyle.Bold);
            _grid.DefaultCellStyle.ForeColor = primaryText;
            _grid.DefaultCellStyle.BackColor = surface;
            _grid.DefaultCellStyle.Font = new Font("Segoe UI", 9.5F, FontStyle.Bold);
            _grid.DefaultCellStyle.SelectionBackColor = Color.FromArgb(219, 234, 254);
            _grid.DefaultCellStyle.SelectionForeColor = Color.FromArgb(20, 48, 91);
            _grid.DefaultCellStyle.Padding = new Padding(12, 4, 8, 4);
            _grid.DefaultCellStyle.WrapMode = DataGridViewTriState.True;
            _grid.AutoSizeRowsMode = DataGridViewAutoSizeRowsMode.AllCellsExceptHeaders;
            _grid.AlternatingRowsDefaultCellStyle.BackColor = raisedSurface;
            _grid.AlternatingRowsDefaultCellStyle.ForeColor = primaryText;
            _grid.AlternatingRowsDefaultCellStyle.Font = new Font("Segoe UI", 9.5F, FontStyle.Bold);
            _grid.AlternatingRowsDefaultCellStyle.SelectionBackColor = Color.FromArgb(219, 234, 254);
            _grid.AlternatingRowsDefaultCellStyle.SelectionForeColor = Color.FromArgb(20, 48, 91);
            _grid.RowTemplate.Height = 46;
            _grid.EnableHeadersVisualStyles = false;
            _grid.ColumnHeadersHeightSizeMode = DataGridViewColumnHeadersHeightSizeMode.EnableResizing;
            _grid.ColumnHeadersHeight = 52;
            _grid.ColumnHeadersBorderStyle = DataGridViewHeaderBorderStyle.Single;
            _grid.ColumnHeadersDefaultCellStyle.BackColor = Color.FromArgb(235, 243, 253);
            _grid.ColumnHeadersDefaultCellStyle.ForeColor = Color.FromArgb(29, 80, 157);
            _grid.ColumnHeadersDefaultCellStyle.Font = new Font("Segoe UI", 9F, FontStyle.Bold);
            _grid.ColumnHeadersDefaultCellStyle.SelectionBackColor = Color.FromArgb(235, 243, 253);
            _grid.ColumnHeadersDefaultCellStyle.Padding = new Padding(12, 0, 8, 0);
            _grid.ColumnHeadersDefaultCellStyle.WrapMode = DataGridViewTriState.True;
            _grid.SelectionMode = DataGridViewSelectionMode.FullRowSelect;
            _grid.CellBorderStyle = DataGridViewCellBorderStyle.SingleHorizontal;
            _grid.RowHeadersVisible = false;
            _grid.AllowUserToResizeColumns = true;
            _grid.AllowUserToOrderColumns = true;
            _grid.CellClick += Grid_CellClick;

            pnlAttendanceFilterInner.Controls.Add(tlpFilter);
            panelAttendanceFilterOuter.Controls.Add(pnlLogbookFilterAccent);
            panelAttendanceFilterOuter.Controls.Add(pnlAttendanceFilterInner);

            var paginationPanel = new Panel
            {
                Dock = DockStyle.Fill,
                BackColor = surface,
                Padding = new Padding(12, 6, 12, 6),
                Margin = Padding.Empty
            };
            var paginationControls = new FlowLayoutPanel
            {
                Dock = DockStyle.Fill,
                FlowDirection = FlowDirection.LeftToRight,
                WrapContents = false,
                AutoSize = false,
                Padding = Padding.Empty,
                Margin = Padding.Empty,
                BackColor = surface
            };

            StylePaginationButton(_btnPreviousPage, "Previous", 92, primaryText, subtleBorder);
            StylePaginationButton(_btnGoToPage, "Go", 58, electricBlue, electricBlue, Color.White);
            StylePaginationButton(_btnNextPage, "Next", 76, primaryText, subtleBorder);
            _btnPreviousPage.Click += (_, __) =>
            {
                if (_currentPage <= 1) return;
                _currentPage--;
                LoadPrivilegedLogs();
            };
            _btnGoToPage.Click += (_, __) =>
            {
                _currentPage = decimal.ToInt32(_pageSelector.Value);
                LoadPrivilegedLogs();
            };
            _btnNextPage.Click += (_, __) =>
            {
                if (_currentPage >= _totalPages) return;
                _currentPage++;
                LoadPrivilegedLogs();
            };

            var lblPage = new Label
            {
                Text = "Page",
                AutoSize = true,
                Font = new Font("Segoe UI", 9F, FontStyle.Bold),
                ForeColor = primaryText,
                TextAlign = ContentAlignment.MiddleCenter,
                Margin = new Padding(14, 8, 4, 0)
            };
            _pageSelector.Minimum = 1;
            _pageSelector.Maximum = 1;
            _pageSelector.Value = 1;
            _pageSelector.Width = 58;
            _pageSelector.Height = 32;
            _pageSelector.Font = new Font("Segoe UI", 9F, FontStyle.Bold);
            _pageSelector.Margin = new Padding(0, 3, 4, 0);

            _lblPageCount.Text = "of 1";
            _lblPageCount.AutoSize = true;
            _lblPageCount.Font = new Font("Segoe UI", 9F, FontStyle.Bold);
            _lblPageCount.ForeColor = primaryText;
            _lblPageCount.TextAlign = ContentAlignment.MiddleCenter;
            _lblPageCount.Margin = new Padding(0, 8, 4, 0);

            _lblPageSize.Text = "Items per page";
            _lblPageSize.AutoSize = true;
            _lblPageSize.Font = new Font("Segoe UI", 9F, FontStyle.Bold);
            _lblPageSize.ForeColor = primaryText;
            _lblPageSize.TextAlign = ContentAlignment.MiddleCenter;
            _lblPageSize.Margin = new Padding(18, 8, 6, 0);
            _pageSizeSelector.DropDownStyle = ComboBoxStyle.DropDownList;
            _pageSizeSelector.Font = new Font("Segoe UI", 9F, FontStyle.Bold);
            _pageSizeSelector.Width = 78;
            _pageSizeSelector.Margin = new Padding(0, 3, 12, 0);
            _pageSizeSelector.Items.AddRange(new object[] { 25, 50, 100 });
            _pageSizeSelector.SelectedItem = _pageSize;
            _pageSizeSelector.SelectedIndexChanged += (_, __) =>
            {
                if (_pageSizeSelector.SelectedItem == null) return;
                _pageSize = Convert.ToInt32(_pageSizeSelector.SelectedItem);
                _currentPage = 1;
                LoadPrivilegedLogs();
            };

            paginationControls.Controls.Add(_btnPreviousPage);
            paginationControls.Controls.Add(lblPage);
            paginationControls.Controls.Add(_pageSelector);
            paginationControls.Controls.Add(_lblPageCount);
            paginationControls.Controls.Add(_btnGoToPage);
            paginationControls.Controls.Add(_btnNextPage);
            paginationControls.Controls.Add(_lblPageSize);
            paginationControls.Controls.Add(_pageSizeSelector);
            paginationPanel.Controls.Add(paginationControls);

            // Same vertical rhythm as AttendanceLogForm — filter strip, section title, grid.
            var layoutVisitorEntries = new TableLayoutPanel
            {
                Dock = DockStyle.Fill,
                ColumnCount = 1,
                RowCount = 4,
                BackColor = page,
                Padding = Padding.Empty,
                Margin = Padding.Empty,
                GrowStyle = TableLayoutPanelGrowStyle.FixedSize
            };
            layoutVisitorEntries.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 100F));
            layoutVisitorEntries.RowStyles.Add(new RowStyle(SizeType.Absolute, 126F));
            layoutVisitorEntries.RowStyles.Add(new RowStyle(SizeType.Absolute, 34F));
            layoutVisitorEntries.RowStyles.Add(new RowStyle(SizeType.Percent, 100F));
            layoutVisitorEntries.RowStyles.Add(new RowStyle(SizeType.Absolute, 54F));
            layoutVisitorEntries.Controls.Add(panelAttendanceFilterOuter, 0, 0);
            layoutVisitorEntries.Controls.Add(_lblSectionLabel, 0, 1);
            layoutVisitorEntries.Controls.Add(_grid, 0, 2);
            layoutVisitorEntries.Controls.Add(paginationPanel, 0, 3);

            panelAttendanceFilterOuter.Dock = DockStyle.Fill;
            _lblSectionLabel.Dock = DockStyle.Fill;
            _grid.Dock = DockStyle.Fill;

            _panelSensitive.Controls.Add(layoutVisitorEntries);
            _panelSensitive.Controls.Add(_lblRestrictedBanner);

            _layoutRoot = new TableLayoutPanel
            {
                Dock = DockStyle.Fill,
                ColumnCount = 1,
                RowCount = 4,
                BackColor = page,
                Padding = Padding.Empty,
                Margin = Padding.Empty
            };
            _layoutRoot.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 100F));
            _layoutRoot.RowStyles.Add(new RowStyle(SizeType.Absolute, headerBarHeight));
            _layoutRoot.RowStyles.Add(new RowStyle(SizeType.Absolute, 3F));
            _layoutRoot.RowStyles.Add(new RowStyle(SizeType.Percent, 100F));
            _layoutRoot.RowStyles.Add(new RowStyle(SizeType.Absolute, 0F));

            _layoutRoot.Controls.Add(_panelHeader, 0, 0);
            _layoutRoot.Controls.Add(pnlHeaderAccent, 0, 1);
            _layoutRoot.Controls.Add(_panelActions, 0, 2);
            _layoutRoot.Controls.Add(_panelSensitive, 0, 3);

            Controls.Add(_layoutRoot);
            DesktopTheme.Apply(this);
            _layoutRoot.BackColor = page;
            _panelHeader.BackColor = headerBlue;
            _panelHeader.Paint += (_, e) =>
                DrawGlowBorder(e.Graphics, _panelHeader.ClientRectangle, cyan);
            pnlHeaderAccentBar.BackColor = electricBlue;
            pnlHeaderAccent.BackColor = cyan;
            lblTitle.ForeColor = Color.White;
            lblTitle.Font = new Font("Segoe UI", 19F, FontStyle.Bold);
            lblTitle.TextAlign = ContentAlignment.MiddleCenter;
            _panelActions.BackColor = page;
            tableActions.BackColor = page;
            tableActions.Padding = new Padding(4);
            _panelSensitive.BackColor = page;
            _lblRestrictedBanner.BackColor = surface;
            _lblRestrictedBanner.ForeColor = Color.FromArgb(29, 80, 157);
            _lblSectionLabel.BackColor = page;
            _lblSectionLabel.ForeColor = Color.FromArgb(29, 80, 157);
            panelAttendanceFilterOuter.BackColor = surface;
            panelAttendanceFilterOuter.Paint += (_, e) =>
                DrawGlowBorder(e.Graphics, panelAttendanceFilterOuter.ClientRectangle, electricBlue);
            pnlLogbookFilterAccent.BackColor = cyan;
            pnlAttendanceFilterInner.BackColor = surface;
            tlpFilter.BackColor = surface;
            lblDateLabel.ForeColor = secondaryText;
            lblSearch.ForeColor = secondaryText;
            _datePicker.CalendarTitleBackColor = electricBlue;
            _datePicker.CalendarTitleForeColor = Color.White;
            _datePicker.CalendarMonthBackground = surface;
            _datePicker.CalendarForeColor = primaryText;
            _datePicker.BackColor = surface;
            _datePicker.ForeColor = primaryText;
            _btnLoad.BackColor = electricBlue;
            _btnLoad.ForeColor = Color.White;
            _txtSearchLog.BackColor = raisedSurface;
            _txtSearchLog.ForeColor = primaryText;
            _txtSearchLog.BorderStyle = BorderStyle.FixedSingle;
            _grid.BackgroundColor = surface;
            _grid.GridColor = subtleBorder;
            _grid.DefaultCellStyle.BackColor = surface;
            _grid.DefaultCellStyle.ForeColor = primaryText;
            _grid.DefaultCellStyle.SelectionBackColor = Color.FromArgb(219, 234, 254);
            _grid.DefaultCellStyle.SelectionForeColor = Color.FromArgb(20, 48, 91);
            _grid.AlternatingRowsDefaultCellStyle.BackColor = raisedSurface;
            _grid.AlternatingRowsDefaultCellStyle.ForeColor = primaryText;
            _grid.AlternatingRowsDefaultCellStyle.SelectionBackColor = Color.FromArgb(219, 234, 254);
            _grid.AlternatingRowsDefaultCellStyle.SelectionForeColor = Color.FromArgb(20, 48, 91);
            _grid.ColumnHeadersDefaultCellStyle.BackColor = Color.FromArgb(235, 243, 253);
            _grid.ColumnHeadersDefaultCellStyle.ForeColor = Color.FromArgb(29, 80, 157);
            _grid.ColumnHeadersDefaultCellStyle.SelectionBackColor = Color.FromArgb(235, 243, 253);
            _grid.ColumnHeadersDefaultCellStyle.Font = new Font("Segoe UI", 9F, FontStyle.Bold);
            StyleLogbookActionButton(_btnNewEntry);
            StyleLogbookActionButton(_btnManualVisitorEntry);
            StyleLogbookActionButton(_btnDocumentClaim);
            StyleLogbookActionButton(_btnLogbookEntries);
            _btnNewEntry.BackColor = Color.FromArgb(6, 78, 59);
            _btnNewEntry.ForeColor = Color.White;
            _btnNewEntry.FlatAppearance.MouseOverBackColor = Color.FromArgb(4, 60, 45);
            _btnNewEntry.FlatAppearance.MouseDownBackColor = Color.FromArgb(2, 44, 34);
            _btnDocumentClaim.BackColor = Color.FromArgb(31, 41, 55);
            _btnDocumentClaim.ForeColor = Color.White;
            _btnDocumentClaim.FlatAppearance.MouseOverBackColor = Color.FromArgb(17, 24, 39);
            _btnDocumentClaim.FlatAppearance.MouseDownBackColor = Color.FromArgb(10, 15, 25);
            _btnLogbookEntries.BackColor = Color.FromArgb(127, 29, 29);
            _btnLogbookEntries.ForeColor = Color.White;
            _btnLogbookEntries.FlatAppearance.MouseOverBackColor = Color.FromArgb(104, 22, 22);
            _btnLogbookEntries.FlatAppearance.MouseDownBackColor = Color.FromArgb(69, 10, 10);
            StyleDashboardButton(_btnLoad, true, electricBlue, primaryText, subtleBorder);

            KeyPreview = true;
            KeyDown += (_, e) =>
            {
                if (e.KeyCode != Keys.Escape) return;
                Close();
            };

            Load += (_, __) =>
            {
                _datePicker.Value = DateTime.Today;
            };
            FormClosed += (_, __) =>
            {
                _searchDebounce.Stop();
                _searchDebounce.Dispose();
            };
        }

        private static void StyleDashboardButton(
            Button button,
            bool primary,
            Color electricBlue,
            Color primaryText,
            Color border)
        {
            button.Font = new Font("Segoe UI", 10F, FontStyle.Bold);
            button.Padding = new Padding(14, 5, 14, 5);
            button.TextAlign = ContentAlignment.MiddleCenter;
            button.FlatStyle = FlatStyle.Flat;
            button.UseVisualStyleBackColor = false;
            button.Cursor = Cursors.Hand;
            button.FlatAppearance.BorderSize = 1;
            button.FlatAppearance.BorderColor = primary ? electricBlue : border;
            button.FlatAppearance.MouseOverBackColor = Color.FromArgb(232, 241, 253);
            button.FlatAppearance.MouseDownBackColor = Color.FromArgb(215, 229, 250);

            if (primary)
            {
                button.BackColor = electricBlue;
                button.ForeColor = Color.White;
            }
            else
            {
                button.BackColor = Color.White;
                button.ForeColor = primaryText;
            }
        }

        private static void StylePaginationButton(
            Button button,
            string text,
            int width,
            Color backColor,
            Color borderColor,
            Color foreColor = default(Color))
        {
            button.Text = text;
            button.Width = width;
            button.Height = 34;
            button.Font = new Font("Segoe UI", 9F, FontStyle.Bold);
            button.BackColor = backColor;
            button.ForeColor = foreColor == default(Color) ? Color.FromArgb(30, 41, 59) : foreColor;
            button.FlatStyle = FlatStyle.Flat;
            button.UseVisualStyleBackColor = false;
            button.FlatAppearance.BorderSize = 1;
            button.FlatAppearance.BorderColor = borderColor;
            button.FlatAppearance.MouseOverBackColor = Color.FromArgb(232, 241, 253);
            button.FlatAppearance.MouseDownBackColor = Color.FromArgb(215, 229, 250);
            button.Cursor = Cursors.Hand;
            button.Margin = new Padding(3, 3, 3, 0);
        }

        private static void StyleLogbookActionButton(Button button)
        {
            Color darkBlue = Color.FromArgb(11, 19, 43);
            button.Padding = new Padding(12, 0, 12, 0);
            button.TextAlign = ContentAlignment.MiddleCenter;
            button.Font = new Font("Segoe UI", 13F, FontStyle.Bold);
            button.BackColor = darkBlue;
            button.ForeColor = Color.FromArgb(255, 255, 255);
            button.FlatAppearance.BorderSize = 1;
            button.FlatAppearance.BorderColor = Color.FromArgb(45, 63, 101);
            button.FlatAppearance.MouseOverBackColor = Color.FromArgb(20, 32, 63);
            button.FlatAppearance.MouseDownBackColor = Color.FromArgb(7, 13, 30);
        }

        private static void DrawGlowBorder(Graphics graphics, Rectangle bounds, Color accent)
        {
            if (bounds.Width < 8 || bounds.Height < 8)
                return;

            graphics.SmoothingMode = SmoothingMode.AntiAlias;
            Rectangle border = Rectangle.Inflate(bounds, -2, -2);
            using (var shadow = new Pen(Color.FromArgb(18, 30, 55, 90), 2F))
                graphics.DrawRectangle(shadow, border);
            using (var edge = new Pen(Color.FromArgb(205, 217, 234), 1F))
                graphics.DrawRectangle(edge, border);
            using (var accentEdge = new Pen(Color.FromArgb(90, accent), 1F))
                graphics.DrawLine(accentEdge, border.Left + 1, border.Top, border.Right - 1, border.Top);
        }

        private void TryOpenLogbookEntriesSection()
        {
            if (!_logbookSensitiveVisible)
            {
                if (!_promptLogbookPrivileged())
                    return;

                _logbookSensitiveVisible = true;

                SuspendLayout();
                try
                {
                    // Logbook-only view: drop action buttons and remove banner rows so layout is only toolbar + grid.
                    _panelActions.Visible = false;
                    _layoutRoot.RowStyles[2] = new RowStyle(SizeType.Absolute, 0f);
                    _layoutRoot.RowStyles[3] = new RowStyle(SizeType.Percent, 100F);

                    if (_lblRestrictedBanner.Parent != null)
                        _lblRestrictedBanner.Parent.Controls.Remove(_lblRestrictedBanner);

                    FormBorderStyle = FormBorderStyle.Sizable;
                    MaximizeBox = true;

                    _panelSensitive.Visible = true;
                    _datePicker.Value = DateTime.Today;

                    ResponsiveLayout.ClampToWorkingArea(this);
                    CenterToParentOrScreen();

                    _panelSensitive.BringToFront();
                }
                finally
                {
                    ResumeLayout(true);
                }
            }

            LoadPrivilegedLogs();
        }

        private void CenterToParentOrScreen()
        {
            if (Owner != null && Owner.WindowState != FormWindowState.Minimized)
            {
                var o = Owner.Bounds;
                Left = o.Left + (o.Width - Width) / 2;
                Top = o.Top + (o.Height - Height) / 2;
            }
            else
                CenterToScreen();
        }

        private void LoadPrivilegedLogs()
        {
            if (!_logbookSensitiveVisible)
                return;

            string term = _txtSearchLog.Text?.Trim();
            string searchFilter = string.IsNullOrEmpty(term) ? null : term;
            var logs = _db.GetVisitorLogEntries(
                _datePicker.Value, searchFilter, _currentPage, _pageSize, out int totalCount);
            _totalPages = Math.Max(1, (int)Math.Ceiling(totalCount / (double)_pageSize));
            if (_currentPage > _totalPages)
            {
                _currentPage = _totalPages;
                logs = _db.GetVisitorLogEntries(
                    _datePicker.Value, searchFilter, _currentPage, _pageSize, out totalCount);
                _totalPages = Math.Max(1, (int)Math.Ceiling(totalCount / (double)_pageSize));
            }

            _grid.DataSource = logs;
            _pageSelector.Maximum = _totalPages;
            _pageSelector.Value = _currentPage;
            _lblPageCount.Text = $"of {_totalPages}";
            _btnPreviousPage.Enabled = _currentPage > 1;
            _btnNextPage.Enabled = _currentPage < _totalPages;
            _btnGoToPage.Enabled = _totalPages > 1;
            if (_grid.Columns.Count == 0) return;

            _grid.Columns["LogbookId"].Visible = false;
            _grid.Columns["FullName"].HeaderText = "Full Name";
            _grid.Columns["Address"].HeaderText = "Address";
            _grid.Columns["ContactNumber"].HeaderText = "Contact Number";
            if (_grid.Columns.Contains("TransactionType"))
                _grid.Columns["TransactionType"].Visible = false;
            if (_grid.Columns.Contains("TransactionTypeLabel"))
                _grid.Columns["TransactionTypeLabel"].Visible = false;
            if (_grid.Columns.Contains("PersonClassification"))
                _grid.Columns["PersonClassification"].HeaderText = "Person Category";
            if (_grid.Columns.Contains("RepresentedPersonClassification"))
                _grid.Columns["RepresentedPersonClassification"].Visible = false;
            _grid.Columns["PurposeOfTransaction"].HeaderText = "Purpose";
            _grid.Columns["OfficeOrPersonToVisit"].Visible = false;
            _grid.Columns["IsAuthorizedRepresentative"].HeaderText = "Auth Rep";
            _grid.Columns["AuthorizationLetterVerified"].HeaderText = "Auth Letter";
            _grid.Columns["ValidIdVerified"].HeaderText = "Valid ID";
            _grid.Columns["RepresentedPersonName"].HeaderText = "Representing";
            if (_grid.Columns.Contains("FingerprintId"))
                _grid.Columns["FingerprintId"].Visible = false;
            if (_grid.Columns.Contains("FingerprintIdDisplay"))
                _grid.Columns["FingerprintIdDisplay"].Visible = false;
            if (_grid.Columns.Contains("HasFingerprintOnFile"))
                _grid.Columns["HasFingerprintOnFile"].Visible = false;
            if (_grid.Columns.Contains("FingerprintEnrollmentLabel"))
                _grid.Columns["FingerprintEnrollmentLabel"].HeaderText = "Fingerprint";
            _grid.Columns["TimeIn"].HeaderText = "Time In";

            ApplyLogbookGridColumnSizing();
            ApplyLogbookColumnDisplayOrder();
        }

        /// <summary>
        /// <see cref="VisitorLogEntry"/> declares <c>TimeIn</c> before fingerprint display fields, so auto-generated
        /// columns would show time before enrollment. Force fingerprint status then time as the last column.
        /// </summary>
        private void ApplyLogbookColumnDisplayOrder()
        {
            var ordered = new List<string>
            {
                "FullName",
                "Address",
                "ContactNumber",
                "PersonClassification",
                "PurposeOfTransaction",
                "IsAuthorizedRepresentative",
                "AuthorizationLetterVerified",
                "ValidIdVerified",
                "RepresentedPersonName",
                "FingerprintEnrollmentLabel",
                "TimeIn"
            };

            foreach (DataGridViewColumn c in _grid.Columns)
            {
                if (!ordered.Contains(c.Name))
                    ordered.Add(c.Name);
            }

            for (int i = 0; i < ordered.Count; i++)
            {
                if (_grid.Columns.Contains(ordered[i]))
                    _grid.Columns[ordered[i]].DisplayIndex = i;
            }
        }

        /// <summary>
        /// Sets readable column widths and allows horizontal scrolling when needed.
        /// </summary>
        private void ApplyLogbookGridColumnSizing()
        {
            void setCol(string name, int width, bool frozen = false, DataGridViewTriState wrap = DataGridViewTriState.True)
            {
                if (!_grid.Columns.Contains(name)) return;
                var c = _grid.Columns[name];
                c.AutoSizeMode = DataGridViewAutoSizeColumnMode.None;
                c.Width = width;
                c.MinimumWidth = width;
                c.Frozen = frozen;
                c.DefaultCellStyle.WrapMode = wrap;
            }

            // Freeze the key identity columns so they stay visible while scrolling horizontally.
            setCol("FullName", 210, frozen: true);
            setCol("Address", 240);
            setCol("ContactNumber", 195, wrap: DataGridViewTriState.False);
            if (_grid.Columns.Contains("PersonClassification"))
                setCol("PersonClassification", 140, wrap: DataGridViewTriState.False);
            setCol("PurposeOfTransaction", 320);
            setCol("IsAuthorizedRepresentative", 110, wrap: DataGridViewTriState.False);
            setCol("AuthorizationLetterVerified", 115, wrap: DataGridViewTriState.False);
            setCol("ValidIdVerified", 100, wrap: DataGridViewTriState.False);
            setCol("RepresentedPersonName", 200);
            if (_grid.Columns.Contains("FingerprintEnrollmentLabel"))
                setCol("FingerprintEnrollmentLabel", 140, wrap: DataGridViewTriState.False);
            if (_grid.Columns.Contains("TimeIn"))
            {
                _grid.Columns["TimeIn"].DefaultCellStyle.Format = "yyyy-MM-dd hh:mm:ss tt";
                _grid.Columns["TimeIn"].MinimumWidth = 235;
                _grid.Columns["TimeIn"].AutoSizeMode = DataGridViewAutoSizeColumnMode.AllCells;
                _grid.Columns["TimeIn"].DefaultCellStyle.WrapMode = DataGridViewTriState.False;
            }
            if (_grid.Columns.Contains("ContactNumber"))
            {
                _grid.Columns["ContactNumber"].MinimumWidth = 195;
                _grid.Columns["ContactNumber"].AutoSizeMode = DataGridViewAutoSizeColumnMode.AllCells;
            }

            foreach (DataGridViewColumn col in _grid.Columns)
            {
                if (!col.Visible) continue;
                col.SortMode = DataGridViewColumnSortMode.Automatic;
                if (col is DataGridViewCheckBoxColumn)
                {
                    col.DefaultCellStyle.WrapMode = DataGridViewTriState.False;
                    col.DefaultCellStyle.Alignment = DataGridViewContentAlignment.MiddleCenter;
                }
            }
        }

        private void StartNewVisitorEntry()
        {
            using var entryForm = new VisitorLogbookEntryForm(_db);
            if (entryForm.ShowDialog(this) != DialogResult.OK)
                return;

            byte[] template = null;
            VisitorProfile linked = entryForm.LinkedReturningIdentity;

            if (linked != null)
            {
                int linkedFingerprintId = linked.FingerprintId;
                if (entryForm.RegisterFingerprint)
                {
                    if (!ShowVisitorFingerprintScanOverlay(out template) || template == null || template.Length == 0)
                        return;

                }

                int? savedLinkedFingerprintId = _db.InsertNewVisitorLog(
                    linkedFingerprintId > 0 ? (int?)linkedFingerprintId : null,
                    entryForm.VisitorFullName,
                    entryForm.VisitorAddress,
                    entryForm.VisitorContactNumber,
                    entryForm.PurposeOfVisit,
                    string.Empty,
                    false,
                    false,
                    false,
                    string.Empty,
                    template,
                    DatabaseHelper.LogbookTransactionTypeVisitor,
                    linked.PersonId,
                    entryForm.SelectedPersonClassification);

                ReloadFingerprintTemplates(savedLinkedFingerprintId);
                LoadPrivilegedLogs();
                MessageBox.Show(
                    "Returning visitor entry saved. Identity information was reused — only this visit was added.",
                    "Saved",
                    MessageBoxButtons.OK,
                    MessageBoxIcon.Information);
                return;
            }

            VisitorProfile existing = _db.FindExistingPersonProfile(
                entryForm.VisitorFullName,
                entryForm.VisitorContactNumber);
            if (existing != null)
            {
                var merge = MessageBox.Show(
                    "A person with this name and contact is already on file.\n\n" +
                    "Save this transaction under the existing account to avoid a duplicate record?",
                    "Matching person found",
                    MessageBoxButtons.YesNo,
                    MessageBoxIcon.Question);
                if (merge == DialogResult.Yes)
                {
                    template = null;
                    int matchedFingerprintId = existing.FingerprintId;
                    if (!existing.HasFingerprintOnFile)
                    {
                        if (!entryForm.RegisterFingerprint)
                        {
                            MessageBox.Show(
                                "This person has no fingerprint on file. Accept the consent on the form to register one.",
                                "Fingerprint required",
                                MessageBoxButtons.OK,
                                MessageBoxIcon.Warning);
                            return;
                        }

                        if (!ShowVisitorFingerprintScanOverlay(out template) || template == null || template.Length == 0)
                            return;

                    }

                    int? savedExistingFingerprintId = _db.InsertNewVisitorLog(
                        matchedFingerprintId > 0 ? (int?)matchedFingerprintId : null,
                        existing.FullName,
                        existing.Address,
                        existing.ContactNumber,
                        entryForm.PurposeOfVisit,
                        string.Empty,
                        false,
                        false,
                        false,
                        string.Empty,
                        template,
                        DatabaseHelper.LogbookTransactionTypeVisitor,
                        existing.PersonId,
                        existing.PersonClassification);

                    ReloadFingerprintTemplates(savedExistingFingerprintId);
                    LoadPrivilegedLogs();
                    MessageBox.Show(
                        "Transaction saved under the existing person account.",
                        "Saved",
                        MessageBoxButtons.OK,
                        MessageBoxIcon.Information);
                    return;
                }
            }

            if (!PersonRegistrationGuard.CanCreateNewPerson(_db, entryForm.VisitorFullName, entryForm.VisitorAddress))
                return;

            if (!ShowVisitorFingerprintScanOverlay(out template) || template == null || template.Length == 0)
                return;

            int? savedFingerprintId = _db.InsertNewVisitorLog(
                null,
                entryForm.VisitorFullName,
                entryForm.VisitorAddress,
                entryForm.VisitorContactNumber,
                entryForm.PurposeOfVisit,
                string.Empty,
                false,
                false,
                false,
                string.Empty,
                template,
                DatabaseHelper.LogbookTransactionTypeVisitor,
                personClassification: entryForm.SelectedPersonClassification);

            ReloadFingerprintTemplates(savedFingerprintId);
            LoadPrivilegedLogs();
            MessageBox.Show("Visitor logbook entry saved successfully.", "Saved", MessageBoxButtons.OK, MessageBoxIcon.Information);
        }

        private void StartManualVisitorEntry()
        {
            VisitorProfile preLinked;
            using (var gate = new VisitorSearchDialog(_db))
            {
                DialogResult gateResult = gate.ShowDialog(this);
                if (gateResult == DialogResult.Cancel)
                    return;
                if (gateResult == DialogResult.Retry)
                    preLinked = null;
                else if (gateResult == DialogResult.OK)
                    preLinked = gate.SelectedProfile;
                else
                    return;
            }

            using var entryForm = new ManualVisitorEntryForm(_db, preLinked);
            if (entryForm.ShowDialog(this) != DialogResult.OK)
                return;

            byte[] template = null;
            VisitorProfile linked = entryForm.LinkedReturningIdentity;

            if (linked != null)
            {
                if (entryForm.RegisterFingerprint && !linked.HasFingerprintOnFile &&
                    (!ShowVisitorFingerprintScanOverlay(out template) || template == null || template.Length == 0))
                    return;

                int? savedFingerprintId = _db.InsertNewVisitorLog(
                    linked.FingerprintId > 0 ? (int?)linked.FingerprintId : null,
                    entryForm.VisitorFullName,
                    entryForm.VisitorAddress,
                    entryForm.VisitorContactNumber,
                    entryForm.PurposeOfVisit,
                    string.Empty,
                    false,
                    false,
                    false,
                    string.Empty,
                    template,
                    DatabaseHelper.LogbookTransactionTypeVisitor,
                    linked.PersonId,
                    entryForm.SelectedPersonClassification);

                if (template != null)
                    ReloadFingerprintTemplates(savedFingerprintId);
                LoadPrivilegedLogs();
                MessageBox.Show(
                    "Returning person entry saved. Identity information was reused — only this transaction was added.",
                    "Saved",
                    MessageBoxButtons.OK,
                    MessageBoxIcon.Information);
                return;
            }

            VisitorProfile existing = _db.FindExistingPersonProfile(
                entryForm.VisitorFullName,
                entryForm.VisitorContactNumber);
            if (existing != null)
            {
                var merge = MessageBox.Show(
                    "A person with this name and contact is already on file.\n\n" +
                    "Save this transaction under the existing account?",
                    "Matching person found",
                    MessageBoxButtons.YesNo,
                    MessageBoxIcon.Question);
                if (merge == DialogResult.Yes)
                {
                    if (entryForm.RegisterFingerprint && !existing.HasFingerprintOnFile &&
                        (!ShowVisitorFingerprintScanOverlay(out template) || template == null || template.Length == 0))
                        return;

                    int? savedFingerprintId = _db.InsertNewVisitorLog(
                        existing.FingerprintId > 0 ? (int?)existing.FingerprintId : null,
                        existing.FullName,
                        existing.Address,
                        existing.ContactNumber,
                        entryForm.PurposeOfVisit,
                        string.Empty,
                        false,
                        false,
                        false,
                        string.Empty,
                        template,
                        DatabaseHelper.LogbookTransactionTypeVisitor,
                        existing.PersonId,
                        existing.PersonClassification);

                    if (template != null)
                        ReloadFingerprintTemplates(savedFingerprintId);
                    LoadPrivilegedLogs();
                    MessageBox.Show(
                        "Transaction saved under the existing person account.",
                        "Saved",
                        MessageBoxButtons.OK,
                        MessageBoxIcon.Information);
                    return;
                }
            }

            if (!PersonRegistrationGuard.CanCreateNewPerson(_db, entryForm.VisitorFullName, entryForm.VisitorAddress))
                return;

            if (entryForm.RegisterFingerprint &&
                (!ShowVisitorFingerprintScanOverlay(out template) || template == null || template.Length == 0))
                return;

            int? savedNewFingerprintId = _db.InsertNewVisitorLog(
                null,
                entryForm.VisitorFullName,
                entryForm.VisitorAddress,
                entryForm.VisitorContactNumber,
                entryForm.PurposeOfVisit,
                string.Empty,
                false,
                false,
                false,
                string.Empty,
                template,
                DatabaseHelper.LogbookTransactionTypeVisitor,
                personClassification: entryForm.SelectedPersonClassification);

            if (template != null)
                ReloadFingerprintTemplates(savedNewFingerprintId);
            LoadPrivilegedLogs();
            MessageBox.Show("Visitor logbook entry saved successfully.", "Saved", MessageBoxButtons.OK, MessageBoxIcon.Information);
        }

        private void StartDocumentClaimOnBehalfEntry()
        {
            using var entryForm = new DocumentClaimOnBehalfEntryForm(_db);
            if (entryForm.ShowDialog(this) != DialogResult.OK)
                return;

            VisitorProfile claimant = entryForm.LinkedClaimantIdentity ??
                _db.FindExistingPersonProfile(entryForm.ClaimantFullName, entryForm.ClaimantContact);
            if (claimant != null && entryForm.LinkedClaimantIdentity == null)
            {
                var merge = MessageBox.Show(
                    "A person with this name and contact is already on file.\n\n" +
                    "Save the claim under the existing claimant account?",
                    "Matching claimant found",
                    MessageBoxButtons.YesNo,
                    MessageBoxIcon.Question);
                if (merge != DialogResult.Yes)
                    claimant = null;
            }

            if (claimant == null &&
                !PersonRegistrationGuard.CanCreateNewPerson(_db, entryForm.ClaimantFullName, entryForm.ClaimantAddress))
                return;
            string claimantClassification = claimant?.PersonClassification ?? entryForm.ClaimantClassification;

            byte[] template = null;
            if (!entryForm.RegisterFingerprint)
            {
                _db.InsertNewVisitorLog(
                    null,
                    claimant?.FullName ?? entryForm.ClaimantFullName,
                    claimant?.Address ?? entryForm.ClaimantAddress,
                    claimant?.ContactNumber ?? entryForm.ClaimantContact,
                    entryForm.PurposeSummary,
                    string.Empty,
                    true,
                    entryForm.AuthorizationLetterVerified,
                    entryForm.ValidIdsVerified,
                    entryForm.PrincipalFullName,
                    null,
                    DatabaseHelper.LogbookTransactionTypeDocumentClaimBehalf,
                    claimant?.PersonId,
                    claimantClassification,
                    entryForm.PrincipalClassification);

                LoadPrivilegedLogs();
                MessageBox.Show(
                    "Document claim log entry saved. Documents may only be released after at least one verification option has been completed (authorization letter OR valid IDs).",
                    "Saved",
                    MessageBoxButtons.OK,
                    MessageBoxIcon.Information);
                return;
            }

            if (!ShowVisitorFingerprintScanOverlay(out template) || template == null || template.Length == 0)
                return;

            int? savedFingerprintId = _db.InsertNewVisitorLog(
                null,
                claimant?.FullName ?? entryForm.ClaimantFullName,
                claimant?.Address ?? entryForm.ClaimantAddress,
                claimant?.ContactNumber ?? entryForm.ClaimantContact,
                entryForm.PurposeSummary,
                string.Empty,
                true,
                entryForm.AuthorizationLetterVerified,
                entryForm.ValidIdsVerified,
                entryForm.PrincipalFullName,
                template,
                DatabaseHelper.LogbookTransactionTypeDocumentClaimBehalf,
                claimant?.PersonId,
                claimantClassification,
                entryForm.PrincipalClassification);

            ReloadFingerprintTemplates(savedFingerprintId);
            LoadPrivilegedLogs();
            MessageBox.Show(
                "Document claim log entry saved. Documents may only be released after at least one verification option has been completed (authorization letter OR valid IDs).",
                "Saved",
                MessageBoxButtons.OK,
                MessageBoxIcon.Information);
        }

        private void ReloadFingerprintTemplates(int? fingerprintId)
        {
            if (_engine == null)
                return;

            if (fingerprintId.HasValue)
                _engine.RemoveTemplateFromMemory(fingerprintId.Value);
            _engine.LoadAllFingerprintsFromDatabase();
        }

        private void Grid_CellClick(object sender, DataGridViewCellEventArgs e)
        {
            if (e.RowIndex < 0 || e.RowIndex >= _grid.Rows.Count) return;

            if (!(_grid.Rows[e.RowIndex].DataBoundItem is VisitorLogEntry entry))
                return;

            ShowVisitorRecordOverlay(entry);
        }

        private void ShowVisitorRecordOverlay(VisitorLogEntry entry)
        {
            bool isDocClaim = string.Equals(entry.TransactionType, DatabaseHelper.LogbookTransactionTypeDocumentClaimBehalf,
                StringComparison.OrdinalIgnoreCase);
            Rectangle workArea = Screen.FromControl(this).WorkingArea;
            int dialogHeight = Math.Min(620, Math.Max(240, (int)(workArea.Height * 0.85)));

            using var detailForm = new Form
            {
                Text = isDocClaim ? "Document Claim (On Behalf)" : "Visitor / Resident Record",
                StartPosition = FormStartPosition.CenterParent,
                FormBorderStyle = FormBorderStyle.FixedDialog,
                MaximizeBox = false,
                MinimizeBox = false,
                ShowInTaskbar = false,
                ClientSize = new Size(660, dialogHeight),
                AutoScaleMode = AutoScaleMode.Font,
                BackColor = Color.FromArgb(236, 241, 248)
            };

            // Header
            var header = new Panel
            {
                Dock = DockStyle.Top,
                Height = 76,
                BackColor = Color.FromArgb(13, 27, 42)
            };

            var scrollBody = new Panel
            {
                Dock = DockStyle.Fill,
                AutoScroll = true,
                BackColor = Color.FromArgb(236, 241, 248)
            };

            var footer = new Panel
            {
                Dock = DockStyle.Bottom,
                Height = 72,
                BackColor = Color.FromArgb(236, 241, 248)
            };

            var pnlDetailAccent = new Panel
            {
                Location = new Point(0, 0),
                Size = new Size(6, 76),
                BackColor = isDocClaim ? Color.FromArgb(180, 83, 9) : Color.FromArgb(8, 145, 178)
            };

            var lblTitle = new Label
            {
                Text = isDocClaim ? "Document Claim On Behalf" : "Visitor / Resident Record Details",
                AutoSize = true,
                Font = new Font("Segoe UI", 13F, FontStyle.Bold),
                ForeColor = Color.FromArgb(240, 246, 255),
                BackColor = Color.Transparent,
                Left = 22,
                Top = 12
            };

            var lblSub = new Label
            {
                Text = isDocClaim
                    ? "Claimant pickup log · authorization letter and valid IDs must be verified before release"
                    : "Complete information for the selected logbook entry",
                AutoSize = true,
                Font = new Font("Segoe UI", 9F),
                ForeColor = Color.FromArgb(100, 140, 185),
                BackColor = Color.Transparent,
                Left = 22,
                Top = 46,
                MaximumSize = new Size(610, 0)
            };
            header.Controls.AddRange(new Control[] { pnlDetailAccent, lblTitle, lblSub });

            // Data card
            var card = new Panel
            {
                Left = 20,
                Top = 16,
                Width = 618,
                BackColor = Color.White,
                BorderStyle = BorderStyle.FixedSingle,
                Padding = new Padding(18, 14, 18, 14)
            };

            var details = new TableLayoutPanel
            {
                Dock = DockStyle.Top,
                AutoSize = true,
                AutoSizeMode = AutoSizeMode.GrowAndShrink,
                ColumnCount = 2,
                RowCount = isDocClaim ? 12 : 7,
                Padding = Padding.Empty,
                BackColor = Color.FromArgb(203, 213, 225),
                CellBorderStyle = TableLayoutPanelCellBorderStyle.Single
            };
            details.ColumnStyles.Add(new ColumnStyle(SizeType.Absolute, 170F));
            details.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 100F));
            for (int row = 0; row < details.RowCount; row++)
                details.RowStyles.Add(new RowStyle(SizeType.AutoSize));

            int rowIndex = 0;
            AddDetailRow(details, rowIndex++, isDocClaim ? "Claimant Name" : "Full Name", entry.FullName);
            AddDetailRow(details, rowIndex++, "Person Category", entry.PersonClassification);
            AddDetailRow(details, rowIndex++, "Address", entry.Address);
            AddDetailRow(details, rowIndex++, "Contact Number", entry.ContactNumber);
            AddDetailRow(details, rowIndex++, "Purpose / Details", entry.PurposeOfTransaction);

            if (isDocClaim)
            {
                AddDetailRow(details, rowIndex++, "Authorized Representative", entry.IsAuthorizedRepresentative ? "Yes" : "No");
                AddDetailRow(details, rowIndex++, "Document Owner", entry.RepresentedPersonName);
                AddDetailRow(details, rowIndex++, "Document Owner Category", entry.RepresentedPersonClassification);
                AddDetailRow(details, rowIndex++, "Authorization Letter Verified", entry.AuthorizationLetterVerified ? "Yes" : "No");
                AddDetailRow(details, rowIndex++, "Valid ID Verified", entry.ValidIdVerified ? "Yes" : "No");
            }

            AddDetailRow(details, rowIndex++, "Fingerprint", entry.FingerprintEnrollmentLabel);
            AddDetailRow(details, rowIndex, "Time In", entry.TimeIn.ToString("yyyy-MM-dd hh:mm:ss tt"));
            card.Controls.Add(details);
            scrollBody.Controls.Add(card);

            Panel releaseBanner = null;
            Label lblRelease = null;
            if (isDocClaim)
            {
                bool okRelease = entry.AuthorizationLetterVerified && entry.ValidIdVerified;
                releaseBanner = new Panel
                {
                    Left = 20,
                    Top = card.Bottom + 12,
                    Width = 618,
                    Height = 56,
                    BackColor = okRelease ? Color.FromArgb(236, 253, 245) : Color.FromArgb(254, 243, 199),
                    BorderStyle = BorderStyle.FixedSingle
                };
                lblRelease = new Label
                {
                    Left = 14,
                    Top = 10,
                    MaximumSize = new Size(590, 0),
                    AutoSize = true,
                    Font = new Font("Segoe UI", 9F),
                    ForeColor = okRelease ? Color.FromArgb(20, 83, 45) : Color.FromArgb(120, 53, 15),
                    Text = okRelease
                        ? "Verification recorded: authorization letter and valid IDs were confirmed. Documents may be released according to barangay procedures."
                        : "Do not release documents until an authorization letter and valid IDs have been verified and recorded."
                };
                releaseBanner.Controls.Add(lblRelease);
                scrollBody.Controls.Add(releaseBanner);
            }

            Action layoutContent = () =>
            {
                int contentWidth = Math.Max(1, scrollBody.ClientSize.Width - 40);
                card.Width = contentWidth;
                details.Width = Math.Max(1, card.ClientSize.Width - card.Padding.Horizontal);

                int valueWidth = Math.Max(80, details.Width - 190);
                foreach (Control control in details.Controls)
                {
                    if (details.GetColumn(control) == 1 && control is Label valueLabel)
                        valueLabel.MaximumSize = new Size(valueWidth, 0);
                }

                details.PerformLayout();
                card.Height = details.PreferredSize.Height + card.Padding.Vertical + 2;
                if (releaseBanner != null)
                {
                    releaseBanner.Top = card.Bottom + 12;
                    releaseBanner.Width = contentWidth;
                    lblRelease.MaximumSize = new Size(Math.Max(80, contentWidth - 28), 0);
                    releaseBanner.Height = Math.Max(56, lblRelease.Bottom + 10);
                }
            };
            scrollBody.Resize += (_, __) => layoutContent();

            var btnClose = new Button
            {
                Text = "Close",
                Width = 130,
                Height = 40,
                Left = 510,
                Top = 16,
                Anchor = AnchorStyles.Top | AnchorStyles.Right,
                BackColor = Color.FromArgb(8, 145, 178),
                ForeColor = Color.White,
                Font = new Font("Segoe UI", 9.5F, FontStyle.Bold),
                FlatStyle = FlatStyle.Flat,
                Cursor = Cursors.Hand
            };
            btnClose.FlatAppearance.BorderSize = 0;
            btnClose.FlatAppearance.MouseOverBackColor = Color.FromArgb(6, 118, 146);
            btnClose.FlatAppearance.MouseDownBackColor = Color.FromArgb(4, 94, 116);
            btnClose.Click += (_, __) => detailForm.Close();

            footer.Controls.Add(btnClose);
            detailForm.CancelButton = btnClose;
            detailForm.Controls.Add(scrollBody);
            detailForm.Controls.Add(footer);
            detailForm.Controls.Add(header);
            detailForm.Shown += (_, __) => layoutContent();
            DesktopTheme.Apply(detailForm);
            detailForm.ShowDialog(this);
        }

        /// <summary>
        /// Modal overlay for visitor fingerprint registration after Data Privacy consent.
        /// Saves to DB only after this returns a captured template.
        /// </summary>
        private bool ShowVisitorFingerprintScanOverlay(out byte[] template)
        {
            template = null;
            if (_engine == null || !_engine.IsConnected)
            {
                MessageBox.Show("Fingerprint device is not connected.", "Device Error",
                    MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return false;
            }

            using var scanForm = new Form
            {
                Text = "Fingerprint Registration",
                StartPosition = FormStartPosition.CenterParent,
                FormBorderStyle = FormBorderStyle.FixedDialog,
                MaximizeBox = false,
                MinimizeBox = false,
                ShowInTaskbar = false,
                ClientSize = new Size(600, 320),
                BackColor = Color.FromArgb(244, 247, 250),
                KeyPreview = true
            };

            var panelHeader = new Panel
            {
                Dock = DockStyle.Fill,
                BackColor = Color.FromArgb(10, 17, 40),
                Padding = new Padding(4, 0, 0, 0)
            };
            var lblScanTitle = new Label
            {
                Text = "Fingerprint Registration",
                Dock = DockStyle.Fill,
                TextAlign = ContentAlignment.MiddleLeft,
                Padding = new Padding(20, 0, 18, 0),
                Font = new Font("Segoe UI", 15F, FontStyle.Bold),
                ForeColor = Color.White,
                BackColor = Color.Transparent,
            };
            var lblScanSub = new Label
            {
                Text = "Place your finger on the scanner. Your logbook entry will be saved when capture succeeds.",
                Dock = DockStyle.Fill,
                TextAlign = ContentAlignment.TopLeft,
                Padding = new Padding(22, 4, 18, 10),
                Font = new Font("Segoe UI", 9.5F, FontStyle.Bold),
                ForeColor = Color.White,
                BackColor = Color.Transparent,
            };
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
            headerLayout.Controls.Add(lblScanTitle, 0, 0);
            headerLayout.Controls.Add(lblScanSub, 0, 1);
            panelHeader.Controls.Add(headerLayout);
            panelHeader.Paint += (_, e) =>
            {
                using (var accent = new SolidBrush(Color.FromArgb(0, 210, 255)))
                    e.Graphics.FillRectangle(accent, 0, 0, 4, panelHeader.Height);
            };

            var panelBody = new Panel
            {
                Dock = DockStyle.Fill,
                Padding = new Padding(20, 12, 20, 12),
                BackColor = Color.FromArgb(244, 247, 250)
            };
            var statusCard = new FingerprintStatusCard
            {
                Dock = DockStyle.Fill,
                Padding = new Padding(18, 12, 18, 12),
                BackColor = Color.White
            };
            var lblStatus = new Label
            {
                Text = "Waiting for fingerprint…",
                Dock = DockStyle.Fill,
                TextAlign = ContentAlignment.MiddleLeft,
                Font = new Font("Segoe UI", 11F, FontStyle.Bold),
                ForeColor = Color.FromArgb(38, 61, 91),
                BackColor = Color.Transparent
            };
            statusCard.Controls.Add(lblStatus);
            panelBody.Controls.Add(statusCard);

            var panelFooter = new Panel
            {
                Dock = DockStyle.Fill,
                Padding = new Padding(20, 10, 20, 14),
                BackColor = Color.FromArgb(244, 247, 250)
            };
            var footerActions = new FlowLayoutPanel
            {
                Dock = DockStyle.Fill,
                FlowDirection = FlowDirection.RightToLeft,
                WrapContents = false,
                BackColor = Color.Transparent,
                Padding = Padding.Empty,
                Margin = Padding.Empty
            };
            var btnCancel = new Button
            {
                Text = "Cancel",
                Width = 128,
                Height = 40,
                Margin = Padding.Empty,
                DialogResult = DialogResult.Cancel,
                BackColor = Color.White,
                ForeColor = Color.FromArgb(39, 62, 92),
                Font = new Font("Segoe UI", 9.5F, FontStyle.Bold),
                FlatStyle = FlatStyle.Flat,
                Cursor = Cursors.Hand
            };
            btnCancel.FlatAppearance.BorderSize = 1;
            btnCancel.FlatAppearance.BorderColor = Color.FromArgb(190, 207, 228);
            btnCancel.FlatAppearance.MouseOverBackColor = Color.FromArgb(229, 241, 255);
            btnCancel.FlatAppearance.MouseDownBackColor = Color.FromArgb(215, 232, 252);
            footerActions.Controls.Add(btnCancel);
            panelFooter.Controls.Add(footerActions);

            var dialogLayout = new TableLayoutPanel
            {
                Dock = DockStyle.Fill,
                ColumnCount = 1,
                RowCount = 3,
                BackColor = Color.FromArgb(244, 247, 250),
                Margin = Padding.Empty,
                Padding = Padding.Empty
            };
            dialogLayout.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 100F));
            dialogLayout.RowStyles.Add(new RowStyle(SizeType.Absolute, 112F));
            dialogLayout.RowStyles.Add(new RowStyle(SizeType.Percent, 100F));
            dialogLayout.RowStyles.Add(new RowStyle(SizeType.Absolute, 68F));
            dialogLayout.Controls.Add(panelHeader, 0, 0);
            dialogLayout.Controls.Add(panelBody, 0, 1);
            dialogLayout.Controls.Add(panelFooter, 0, 2);
            scanForm.Controls.Add(dialogLayout);

            scanForm.CancelButton = btnCancel;
            var userDismissed = false;
            btnCancel.Click += (_, __) => { userDismissed = true; };
            scanForm.KeyDown += (_, e) =>
            {
                if (e.KeyCode != Keys.Escape) return;
                userDismissed = true;
                scanForm.DialogResult = DialogResult.Cancel;
                scanForm.Close();
            };

            byte[] captured = null;
            var scanTask = Task.Run(() =>
            {
                try
                {
                    return _engine.CaptureScan(timeoutMs: 60000);
                }
                catch
                {
                    return null;
                }
            });

            scanTask.ContinueWith(t =>
            {
                if (scanForm.IsDisposed) return;
                try
                {
                    scanForm.BeginInvoke((Action)(() =>
                    {
                        captured = t.Result;
                        if (captured != null && captured.Length > 0)
                        {
                            _playResultSound(true);
                            scanForm.DialogResult = DialogResult.OK;
                        }
                        else
                            scanForm.DialogResult = DialogResult.Cancel;
                        scanForm.Close();
                    }));
                }
                catch
                {
                    // Form may already be closed.
                }
            });

            DesktopTheme.Apply(scanForm);
            scanForm.BackColor = Color.FromArgb(244, 247, 250);
            panelHeader.BackColor = Color.FromArgb(10, 17, 40);
            lblScanTitle.ForeColor = Color.White;
            lblScanTitle.Font = new Font("Segoe UI", 15F, FontStyle.Bold);
            lblScanSub.ForeColor = Color.White;
            lblScanSub.Font = new Font("Segoe UI", 9.5F, FontStyle.Bold);
            panelBody.BackColor = Color.FromArgb(244, 247, 250);
            statusCard.BackColor = Color.White;
            lblStatus.ForeColor = Color.FromArgb(38, 61, 91);
            panelFooter.BackColor = Color.FromArgb(244, 247, 250);
            btnCancel.BackColor = Color.White;
            btnCancel.ForeColor = Color.FromArgb(39, 62, 92);
            btnCancel.FlatAppearance.BorderSize = 1;
            btnCancel.FlatAppearance.BorderColor = Color.FromArgb(190, 207, 228);
            btnCancel.FlatAppearance.MouseOverBackColor = Color.FromArgb(229, 241, 255);
            btnCancel.FlatAppearance.MouseDownBackColor = Color.FromArgb(215, 232, 252);
            var result = scanForm.ShowDialog(this);
            if (result != DialogResult.OK || captured == null || captured.Length == 0)
            {
                if (!userDismissed)
                {
                    _playResultSound(false);
                    string msg = string.IsNullOrWhiteSpace(_engine.LastError)
                        ? "Fingerprint capture failed."
                        : _engine.LastError;
                    MessageBox.Show(msg, "Fingerprint capture", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                }
                return false;
            }

            template = captured;
            return true;
        }

        private sealed class FingerprintStatusCard : Panel
        {
            public FingerprintStatusCard()
            {
                SetStyle(
                    ControlStyles.UserPaint |
                    ControlStyles.AllPaintingInWmPaint |
                    ControlStyles.OptimizedDoubleBuffer,
                    true);
                UpdateRoundedRegion();
            }

            protected override void OnResize(EventArgs eventArgs)
            {
                base.OnResize(eventArgs);
                UpdateRoundedRegion();
                Invalidate();
            }

            protected override void OnPaint(PaintEventArgs eventArgs)
            {
                base.OnPaint(eventArgs);
                if (ClientSize.Width < 2 || ClientSize.Height < 2)
                    return;

                eventArgs.Graphics.SmoothingMode = SmoothingMode.AntiAlias;
                Rectangle shadowBounds = new Rectangle(1, 2, ClientSize.Width - 3, ClientSize.Height - 4);
                using (GraphicsPath shadowPath = CreateRoundedPath(shadowBounds, 10))
                using (var shadowPen = new Pen(Color.FromArgb(22, 38, 71, 112), 2F))
                    eventArgs.Graphics.DrawPath(shadowPen, shadowPath);

                Rectangle cardBounds = new Rectangle(0, 0, ClientSize.Width - 1, ClientSize.Height - 1);
                using (GraphicsPath cardPath = CreateRoundedPath(cardBounds, 10))
                using (var cardBrush = new SolidBrush(Color.White))
                using (var borderPen = new Pen(Color.FromArgb(205, 219, 237), 1F))
                {
                    eventArgs.Graphics.FillPath(cardBrush, cardPath);
                    eventArgs.Graphics.DrawPath(borderPen, cardPath);
                }
            }

            private void UpdateRoundedRegion()
            {
                if (ClientSize.Width < 2 || ClientSize.Height < 2)
                    return;

                using (GraphicsPath path = CreateRoundedPath(
                    new Rectangle(0, 0, ClientSize.Width, ClientSize.Height), 10))
                {
                    Region previousRegion = Region;
                    Region = new Region(path);
                    previousRegion?.Dispose();
                }
            }

            private static GraphicsPath CreateRoundedPath(Rectangle bounds, int radius)
            {
                int diameter = Math.Min(radius * 2, Math.Min(bounds.Width, bounds.Height));
                var path = new GraphicsPath();
                path.AddArc(bounds.Left, bounds.Top, diameter, diameter, 180, 90);
                path.AddArc(bounds.Right - diameter, bounds.Top, diameter, diameter, 270, 90);
                path.AddArc(bounds.Right - diameter, bounds.Bottom - diameter, diameter, diameter, 0, 90);
                path.AddArc(bounds.Left, bounds.Bottom - diameter, diameter, diameter, 90, 90);
                path.CloseFigure();
                return path;
            }
        }

        private static void AddDetailRow(TableLayoutPanel container, int rowIndex, string label, string value)
        {
            var lblKey = new Label
            {
                Text = label,
                AutoSize = true,
                Anchor = AnchorStyles.Top | AnchorStyles.Left | AnchorStyles.Right,
                Font = new Font("Segoe UI", 9F, FontStyle.Bold),
                ForeColor = Color.FromArgb(71, 85, 105),
                TextAlign = ContentAlignment.MiddleLeft,
                BackColor = rowIndex % 2 == 0 ? Color.White : Color.FromArgb(248, 250, 252),
                Padding = new Padding(8, 6, 8, 6),
                Margin = Padding.Empty
            };

            var lblValue = new Label
            {
                Text = string.IsNullOrWhiteSpace(value) ? "—" : value,
                AutoSize = true,
                Anchor = AnchorStyles.Top | AnchorStyles.Left | AnchorStyles.Right,
                Font = new Font("Segoe UI", 9.5F),
                ForeColor = Color.FromArgb(30, 41, 59),
                MaximumSize = new Size(390, 0),
                TextAlign = ContentAlignment.MiddleLeft,
                BackColor = rowIndex % 2 == 0 ? Color.White : Color.FromArgb(248, 250, 252),
                Padding = new Padding(8, 6, 8, 6),
                Margin = Padding.Empty
            };

            container.Controls.Add(lblKey, 0, rowIndex);
            container.Controls.Add(lblValue, 1, rowIndex);
        }
    }
}
