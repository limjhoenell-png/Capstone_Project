using System;
using System.Collections.Generic;
using System.Drawing;
using System.Linq;
using System.Net.Http;
using System.Text.RegularExpressions;
using System.Threading;
using System.Threading.Tasks;
using System.Windows.Forms;
using FingerprintAttendance.Core;
using FingerprintAttendance.Properties;

namespace FingerprintAttendance
{
    public class VisitorRegistrationForm : Form
    {
        private readonly TextBox _txtFullName = new TextBox();
        private readonly ComboBox _cmbRegion = new ComboBox();
        private readonly ComboBox _cmbProvince = new ComboBox();
        private readonly ComboBox _cmbCityMunicipality = new ComboBox();
        private readonly ComboBox _cmbBarangay = new ComboBox();
        private readonly TextBox _txtPurokStreetHouseNo = new TextBox();
        private readonly TextBox _txtOtherAddress = new TextBox();
        private readonly TextBox _txtContact = new TextBox();
        private readonly ComboBox _cmbPurpose = new ComboBox();
        private readonly CheckBox _chkOtherPurpose = new CheckBox();
        private readonly TextBox _txtOtherTransaction = new TextBox();
        private readonly Label _lblOtherTransaction = new Label();
        private readonly Label _lblLocationHint = new Label();

        private static readonly HttpClient _http = new HttpClient { Timeout = TimeSpan.FromSeconds(6) };
        private static readonly Dictionary<string, List<GeoItem>> _geoCache =
            new Dictionary<string, List<GeoItem>>(StringComparer.OrdinalIgnoreCase);

        private const string PsgcBaseUrl = "https://psgc.gitlab.io/api";
        private const int MaxRecentPlaces = 5;
        private const char RecentSeparator = '|';
        private const string DefaultRegionTop1 = "Davao Region";
        private const string DefaultProvinceTop1 = "Davao Del Sur";
        private const string DefaultCityTop1 = "City of Digos";
        private const string DefaultBarangayTop1 = "San Jose";
        private bool _isLocationLoading;
        private int _regionSelectionVersion;
        private int _provinceSelectionVersion;
        private int _citySelectionVersion;

        // ── Public outputs ───────────────────────────────────────────────────────
        public string VisitorFullName => _txtFullName.Text.Trim();

        public string VisitorAddress
        {
            get
            {
                var parts = new[]
                {
                    _txtPurokStreetHouseNo.Text.Trim(),
                    _txtOtherAddress.Text.Trim(),
                    _cmbBarangay.SelectedItem as string,
                    _cmbCityMunicipality.SelectedItem as string,
                    _cmbProvince.SelectedItem as string,
                    _cmbRegion.SelectedItem as string
                };
                return string.Join(", ", parts.Where(p => !string.IsNullOrWhiteSpace(p)));
            }
        }

        public string VisitorContactNumber => _txtContact.Text.Trim();

        public string PurposeOfTransaction
        {
            get
            {
                if (_chkOtherPurpose.Checked ||
                    string.Equals(_cmbPurpose.SelectedItem as string, PurposeOfVisitCatalog.Other, StringComparison.OrdinalIgnoreCase))
                {
                    return PurposeOfVisitCatalog.FormatOtherPurpose(_txtOtherTransaction.Text);
                }
                return (_cmbPurpose.SelectedItem as string)?.Trim() ?? string.Empty;
            }
        }

