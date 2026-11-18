using FingerprintAttendance.Core;
using System;
using System.Collections.Generic;
using System.Drawing;
using System.Linq;
using System.Threading.Tasks;
using System.Windows.Forms;

namespace FingerprintAttendance
{
    public partial class EnrollForm : Form
    {
        private const string DefaultRegionName = "Region XI (Davao Region)";
        private const string DefaultProvinceName = "Davao del Sur";
        private const string DefaultCityName = "City of Digos";
        private const string DefaultBarangayName = "San Jose";

        private FingerprintEngine _engine;
        private DatabaseHelper _db;
        private readonly Dictionary<string, byte[]> _fingerTemplates =
            new Dictionary<string, byte[]>(StringComparer.OrdinalIgnoreCase);
        private bool _isCapturing;
        private bool _suppressLocationEvents;
        private readonly ContactNumberField _contactField = new ContactNumberField();
        private readonly Action<bool> _playResultSound;

        private readonly ScrollBodyLayout _scrollLayout = new ScrollBodyLayout();

        public EnrollForm(FingerprintEngine engine, DatabaseHelper db, Action<bool> playResultSound)
        {
            InitializeComponent();
            txtStreetDetails.CharacterCasing = CharacterCasing.Upper;
            txtStreetDetails.TextChanged += (_, __) =>
            {
                string uppercase = txtStreetDetails.Text.ToUpperInvariant();
                if (txtStreetDetails.Text == uppercase)
                    return;

                int selectionStart = txtStreetDetails.SelectionStart;
                int selectionLength = txtStreetDetails.SelectionLength;
                txtStreetDetails.Text = uppercase;
                txtStreetDetails.Select(
                    Math.Min(selectionStart, uppercase.Length),
                    Math.Min(selectionLength, uppercase.Length - Math.Min(selectionStart, uppercase.Length)));
            };
            DesktopTheme.Apply(this);
            panelHeader.BackColor = Color.FromArgb(13, 20, 38);
            lblTitle.ForeColor = Color.White;
            lblSubtitle.ForeColor = Color.White;
            pnlSectionA.BackColor = Color.FromArgb(10, 17, 40);
            lblSectionA.ForeColor = Color.White;
            pnlSectionB.BackColor = Color.FromArgb(10, 17, 40);
            lblSectionB.ForeColor = Color.White;
            pnlSectionC.BackColor = Color.FromArgb(10, 17, 40);
            lblSectionC.ForeColor = Color.White;
            _engine = engine;
            _db = db;
            _playResultSound = playResultSound;
            PrepareResponsiveLayout();
            ReplaceContactNumberField();
            Load += (_, __) => LoadRegions();
            cmbRegion.SelectedIndexChanged += (_, __) => OnRegionChanged();
            cmbProvince.SelectedIndexChanged += (_, __) => OnProvinceChanged();
            cmbCityMunicipality.SelectedIndexChanged += (_, __) => OnCityChanged();

            KeyPreview = true;
            if (btnCancel != null)
                CancelButton = btnCancel;
            KeyDown += (_, e) =>
            {
                if (e.KeyCode != Keys.Escape) return;
                Close();
            };
            RefreshEnrollmentControls();
        }

        private void PrepareResponsiveLayout()
        {
            AutoScaleMode = AutoScaleMode.Font;
            FormBorderStyle = FormBorderStyle.Sizable;
            MaximizeBox = true;
            MinimizeBox = true;
            SizeGripStyle = SizeGripStyle.Show;

            pnlHeaderAccentBar.Dock = DockStyle.Left;
            lblTitle.AutoSize = false;
            lblTitle.Dock = DockStyle.Top;
            lblTitle.Height = 42;
            lblTitle.Padding = new Padding(20, 8, 12, 0);
            lblSubtitle.Dock = DockStyle.Fill;
            lblSubtitle.Padding = new Padding(22, 0, 12, 8);
            lblSubtitle.TextAlign = ContentAlignment.MiddleLeft;
            panelHeader.Dock = DockStyle.Top;
            panelHeader.Controls.Clear();
            panelHeader.Controls.Add(lblSubtitle);
            panelHeader.Controls.Add(lblTitle);
            panelHeader.Controls.Add(pnlHeaderAccentBar);

            panelScrollable.Dock = DockStyle.Fill;
            panelScrollable.Controls.Remove(btnStartEnroll);
            panelScrollable.Controls.Remove(btnCancel);
            ResponsiveLayout.PinActionButtons(this, btnStartEnroll, btnCancel);
            Controls.SetChildIndex(panelScrollable, 0);
            Controls.SetChildIndex(panelHeader, Controls.Count - 1);

            _scrollLayout.Attach(this, panelScrollable, panelBody, null, null, null);
        }

