using System;
using System.Collections.Generic;
using System.Drawing;
using System.Linq;
using System.Windows.Forms;
using FingerprintAttendance.Core;

namespace FingerprintAttendance
{
    /// <summary>
    /// Enroll New Employee Address Information section: cascading Region → Province →
    /// City/Municipality → Barangay plus Purok/Street, loaded from local PSGC data.
    /// </summary>
    public sealed class AddressInformationPanel : Panel
    {
        private readonly Panel _pnlSection = new Panel();
        private readonly Panel _pnlAccent = new Panel();
        private readonly Label _lblSection = new Label();
        private readonly CheckBox _chkOther = new CheckBox();
        private readonly Label _lblOtherHint = new Label();
        private readonly Label _lblRegion = new Label();
        private readonly ComboBox _cmbRegion = new ComboBox();
        private readonly Label _lblProvince = new Label();
        private readonly ComboBox _cmbProvince = new ComboBox();
        private readonly Label _lblCityMunicipality = new Label();
        private readonly ComboBox _cmbCityMunicipality = new ComboBox();
        private readonly Label _lblBarangay = new Label();
        private readonly ComboBox _cmbBarangay = new ComboBox();
        private readonly Label _lblStreetDetails = new Label();
        private readonly TextBox _txtStreetDetails = new TextBox();
        private readonly ComboBox _cmbPurok = new ComboBox();
        private readonly Label _lblCompleteAddress = new Label();
        private readonly TextBox _txtCompleteAddress = new TextBox();
        private readonly TextBox _txtLockedAddress = new TextBox();
        private readonly Control[] _philippineAddressControls;
        private readonly Control[] _internationalAddressControls;
        private const string DefaultRegionName = "Region XI (Davao Region)";
        private const string DefaultProvinceName = "Davao del Sur";
        private const string DefaultCityName = "City of Digos";
        private const string DefaultBarangayName = "San Jose";
        private static readonly string[] SanJosePuroks =
        {
            "MANGGA",
            "POMELO",
            "MANGO DRIVE",
            "NINDOT",
            "NANGKA",
            "TALISAY I",
            "TALISAY II",
            "CAMANCHILLES",
            "SUPERHIGHWAY",
            "MADASIGON",
            "BATANGUEÑO",
            "CAGAPE",
            "PIONEER",
            "ACACIA",
            "GEMELINA",
            "ROSE",
            "MALINAWON",
            "JAKOSALEM",
            "MAHOGANY",
            "DURANTA",
            "SAN FRANCISCO",
            "PABALAN"
        };

        private bool _suppressLocationEvents;
        private bool _suppressStreetDetailsEvents;
        private bool _regionsLoaded;
        private string _lockedAddress;