        // ── Constructor ──────────────────────────────────────────────────────────
        public VisitorRegistrationForm()
        {
            Text = "Register New Visitor / Resident";
            StartPosition = FormStartPosition.CenterParent;
            FormBorderStyle = FormBorderStyle.Sizable;
            MaximizeBox = true;
            MinimizeBox = true;
            MinimumSize = new Size(480, 420);
            BackColor = Color.FromArgb(236, 241, 248);
            Font = new Font("Segoe UI", 10F);
            AutoScaleMode = AutoScaleMode.Font;
            AutoScroll = true;
            AutoScrollMinSize = new Size(620, 760);
            ClientSize = new Size(640, 740);
            ResponsiveLayout.FitClientToWorkingArea(this, 640, 740, 480, 420);

            // ── Header ──────────────────────────────────────────────────────────
            var panelHeader = new Panel
            {
                Location = new Point(0, 0),
                Size = new Size(620, 80),
                BackColor = Color.FromArgb(13, 27, 42)
            };

            var pnlHeaderAccentBar = new Panel
            {
                Location = new Point(0, 0),
                Size = new Size(6, 80),
                BackColor = Color.FromArgb(8, 145, 178)
            };

            var lblTitle = new Label
            {
                Text = "Visitor / Resident Registration",
                Left = 26,
                Top = 10,
                Width = 570,
                Height = 34,
                Font = new Font("Segoe UI", 15F, FontStyle.Bold),
                ForeColor = Color.FromArgb(240, 246, 255),
                BackColor = Color.Transparent
            };

            var lblSubtitle = new Label
            {
                Text = "Complete all fields to create a new logbook entry.",
                Left = 28,
                Top = 50,
                Width = 570,
                Height = 22,
                Font = new Font("Segoe UI", 9F),
                ForeColor = Color.FromArgb(100, 140, 185),
                BackColor = Color.Transparent
            };

            panelHeader.Controls.AddRange(new Control[] {
                pnlHeaderAccentBar, lblTitle, lblSubtitle });

            // Teal accent stripe below header
            var pnlHeaderAccent = new Panel
            {
                Location = new Point(0, 80),
                Size = new Size(620, 3),
                BackColor = Color.FromArgb(8, 145, 178)
            };

            // ── Scrollable content area ──────────────────────────────────────────
            var panelScrollable = new Panel
            {
                Location = new Point(0, 83),
                Size = new Size(620, 657),
                AutoScroll = true,
                BackColor = Color.FromArgb(236, 241, 248)
            };

            // ── Body card ───────────────────────────────────────────────────────
            var panelBody = new Panel
            {
                Location = new Point(16, 14),
                Width = 582,
                BackColor = Color.White
            };

            int y = 0;

            // ── Helper: create a section header ─────────────────────────────────
            Panel MakeSectionHeader(int top, string title, Color accentColor)
            {
                var pnl = new Panel
                {
                    Location = new Point(0, top),
                    Size = new Size(582, 32),
                    BackColor = Color.FromArgb(248, 250, 252)
                };
                var accent = new Panel
                {
                    Location = new Point(0, 0),
                    Size = new Size(4, 32),
                    BackColor = accentColor
                };
                var lbl = new Label
                {
                    Text = title,
                    Left = 14,
                    Top = 0,
                    Width = 460,
                    Height = 32,
                    Font = new Font("Segoe UI", 8.5F, FontStyle.Bold),
                    ForeColor = accentColor,
                    TextAlign = ContentAlignment.MiddleLeft
                };
                pnl.Controls.Add(accent);
                pnl.Controls.Add(lbl);
                return pnl;
            }

            // ── Helper: text field ───────────────────────────────────────────────
            Label MakeFieldLabel(string text, int top)
            {
                return new Label
                {
                    Text = text,
                    Left = 22,
                    Top = top,
                    Width = 534,
                    Height = 18,
                    Font = new Font("Segoe UI", 8.5F, FontStyle.Bold),
                    ForeColor = Color.FromArgb(100, 116, 139)
                };
            }

            void StyleTextBox(TextBox tb, int top)
            {
                tb.SetBounds(22, top, 534, 32);
                tb.Font = new Font("Segoe UI", 11F);
                tb.BorderStyle = BorderStyle.FixedSingle;
                tb.BackColor = Color.FromArgb(248, 250, 252);
            }

            void StyleComboBox(ComboBox cb, int top)
            {
                cb.SetBounds(22, top, 534, 32);
                cb.Font = new Font("Segoe UI", 10.5F);
                cb.DropDownStyle = ComboBoxStyle.DropDownList;
                cb.IntegralHeight = false;
                cb.MaxDropDownItems = 6;
                cb.BackColor = Color.FromArgb(248, 250, 252);
                cb.Enabled = false;
            }

            // ── SECTION A: Personal Information ──────────────────────────────────
            y += 8;
            var secA = MakeSectionHeader(y, "PERSONAL INFORMATION", Color.FromArgb(37, 99, 235));
            panelBody.Controls.Add(secA);
            y += 36;

            panelBody.Controls.Add(MakeFieldLabel("FULL NAME", y));
            y += 24;
            StyleTextBox(_txtFullName, y);
            panelBody.Controls.Add(_txtFullName);
            y += 62;

            panelBody.Controls.Add(MakeFieldLabel("CONTACT NUMBER", y));
            y += 24;
            StyleTextBox(_txtContact, y);
            panelBody.Controls.Add(_txtContact);
            y += 70;

            // ── SECTION B: Address ───────────────────────────────────────────────
            var secB = MakeSectionHeader(y, "ADDRESS INFORMATION", Color.FromArgb(8, 145, 178));
            panelBody.Controls.Add(secB);
            y += 36;

            panelBody.Controls.Add(MakeFieldLabel("REGION", y));
            y += 24;
            StyleComboBox(_cmbRegion, y);
            panelBody.Controls.Add(_cmbRegion);
            y += 62;

            panelBody.Controls.Add(MakeFieldLabel("PROVINCE", y));
            y += 24;
            StyleComboBox(_cmbProvince, y);
            panelBody.Controls.Add(_cmbProvince);
            y += 62;

            panelBody.Controls.Add(MakeFieldLabel("CITY / MUNICIPALITY", y));
            y += 24;
            StyleComboBox(_cmbCityMunicipality, y);
            panelBody.Controls.Add(_cmbCityMunicipality);
            y += 62;

            panelBody.Controls.Add(MakeFieldLabel("BARANGAY", y));
            y += 24;
            StyleComboBox(_cmbBarangay, y);
            panelBody.Controls.Add(_cmbBarangay);
            y += 62;

            panelBody.Controls.Add(MakeFieldLabel("PUROK / STREET / HOUSE NO.", y));
            y += 24;
            StyleTextBox(_txtPurokStreetHouseNo, y);
            panelBody.Controls.Add(_txtPurokStreetHouseNo);
            y += 62;

            panelBody.Controls.Add(MakeFieldLabel("OTHER (Optional — for foreign/non-local address)", y));
            y += 24;
            StyleTextBox(_txtOtherAddress, y);
            panelBody.Controls.Add(_txtOtherAddress);
            y += 70;

            // ── SECTION C: Purpose ───────────────────────────────────────────────
            var secC = MakeSectionHeader(y, "PURPOSE OF VISIT", Color.FromArgb(100, 116, 139));
            panelBody.Controls.Add(secC);
            y += 36;

            // Purpose label row (has a checkbox on the right)
            var lblPurpose = MakeFieldLabel("PURPOSE OF TRANSACTION", y);
            panelBody.Controls.Add(lblPurpose);

            _chkOtherPurpose.Text = "Other purpose";
            _chkOtherPurpose.Left = 380;
            _chkOtherPurpose.Top = y + 1;
            _chkOtherPurpose.Width = 176;
            _chkOtherPurpose.Height = 20;
            _chkOtherPurpose.Font = new Font("Segoe UI", 9F);
            _chkOtherPurpose.ForeColor = Color.FromArgb(71, 85, 105);
            _chkOtherPurpose.BackColor = Color.Transparent;
            panelBody.Controls.Add(_chkOtherPurpose);
            y += 24;

            // Purpose dropdown (slightly narrower to leave room)
            _cmbPurpose.SetBounds(22, y, 534, 32);
            _cmbPurpose.Font = new Font("Segoe UI", 10.5F);
            _cmbPurpose.DropDownStyle = ComboBoxStyle.DropDownList;
            _cmbPurpose.IntegralHeight = true;
            _cmbPurpose.MaxDropDownItems = 16;
            _cmbPurpose.BackColor = Color.FromArgb(248, 250, 252);
            _cmbPurpose.Enabled = false;
            panelBody.Controls.Add(_cmbPurpose);
            y += 62;

            // "Other" textbox (hidden by default)
            _lblOtherTransaction.Text = "OTHER TRANSACTION DETAILS";
            _lblOtherTransaction.Left = 22;
            _lblOtherTransaction.Top = y;
            _lblOtherTransaction.Width = 534;
            _lblOtherTransaction.Height = 18;
            _lblOtherTransaction.Font = new Font("Segoe UI", 8.5F, FontStyle.Bold);
            _lblOtherTransaction.ForeColor = Color.FromArgb(100, 116, 139);
            _lblOtherTransaction.Visible = false;

            _txtOtherTransaction.SetBounds(22, y + 24, 534, 96);
            _txtOtherTransaction.Font = new Font("Segoe UI", 10.5F);
            _txtOtherTransaction.Multiline = true;
            _txtOtherTransaction.ScrollBars = ScrollBars.Vertical;
            _txtOtherTransaction.BorderStyle = BorderStyle.FixedSingle;
            _txtOtherTransaction.BackColor = Color.FromArgb(241, 245, 249);
            _txtOtherTransaction.Enabled = false;
            _txtOtherTransaction.Visible = false;

            panelBody.Controls.Add(_lblOtherTransaction);
            panelBody.Controls.Add(_txtOtherTransaction);
            y += 138;

            // Location hint
            _lblLocationHint.Text = "Loading Philippine location data…";
            _lblLocationHint.Left = 22;
            _lblLocationHint.Top = y;
            _lblLocationHint.Width = 534;
            _lblLocationHint.Height = 20;
            _lblLocationHint.Font = new Font("Segoe UI", 8.5F, FontStyle.Italic);
            _lblLocationHint.ForeColor = Color.FromArgb(100, 116, 139);
            panelBody.Controls.Add(_lblLocationHint);
            y += 32;

            panelBody.Height = y + 16;

            // ── Action buttons ──────────────────────────────────────────────────
            var btnSave = new Button
            {
                Text = "Save Entry",
                Left = 16,
                Top = panelBody.Bottom + 14,
                Width = 278,
                Height = 48,
                Font = new Font("Segoe UI", 10.5F, FontStyle.Bold),
                BackColor = Color.FromArgb(37, 99, 235),
                ForeColor = Color.White,
                FlatStyle = FlatStyle.Flat,
                DialogResult = DialogResult.OK,
                Cursor = Cursors.Hand
            };
            btnSave.FlatAppearance.BorderSize = 0;
            btnSave.FlatAppearance.MouseOverBackColor = Color.FromArgb(29, 78, 216);
            btnSave.FlatAppearance.MouseDownBackColor = Color.FromArgb(23, 64, 180);
            btnSave.Click += (_, __) =>
            {
                if (!ValidateInputs()) DialogResult = DialogResult.None;
            };

            var btnCancel = new Button
            {
                Text = "Cancel",
                Left = 308,
                Top = panelBody.Bottom + 14,
                Width = 278,
                Height = 48,
                Font = new Font("Segoe UI", 10.5F, FontStyle.Bold),
                BackColor = Color.FromArgb(22, 40, 62),
                ForeColor = Color.FromArgb(180, 210, 240),
                FlatStyle = FlatStyle.Flat,
                DialogResult = DialogResult.Cancel,
                Cursor = Cursors.Hand
            };
            btnCancel.FlatAppearance.BorderSize = 1;
            btnCancel.FlatAppearance.BorderColor = Color.FromArgb(50, 80, 120);
            btnCancel.FlatAppearance.MouseOverBackColor = Color.FromArgb(30, 55, 85);
            btnCancel.FlatAppearance.MouseDownBackColor = Color.FromArgb(15, 35, 60);

            KeyPreview = true;
            CancelButton = btnCancel;
            KeyDown += (_, e) =>
            {
                if (e.KeyCode != Keys.Escape) return;
                DialogResult = DialogResult.Cancel;
                Close();
            };

            panelScrollable.Controls.Add(panelBody);
            panelScrollable.Controls.Add(btnSave);
            panelScrollable.Controls.Add(btnCancel);
            panelScrollable.AutoScrollMinSize = new Size(0, btnCancel.Bottom + 24);

            Controls.AddRange(new Control[] { panelHeader, pnlHeaderAccent, panelScrollable });

            PopulatePurposeOptions();
            SyncOtherTransactionVisibility();
            Load += async (_, __) => await LoadRegionsAsync();
            _cmbRegion.SelectedIndexChanged += async (_, __) => await OnRegionChangedAsync();
            _cmbProvince.SelectedIndexChanged += async (_, __) => await OnProvinceChangedAsync();
            _cmbCityMunicipality.SelectedIndexChanged += async (_, __) => await OnCityChangedAsync();
            _cmbBarangay.SelectedIndexChanged += (_, __) => OnBarangayChanged();
            _cmbPurpose.SelectedIndexChanged += (_, __) => SyncOtherTransactionVisibility();
            _chkOtherPurpose.CheckedChanged += (_, __) => SyncOtherTransactionVisibility();
            DesktopTheme.Apply(this);
        }

