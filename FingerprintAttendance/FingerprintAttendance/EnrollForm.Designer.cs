namespace FingerprintAttendance
{
    partial class EnrollForm
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
            this.pnlHeaderAccentBar = new System.Windows.Forms.Panel();
            this.lblTitle = new System.Windows.Forms.Label();
            this.lblSubtitle = new System.Windows.Forms.Label();
            this.panelScrollable = new System.Windows.Forms.Panel();
            this.panelBody = new System.Windows.Forms.Panel();

            // Section A — Employee Details
            this.pnlSectionA = new System.Windows.Forms.Panel();
            this.pnlSectionAAccent = new System.Windows.Forms.Panel();
            this.lblSectionA = new System.Windows.Forms.Label();
            this.pnlSectionADiv = new System.Windows.Forms.Panel();

            this.lblFirstName = new System.Windows.Forms.Label();
            this.txtFirstName = new System.Windows.Forms.TextBox();
            this.lblLastName = new System.Windows.Forms.Label();
            this.txtLastName = new System.Windows.Forms.TextBox();
            this.lblMiddleName = new System.Windows.Forms.Label();
            this.txtMiddleName = new System.Windows.Forms.TextBox();
            this.lblNameExtension = new System.Windows.Forms.Label();
            this.txtNameExtension = new System.Windows.Forms.TextBox();
            this.lblEmpId = new System.Windows.Forms.Label();
            this.txtEmpId = new System.Windows.Forms.TextBox();
            this.lblContactNumber = new System.Windows.Forms.Label();
            this.txtContactNumber = new System.Windows.Forms.TextBox();

            // Section B — Address
            this.pnlSectionB = new System.Windows.Forms.Panel();
            this.pnlSectionBAccent = new System.Windows.Forms.Panel();
            this.lblSectionB = new System.Windows.Forms.Label();
            this.pnlSectionBDiv = new System.Windows.Forms.Panel();

            this.lblRegion = new System.Windows.Forms.Label();
            this.cmbRegion = new System.Windows.Forms.ComboBox();
            this.lblProvince = new System.Windows.Forms.Label();
            this.cmbProvince = new System.Windows.Forms.ComboBox();
            this.lblCityMunicipality = new System.Windows.Forms.Label();
            this.cmbCityMunicipality = new System.Windows.Forms.ComboBox();
            this.lblBarangay = new System.Windows.Forms.Label();
            this.cmbBarangay = new System.Windows.Forms.ComboBox();
            this.lblStreetDetails = new System.Windows.Forms.Label();
            this.txtStreetDetails = new System.Windows.Forms.TextBox();
            // Section C — Fingerprint Enrollment
            this.pnlSectionC = new System.Windows.Forms.Panel();
            this.pnlSectionCAccent = new System.Windows.Forms.Panel();
            this.lblSectionC = new System.Windows.Forms.Label();
            this.pnlSectionCDiv = new System.Windows.Forms.Panel();

            this.lblScanStatus = new System.Windows.Forms.Label();
            this.lblProgress = new System.Windows.Forms.Label();
            this.progressBar = new System.Windows.Forms.ProgressBar();
            this.lblFingerSelection = new System.Windows.Forms.Label();
            this.checkedListFingers = new System.Windows.Forms.CheckedListBox();
            this.btnAddAnother = new System.Windows.Forms.Button();
            this.btnSaveEmployee = new System.Windows.Forms.Button();

            // Buttons
            this.btnStartEnroll = new System.Windows.Forms.Button();
            this.btnCancel = new System.Windows.Forms.Button();

            this.panelHeader.SuspendLayout();
            this.panelScrollable.SuspendLayout();
            this.panelBody.SuspendLayout();
            this.SuspendLayout();

            // ── FORM ────────────────────────────────────────────────────────────
            this.Text = "Enroll New Employee";
            this.ClientSize = new System.Drawing.Size(720, 780);
            this.StartPosition = System.Windows.Forms.FormStartPosition.CenterParent;
            this.FormBorderStyle = System.Windows.Forms.FormBorderStyle.Sizable;
            this.MaximizeBox = true;
            this.MinimizeBox = true;
            this.MinimumSize = new System.Drawing.Size(520, 480);
            this.AutoScaleMode = System.Windows.Forms.AutoScaleMode.Font;
            this.BackColor = System.Drawing.Color.FromArgb(236, 241, 248);

            // ── HEADER ──────────────────────────────────────────────────────────
            this.panelHeader.Location = new System.Drawing.Point(0, 0);
            this.panelHeader.Size = new System.Drawing.Size(640, 80);
            this.panelHeader.BackColor = System.Drawing.Color.FromArgb(13, 27, 42);

            this.pnlHeaderAccentBar.Location = new System.Drawing.Point(0, 0);
            this.pnlHeaderAccentBar.Size = new System.Drawing.Size(6, 80);
            this.pnlHeaderAccentBar.BackColor = System.Drawing.Color.FromArgb(5, 150, 105); // green accent for enrollment

            this.lblTitle.Location = new System.Drawing.Point(26, 10);
            this.lblTitle.Size = new System.Drawing.Size(600, 34);
            this.lblTitle.Text = "Enroll New Employee Fingerprint";
            this.lblTitle.Font = new System.Drawing.Font("Segoe UI", 15F, System.Drawing.FontStyle.Bold);
            this.lblTitle.ForeColor = System.Drawing.Color.FromArgb(240, 246, 255);
            this.lblTitle.BackColor = System.Drawing.Color.Transparent;

            this.lblSubtitle.Location = new System.Drawing.Point(28, 50);
            this.lblSubtitle.Size = new System.Drawing.Size(600, 22);
            this.lblSubtitle.Text = "Select one or more fingers, scan each 3 times, then save the employee.";
            this.lblSubtitle.Font = new System.Drawing.Font("Segoe UI", 9F);
            this.lblSubtitle.ForeColor = System.Drawing.Color.FromArgb(100, 140, 185);
            this.lblSubtitle.BackColor = System.Drawing.Color.Transparent;

            this.panelHeader.Controls.AddRange(new System.Windows.Forms.Control[] {
                this.pnlHeaderAccentBar, this.lblTitle, this.lblSubtitle });

            // ── SCROLLABLE WRAPPER ───────────────────────────────────────────────
            this.panelScrollable.Location = new System.Drawing.Point(0, 80);
            this.panelScrollable.Size = new System.Drawing.Size(640, 700);
            this.panelScrollable.AutoScroll = true;
            this.panelScrollable.BackColor = System.Drawing.Color.FromArgb(236, 241, 248);

            // ── BODY CARD ───────────────────────────────────────────────────────
            this.panelBody.Location = new System.Drawing.Point(16, 16);
            this.panelBody.Size = new System.Drawing.Size(600, 20); // height computed below
            this.panelBody.BackColor = System.Drawing.Color.White;

            // ── SECTION A: Employee Details ──────────────────────────────────────
            int y = 16;

            // Section header A
            this.pnlSectionA.Location = new System.Drawing.Point(0, y);
            this.pnlSectionA.Size = new System.Drawing.Size(600, 32);
            this.pnlSectionA.BackColor = System.Drawing.Color.FromArgb(248, 250, 252);

            this.pnlSectionAAccent.Location = new System.Drawing.Point(0, 0);
            this.pnlSectionAAccent.Size = new System.Drawing.Size(4, 32);
            this.pnlSectionAAccent.BackColor = System.Drawing.Color.FromArgb(37, 99, 235);

            this.lblSectionA.Location = new System.Drawing.Point(14, 0);
            this.lblSectionA.Size = new System.Drawing.Size(400, 32);
            this.lblSectionA.Text = "EMPLOYEE DETAILS";
            this.lblSectionA.Font = new System.Drawing.Font("Segoe UI", 8.5F, System.Drawing.FontStyle.Bold);
            this.lblSectionA.ForeColor = System.Drawing.Color.FromArgb(37, 99, 235);
            this.lblSectionA.TextAlign = System.Drawing.ContentAlignment.MiddleLeft;

            this.pnlSectionA.Controls.Add(this.pnlSectionAAccent);
            this.pnlSectionA.Controls.Add(this.lblSectionA);
            y += 32 + 4;

            // Employee Name
            int nameFieldWidth = 266;
            this.lblFirstName.Location = new System.Drawing.Point(24, y);
            this.lblFirstName.Size = new System.Drawing.Size(nameFieldWidth, 18);
            this.lblFirstName.Text = "FIRST NAME";
            this.lblFirstName.Font = new System.Drawing.Font("Segoe UI", 8.5F, System.Drawing.FontStyle.Bold);
            this.lblFirstName.ForeColor = System.Drawing.Color.FromArgb(100, 116, 139);

            this.lblLastName.Location = new System.Drawing.Point(308, y);
            this.lblLastName.Size = new System.Drawing.Size(nameFieldWidth, 18);
            this.lblLastName.Text = "LAST NAME";
            this.lblLastName.Font = new System.Drawing.Font("Segoe UI", 8.5F, System.Drawing.FontStyle.Bold);
            this.lblLastName.ForeColor = System.Drawing.Color.FromArgb(100, 116, 139);
            y += 22;

            this.txtFirstName.Location = new System.Drawing.Point(24, y);
            this.txtFirstName.Size = new System.Drawing.Size(nameFieldWidth, 32);
            this.txtFirstName.Font = new System.Drawing.Font("Segoe UI", 11F);
            this.txtFirstName.BorderStyle = System.Windows.Forms.BorderStyle.FixedSingle;
            this.txtFirstName.BackColor = System.Drawing.Color.FromArgb(248, 250, 252);
            this.txtFirstName.CharacterCasing = System.Windows.Forms.CharacterCasing.Upper;
            this.txtFirstName.MaxLength = 100;

            this.txtLastName.Location = new System.Drawing.Point(308, y);
            this.txtLastName.Size = new System.Drawing.Size(nameFieldWidth, 32);
            this.txtLastName.Font = new System.Drawing.Font("Segoe UI", 11F);
            this.txtLastName.BorderStyle = System.Windows.Forms.BorderStyle.FixedSingle;
            this.txtLastName.BackColor = System.Drawing.Color.FromArgb(248, 250, 252);
            this.txtLastName.CharacterCasing = System.Windows.Forms.CharacterCasing.Upper;
            this.txtLastName.MaxLength = 100;
            y += 44;

            this.lblMiddleName.Location = new System.Drawing.Point(24, y);
            this.lblMiddleName.Size = new System.Drawing.Size(nameFieldWidth, 18);
            this.lblMiddleName.Text = "MIDDLE NAME (OPTIONAL)";
            this.lblMiddleName.Font = new System.Drawing.Font("Segoe UI", 8.5F, System.Drawing.FontStyle.Bold);
            this.lblMiddleName.ForeColor = System.Drawing.Color.FromArgb(100, 116, 139);

            this.lblNameExtension.Location = new System.Drawing.Point(308, y);
            this.lblNameExtension.Size = new System.Drawing.Size(nameFieldWidth, 18);
            this.lblNameExtension.Text = "NAME EXTENSION (OPTIONAL: JR., SR., 1ST, 2ND, ETC.)";
            this.lblNameExtension.Font = new System.Drawing.Font("Segoe UI", 8.5F, System.Drawing.FontStyle.Bold);
            this.lblNameExtension.ForeColor = System.Drawing.Color.FromArgb(100, 116, 139);
            y += 22;

            this.txtMiddleName.Location = new System.Drawing.Point(24, y);
            this.txtMiddleName.Size = new System.Drawing.Size(nameFieldWidth, 32);
            this.txtMiddleName.Font = new System.Drawing.Font("Segoe UI", 11F);
            this.txtMiddleName.BorderStyle = System.Windows.Forms.BorderStyle.FixedSingle;
            this.txtMiddleName.BackColor = System.Drawing.Color.FromArgb(248, 250, 252);
            this.txtMiddleName.CharacterCasing = System.Windows.Forms.CharacterCasing.Upper;
            this.txtMiddleName.MaxLength = 100;

            this.txtNameExtension.Location = new System.Drawing.Point(308, y);
            this.txtNameExtension.Size = new System.Drawing.Size(nameFieldWidth, 32);
            this.txtNameExtension.Font = new System.Drawing.Font("Segoe UI", 11F);
            this.txtNameExtension.BorderStyle = System.Windows.Forms.BorderStyle.FixedSingle;
            this.txtNameExtension.BackColor = System.Drawing.Color.FromArgb(248, 250, 252);
            this.txtNameExtension.CharacterCasing = System.Windows.Forms.CharacterCasing.Upper;
            this.txtNameExtension.MaxLength = 20;
            y += 44;

            // Employee ID
            this.lblEmpId.Location = new System.Drawing.Point(24, y);
            this.lblEmpId.Size = new System.Drawing.Size(550, 18);
            this.lblEmpId.Text = "EMPLOYEE ID";
            this.lblEmpId.Font = new System.Drawing.Font("Segoe UI", 8.5F, System.Drawing.FontStyle.Bold);
            this.lblEmpId.ForeColor = System.Drawing.Color.FromArgb(100, 116, 139);
            y += 22;

            this.txtEmpId.Location = new System.Drawing.Point(24, y);
            this.txtEmpId.Size = new System.Drawing.Size(550, 32);
            this.txtEmpId.Font = new System.Drawing.Font("Segoe UI", 11F);
            this.txtEmpId.BorderStyle = System.Windows.Forms.BorderStyle.FixedSingle;
            this.txtEmpId.BackColor = System.Drawing.Color.FromArgb(248, 250, 252);
            y += 44;

            // Contact Number
            this.lblContactNumber.Location = new System.Drawing.Point(24, y);
            this.lblContactNumber.Size = new System.Drawing.Size(550, 18);
            this.lblContactNumber.Text = "CONTACT NUMBER";
            this.lblContactNumber.Font = new System.Drawing.Font("Segoe UI", 8.5F, System.Drawing.FontStyle.Bold);
            this.lblContactNumber.ForeColor = System.Drawing.Color.FromArgb(100, 116, 139);
            y += 22;

            this.txtContactNumber.Location = new System.Drawing.Point(24, y);
            this.txtContactNumber.Size = new System.Drawing.Size(550, 32);
            this.txtContactNumber.Font = new System.Drawing.Font("Segoe UI", 11F);
            this.txtContactNumber.BorderStyle = System.Windows.Forms.BorderStyle.FixedSingle;
            this.txtContactNumber.BackColor = System.Drawing.Color.FromArgb(248, 250, 252);
            y += 52;

            // ── SECTION B: Address ───────────────────────────────────────────────
            this.pnlSectionB.Location = new System.Drawing.Point(0, y);
            this.pnlSectionB.Size = new System.Drawing.Size(600, 32);
            this.pnlSectionB.BackColor = System.Drawing.Color.FromArgb(248, 250, 252);

            this.pnlSectionBAccent.Location = new System.Drawing.Point(0, 0);
            this.pnlSectionBAccent.Size = new System.Drawing.Size(4, 32);
            this.pnlSectionBAccent.BackColor = System.Drawing.Color.FromArgb(8, 145, 178);

            this.lblSectionB.Location = new System.Drawing.Point(14, 0);
            this.lblSectionB.Size = new System.Drawing.Size(400, 32);
            this.lblSectionB.Text = "ADDRESS INFORMATION";
            this.lblSectionB.Font = new System.Drawing.Font("Segoe UI", 8.5F, System.Drawing.FontStyle.Bold);
            this.lblSectionB.ForeColor = System.Drawing.Color.FromArgb(8, 145, 178);
            this.lblSectionB.TextAlign = System.Drawing.ContentAlignment.MiddleLeft;

            this.pnlSectionB.Controls.Add(this.pnlSectionBAccent);
            this.pnlSectionB.Controls.Add(this.lblSectionB);
            y += 32 + 4;

            int addressFieldsStartY = y;

            // Region
            this.lblRegion.Location = new System.Drawing.Point(24, y);
            this.lblRegion.Size = new System.Drawing.Size(550, 18);
            this.lblRegion.Text = "REGION";
            this.lblRegion.Font = new System.Drawing.Font("Segoe UI", 8.5F, System.Drawing.FontStyle.Bold);
            this.lblRegion.ForeColor = System.Drawing.Color.FromArgb(100, 116, 139);
            y += 22;

            this.cmbRegion.Location = new System.Drawing.Point(24, y);
            this.cmbRegion.Size = new System.Drawing.Size(550, 32);
            this.cmbRegion.Font = new System.Drawing.Font("Segoe UI", 10.5F);
            this.cmbRegion.DropDownStyle = System.Windows.Forms.ComboBoxStyle.DropDownList;
            this.cmbRegion.IntegralHeight = false;
            this.cmbRegion.MaxDropDownItems = 5;
            this.cmbRegion.BackColor = System.Drawing.Color.FromArgb(248, 250, 252);
            this.cmbRegion.Enabled = false;
            y += 44;

            // Province
            this.lblProvince.Location = new System.Drawing.Point(24, y);
            this.lblProvince.Size = new System.Drawing.Size(550, 18);
            this.lblProvince.Text = "PROVINCE";
            this.lblProvince.Font = new System.Drawing.Font("Segoe UI", 8.5F, System.Drawing.FontStyle.Bold);
            this.lblProvince.ForeColor = System.Drawing.Color.FromArgb(100, 116, 139);
            y += 22;

            this.cmbProvince.Location = new System.Drawing.Point(24, y);
            this.cmbProvince.Size = new System.Drawing.Size(550, 32);
            this.cmbProvince.Font = new System.Drawing.Font("Segoe UI", 10.5F);
            this.cmbProvince.DropDownStyle = System.Windows.Forms.ComboBoxStyle.DropDownList;
            this.cmbProvince.IntegralHeight = false;
            this.cmbProvince.MaxDropDownItems = 5;
            this.cmbProvince.BackColor = System.Drawing.Color.FromArgb(248, 250, 252);
            this.cmbProvince.Enabled = false;
            y += 44;

            // City/Municipality
            this.lblCityMunicipality.Location = new System.Drawing.Point(24, y);
            this.lblCityMunicipality.Size = new System.Drawing.Size(550, 18);
            this.lblCityMunicipality.Text = "CITY / MUNICIPALITY";
            this.lblCityMunicipality.Font = new System.Drawing.Font("Segoe UI", 8.5F, System.Drawing.FontStyle.Bold);
            this.lblCityMunicipality.ForeColor = System.Drawing.Color.FromArgb(100, 116, 139);
            y += 22;

            this.cmbCityMunicipality.Location = new System.Drawing.Point(24, y);
            this.cmbCityMunicipality.Size = new System.Drawing.Size(550, 32);
            this.cmbCityMunicipality.Font = new System.Drawing.Font("Segoe UI", 10.5F);
            this.cmbCityMunicipality.DropDownStyle = System.Windows.Forms.ComboBoxStyle.DropDownList;
            this.cmbCityMunicipality.IntegralHeight = false;
            this.cmbCityMunicipality.MaxDropDownItems = 5;
            this.cmbCityMunicipality.BackColor = System.Drawing.Color.FromArgb(248, 250, 252);
            this.cmbCityMunicipality.Enabled = false;
            y += 44;

            // Barangay
            this.lblBarangay.Location = new System.Drawing.Point(24, y);
            this.lblBarangay.Size = new System.Drawing.Size(550, 18);
            this.lblBarangay.Text = "BARANGAY";
            this.lblBarangay.Font = new System.Drawing.Font("Segoe UI", 8.5F, System.Drawing.FontStyle.Bold);
            this.lblBarangay.ForeColor = System.Drawing.Color.FromArgb(100, 116, 139);
            y += 22;

            this.cmbBarangay.Location = new System.Drawing.Point(24, y);
            this.cmbBarangay.Size = new System.Drawing.Size(550, 32);
            this.cmbBarangay.Font = new System.Drawing.Font("Segoe UI", 10.5F);
            this.cmbBarangay.DropDownStyle = System.Windows.Forms.ComboBoxStyle.DropDownList;
            this.cmbBarangay.IntegralHeight = false;
            this.cmbBarangay.MaxDropDownItems = 5;
            this.cmbBarangay.BackColor = System.Drawing.Color.FromArgb(248, 250, 252);
            this.cmbBarangay.Enabled = false;
            y += 44;

            // Street/House No.
            this.lblStreetDetails.Location = new System.Drawing.Point(24, y);
            this.lblStreetDetails.Size = new System.Drawing.Size(550, 18);
            this.lblStreetDetails.Text = "PUROK / STREET / HOUSE NO.";
            this.lblStreetDetails.Font = new System.Drawing.Font("Segoe UI", 8.5F, System.Drawing.FontStyle.Bold);
            this.lblStreetDetails.ForeColor = System.Drawing.Color.FromArgb(100, 116, 139);
            y += 22;

            this.txtStreetDetails.Location = new System.Drawing.Point(24, y);
            this.txtStreetDetails.Size = new System.Drawing.Size(550, 32);
            this.txtStreetDetails.Font = new System.Drawing.Font("Segoe UI", 11F);
            this.txtStreetDetails.BorderStyle = System.Windows.Forms.BorderStyle.FixedSingle;
            this.txtStreetDetails.BackColor = System.Drawing.Color.FromArgb(248, 250, 252);
            y += 52;

            // ── SECTION C: Fingerprint Enrollment ───────────────────────────────
            this.pnlSectionC.Location = new System.Drawing.Point(0, y);
            this.pnlSectionC.Size = new System.Drawing.Size(600, 32);
            this.pnlSectionC.BackColor = System.Drawing.Color.FromArgb(248, 250, 252);

            this.pnlSectionCAccent.Location = new System.Drawing.Point(0, 0);
            this.pnlSectionCAccent.Size = new System.Drawing.Size(4, 32);
            this.pnlSectionCAccent.BackColor = System.Drawing.Color.FromArgb(5, 150, 105);

            this.lblSectionC.Location = new System.Drawing.Point(14, 0);
            this.lblSectionC.Size = new System.Drawing.Size(400, 32);
            this.lblSectionC.Text = "FINGERPRINT ENROLLMENT";
            this.lblSectionC.Font = new System.Drawing.Font("Segoe UI", 8.5F, System.Drawing.FontStyle.Bold);
            this.lblSectionC.ForeColor = System.Drawing.Color.FromArgb(5, 150, 105);
            this.lblSectionC.TextAlign = System.Drawing.ContentAlignment.MiddleLeft;

            this.pnlSectionC.Controls.Add(this.pnlSectionCAccent);
            this.pnlSectionC.Controls.Add(this.lblSectionC);
            y += 32 + 8;

            this.lblFingerSelection.Location = new System.Drawing.Point(24, y);
            this.lblFingerSelection.Size = new System.Drawing.Size(550, 20);
            this.lblFingerSelection.Text = "SELECT FINGERS TO ENROLL";
            this.lblFingerSelection.Font = new System.Drawing.Font("Segoe UI", 8.5F, System.Drawing.FontStyle.Bold);
            this.lblFingerSelection.ForeColor = System.Drawing.Color.FromArgb(100, 116, 139);
            y += 22;

            this.checkedListFingers.Location = new System.Drawing.Point(24, y);
            this.checkedListFingers.Size = new System.Drawing.Size(550, 94);
            this.checkedListFingers.CheckOnClick = true;
            this.checkedListFingers.ColumnWidth = 180;
            this.checkedListFingers.MultiColumn = true;
            this.checkedListFingers.IntegralHeight = false;
            this.checkedListFingers.Items.AddRange(new object[] {
                "Left Thumb", "Left Index", "Left Middle", "Left Ring", "Left Pinky",
                "Right Thumb", "Right Index", "Right Middle", "Right Ring", "Right Pinky"
            });
            this.checkedListFingers.SetItemChecked(6, true);
            this.checkedListFingers.ItemCheck += new System.Windows.Forms.ItemCheckEventHandler(this.checkedListFingers_ItemCheck);
            y += 104;

            // Scan status display
            this.lblScanStatus.Location = new System.Drawing.Point(24, y);
            this.lblScanStatus.Size = new System.Drawing.Size(550, 54);
            this.lblScanStatus.Text = "Select one or more fingers, then start enrollment.";
            this.lblScanStatus.Font = new System.Drawing.Font("Consolas", 10F);
            this.lblScanStatus.ForeColor = System.Drawing.Color.FromArgb(37, 99, 235);
            this.lblScanStatus.BackColor = System.Drawing.Color.FromArgb(239, 246, 255);
            this.lblScanStatus.TextAlign = System.Drawing.ContentAlignment.MiddleCenter;
            y += 66;

            // Progress label
            this.lblProgress.Location = new System.Drawing.Point(24, y);
            this.lblProgress.Size = new System.Drawing.Size(550, 20);
            this.lblProgress.Text = "SCAN PROGRESS  (0 of 0 scans completed)";
            this.lblProgress.Font = new System.Drawing.Font("Segoe UI", 8.5F, System.Drawing.FontStyle.Bold);
            this.lblProgress.ForeColor = System.Drawing.Color.FromArgb(100, 116, 139);
            y += 24;

            // Progress bar
            this.progressBar.Location = new System.Drawing.Point(24, y);
            this.progressBar.Size = new System.Drawing.Size(550, 14);
            this.progressBar.Maximum = 3;
            this.progressBar.Value = 0;
            this.progressBar.Style = System.Windows.Forms.ProgressBarStyle.Continuous;
            this.progressBar.BackColor = System.Drawing.Color.FromArgb(226, 232, 240);
            this.progressBar.ForeColor = System.Drawing.Color.FromArgb(5, 150, 105);
            y += 30;

            this.btnAddAnother.Location = new System.Drawing.Point(24, y);
            this.btnAddAnother.Size = new System.Drawing.Size(265, 38);
            this.btnAddAnother.Text = "Add Another Finger";
            this.btnAddAnother.Enabled = false;
            this.btnAddAnother.Click += new System.EventHandler(this.btnAddAnother_Click);

            this.btnSaveEmployee.Location = new System.Drawing.Point(309, y);
            this.btnSaveEmployee.Size = new System.Drawing.Size(265, 38);
            this.btnSaveEmployee.Text = "Save Employee";
            this.btnSaveEmployee.Enabled = false;
            this.btnSaveEmployee.Click += new System.EventHandler(this.btnSaveEmployee_Click);
            y += 48;

            // Finalize body panel height
            this.panelBody.Height = y + 16;

            // Add all controls to body
            this.panelBody.Controls.AddRange(new System.Windows.Forms.Control[] {
                this.pnlSectionA,
                this.lblFirstName, this.txtFirstName,
                this.lblLastName, this.txtLastName,
                this.lblMiddleName, this.txtMiddleName,
                this.lblNameExtension, this.txtNameExtension,
                this.lblEmpId, this.txtEmpId,
                this.lblContactNumber, this.txtContactNumber,
                this.pnlSectionB,
                this.lblRegion, this.cmbRegion,
                this.lblProvince, this.cmbProvince,
                this.lblCityMunicipality, this.cmbCityMunicipality,
                this.lblBarangay, this.cmbBarangay,
                this.lblStreetDetails, this.txtStreetDetails,
                this.pnlSectionC,
                this.lblFingerSelection, this.checkedListFingers,
                this.lblScanStatus, this.lblProgress, this.progressBar,
                this.btnAddAnother, this.btnSaveEmployee
            });

            // ── BUTTONS ─────────────────────────────────────────────────────────
            int btnY = this.panelBody.Bottom + 16;

            this.btnStartEnroll.Location = new System.Drawing.Point(16, btnY);
            this.btnStartEnroll.Size = new System.Drawing.Size(296, 48);
            this.btnStartEnroll.Text = "▶  Start Enrollment";
            this.btnStartEnroll.Font = new System.Drawing.Font("Segoe UI", 10.5F, System.Drawing.FontStyle.Bold);
            this.btnStartEnroll.BackColor = System.Drawing.Color.FromArgb(5, 150, 105);
            this.btnStartEnroll.ForeColor = System.Drawing.Color.White;
            this.btnStartEnroll.FlatStyle = System.Windows.Forms.FlatStyle.Flat;
            this.btnStartEnroll.FlatAppearance.BorderSize = 0;
            this.btnStartEnroll.FlatAppearance.MouseOverBackColor = System.Drawing.Color.FromArgb(4, 120, 87);
            this.btnStartEnroll.FlatAppearance.MouseDownBackColor = System.Drawing.Color.FromArgb(3, 94, 69);
            this.btnStartEnroll.Cursor = System.Windows.Forms.Cursors.Hand;
            this.btnStartEnroll.Click += new System.EventHandler(this.btnStartEnroll_Click);

            this.btnCancel.Location = new System.Drawing.Point(322, btnY);
            this.btnCancel.Size = new System.Drawing.Size(294, 48);
            this.btnCancel.Text = "Cancel";
            this.btnCancel.Font = new System.Drawing.Font("Segoe UI", 10.5F, System.Drawing.FontStyle.Bold);
            this.btnCancel.BackColor = System.Drawing.Color.FromArgb(22, 40, 62);
            this.btnCancel.ForeColor = System.Drawing.Color.FromArgb(180, 210, 240);
            this.btnCancel.FlatStyle = System.Windows.Forms.FlatStyle.Flat;
            this.btnCancel.FlatAppearance.BorderSize = 1;
            this.btnCancel.FlatAppearance.BorderColor = System.Drawing.Color.FromArgb(50, 80, 120);
            this.btnCancel.FlatAppearance.MouseOverBackColor = System.Drawing.Color.FromArgb(30, 55, 85);
            this.btnCancel.FlatAppearance.MouseDownBackColor = System.Drawing.Color.FromArgb(15, 35, 60);
            this.btnCancel.Cursor = System.Windows.Forms.Cursors.Hand;
            this.btnCancel.Click += new System.EventHandler(this.btnCancel_Click);

            int scrollableContentHeight = btnY + 48 + 24;
            this.panelScrollable.Controls.AddRange(new System.Windows.Forms.Control[] {
                this.panelBody, this.btnStartEnroll, this.btnCancel });
            this.panelScrollable.AutoScrollMinSize = new System.Drawing.Size(0, scrollableContentHeight);

            this.Controls.AddRange(new System.Windows.Forms.Control[] {
                this.panelHeader, this.panelScrollable });

            this.panelHeader.ResumeLayout(false);
            this.panelScrollable.ResumeLayout(false);
            this.panelBody.ResumeLayout(false);
            this.ResumeLayout(false);
        }

        private System.Windows.Forms.Panel panelHeader;
        private System.Windows.Forms.Panel pnlHeaderAccentBar;
        private System.Windows.Forms.Label lblTitle;
        private System.Windows.Forms.Label lblSubtitle;
        private System.Windows.Forms.Panel panelScrollable;
        private System.Windows.Forms.Panel panelBody;
        private System.Windows.Forms.Panel pnlSectionA;
        private System.Windows.Forms.Panel pnlSectionAAccent;
        private System.Windows.Forms.Label lblSectionA;
        private System.Windows.Forms.Panel pnlSectionADiv;
        private System.Windows.Forms.Label lblFirstName;
        private System.Windows.Forms.TextBox txtFirstName;
        private System.Windows.Forms.Label lblLastName;
        private System.Windows.Forms.TextBox txtLastName;
        private System.Windows.Forms.Label lblMiddleName;
        private System.Windows.Forms.TextBox txtMiddleName;
        private System.Windows.Forms.Label lblNameExtension;
        private System.Windows.Forms.TextBox txtNameExtension;
        private System.Windows.Forms.Label lblEmpId;
        private System.Windows.Forms.TextBox txtEmpId;
        private System.Windows.Forms.Label lblContactNumber;
        private System.Windows.Forms.TextBox txtContactNumber;
        private System.Windows.Forms.Panel pnlSectionB;
        private System.Windows.Forms.Panel pnlSectionBAccent;
        private System.Windows.Forms.Label lblSectionB;
        private System.Windows.Forms.Panel pnlSectionBDiv;
        private System.Windows.Forms.Label lblRegion;
        private System.Windows.Forms.ComboBox cmbRegion;
        private System.Windows.Forms.Label lblProvince;
        private System.Windows.Forms.ComboBox cmbProvince;
        private System.Windows.Forms.Label lblCityMunicipality;
        private System.Windows.Forms.ComboBox cmbCityMunicipality;
        private System.Windows.Forms.Label lblBarangay;
        private System.Windows.Forms.ComboBox cmbBarangay;
        private System.Windows.Forms.Label lblStreetDetails;
        private System.Windows.Forms.TextBox txtStreetDetails;
        private System.Windows.Forms.Panel pnlSectionC;
        private System.Windows.Forms.Panel pnlSectionCAccent;
        private System.Windows.Forms.Label lblSectionC;
        private System.Windows.Forms.Panel pnlSectionCDiv;
        private System.Windows.Forms.Label lblScanStatus;
        private System.Windows.Forms.Label lblProgress;
        private System.Windows.Forms.ProgressBar progressBar;
        private System.Windows.Forms.Label lblFingerSelection;
        private System.Windows.Forms.CheckedListBox checkedListFingers;
        private System.Windows.Forms.Button btnAddAnother;
        private System.Windows.Forms.Button btnSaveEmployee;
        private System.Windows.Forms.Button btnStartEnroll;
        private System.Windows.Forms.Button btnCancel;
    }
}