        public AddressInformationPanel()
        {
            Width = 680;
            BackColor = Color.White;

            int y = 0;
            _pnlSection.Location = new Point(0, y);
            _pnlSection.Size = new Size(680, 32);
            _pnlSection.BackColor = Color.FromArgb(248, 250, 252);
            _pnlAccent.Location = new Point(0, 0);
            _pnlAccent.Size = new Size(4, 32);
            _pnlAccent.BackColor = Color.FromArgb(8, 145, 178);
            _lblSection.Location = new Point(14, 0);
            _lblSection.Size = new Size(650, 32);
            _lblSection.Text = "ADDRESS INFORMATION";
            _lblSection.Font = new Font("Segoe UI", 8.5F, FontStyle.Bold);
            _lblSection.ForeColor = Color.FromArgb(8, 145, 178);
            _lblSection.TextAlign = ContentAlignment.MiddleLeft;
            _pnlSection.Controls.Add(_pnlAccent);
            _pnlSection.Controls.Add(_lblSection);
            Controls.Add(_pnlSection);
            y += 32 + 4;

            _chkOther.Location = new Point(18, y);
            _chkOther.Size = new Size(72, 22);
            _chkOther.Text = "Other";
            _chkOther.Font = new Font("Segoe UI", 9F, FontStyle.Bold);
            _chkOther.ForeColor = Color.FromArgb(30, 41, 59);
            _chkOther.AutoSize = true;
            _chkOther.CheckedChanged += (_, __) => ApplyAddressMode();
            _txtStreetDetails.CharacterCasing = CharacterCasing.Upper;
            _txtStreetDetails.TextChanged += (_, __) =>
            {
                if (_suppressStreetDetailsEvents)
                    return;

                string uppercase = _txtStreetDetails.Text.ToUpperInvariant();
                if (_txtStreetDetails.Text == uppercase)
                    return;

                int selectionStart = _txtStreetDetails.SelectionStart;
                int selectionLength = _txtStreetDetails.SelectionLength;
                _txtStreetDetails.Text = uppercase;
                _txtStreetDetails.Select(
                    Math.Min(selectionStart, uppercase.Length),
                    Math.Min(selectionLength, uppercase.Length - Math.Min(selectionStart, uppercase.Length)));
            };
            _cmbPurok.DropDownStyle = ComboBoxStyle.DropDown;
            _cmbPurok.Items.AddRange(SanJosePuroks);
            _cmbPurok.TextChanged += (_, __) =>
            {
                if (_suppressStreetDetailsEvents)
                    return;

                string uppercase = _cmbPurok.Text.ToUpperInvariant();
                if (_cmbPurok.Text == uppercase)
                    return;

                int selectionStart = _cmbPurok.SelectionStart;
                int selectionLength = _cmbPurok.SelectionLength;
                _cmbPurok.Text = uppercase;
                _cmbPurok.Select(
                    Math.Min(selectionStart, uppercase.Length),
                    Math.Min(selectionLength, uppercase.Length - Math.Min(selectionStart, uppercase.Length)));
            };
            _lblOtherHint.Location = new Point(88, y + 1);
            _lblOtherHint.Size = new Size(574, 20);
            _lblOtherHint.Text = "Check this if your address is outside the Philippines.";
            _lblOtherHint.Font = new Font("Segoe UI", 8.5F);
            _lblOtherHint.ForeColor = Color.FromArgb(100, 116, 139);
            Controls.Add(_chkOther);
            Controls.Add(_lblOtherHint);
            y += 28;

            int fieldsStartY = y;
            AddLabeledCombo(_lblRegion, "REGION", _cmbRegion, ref y);
            AddLabeledCombo(_lblProvince, "PROVINCE", _cmbProvince, ref y);
            AddLabeledCombo(_lblCityMunicipality, "CITY / MUNICIPALITY", _cmbCityMunicipality, ref y);
            AddLabeledCombo(_lblBarangay, "BARANGAY", _cmbBarangay, ref y);
            AddLabeledText(_lblStreetDetails, "PUROK / STREET / HOUSE NO.", _txtStreetDetails, ref y, 52);
            _cmbPurok.SetBounds(
                _txtStreetDetails.Left,
                _txtStreetDetails.Top,
                _txtStreetDetails.Width,
                _txtStreetDetails.Height);
            _cmbPurok.Font = _txtStreetDetails.Font;
            _cmbPurok.BackColor = _txtStreetDetails.BackColor;
            _cmbPurok.Visible = false;
            Controls.Add(_cmbPurok);
            int fieldsEndY = y;

            y = fieldsStartY;
            AddCompleteAddressField(ref y);
            y = fieldsEndY;

            _philippineAddressControls = new Control[]
            {
                _lblRegion, _cmbRegion,
                _lblProvince, _cmbProvince,
                _lblCityMunicipality, _cmbCityMunicipality,
                _lblBarangay, _cmbBarangay,
                _lblStreetDetails, _txtStreetDetails, _cmbPurok
            };
            _internationalAddressControls = new Control[]
            {
                _lblCompleteAddress, _txtCompleteAddress
            };
            ApplyAddressMode();

            _txtLockedAddress.Location = new Point(18, 36);
            _txtLockedAddress.Size = new Size(644, y - 48);
            _txtLockedAddress.Multiline = true;
            _txtLockedAddress.ScrollBars = ScrollBars.Vertical;
            _txtLockedAddress.ReadOnly = true;
            _txtLockedAddress.BorderStyle = BorderStyle.FixedSingle;
            _txtLockedAddress.BackColor = Color.FromArgb(241, 245, 249);
            _txtLockedAddress.Font = new Font("Segoe UI", 10.5F);
            _txtLockedAddress.Visible = false;
            Controls.Add(_txtLockedAddress);

            Height = y;

            Resize += (_, __) => ApplyWidthLayout();
            ApplyWidthLayout();

            _cmbRegion.SelectedIndexChanged += (_, __) => OnRegionChanged();
            _cmbProvince.SelectedIndexChanged += (_, __) => OnProvinceChanged();
            _cmbCityMunicipality.SelectedIndexChanged += (_, __) => OnCityChanged();
            _cmbBarangay.SelectedIndexChanged += (_, __) => OnBarangayChanged();

            // Bind after the panel is parented (same timing as EnrollForm.Load).
            // Setting ComboBox.DataSource in the constructor, before BindingContext
            // exists, leaves the dropdowns empty or throws when the form opens.
            HandleCreated += (_, __) => LoadRegions();
        }

