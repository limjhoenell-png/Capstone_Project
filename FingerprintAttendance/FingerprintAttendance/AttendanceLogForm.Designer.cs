namespace FingerprintAttendance
{
    partial class AttendanceLogForm
    {
        private System.ComponentModel.IContainer components = null;

        protected override void Dispose(bool disposing)
        {
            if (disposing && (components != null)) components.Dispose();
            base.Dispose(disposing);
        }

        private void InitializeComponent()
        {
            this.panelHeader = new System.Windows.Forms.Panel();
            this.pnlHeaderLeft = new System.Windows.Forms.Panel();
            this.lblTitle = new System.Windows.Forms.Label();
            this.lblTitleSub = new System.Windows.Forms.Label();
            this.pnlHeaderAccent = new System.Windows.Forms.Panel();
            this.panelFilter = new System.Windows.Forms.Panel();
            this.pnlFilterAccent = new System.Windows.Forms.Panel();
            this.pnlFilterInner = new System.Windows.Forms.Panel();
            this.lblDate = new System.Windows.Forms.Label();
            this.datePicker = new System.Windows.Forms.DateTimePicker();
            this.btnLoad = new System.Windows.Forms.Button();
            this.pnlGridSection = new System.Windows.Forms.Panel();
            this.lblGridSection = new System.Windows.Forms.Label();
            this.dgvLogs = new System.Windows.Forms.DataGridView();

            ((System.ComponentModel.ISupportInitialize)(this.dgvLogs)).BeginInit();
            this.SuspendLayout();

            // ── FORM ────────────────────────────────────────────────────────────
            this.Text = "Attendance Log";
            this.Size = new System.Drawing.Size(1000, 680);
            this.MinimumSize = new System.Drawing.Size(720, 480);
            this.StartPosition = System.Windows.Forms.FormStartPosition.CenterParent;
            this.FormBorderStyle = System.Windows.Forms.FormBorderStyle.Sizable;
            this.MaximizeBox = true;
            this.MinimizeBox = true;
            this.AutoScaleMode = System.Windows.Forms.AutoScaleMode.Font;
            this.BackColor = System.Drawing.Color.FromArgb(236, 241, 248);
            this.Load += new System.EventHandler(this.AttendanceLogForm_Load);

            // ── HEADER ──────────────────────────────────────────────────────────
            this.panelHeader.Location = new System.Drawing.Point(0, 0);
            this.panelHeader.Size = new System.Drawing.Size(1000, 80);
            this.panelHeader.BackColor = System.Drawing.Color.FromArgb(13, 27, 42);

            this.pnlHeaderLeft.Location = new System.Drawing.Point(0, 0);
            this.pnlHeaderLeft.Size = new System.Drawing.Size(6, 80);
            this.pnlHeaderLeft.BackColor = System.Drawing.Color.FromArgb(37, 99, 235);

            this.lblTitle.Location = new System.Drawing.Point(26, 10);
            this.lblTitle.Size = new System.Drawing.Size(700, 34);
            this.lblTitle.Text = "Employee Attendance Records";
            this.lblTitle.Font = new System.Drawing.Font("Segoe UI", 15F, System.Drawing.FontStyle.Bold);
            this.lblTitle.ForeColor = System.Drawing.Color.FromArgb(240, 246, 255);
            this.lblTitle.BackColor = System.Drawing.Color.Transparent;

            this.lblTitleSub.Location = new System.Drawing.Point(28, 48);
            this.lblTitleSub.Size = new System.Drawing.Size(700, 22);
            this.lblTitleSub.Text = "View and filter daily attendance entries by date · Click any row for details";
            this.lblTitleSub.Font = new System.Drawing.Font("Segoe UI", 9F);
            this.lblTitleSub.ForeColor = System.Drawing.Color.FromArgb(100, 140, 185);
            this.lblTitleSub.BackColor = System.Drawing.Color.Transparent;

            this.panelHeader.Controls.AddRange(new System.Windows.Forms.Control[] {
                this.pnlHeaderLeft, this.lblTitle, this.lblTitleSub });

            // Header accent line
            this.pnlHeaderAccent.Location = new System.Drawing.Point(0, 80);
            this.pnlHeaderAccent.Size = new System.Drawing.Size(1000, 3);
            this.pnlHeaderAccent.BackColor = System.Drawing.Color.FromArgb(37, 99, 235);

            // ── FILTER BAR ──────────────────────────────────────────────────────
            this.panelFilter.Location = new System.Drawing.Point(18, 100);
            this.panelFilter.Size = new System.Drawing.Size(960, 64);
            this.panelFilter.BackColor = System.Drawing.Color.White;

            // Left accent on filter bar
            this.pnlFilterAccent.Location = new System.Drawing.Point(0, 0);
            this.pnlFilterAccent.Size = new System.Drawing.Size(4, 64);
            this.pnlFilterAccent.BackColor = System.Drawing.Color.FromArgb(37, 99, 235);

            // Inner filter controls (offset for the accent bar)
            this.pnlFilterInner.Location = new System.Drawing.Point(4, 0);
            this.pnlFilterInner.Size = new System.Drawing.Size(956, 64);
            this.pnlFilterInner.BackColor = System.Drawing.Color.Transparent;

            this.lblDate.Location = new System.Drawing.Point(20, 22);
            this.lblDate.Size = new System.Drawing.Size(90, 20);
            this.lblDate.Text = "FILTER DATE";
            this.lblDate.Font = new System.Drawing.Font("Segoe UI", 8.5F, System.Drawing.FontStyle.Bold);
            this.lblDate.ForeColor = System.Drawing.Color.FromArgb(100, 116, 139);
            this.lblDate.TextAlign = System.Drawing.ContentAlignment.MiddleLeft;

            this.datePicker.Location = new System.Drawing.Point(122, 17);
            this.datePicker.Size = new System.Drawing.Size(175, 30);
            this.datePicker.Font = new System.Drawing.Font("Segoe UI", 10F);
            this.datePicker.Format = System.Windows.Forms.DateTimePickerFormat.Short;

            this.btnLoad.Location = new System.Drawing.Point(312, 14);
            this.btnLoad.Size = new System.Drawing.Size(160, 36);
            this.btnLoad.Text = "Load Records";
            this.btnLoad.Font = new System.Drawing.Font("Segoe UI", 10F, System.Drawing.FontStyle.Bold);
            this.btnLoad.BackColor = System.Drawing.Color.FromArgb(37, 99, 235);
            this.btnLoad.ForeColor = System.Drawing.Color.White;
            this.btnLoad.FlatStyle = System.Windows.Forms.FlatStyle.Flat;
            this.btnLoad.FlatAppearance.BorderSize = 0;
            this.btnLoad.FlatAppearance.MouseOverBackColor = System.Drawing.Color.FromArgb(29, 78, 216);
            this.btnLoad.FlatAppearance.MouseDownBackColor = System.Drawing.Color.FromArgb(23, 64, 180);
            this.btnLoad.Cursor = System.Windows.Forms.Cursors.Hand;
            this.btnLoad.Click += new System.EventHandler(this.btnLoad_Click);

            this.pnlFilterInner.Controls.AddRange(new System.Windows.Forms.Control[] {
                this.lblDate, this.datePicker, this.btnLoad });
            this.panelFilter.Controls.AddRange(new System.Windows.Forms.Control[] {
                this.pnlFilterAccent, this.pnlFilterInner });

            // ── GRID SECTION LABEL ───────────────────────────────────────────────
            this.pnlGridSection.Location = new System.Drawing.Point(18, 176);
            this.pnlGridSection.Size = new System.Drawing.Size(960, 30);
            this.pnlGridSection.BackColor = System.Drawing.Color.Transparent;

            this.lblGridSection.Location = new System.Drawing.Point(0, 0);
            this.lblGridSection.Size = new System.Drawing.Size(400, 30);
            this.lblGridSection.Text = "ATTENDANCE ENTRIES";
            this.lblGridSection.Font = new System.Drawing.Font("Segoe UI", 8F, System.Drawing.FontStyle.Bold);
            this.lblGridSection.ForeColor = System.Drawing.Color.FromArgb(100, 116, 139);
            this.lblGridSection.TextAlign = System.Drawing.ContentAlignment.BottomLeft;

            this.pnlGridSection.Controls.Add(this.lblGridSection);

            // ── DATA GRID ───────────────────────────────────────────────────────
            this.dgvLogs.Location = new System.Drawing.Point(18, 210);
            this.dgvLogs.Size = new System.Drawing.Size(960, 432);

            this.dgvLogs.BackgroundColor = System.Drawing.Color.White;
            this.dgvLogs.BorderStyle = System.Windows.Forms.BorderStyle.None;
            this.dgvLogs.GridColor = System.Drawing.Color.FromArgb(226, 232, 240);
            this.dgvLogs.ColumnHeadersHeight = 44;
            this.dgvLogs.ColumnHeadersDefaultCellStyle.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Bold);
            this.dgvLogs.ColumnHeadersDefaultCellStyle.BackColor = System.Drawing.Color.FromArgb(248, 250, 252);
            this.dgvLogs.ColumnHeadersDefaultCellStyle.ForeColor = System.Drawing.Color.FromArgb(51, 65, 85);
            this.dgvLogs.ColumnHeadersDefaultCellStyle.SelectionBackColor = System.Drawing.Color.FromArgb(248, 250, 252);
            this.dgvLogs.ColumnHeadersDefaultCellStyle.Padding = new System.Windows.Forms.Padding(10, 0, 0, 0);

            this.dgvLogs.RowTemplate.Height = 36;
            this.dgvLogs.Font = new System.Drawing.Font("Segoe UI", 10F);
            this.dgvLogs.DefaultCellStyle.ForeColor = System.Drawing.Color.FromArgb(30, 41, 59);
            this.dgvLogs.DefaultCellStyle.SelectionBackColor = System.Drawing.Color.FromArgb(219, 234, 254);
            this.dgvLogs.DefaultCellStyle.SelectionForeColor = System.Drawing.Color.FromArgb(30, 41, 59);
            this.dgvLogs.DefaultCellStyle.Padding = new System.Windows.Forms.Padding(10, 0, 0, 0);
            this.dgvLogs.AlternatingRowsDefaultCellStyle.BackColor = System.Drawing.Color.FromArgb(248, 250, 252);

            this.dgvLogs.AllowUserToAddRows = false;
            this.dgvLogs.ReadOnly = true;
            this.dgvLogs.SelectionMode = System.Windows.Forms.DataGridViewSelectionMode.FullRowSelect;
            this.dgvLogs.AutoSizeColumnsMode = System.Windows.Forms.DataGridViewAutoSizeColumnsMode.Fill;
            this.dgvLogs.EnableHeadersVisualStyles = false;
            this.dgvLogs.CellBorderStyle = System.Windows.Forms.DataGridViewCellBorderStyle.SingleHorizontal;
            this.dgvLogs.RowHeadersVisible = false;

            // ── DOCK LAYOUT ──────────────────────────────────────────────────────
            this.panelHeader.Dock = System.Windows.Forms.DockStyle.Top;
            this.pnlHeaderLeft.Dock = System.Windows.Forms.DockStyle.Left;
            this.lblTitle.Dock = System.Windows.Forms.DockStyle.Top;
            this.lblTitle.Height = 40;
            this.lblTitle.Padding = new System.Windows.Forms.Padding(20, 8, 12, 0);
            this.lblTitle.AutoEllipsis = true;
            this.lblTitleSub.Dock = System.Windows.Forms.DockStyle.Fill;
            this.lblTitleSub.Padding = new System.Windows.Forms.Padding(22, 0, 12, 6);
            this.lblTitleSub.AutoEllipsis = true;
            this.panelHeader.Controls.Clear();
            this.panelHeader.Controls.Add(this.lblTitleSub);
            this.panelHeader.Controls.Add(this.lblTitle);
            this.panelHeader.Controls.Add(this.pnlHeaderLeft);

            this.pnlHeaderAccent.Dock = System.Windows.Forms.DockStyle.Top;
            this.pnlHeaderAccent.Height = 3;

            this.panelFilter.Dock = System.Windows.Forms.DockStyle.Top;
            this.pnlFilterAccent.Dock = System.Windows.Forms.DockStyle.Left;
            this.pnlFilterInner.Dock = System.Windows.Forms.DockStyle.Fill;

            this.pnlGridSection.Dock = System.Windows.Forms.DockStyle.Top;
            this.lblGridSection.Dock = System.Windows.Forms.DockStyle.Fill;

            this.dgvLogs.Dock = System.Windows.Forms.DockStyle.Fill;

            // Fill is added first so the header docks against the form edge.
            this.Controls.Add(this.dgvLogs);
            this.Controls.Add(this.pnlGridSection);
            this.Controls.Add(this.panelFilter);
            this.Controls.Add(this.pnlHeaderAccent);
            this.Controls.Add(this.panelHeader);

            ((System.ComponentModel.ISupportInitialize)(this.dgvLogs)).EndInit();
            this.ResumeLayout(false);
        }

        private System.Windows.Forms.Panel panelHeader;
        private System.Windows.Forms.Panel pnlHeaderLeft;
        private System.Windows.Forms.Label lblTitle;
        private System.Windows.Forms.Label lblTitleSub;
        private System.Windows.Forms.Panel pnlHeaderAccent;
        private System.Windows.Forms.Panel panelFilter;
        private System.Windows.Forms.Panel pnlFilterAccent;
        private System.Windows.Forms.Panel pnlFilterInner;
        private System.Windows.Forms.Label lblDate;
        private System.Windows.Forms.DateTimePicker datePicker;
        private System.Windows.Forms.Button btnLoad;
        private System.Windows.Forms.Panel pnlGridSection;
        private System.Windows.Forms.Label lblGridSection;
        private System.Windows.Forms.DataGridView dgvLogs;
    }
}