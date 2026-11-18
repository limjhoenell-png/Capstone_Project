namespace FingerprintAttendance
{
    partial class MainForm
    {
        private System.ComponentModel.IContainer components = null;

        protected override void Dispose(bool disposing)
        {
            if (disposing && (components != null)) components.Dispose();
            base.Dispose(disposing);
        }

        private void InitializeComponent()
        {
            this.components = new System.ComponentModel.Container();
            this.panelHeader = new System.Windows.Forms.Panel();
            this.lblAppTitle = new System.Windows.Forms.Label();
            this.lblCurrentTime = new System.Windows.Forms.Label();
            this.lblCurrentDate = new System.Windows.Forms.Label();
            this.headerClockLayout = new System.Windows.Forms.TableLayoutPanel();
            this.pnlHeaderAccent = new System.Windows.Forms.Panel();
            this.panelLeft = new System.Windows.Forms.Panel();
            this.pnlScanBanner = new System.Windows.Forms.Panel();
            this.lblScanSection = new System.Windows.Forms.Label();
            this.pnlScanFrame = new System.Windows.Forms.Panel();
            this.pnlScanInner = new System.Windows.Forms.Panel();
            this.picBarangayLogo = new System.Windows.Forms.PictureBox();
            this.pnlStatusArea = new System.Windows.Forms.Panel();
            this.lblUser = new System.Windows.Forms.Label();
            this.pnlStatusDivider = new System.Windows.Forms.Panel();
            this.lblStatus = new System.Windows.Forms.Label();
            this.panelRight = new System.Windows.Forms.Panel();
            this.panelWorkspace = new System.Windows.Forms.Panel();
            this.pnlSidebarAccent = new System.Windows.Forms.Panel();
            this.pnlSidebarContent = new System.Windows.Forms.Panel();
            this.pnlSidebarHeader = new System.Windows.Forms.Panel();
            this.lblActionsSection = new System.Windows.Forms.Label();
            this.pnlSidebarDivider = new System.Windows.Forms.Panel();
            this.pnlButtonGroup = new System.Windows.Forms.Panel();
            this.btnEnroll = new System.Windows.Forms.Button();
            this.pnlBtnSpacer1 = new System.Windows.Forms.Panel();
            this.btnLogs = new System.Windows.Forms.Button();
            this.pnlBtnSpacer2 = new System.Windows.Forms.Panel();
            this.btnVisitorLogbook = new System.Windows.Forms.Button();
            this.pnlFooterInfo = new System.Windows.Forms.Panel();
            this.lblFooterHint = new System.Windows.Forms.Label();
            this.scanTimer = new System.Windows.Forms.Timer(this.components);
            this.clockTimer = new System.Windows.Forms.Timer(this.components);

            ((System.ComponentModel.ISupportInitialize)(this.picBarangayLogo)).BeginInit();
            this.SuspendLayout();

            // ── FORM ────────────────────────────────────────────────────────────
            this.Text = "BioPayLog: Attendance & Logbook Transactions";
            this.MinimumSize = new System.Drawing.Size(800, 520);
            this.StartPosition = System.Windows.Forms.FormStartPosition.CenterScreen;
            this.FormBorderStyle = System.Windows.Forms.FormBorderStyle.Sizable;
            this.MaximizeBox = true;
            this.BackColor = System.Drawing.Color.FromArgb(236, 241, 248);
            this.AutoScaleMode = System.Windows.Forms.AutoScaleMode.Font;
            this.Load += new System.EventHandler(this.MainForm_Load);
            this.FormClosing += new System.Windows.Forms.FormClosingEventHandler(this.MainForm_FormClosing);

            // ── TOP BAR ─────────────────────────────────────────────────────────
            this.panelHeader.Dock = System.Windows.Forms.DockStyle.Top;
            this.panelHeader.Height = 76;
            this.panelHeader.BackColor = System.Drawing.Color.White;
            this.headerLayout = new System.Windows.Forms.TableLayoutPanel();
            this.headerLayout.Dock = System.Windows.Forms.DockStyle.Fill;
            this.headerLayout.ColumnCount = 2;
            this.headerLayout.RowCount = 1;
            this.headerLayout.BackColor = System.Drawing.Color.Transparent;
            this.headerLayout.Padding = new System.Windows.Forms.Padding(18, 6, 18, 4);
            this.headerLayout.ColumnStyles.Add(new System.Windows.Forms.ColumnStyle(System.Windows.Forms.SizeType.Percent, 60F));
            this.headerLayout.ColumnStyles.Add(new System.Windows.Forms.ColumnStyle(System.Windows.Forms.SizeType.Percent, 40F));
            this.headerLayout.RowStyles.Add(new System.Windows.Forms.RowStyle(System.Windows.Forms.SizeType.Percent, 100F));

            // App name - large and bold
            this.lblAppTitle.Dock = System.Windows.Forms.DockStyle.Fill;
            this.lblAppTitle.Text = "BioPayLog: Attendance & Logbook Transactions";
            this.lblAppTitle.Font = new System.Drawing.Font("Segoe UI", 16F, System.Drawing.FontStyle.Bold);
            this.lblAppTitle.ForeColor = System.Drawing.Color.FromArgb(17, 24, 39);
            this.lblAppTitle.BackColor = System.Drawing.Color.Transparent;
            this.lblAppTitle.TextAlign = System.Drawing.ContentAlignment.MiddleLeft;
            this.lblAppTitle.AutoEllipsis = false;
            this.lblAppTitle.AutoSize = false;
            this.lblAppTitle.UseMnemonic = false;
            this.lblAppTitle.Margin = new System.Windows.Forms.Padding(0);

            // Clock - time (large, right-aligned)
            this.lblCurrentTime.Dock = System.Windows.Forms.DockStyle.None;
            this.lblCurrentTime.AutoSize = true;
            this.lblCurrentTime.Text = "00:00:00 AM";
            this.lblCurrentTime.Font = new System.Drawing.Font("Segoe UI", 15F, System.Drawing.FontStyle.Bold);
            this.lblCurrentTime.ForeColor = System.Drawing.Color.FromArgb(17, 24, 39);
            this.lblCurrentTime.BackColor = System.Drawing.Color.Transparent;
            this.lblCurrentTime.TextAlign = System.Drawing.ContentAlignment.MiddleRight;
            this.lblCurrentTime.AutoEllipsis = false;
            this.lblCurrentTime.Margin = new System.Windows.Forms.Padding(10, 0, 0, 0);

            this.lblCurrentDate.Dock = System.Windows.Forms.DockStyle.None;
            this.lblCurrentDate.AutoSize = true;
            this.lblCurrentDate.Text = "Monday, April 13, 2026";
            this.lblCurrentDate.Font = new System.Drawing.Font("Segoe UI", 8.5F);
            this.lblCurrentDate.ForeColor = System.Drawing.Color.FromArgb(75, 85, 99);
            this.lblCurrentDate.BackColor = System.Drawing.Color.Transparent;
            this.lblCurrentDate.TextAlign = System.Drawing.ContentAlignment.MiddleRight;
            this.lblCurrentDate.AutoEllipsis = false;
            this.lblCurrentDate.Margin = new System.Windows.Forms.Padding(0, 4, 0, 0);

            this.headerClockLayout.Dock = System.Windows.Forms.DockStyle.Fill;
            this.headerClockLayout.ColumnCount = 2;
            this.headerClockLayout.RowCount = 1;
            this.headerClockLayout.BackColor = System.Drawing.Color.Transparent;
            this.headerClockLayout.Margin = new System.Windows.Forms.Padding(0);
            this.headerClockLayout.Padding = new System.Windows.Forms.Padding(0);
            this.headerClockLayout.ColumnStyles.Add(new System.Windows.Forms.ColumnStyle(System.Windows.Forms.SizeType.Percent, 68F));
            this.headerClockLayout.ColumnStyles.Add(new System.Windows.Forms.ColumnStyle(System.Windows.Forms.SizeType.Percent, 32F));
            this.headerClockLayout.RowStyles.Add(new System.Windows.Forms.RowStyle(System.Windows.Forms.SizeType.Percent, 100F));
            this.lblCurrentDate.Dock = System.Windows.Forms.DockStyle.Fill;
            this.lblCurrentDate.AutoSize = false;
            this.lblCurrentDate.Margin = new System.Windows.Forms.Padding(0);
            this.lblCurrentTime.Dock = System.Windows.Forms.DockStyle.Fill;
            this.lblCurrentTime.AutoSize = false;
            this.lblCurrentTime.Margin = new System.Windows.Forms.Padding(0);
            this.lblCurrentDate.TextAlign = System.Drawing.ContentAlignment.MiddleRight;
            this.lblCurrentTime.TextAlign = System.Drawing.ContentAlignment.MiddleRight;
            this.headerClockLayout.Controls.Add(this.lblCurrentDate, 0, 0);
            this.headerClockLayout.Controls.Add(this.lblCurrentTime, 1, 0);

            this.headerLayout.Controls.Add(this.lblAppTitle, 0, 0);
            this.headerLayout.Controls.Add(this.headerClockLayout, 1, 0);
            this.panelHeader.Controls.Add(this.headerLayout);

            // ── HEADER ACCENT STRIPE (blue underline) ───────────────────────────
            this.pnlHeaderAccent.Dock = System.Windows.Forms.DockStyle.Top;
            this.pnlHeaderAccent.Height = 3;
            this.pnlHeaderAccent.BackColor = System.Drawing.Color.FromArgb(228, 234, 245);

            // ── LEFT NAVIGATION (Laravel admin sidebar) ─────────────────────────
            this.panelRight.Name = "navigationSidebar";
            this.panelRight.Dock = System.Windows.Forms.DockStyle.Left;
            this.panelRight.Width = 320;
            this.panelRight.BackColor = System.Drawing.Color.FromArgb(13, 20, 38);

            // Left accent bar on sidebar (3px cyan stripe)
            this.pnlSidebarAccent.Dock = System.Windows.Forms.DockStyle.Left;
            this.pnlSidebarAccent.Width = 3;
            this.pnlSidebarAccent.BackColor = System.Drawing.Color.FromArgb(13, 20, 38);

            // Content wrapper inside sidebar
            this.pnlSidebarContent.Dock = System.Windows.Forms.DockStyle.Fill;
            this.pnlSidebarContent.Padding = new System.Windows.Forms.Padding(16, 20, 16, 18);
            this.pnlSidebarContent.BackColor = System.Drawing.Color.Transparent;

            // Sidebar header section label
            this.pnlSidebarHeader.Dock = System.Windows.Forms.DockStyle.Top;
            this.pnlSidebarHeader.Height = 32;
            this.pnlSidebarHeader.BackColor = System.Drawing.Color.Transparent;

            this.lblActionsSection.Dock = System.Windows.Forms.DockStyle.Fill;
            this.lblActionsSection.Text = "System Navigation";
            this.lblActionsSection.Font = new System.Drawing.Font("Segoe UI", 11F, System.Drawing.FontStyle.Bold);
            this.lblActionsSection.ForeColor = System.Drawing.Color.White;
            this.lblActionsSection.BackColor = System.Drawing.Color.Transparent;
            this.lblActionsSection.TextAlign = System.Drawing.ContentAlignment.MiddleLeft;

            // Thin divider below section label
            this.pnlSidebarDivider.Dock = System.Windows.Forms.DockStyle.Top;
            this.pnlSidebarDivider.Height = 1;
            this.pnlSidebarDivider.BackColor = System.Drawing.Color.FromArgb(30, 45, 80);
            this.pnlSidebarDivider.Margin = new System.Windows.Forms.Padding(0, 6, 0, 12);

            // Button group panel
            this.pnlButtonGroup.Dock = System.Windows.Forms.DockStyle.Top;
            this.pnlButtonGroup.Height = 176;
            this.pnlButtonGroup.BackColor = System.Drawing.Color.Transparent;

            // ── ENROLL BUTTON (primary - blue) ──────────────────────────────────
            this.btnEnroll.Dock = System.Windows.Forms.DockStyle.Top;
            this.btnEnroll.Height = 46;
            this.btnEnroll.Text = "+   Administrator";
            this.btnEnroll.Font = new System.Drawing.Font("Segoe UI", 9.5F, System.Drawing.FontStyle.Bold);
            this.btnEnroll.BackColor = System.Drawing.Color.FromArgb(13, 20, 38);
            this.btnEnroll.ForeColor = System.Drawing.Color.FromArgb(226, 232, 240);
            this.btnEnroll.FlatStyle = System.Windows.Forms.FlatStyle.Flat;
            this.btnEnroll.FlatAppearance.BorderSize = 0;
            this.btnEnroll.FlatAppearance.MouseOverBackColor = System.Drawing.Color.FromArgb(30, 45, 80);
            this.btnEnroll.FlatAppearance.MouseDownBackColor = System.Drawing.Color.FromArgb(26, 37, 64);
            this.btnEnroll.Cursor = System.Windows.Forms.Cursors.Hand;
            this.btnEnroll.TextAlign = System.Drawing.ContentAlignment.MiddleLeft;
            this.btnEnroll.Padding = new System.Windows.Forms.Padding(16, 0, 0, 0);
            this.btnEnroll.Click += new System.EventHandler(this.btnEnroll_Click);

            // Spacer between enroll and logs
            this.pnlBtnSpacer1.Dock = System.Windows.Forms.DockStyle.Top;
            this.pnlBtnSpacer1.Height = 8;
            this.pnlBtnSpacer1.BackColor = System.Drawing.Color.Transparent;

            // ── LOGS BUTTON (secondary outlined) ────────────────────────────────
            this.btnLogs.Dock = System.Windows.Forms.DockStyle.Top;
            this.btnLogs.Height = 46;
            this.btnLogs.Text = "≡   View Attendance Log";
            this.btnLogs.Font = new System.Drawing.Font("Segoe UI", 9.5F, System.Drawing.FontStyle.Bold);
            this.btnLogs.BackColor = System.Drawing.Color.FromArgb(13, 20, 38);
            this.btnLogs.ForeColor = System.Drawing.Color.FromArgb(226, 232, 240);
            this.btnLogs.FlatStyle = System.Windows.Forms.FlatStyle.Flat;
            this.btnLogs.FlatAppearance.BorderSize = 0;
            this.btnLogs.FlatAppearance.MouseOverBackColor = System.Drawing.Color.FromArgb(30, 45, 80);
            this.btnLogs.FlatAppearance.MouseDownBackColor = System.Drawing.Color.FromArgb(26, 37, 64);
            this.btnLogs.Cursor = System.Windows.Forms.Cursors.Hand;
            this.btnLogs.TextAlign = System.Drawing.ContentAlignment.MiddleLeft;
            this.btnLogs.Padding = new System.Windows.Forms.Padding(16, 0, 0, 0);
            this.btnLogs.Click += new System.EventHandler(this.btnLogs_Click);

            // Spacer between logs and visitor logbook
            this.pnlBtnSpacer2.Dock = System.Windows.Forms.DockStyle.Top;
            this.pnlBtnSpacer2.Height = 8;
            this.pnlBtnSpacer2.BackColor = System.Drawing.Color.Transparent;

            // ── VISITOR LOGBOOK BUTTON (teal) ────────────────────────────────────
            this.btnVisitorLogbook.Dock = System.Windows.Forms.DockStyle.Top;
            this.btnVisitorLogbook.Height = 46;
            this.btnVisitorLogbook.Text = "▤   Logbook Transaction";
            this.btnVisitorLogbook.Font = new System.Drawing.Font("Segoe UI", 9.5F, System.Drawing.FontStyle.Bold);
            this.btnVisitorLogbook.BackColor = System.Drawing.Color.FromArgb(13, 20, 38);
            this.btnVisitorLogbook.ForeColor = System.Drawing.Color.FromArgb(226, 232, 240);
            this.btnVisitorLogbook.FlatStyle = System.Windows.Forms.FlatStyle.Flat;
            this.btnVisitorLogbook.FlatAppearance.BorderSize = 0;
            this.btnVisitorLogbook.FlatAppearance.MouseOverBackColor = System.Drawing.Color.FromArgb(30, 45, 80);
            this.btnVisitorLogbook.FlatAppearance.MouseDownBackColor = System.Drawing.Color.FromArgb(26, 37, 64);
            this.btnVisitorLogbook.Cursor = System.Windows.Forms.Cursors.Hand;
            this.btnVisitorLogbook.TextAlign = System.Drawing.ContentAlignment.MiddleLeft;
            this.btnVisitorLogbook.Padding = new System.Windows.Forms.Padding(16, 0, 0, 0);
            this.btnVisitorLogbook.Click += new System.EventHandler(this.btnVisitorLogbook_Click);

            // Footer hint inside sidebar
            this.pnlFooterInfo.Dock = System.Windows.Forms.DockStyle.Bottom;
            this.pnlFooterInfo.Height = 56;
            this.pnlFooterInfo.BackColor = System.Drawing.Color.FromArgb(6, 10, 18);
            this.pnlFooterInfo.Padding = new System.Windows.Forms.Padding(18, 10, 18, 10);

            this.lblFooterHint.Dock = System.Windows.Forms.DockStyle.Fill;
            this.lblFooterHint.Text = "Fingerprint scan runs continuously.\nPress Esc or use buttons above to manage.";
            this.lblFooterHint.Font = new System.Drawing.Font("Segoe UI", 8F);
            this.lblFooterHint.ForeColor = System.Drawing.Color.FromArgb(148, 163, 184);
            this.lblFooterHint.BackColor = System.Drawing.Color.Transparent;
            this.lblFooterHint.TextAlign = System.Drawing.ContentAlignment.MiddleLeft;

            // Assemble button group (DockStyle.Top stacks bottom-up, so reverse order)
            this.pnlButtonGroup.Controls.Add(this.btnVisitorLogbook);
            this.pnlButtonGroup.Controls.Add(this.pnlBtnSpacer2);
            this.pnlButtonGroup.Controls.Add(this.btnLogs);
            this.pnlButtonGroup.Controls.Add(this.pnlBtnSpacer1);
            this.pnlButtonGroup.Controls.Add(this.btnEnroll);

            // Assemble sidebar header
            this.pnlSidebarHeader.Controls.Add(this.lblActionsSection);

            // Assemble sidebar content (DockStyle.Top - note reverse order for proper stacking)
            this.pnlSidebarContent.Controls.Add(this.pnlFooterInfo);
            this.pnlSidebarContent.Controls.Add(this.pnlButtonGroup);
            this.pnlSidebarContent.Controls.Add(this.pnlSidebarDivider);
            this.pnlSidebarContent.Controls.Add(this.pnlSidebarHeader);

            // Assemble right panel
            this.panelRight.Controls.Add(this.pnlSidebarContent);
            this.panelRight.Controls.Add(this.pnlSidebarAccent);

            // ── LEFT PANEL (scanner area) ────────────────────────────────────────
            this.panelWorkspace.Dock = System.Windows.Forms.DockStyle.Fill;
            this.panelWorkspace.BackColor = System.Drawing.Color.FromArgb(240, 244, 255);
            this.panelLeft.Dock = System.Windows.Forms.DockStyle.Fill;
            this.panelLeft.BackColor = System.Drawing.Color.FromArgb(240, 244, 255);
            this.panelLeft.Padding = new System.Windows.Forms.Padding(28);
            this.panelLeft.BorderStyle = System.Windows.Forms.BorderStyle.None;

            // Scan section banner (top strip)
            this.pnlScanBanner.Dock = System.Windows.Forms.DockStyle.Top;
            this.pnlScanBanner.Height = 40;
            this.pnlScanBanner.BackColor = System.Drawing.Color.White;

            this.lblScanSection.Dock = System.Windows.Forms.DockStyle.Fill;
            this.lblScanSection.Text = "◉  BIOMETRIC SCANNER  —  LIVE  |  AUTO TIME-IN / TIME-OUT";
            this.lblScanSection.Font = new System.Drawing.Font("Segoe UI", 9.5F, System.Drawing.FontStyle.Bold);
            this.lblScanSection.ForeColor = System.Drawing.Color.FromArgb(37, 99, 235);
            this.lblScanSection.BackColor = System.Drawing.Color.Transparent;
            this.lblScanSection.TextAlign = System.Drawing.ContentAlignment.MiddleCenter;

            this.pnlScanBanner.Controls.Add(this.lblScanSection);

            // Scan frame for the biometric guide and Barangay seal.
            this.pnlScanFrame.Dock = System.Windows.Forms.DockStyle.Fill;
            this.pnlScanFrame.BackColor = System.Drawing.Color.FromArgb(15, 23, 42);
            this.pnlScanFrame.Padding = new System.Windows.Forms.Padding(12);

            this.pnlScanInner.Dock = System.Windows.Forms.DockStyle.Fill;
            this.pnlScanInner.BackColor = System.Drawing.Color.FromArgb(15, 23, 42);
            this.pnlScanInner.Padding = new System.Windows.Forms.Padding(0);

            this.picBarangayLogo.Dock = System.Windows.Forms.DockStyle.Fill;
            this.picBarangayLogo.BackColor = System.Drawing.Color.FromArgb(15, 23, 42);
            this.picBarangayLogo.BorderStyle = System.Windows.Forms.BorderStyle.None;
            this.picBarangayLogo.SizeMode = System.Windows.Forms.PictureBoxSizeMode.Zoom;

            this.pnlScanInner.Controls.Add(this.picBarangayLogo);
            this.pnlScanFrame.Controls.Add(this.pnlScanInner);

            // Status area (bottom of left panel)
            this.pnlStatusArea.Dock = System.Windows.Forms.DockStyle.Bottom;
            this.pnlStatusArea.Height = 76;
            this.pnlStatusArea.BackColor = System.Drawing.Color.White;
            this.pnlStatusArea.Padding = new System.Windows.Forms.Padding(0);

            // Name / result display (large, green, top of status area)
            this.lblUser.Dock = System.Windows.Forms.DockStyle.Fill;
            this.lblUser.Text = "";
            this.lblUser.Font = new System.Drawing.Font("Consolas", 24F, System.Drawing.FontStyle.Bold);
            this.lblUser.ForeColor = System.Drawing.Color.FromArgb(15, 81, 50);
            this.lblUser.BackColor = System.Drawing.Color.White;
            this.lblUser.TextAlign = System.Drawing.ContentAlignment.MiddleCenter;
            this.lblUser.Padding = new System.Windows.Forms.Padding(32, 16, 32, 16);

            // Thin divider between user label and status
            this.pnlStatusDivider.Dock = System.Windows.Forms.DockStyle.Top;
            this.pnlStatusDivider.Height = 1;
            this.pnlStatusDivider.BackColor = System.Drawing.Color.FromArgb(226, 232, 240);

            // Status text (system messages, smaller monospace)
            this.lblStatus.Dock = System.Windows.Forms.DockStyle.Fill;
            this.lblStatus.Height = 0;
            this.lblStatus.Text = "Initializing device...";
            this.lblStatus.Font = new System.Drawing.Font("Consolas", 18F, System.Drawing.FontStyle.Bold);
            this.lblStatus.ForeColor = System.Drawing.Color.FromArgb(37, 99, 235);
            this.lblStatus.BackColor = System.Drawing.Color.FromArgb(239, 246, 255);
            this.lblStatus.TextAlign = System.Drawing.ContentAlignment.MiddleCenter;
            this.lblStatus.AutoSize = false;
            this.lblStatus.AutoEllipsis = true;
            this.lblStatus.Padding = new System.Windows.Forms.Padding(24, 6, 24, 6);

            // Assemble status area
            this.pnlStatusArea.Controls.Add(this.lblUser);
            this.pnlStatusArea.Controls.Add(this.pnlStatusDivider);
            this.pnlStatusArea.Controls.Add(this.lblStatus);

            // Assemble left panel
            this.panelLeft.Controls.Add(this.pnlScanFrame);
            this.panelLeft.Controls.Add(this.pnlScanBanner);
            this.panelLeft.Controls.Add(this.pnlStatusArea);
            this.panelWorkspace.Controls.Add(this.panelLeft);
            this.panelWorkspace.Controls.Add(this.pnlHeaderAccent);
            this.panelWorkspace.Controls.Add(this.panelHeader);

            // ── TIMERS ──────────────────────────────────────────────────────────
            this.scanTimer.Interval = 500;
            this.scanTimer.Tick += new System.EventHandler(this.scanTimer_Tick);
            this.clockTimer.Interval = 1000;
            this.clockTimer.Tick += new System.EventHandler(this.clockTimer_Tick);

            // ── ASSEMBLE FORM (order matters for DockStyle) ──────────────────────
            // DockStyle.Top panels must be added before Fill panels
            this.Controls.Add(this.panelWorkspace);
            this.Controls.Add(this.panelRight);

            ((System.ComponentModel.ISupportInitialize)(this.picBarangayLogo)).EndInit();
            this.ResumeLayout(false);
        }

        // ── Control declarations ────────────────────────────────────────────────
        private System.Windows.Forms.Panel panelHeader;
        private System.Windows.Forms.TableLayoutPanel headerLayout;
        private System.Windows.Forms.TableLayoutPanel headerClockLayout;
        private System.Windows.Forms.Label lblAppTitle;
        private System.Windows.Forms.Label lblCurrentTime;
        private System.Windows.Forms.Label lblCurrentDate;
        private System.Windows.Forms.Panel pnlHeaderAccent;
        private System.Windows.Forms.Panel panelLeft;
        private System.Windows.Forms.Panel pnlScanBanner;
        private System.Windows.Forms.Label lblScanSection;
        private System.Windows.Forms.Panel pnlScanFrame;
        private System.Windows.Forms.Panel pnlScanInner;
        private System.Windows.Forms.PictureBox picBarangayLogo;
        private System.Windows.Forms.Panel pnlStatusArea;
        private System.Windows.Forms.Label lblUser;
        private System.Windows.Forms.Panel pnlStatusDivider;
        private System.Windows.Forms.Label lblStatus;
        private System.Windows.Forms.Panel panelRight;
        private System.Windows.Forms.Panel panelWorkspace;
        private System.Windows.Forms.Panel pnlSidebarAccent;
        private System.Windows.Forms.Panel pnlSidebarContent;
        private System.Windows.Forms.Panel pnlSidebarHeader;
        private System.Windows.Forms.Label lblActionsSection;
        private System.Windows.Forms.Panel pnlSidebarDivider;
        private System.Windows.Forms.Panel pnlButtonGroup;
        private System.Windows.Forms.Button btnEnroll;
        private System.Windows.Forms.Panel pnlBtnSpacer1;
        private System.Windows.Forms.Button btnLogs;
        private System.Windows.Forms.Panel pnlBtnSpacer2;
        private System.Windows.Forms.Button btnVisitorLogbook;
        private System.Windows.Forms.Panel pnlFooterInfo;
        private System.Windows.Forms.Label lblFooterHint;
        private System.Windows.Forms.Timer scanTimer;
        private System.Windows.Forms.Timer clockTimer;
    }
}