        internal void ApplySectionHeaderStyle(Color background, Color foreground)
        {
            _pnlSection.BackColor = background;
            _lblSection.BackColor = background;
            _lblSection.ForeColor = foreground;
        }

        public string ComposedAddress
        {
            get
            {
                if (!string.IsNullOrWhiteSpace(_lockedAddress))
                    return _lockedAddress.Trim();

                if (_chkOther.Checked)
                    return _txtCompleteAddress.Text.Trim();

                var parts = new[]
                {
                    StreetDetailsValue,
                    SelectedLocationName(_cmbBarangay),
                    SelectedLocationName(_cmbCityMunicipality),
                    SelectedLocationName(_cmbProvince),
                    SelectedLocationName(_cmbRegion)
                };
                return string.Join(", ", parts
                    .Where(p =>
                        !string.IsNullOrWhiteSpace(p) &&
                        !string.Equals(p, "N/A", StringComparison.OrdinalIgnoreCase))
                    .Distinct(StringComparer.OrdinalIgnoreCase));
            }
        }

        public bool IsComplete
        {
            get
            {
                if (!string.IsNullOrWhiteSpace(_lockedAddress))
                    return !string.IsNullOrWhiteSpace(_lockedAddress);

                if (_chkOther.Checked)
                    return !string.IsNullOrWhiteSpace(_txtCompleteAddress.Text);

                var region = SelectedLocation(_cmbRegion);
                var province = SelectedLocation(_cmbProvince);
                var city = SelectedLocation(_cmbCityMunicipality);
                var barangay = SelectedLocation(_cmbBarangay);
                if (region == null || province == null || city == null || barangay == null)
                    return false;
                if (string.IsNullOrWhiteSpace(StreetDetailsValue))
                    return false;
                return PhilippineGeoData.IsValidSelection(region.Code, province.Code, city.Code, barangay.Code);
            }
        }

        public void SetLockedAddress(string address, bool compact = false)
        {
            _lockedAddress = address ?? string.Empty;
            if (compact)
            {
                _txtLockedAddress.SetBounds(18, 40, _txtLockedAddress.Width, 54);
                Height = 108;
            }
            ApplyLockedState();
        }

        private void ApplyLockedState()
        {
            _txtLockedAddress.Text = _lockedAddress ?? string.Empty;
            _txtLockedAddress.Visible = true;
            _txtLockedAddress.BringToFront();

            _chkOther.Visible = false;
            _lblOtherHint.Visible = false;
            SetControlsVisible(_philippineAddressControls, false);
            SetControlsVisible(_internationalAddressControls, false);

            _cmbRegion.Enabled = false;
            _cmbProvince.Enabled = false;
            _cmbCityMunicipality.Enabled = false;
            _cmbBarangay.Enabled = false;
            _txtStreetDetails.ReadOnly = true;
            _txtStreetDetails.BackColor = Color.FromArgb(241, 245, 249);
            _cmbPurok.Enabled = false;
            _cmbPurok.BackColor = Color.FromArgb(241, 245, 249);
        }