        private void ReplaceContactNumberField()
        {
            _contactField.ApplyEnrollStyle();
            _contactField.Location = txtContactNumber.Location;
            _contactField.Size = txtContactNumber.Size;
            _contactField.TabIndex = txtContactNumber.TabIndex;
            txtContactNumber.Visible = false;
            txtContactNumber.Enabled = false;
            txtContactNumber.TabStop = false;
            panelBody.Controls.Add(_contactField);
            _contactField.BringToFront();
        }

        private async void btnStartEnroll_Click(object sender, EventArgs e)
        {
            await ScanSelectedFingersAsync();
        }

        private async void btnAddAnother_Click(object sender, EventArgs e)
        {
            await ScanSelectedFingersAsync();
        }

        private async Task ScanSelectedFingersAsync()
        {
            if (_isCapturing || !ValidateEmployeeInputs())
                return;

            List<string> selectedFingers = checkedListFingers.CheckedItems
                .Cast<string>()
                .Where(finger => !_fingerTemplates.ContainsKey(finger))
                .ToList();
            if (selectedFingers.Count == 0)
            {
                MessageBox.Show("Select at least one finger that has not already been enrolled.",
                    "Finger Selection", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            _isCapturing = true;
            SetEmployeeFieldsEnabled(false);
            progressBar.Maximum = selectedFingers.Count * 3;
            progressBar.Value = 0;
            lblProgress.Text = $"SCAN PROGRESS  (0 of {progressBar.Maximum} scans completed)";
            RefreshEnrollmentControls();

            try
            {
                foreach (string finger in selectedFingers)
                {
                    var scans = new byte[3][];
                    bool captureFailed = false;
                    for (int scanIndex = 0; scanIndex < scans.Length; scanIndex++)
                    {
                        lblScanStatus.Text =
                            $"Scanning {finger} Finger: {scanIndex + 1} of 3 scans...";
                        lblScanStatus.ForeColor = Color.Blue;

                        scans[scanIndex] = await Task.Run(() => _engine.CaptureScan());
                        if (scans[scanIndex] == null)
                        {
                            _playResultSound(false);
                            string error = string.IsNullOrWhiteSpace(_engine.LastError)
                                ? "Failed to capture fingerprint. Please try again."
                                : _engine.LastError;
                            MessageBox.Show(error, "Capture Error",
                                MessageBoxButtons.OK, MessageBoxIcon.Error);
                            captureFailed = true;
                            break;
                        }

                        _playResultSound(true);
                        progressBar.Value++;
                        lblProgress.Text =
                            $"SCAN PROGRESS  ({progressBar.Value} of {progressBar.Maximum} scans completed)";
                        if (scanIndex < scans.Length - 1)
                        {
                            lblScanStatus.Text = "Good! Lift your finger before the next scan.";
                            await Task.Delay(500);
                        }
                    }

                    if (captureFailed)
                        break;

                    byte[] mergedTemplate = _engine.MergeTemplates(scans[0], scans[1], scans[2]);
                    if (mergedTemplate == null)
                    {
                        _playResultSound(false);
                        MessageBox.Show(
                            $"The three scans for {finger} could not be merged. Please retry that finger.",
                            "Enrollment Error", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                        break;
                    }

                    _fingerTemplates.Add(finger, mergedTemplate);
                    int selectedIndex = checkedListFingers.Items.IndexOf(finger);
                    if (selectedIndex >= 0)
                        checkedListFingers.SetItemChecked(selectedIndex, false);
                    lblScanStatus.Text = $"{finger} enrolled. Select another finger or save the employee.";
                    lblScanStatus.ForeColor = Color.Green;
                }
            }
            catch (Exception ex)
            {
                _playResultSound(false);
                MessageBox.Show("Capture error: " + ex.Message, "Capture Error",
                    MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
            finally
            {
                _isCapturing = false;
                RefreshEnrollmentControls();
            }
        }

        private void btnSaveEmployee_Click(object sender, EventArgs e)
        {
            if (_isCapturing || _fingerTemplates.Count == 0 || !ValidateEmployeeInputs())
                return;

            if (!_contactField.TryValidate(out string contactNumber, out string contactError))
            {
                MessageBox.Show(contactError, "Validation",
                    MessageBoxButtons.OK, MessageBoxIcon.Warning);
                _contactField.FocusInput();
                return;
            }

            try
            {
                var templates = _fingerTemplates
                    .Select(entry => new KeyValuePair<string, byte[]>(entry.Key, entry.Value))
                    .ToList();
                _db.EnrollUser(
                    txtFirstName.Text.Trim(),
                    txtLastName.Text.Trim(),
                    txtMiddleName.Text.Trim(),
                    txtNameExtension.Text.Trim(),
                    txtEmpId.Text.Trim(),
                    templates,
                    EmployeeAddress,
                    contactNumber,
                    out _);

                lblScanStatus.Text = "Employee and fingerprint templates saved successfully.";
                lblScanStatus.ForeColor = Color.Green;
                MessageBox.Show($"{EmployeeFullName} enrolled successfully!", "Success",
                    MessageBoxButtons.OK, MessageBoxIcon.Information);
                Close();
            }
            catch (InvalidOperationException ex)
            {
                MessageBox.Show(ex.Message, "Already Registered",
                    MessageBoxButtons.OK, MessageBoxIcon.Warning);
            }
            catch (Exception ex)
            {
                MessageBox.Show("Database error: " + ex.Message, "Database Error",
                    MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void checkedListFingers_ItemCheck(object sender, ItemCheckEventArgs e)
        {
            if (IsHandleCreated)
                BeginInvoke((Action)RefreshEnrollmentControls);
        }

        private void RefreshEnrollmentControls()
        {
            bool hasSelectedFinger = checkedListFingers.CheckedItems
                .Cast<string>()
                .Any(finger => !_fingerTemplates.ContainsKey(finger));

            checkedListFingers.Enabled = !_isCapturing && _fingerTemplates.Count < checkedListFingers.Items.Count;
            btnStartEnroll.Enabled = !_isCapturing && _fingerTemplates.Count == 0 && hasSelectedFinger;
            btnAddAnother.Enabled = !_isCapturing && _fingerTemplates.Count > 0 && hasSelectedFinger;
            btnSaveEmployee.Enabled = !_isCapturing && _fingerTemplates.Count > 0;
        }

        private void SetEmployeeFieldsEnabled(bool enabled)
        {
            foreach (Control control in new Control[]
            {
                txtFirstName, txtLastName, txtMiddleName, txtNameExtension, txtEmpId,
                _contactField, cmbRegion, cmbProvince, cmbCityMunicipality, cmbBarangay,
                txtStreetDetails
            })
                control.Enabled = enabled;
        }

        private void btnCancel_Click(object sender, EventArgs e)
        {
            this.Close();
        }

        private bool ValidateEmployeeInputs()
        {
            bool addressIncomplete =
                SelectedLocation(cmbRegion) == null ||
                SelectedLocation(cmbProvince) == null ||
                SelectedLocation(cmbCityMunicipality) == null ||
                SelectedLocation(cmbBarangay) == null ||
                string.IsNullOrWhiteSpace(txtStreetDetails.Text);

            if (string.IsNullOrWhiteSpace(txtFirstName.Text) ||
                string.IsNullOrWhiteSpace(txtLastName.Text) ||
                string.IsNullOrWhiteSpace(txtEmpId.Text) ||
                addressIncomplete)
            {
                MessageBox.Show("Please complete all required fields.", "Validation",
                    MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return false;
            }

            if (!_contactField.TryValidate(out _, out string contactError))
            {
                MessageBox.Show(contactError, "Validation",
                    MessageBoxButtons.OK, MessageBoxIcon.Warning);
                _contactField.FocusInput();
                return false;
            }

            if (!PersonRegistrationGuard.CanCreateNewPerson(_db, EmployeeFullName, EmployeeAddress))
                return false;

            if (!PhilippineGeoData.IsValidSelection(
                SelectedLocation(cmbRegion).Code,
                SelectedLocation(cmbProvince).Code,
                SelectedLocation(cmbCityMunicipality).Code,
                SelectedLocation(cmbBarangay).Code))
            {
                MessageBox.Show("Please select a complete and valid Philippine address.", "Validation",
                    MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return false;
            }

            return true;
        }

        private string EmployeeFullName =>
            DatabaseHelper.BuildEmployeeFullName(
                txtFirstName.Text,
                txtMiddleName.Text,
                txtLastName.Text,
                txtNameExtension.Text);

        private string EmployeeAddress
        {
            get
            {
                var parts = new[]
                {
                    txtStreetDetails.Text.Trim(),
                    SelectedLocationName(cmbBarangay),
                    SelectedLocationName(cmbCityMunicipality),
                    SelectedLocationName(cmbProvince),
                    SelectedLocationName(cmbRegion)
                };
                return string.Join(", ", parts
                    .Where(p =>
                        !string.IsNullOrWhiteSpace(p) &&
                        !string.Equals(p, "N/A", StringComparison.OrdinalIgnoreCase))
                    .Distinct(StringComparer.OrdinalIgnoreCase));
            }
        }

        private void LoadRegions()
        {
            var regions = PhilippineGeoData.GetRegions();
            if (regions.Count == 0)
            {
                string detail = PhilippineGeoData.LoadError;
                MessageBox.Show(
                    "Unable to load local Philippine geography data." +
                    (string.IsNullOrWhiteSpace(detail) ? string.Empty : Environment.NewLine + detail),
                    "Address Data",
                    MessageBoxButtons.OK,
                    MessageBoxIcon.Warning);
            }

            BindLocationCombo(cmbRegion, regions, "Select Region", enable: regions.Count > 0);
            ResetLowerLocationFields(0);
            SelectLocationByName(cmbRegion, DefaultRegionName);
            SelectLocationByName(cmbProvince, DefaultProvinceName);
            SelectLocationByName(cmbCityMunicipality, DefaultCityName);
            SelectLocationByName(cmbBarangay, DefaultBarangayName);
        }

        private void OnRegionChanged()
        {
            if (_suppressLocationEvents) return;
            ResetLowerLocationFields(0);
            var region = SelectedLocation(cmbRegion);
            if (region == null) return;

            var provinces = PhilippineGeoData.GetProvinces(region.Code);
            BindLocationCombo(cmbProvince, provinces, "Select Province", enable: provinces.Count > 0);
        }

        private void OnProvinceChanged()
        {
            if (_suppressLocationEvents) return;
            ResetLowerLocationFields(1);
            var province = SelectedLocation(cmbProvince);
            if (province == null) return;

            var cities = PhilippineGeoData.GetCities(province.Code);
            BindLocationCombo(cmbCityMunicipality, cities, "Select City/Municipality", enable: cities.Count > 0);
            if (cities.Count == 1)
                cmbCityMunicipality.SelectedIndex = 1;
        }

        private void OnCityChanged()
        {
            if (_suppressLocationEvents) return;
            ResetLowerLocationFields(2);
            var city = SelectedLocation(cmbCityMunicipality);
            if (city == null) return;

            var barangays = PhilippineGeoData.GetBarangays(city.Code);
            BindLocationCombo(cmbBarangay, barangays, "Select Barangay", enable: barangays.Count > 0);
        }

        private void ResetLowerLocationFields(int fromLevel)
        {
            if (fromLevel <= 0)
                BindLocationCombo(cmbProvince, new List<GeoItem>(), "Select Province", enable: false);
            if (fromLevel <= 1)
                BindLocationCombo(cmbCityMunicipality, new List<GeoItem>(), "Select City/Municipality", enable: false);
            if (fromLevel <= 2)
                BindLocationCombo(cmbBarangay, new List<GeoItem>(), "Select Barangay", enable: false);
        }

        private void BindLocationCombo(ComboBox combo, List<GeoItem> items, string placeholder, bool enable)
        {
            _suppressLocationEvents = true;
            try
            {
                var bound = new List<GeoItem>(items.Count + 1)
                {
                    new GeoItem(string.Empty, placeholder)
                };
                bound.AddRange(items);
                combo.DataSource = null;
                combo.DisplayMember = "Name";
                combo.ValueMember = "Code";
                combo.DataSource = bound;
                combo.SelectedIndex = 0;
                combo.MaxDropDownItems = 12;
                combo.Enabled = enable;
            }
            finally
            {
                _suppressLocationEvents = false;
            }
        }

        private static bool SelectLocationByName(ComboBox combo, string name)
        {
            var items = combo.DataSource as List<GeoItem>;
            if (items == null)
                return false;

            for (int i = 0; i < items.Count; i++)
            {
                if (string.IsNullOrWhiteSpace(items[i].Code))
                    continue;
                if (!string.Equals(items[i].Name, name, StringComparison.OrdinalIgnoreCase))
                    continue;

                combo.SelectedIndex = i;
                return true;
            }

            return false;
        }

        private static GeoItem SelectedLocation(ComboBox combo)
        {
            var item = combo.SelectedItem as GeoItem;
            if (item == null || string.IsNullOrWhiteSpace(item.Code))
                return null;
            return item;
        }

        private static string SelectedLocationName(ComboBox combo)
        {
            var item = SelectedLocation(combo);
            return item == null ? null : item.Name;
        }
    }
}