        private bool ValidateInputs()
        {
            if (string.IsNullOrWhiteSpace(VisitorFullName) ||
                _cmbRegion.SelectedIndex < 0 ||
                _cmbProvince.SelectedIndex < 0 ||
                _cmbCityMunicipality.SelectedIndex < 0 ||
                _cmbBarangay.SelectedIndex < 0 ||
                string.IsNullOrWhiteSpace(_txtPurokStreetHouseNo.Text) ||
                string.IsNullOrWhiteSpace(VisitorContactNumber) ||
                ((!_chkOtherPurpose.Checked &&
                  !string.Equals(_cmbPurpose.SelectedItem as string, PurposeOfVisitCatalog.Other, StringComparison.OrdinalIgnoreCase)) &&
                 _cmbPurpose.SelectedIndex < 0) ||
                ((_chkOtherPurpose.Checked ||
                  string.Equals(_cmbPurpose.SelectedItem as string, PurposeOfVisitCatalog.Other, StringComparison.OrdinalIgnoreCase)) &&
                 string.IsNullOrWhiteSpace(_txtOtherTransaction.Text)))
            {
                MessageBox.Show("Please complete all required fields.", "Validation",
                    MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return false;
            }
            return true;
        }

        private async Task LoadRegionsAsync()
        {
            if (_isLocationLoading) return;
            _isLocationLoading = true;
            try
            {
                var regions = await FetchGeoItemsAsync($"{PsgcBaseUrl}/regions/");
                if (regions.Count == 0) regions = GetFallbackRegions();

                _cmbRegion.Items.Clear();
                foreach (var item in regions) _cmbRegion.Items.Add(item.Name);
                _cmbRegion.Tag = regions;
                PinRecentToTop(_cmbRegion,
                    GetRecentsOrDefault(Settings.Default.RecentVisitorRegions, DefaultRegionTop1));
                _cmbRegion.Enabled = _cmbRegion.Items.Count > 0;
                _lblLocationHint.Text = _cmbRegion.Items.Count > 0
                    ? "Philippine locations loaded — select a region to begin."
                    : "Could not load location data. Please check connection.";
            }
            finally { _isLocationLoading = false; }
        }

        private async Task OnRegionChangedAsync()
        {
            if (_cmbRegion.SelectedIndex < 0) return;
            int selectionVersion = Interlocked.Increment(ref _regionSelectionVersion);
            RememberRecent(() => Settings.Default.RecentVisitorRegions,
                v => Settings.Default.RecentVisitorRegions = v,
                _cmbRegion.SelectedItem as string);

            var regions = _cmbRegion.Tag as List<GeoItem>;
            if (regions == null) return;
            string selectedName = _cmbRegion.SelectedItem as string;
            var selected = regions.FirstOrDefault(r => string.Equals(r.Name, selectedName, StringComparison.OrdinalIgnoreCase));
            if (selected == null) return;
            ResetLowerLocationFields(0);
            _lblLocationHint.Text = "Loading provinces…";
            var provinces = await FetchGeoItemsAsync($"{PsgcBaseUrl}/regions/{selected.Code}/provinces/");
            if (selectionVersion != _regionSelectionVersion) return;
            if (provinces.Count == 0) provinces = GetFallbackProvinces(selected.Code);
            _cmbProvince.Items.Clear();
            foreach (var p in provinces) _cmbProvince.Items.Add(p.Name);
            _cmbProvince.Tag = provinces;
            PinRecentToTop(_cmbProvince,
                GetRecentsOrDefault(Settings.Default.RecentVisitorProvinces, DefaultProvinceTop1));
            _cmbProvince.Enabled = provinces.Count > 0;
            _lblLocationHint.Text = provinces.Count > 0 ? "Select province." : "No provinces found.";
        }

        private async Task OnProvinceChangedAsync()
        {
            if (_cmbProvince.SelectedIndex < 0) return;
            int selectionVersion = Interlocked.Increment(ref _provinceSelectionVersion);
            RememberRecent(() => Settings.Default.RecentVisitorProvinces,
                v => Settings.Default.RecentVisitorProvinces = v,
                _cmbProvince.SelectedItem as string);

            var provinces = _cmbProvince.Tag as List<GeoItem>;
            if (provinces == null) return;
            string selectedName = _cmbProvince.SelectedItem as string;
            var selected = provinces.FirstOrDefault(p => string.Equals(p.Name, selectedName, StringComparison.OrdinalIgnoreCase));
            if (selected == null) return;
            ResetLowerLocationFields(1);
            _lblLocationHint.Text = "Loading cities / municipalities…";
            var cities = await FetchGeoItemsAsync($"{PsgcBaseUrl}/provinces/{selected.Code}/cities-municipalities/");
            if (selectionVersion != _provinceSelectionVersion) return;
            if (cities.Count == 0) cities = GetFallbackCities(selected.Code);
            _cmbCityMunicipality.Items.Clear();
            foreach (var c in cities) _cmbCityMunicipality.Items.Add(c.Name);
            _cmbCityMunicipality.Tag = cities;
            PinRecentToTop(_cmbCityMunicipality,
                GetRecentsOrDefault(Settings.Default.RecentVisitorCities, DefaultCityTop1));
            _cmbCityMunicipality.Enabled = cities.Count > 0;
            _lblLocationHint.Text = cities.Count > 0 ? "Select city / municipality." : "No cities found.";
        }

        private async Task OnCityChangedAsync()
        {
            if (_cmbCityMunicipality.SelectedIndex < 0) return;
            int selectionVersion = Interlocked.Increment(ref _citySelectionVersion);
            RememberRecent(() => Settings.Default.RecentVisitorCities,
                v => Settings.Default.RecentVisitorCities = v,
                _cmbCityMunicipality.SelectedItem as string);

            var cities = _cmbCityMunicipality.Tag as List<GeoItem>;
            if (cities == null) return;
            string selectedName = _cmbCityMunicipality.SelectedItem as string;
            var selected = cities.FirstOrDefault(c => string.Equals(c.Name, selectedName, StringComparison.OrdinalIgnoreCase));
            if (selected == null) return;
            ResetLowerLocationFields(2);
            _lblLocationHint.Text = "Loading barangays…";
            var barangays = await FetchGeoItemsAsync($"{PsgcBaseUrl}/cities-municipalities/{selected.Code}/barangays/");
            if (selectionVersion != _citySelectionVersion) return;
            if (barangays.Count == 0) barangays = GetFallbackBarangays(selected.Code);
            _cmbBarangay.Items.Clear();
            foreach (var b in barangays) _cmbBarangay.Items.Add(b.Name);
            _cmbBarangay.Tag = barangays;
            PinRecentToTop(_cmbBarangay,
                GetRecentsOrDefault(Settings.Default.RecentVisitorBarangays, DefaultBarangayTop1));
            _cmbBarangay.Enabled = barangays.Count > 0;
            _lblLocationHint.Text = barangays.Count > 0 ? "Select barangay." : "No barangays found.";
        }

        private void OnBarangayChanged()
        {
            if (_cmbBarangay.SelectedIndex < 0) return;
            RememberRecent(() => Settings.Default.RecentVisitorBarangays,
                v => Settings.Default.RecentVisitorBarangays = v,
                _cmbBarangay.SelectedItem as string);
            ResetLowerLocationFields(3);
            _lblLocationHint.Text = "Enter Purok / Street / House No. Use 'Other' for non-local addresses.";
        }

        private void SyncOtherTransactionVisibility()
        {
            bool checkboxOther = _chkOtherPurpose.Checked;
            bool selectedOther = string.Equals(
                _cmbPurpose.SelectedItem as string,
                PurposeOfVisitCatalog.Other,
                StringComparison.OrdinalIgnoreCase);
            bool isOther = checkboxOther || selectedOther;
            if (checkboxOther)
            {
                if (_cmbPurpose.SelectedIndex != -1) _cmbPurpose.SelectedIndex = -1;
                _cmbPurpose.Enabled = false;
                _cmbPurpose.BackColor = Color.FromArgb(241, 245, 249);
            }
            else
            {
                _cmbPurpose.Enabled = true;
                _cmbPurpose.BackColor = Color.FromArgb(248, 250, 252);
            }
            _txtOtherTransaction.Enabled = isOther;
            _txtOtherTransaction.Visible = isOther;
            _lblOtherTransaction.Visible = isOther;
            _txtOtherTransaction.BackColor = isOther
                ? Color.FromArgb(248, 250, 252)
                : Color.FromArgb(241, 245, 249);
            if (!isOther) _txtOtherTransaction.Clear();
        }

        private void ResetLowerLocationFields(int fromLevel)
        {
            if (fromLevel <= 0) { _cmbProvince.Items.Clear(); _cmbProvince.Tag = null; _cmbProvince.Enabled = false; }
            if (fromLevel <= 1) { _cmbCityMunicipality.Items.Clear(); _cmbCityMunicipality.Tag = null; _cmbCityMunicipality.Enabled = false; }
            if (fromLevel <= 2) { _cmbBarangay.Items.Clear(); _cmbBarangay.Tag = null; _cmbBarangay.Enabled = false; }
        }

        private void PopulatePurposeOptions()
        {
            _cmbPurpose.Items.Clear();
            _cmbPurpose.Items.AddRange(PurposeOfVisitCatalog.GetOptions());
            _cmbPurpose.Enabled = true;
        }

        private async Task<List<GeoItem>> FetchGeoItemsAsync(string url)
        {
            if (_geoCache.TryGetValue(url, out var cachedItems))
                return new List<GeoItem>(cachedItems);

            try
            {
                string json = await _http.GetStringAsync(url);
                var raw = DeserializePsgcItems(json);
                if (raw == null || raw.Length == 0) return new List<GeoItem>();
                var items = new List<GeoItem>();
                foreach (var entry in raw)
                {
                    if (!string.IsNullOrWhiteSpace(entry.Code) && !string.IsNullOrWhiteSpace(entry.Name))
                        items.Add(new GeoItem(entry.Code, entry.Name));
                }
                var sorted = items.OrderBy(i => i.Name).ToList();
                _geoCache[url] = sorted;
                return new List<GeoItem>(sorted);
            }
            catch { return new List<GeoItem>(); }
        }

        private static PsgcItem[] DeserializePsgcItems(string json)
        {
            if (string.IsNullOrWhiteSpace(json)) return Array.Empty<PsgcItem>();
            var matches = Regex.Matches(json,
                "\"code\"\\s*:\\s*\"(?<code>[^\"]+)\"\\s*,\\s*\"name\"\\s*:\\s*\"(?<name>[^\"]+)\"",
                RegexOptions.CultureInvariant);
            var items = new List<PsgcItem>(matches.Count);
            foreach (Match m in matches)
                items.Add(new PsgcItem { Code = m.Groups["code"].Value, Name = m.Groups["name"].Value });
            return items.ToArray();
        }

        private static List<GeoItem> GetFallbackRegions() =>
            new List<GeoItem> { new GeoItem("110000000", "Davao Region") };

        private static List<GeoItem> GetFallbackProvinces(string code) =>
            code == "110000000"
                ? new List<GeoItem> { new GeoItem("112400000", "Davao Del Sur") }
                : new List<GeoItem>();

        private static List<GeoItem> GetFallbackCities(string code) =>
            code == "112400000"
                ? new List<GeoItem> { new GeoItem("112403000", "City of Digos") }
                : new List<GeoItem>();

        private static List<GeoItem> GetFallbackBarangays(string code)
        {
            if (code != "112403000") return new List<GeoItem>();
            return new List<GeoItem>
            {
                new GeoItem("112403001","Aplaya"),   new GeoItem("112403002","Balabag"),
                new GeoItem("112403003","Cogon"),    new GeoItem("112403004","Dulangan"),
                new GeoItem("112403005","Goma"),     new GeoItem("112403006","Kapatagan"),
                new GeoItem("112403007","Matti"),    new GeoItem("112403008","San Agustin")
            };
        }

        private static List<string> ParseRecents(string raw)
        {
            if (string.IsNullOrWhiteSpace(raw)) return new List<string>();
            return raw.Split(new[] { RecentSeparator }, StringSplitOptions.RemoveEmptyEntries)
                      .Select(p => p.Trim()).Where(p => !string.IsNullOrWhiteSpace(p))
                      .Distinct(StringComparer.OrdinalIgnoreCase).Take(MaxRecentPlaces).ToList();
        }

        private static List<string> GetRecentsOrDefault(string raw, string defaultTop1)
        {
            var r = ParseRecents(raw);
            return r.Count > 0 ? r :
                   string.IsNullOrWhiteSpace(defaultTop1) ? r : new List<string> { defaultTop1 };
        }

        private static void RememberRecent(Func<string> get, Action<string> set, string selected)
        {
            if (string.IsNullOrWhiteSpace(selected)) return;
            var recents = ParseRecents(get());
            recents.RemoveAll(s => string.Equals(s, selected, StringComparison.OrdinalIgnoreCase));
            recents.Insert(0, selected.Trim());
            if (recents.Count > MaxRecentPlaces) recents = recents.Take(MaxRecentPlaces).ToList();
            set(string.Join(RecentSeparator.ToString(), recents));
            Settings.Default.Save();
        }

        private static void PinRecentToTop(ComboBox combo, List<string> recents)
        {
            if (combo?.Items.Count == 0 || recents == null || recents.Count == 0) return;
            var items = combo.Items.Cast<object>().Select(o => o?.ToString()).Where(s => !string.IsNullOrWhiteSpace(s)).ToList();
            var pinned = recents.Select(r => items.FirstOrDefault(i => string.Equals(i, r, StringComparison.OrdinalIgnoreCase)))
                                .Where(m => m != null).ToList();
            if (pinned.Count == 0) return;
            combo.BeginUpdate();
            try
            {
                string sel = combo.SelectedItem as string;
                combo.Items.Clear();
                foreach (var p in pinned) combo.Items.Add(p);
                foreach (var i in items)
                    if (!pinned.Any(p => string.Equals(p, i, StringComparison.OrdinalIgnoreCase)))
                        combo.Items.Add(i);
                if (!string.IsNullOrWhiteSpace(sel))
                {
                    int idx = combo.FindStringExact(sel);
                    if (idx >= 0) combo.SelectedIndex = idx;
                }
            }
            finally { combo.EndUpdate(); }
        }

        private sealed class GeoItem
        {
            public string Code { get; }
            public string Name { get; }
            public GeoItem(string code, string name) { Code = code; Name = name; }
        }

        private sealed class PsgcItem
        {
            public string Code { get; set; }
            public string Name { get; set; }
        }
    }
}