        private void ApplyAddressMode()
        {
            if (!string.IsNullOrWhiteSpace(_lockedAddress))
                return;

            bool outsidePhilippines = _chkOther.Checked;
            SetControlsVisible(_philippineAddressControls, !outsidePhilippines);
            SetControlsVisible(_internationalAddressControls, outsidePhilippines);
            UpdateStreetDetailsInputMode();
            if (outsidePhilippines)
            {
                foreach (var control in _internationalAddressControls)
                    control.BringToFront();
            }
        }

        private static void SetControlsVisible(Control[] controls, bool visible)
        {
            if (controls == null) return;
            foreach (var control in controls)
                control.Visible = visible;
        }

        private void ApplyWidthLayout()
        {
            int w = Width;
            if (w < 80) return;

            int inner = Math.Max(80, w - 36);
            _pnlSection.Width = w;
            _lblSection.Width = Math.Max(40, w - 30);
            _lblOtherHint.Width = Math.Max(40, w - 106);

            _lblRegion.Width = inner;
            _cmbRegion.Width = inner;
            _lblProvince.Width = inner;
            _cmbProvince.Width = inner;
            _lblCityMunicipality.Width = inner;
            _cmbCityMunicipality.Width = inner;
            _lblBarangay.Width = inner;
            _cmbBarangay.Width = inner;
            _lblStreetDetails.Width = inner;
            _txtStreetDetails.Width = inner;
            _cmbPurok.Width = inner;
            _lblCompleteAddress.Width = inner;
            _txtCompleteAddress.Width = inner;
            _txtLockedAddress.Width = inner;
        }

        private void AddLabeledCombo(Label lbl, string caption, ComboBox combo, ref int y)
        {
            lbl.Location = new Point(18, y);
            lbl.Size = new Size(644, 18);
            lbl.Text = caption;
            lbl.Font = new Font("Segoe UI", 8.5F, FontStyle.Bold);
            lbl.ForeColor = Color.FromArgb(100, 116, 139);
            y += 24;
            combo.Location = new Point(18, y);
            combo.Size = new Size(644, 32);
            combo.Font = new Font("Segoe UI", 10.5F);
            combo.DropDownStyle = ComboBoxStyle.DropDownList;
            combo.IntegralHeight = false;
            combo.MaxDropDownItems = 5;
            combo.BackColor = Color.FromArgb(248, 250, 252);
            combo.Enabled = false;
            Controls.Add(lbl);
            Controls.Add(combo);
            y += 66;
        }

        private void AddCompleteAddressField(ref int y)
        {
            _lblCompleteAddress.Location = new Point(18, y);
            _lblCompleteAddress.Size = new Size(644, 18);
            _lblCompleteAddress.Text = "type you complete address here";
            _lblCompleteAddress.Font = new Font("Segoe UI", 8.5F, FontStyle.Bold);
            _lblCompleteAddress.ForeColor = Color.FromArgb(100, 116, 139);
            y += 24;
            _txtCompleteAddress.Location = new Point(18, y);
            _txtCompleteAddress.Size = new Size(644, 32);
            _txtCompleteAddress.Font = new Font("Segoe UI", 11F);
            _txtCompleteAddress.BorderStyle = BorderStyle.FixedSingle;
            _txtCompleteAddress.BackColor = Color.FromArgb(248, 250, 252);
            Controls.Add(_lblCompleteAddress);
            Controls.Add(_txtCompleteAddress);
            y += 52;
        }

        private void AddLabeledText(Label lbl, string caption, TextBox textBox, ref int y, int afterControl = 44)
        {
            lbl.Location = new Point(18, y);
            lbl.Size = new Size(644, 18);
            lbl.Text = caption;
            lbl.Font = new Font("Segoe UI", 8.5F, FontStyle.Bold);
            lbl.ForeColor = Color.FromArgb(100, 116, 139);
            y += 24;
            textBox.Location = new Point(18, y);
            textBox.Size = new Size(644, 32);
            textBox.Font = new Font("Segoe UI", 11F);
            textBox.BorderStyle = BorderStyle.FixedSingle;
            textBox.BackColor = Color.FromArgb(248, 250, 252);
            Controls.Add(lbl);
            Controls.Add(textBox);
            y += afterControl;
        }

        private void LoadRegions()
        {
            if (_regionsLoaded) return;
            _regionsLoaded = true;

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

            BindLocationCombo(_cmbRegion, regions, "Select Region", enable: regions.Count > 0);
            ResetLowerLocationFields(0);

            if (!string.IsNullOrWhiteSpace(_lockedAddress))
            {
                ApplyLockedState();
                return;
            }

            ApplyDefaultDigosAddress();
        }

        private void ApplyDefaultDigosAddress()
        {
            if (!SelectLocationByName(_cmbRegion, DefaultRegionName))
                return;
            if (!SelectLocationByName(_cmbProvince, DefaultProvinceName))
                return;
            if (!SelectLocationByName(_cmbCityMunicipality, DefaultCityName))
                return;
            SelectLocationByName(_cmbBarangay, DefaultBarangayName);
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

        private void OnRegionChanged()
        {
            if (_suppressLocationEvents) return;
            ResetLowerLocationFields(0);
            var region = SelectedLocation(_cmbRegion);
            if (region == null) return;

            var provinces = PhilippineGeoData.GetProvinces(region.Code);
            BindLocationCombo(_cmbProvince, provinces, "Select Province", enable: provinces.Count > 0);
        }

        private void OnProvinceChanged()
        {
            if (_suppressLocationEvents) return;
            ResetLowerLocationFields(1);
            var province = SelectedLocation(_cmbProvince);
            if (province == null) return;

            var cities = PhilippineGeoData.GetCities(province.Code);
            BindLocationCombo(_cmbCityMunicipality, cities, "Select City/Municipality", enable: cities.Count > 0);
            if (cities.Count == 1)
                _cmbCityMunicipality.SelectedIndex = 1;
        }

        private void OnCityChanged()
        {
            if (_suppressLocationEvents) return;
            ResetLowerLocationFields(2);
            var city = SelectedLocation(_cmbCityMunicipality);
            if (city == null) return;

            var barangays = PhilippineGeoData.GetBarangays(city.Code);
            BindLocationCombo(_cmbBarangay, barangays, "Select Barangay", enable: barangays.Count > 0);
            UpdateStreetDetailsInputMode();
        }

        private void OnBarangayChanged()
        {
            if (_suppressLocationEvents) return;
            UpdateStreetDetailsInputMode();
        }

        private void ResetLowerLocationFields(int fromLevel)
        {
            if (fromLevel <= 0)
                BindLocationCombo(_cmbProvince, new List<GeoItem>(), "Select Province", enable: false);
            if (fromLevel <= 1)
                BindLocationCombo(_cmbCityMunicipality, new List<GeoItem>(), "Select City/Municipality", enable: false);
            if (fromLevel <= 2)
                BindLocationCombo(_cmbBarangay, new List<GeoItem>(), "Select Barangay", enable: false);
            if (fromLevel <= 2)
                UpdateStreetDetailsInputMode();
        }

        private string StreetDetailsValue =>
            _cmbPurok.Visible ? _cmbPurok.Text.Trim() : _txtStreetDetails.Text.Trim();

        private void UpdateStreetDetailsInputMode()
        {
            var barangay = SelectedLocation(_cmbBarangay);
            bool usePurokDropdown = barangay != null &&
                string.Equals(barangay.Name, DefaultBarangayName, StringComparison.OrdinalIgnoreCase);
            if (usePurokDropdown != _cmbPurok.Visible)
            {
                _suppressStreetDetailsEvents = true;
                try
                {
                    if (usePurokDropdown)
                        _cmbPurok.Text = _txtStreetDetails.Text;
                    else
                        _txtStreetDetails.Text = _cmbPurok.Text;
                }
                finally
                {
                    _suppressStreetDetailsEvents = false;
                }
            }

            _txtStreetDetails.Visible = !usePurokDropdown;
            _cmbPurok.Visible = usePurokDropdown;
            if (usePurokDropdown)
                _cmbPurok.BringToFront();
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
