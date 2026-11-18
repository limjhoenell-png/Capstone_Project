from pathlib import Path

p = Path(r"c:\Users\Vannydraxbading\source\repos\FingerprintAttendance\FingerprintAttendance\EnrollForm.cs")
text = p.read_text(encoding="utf-8")
idx = text.find("        private Task LoadRegionsAsync()")
if idx < 0:
    raise SystemExit("anchor not found")
prefix = text[:idx]
prefix = prefix.replace("using System.Net.Http;\r\n", "").replace("using System.Net.Http;\n", "")

old_fields = """        private static readonly HttpClient _http = new HttpClient { Timeout = TimeSpan.FromSeconds(6) };
        private static readonly Dictionary<string, List<GeoItem>> _geoCache =
            new Dictionary<string, List<GeoItem>>(StringComparer.OrdinalIgnoreCase);
        private const string PsgcBaseUrl = "https://psgc.gitlab.io/api";
        private bool _isLocationLoading;
        private int _regionSelectionVersion;
        private int _provinceSelectionVersion;
        private int _citySelectionVersion;
"""
new_fields = """        private bool _suppressLocationEvents;
"""
if old_fields not in prefix:
    raise SystemExit("OLD FIELDS NOT FOUND")
prefix = prefix.replace(old_fields, new_fields)

old_events = """            Load += async (_, __) => await LoadRegionsAsync();
            cmbRegion.SelectedIndexChanged += async (_, __) => await OnRegionChangedAsync();
            cmbProvince.SelectedIndexChanged += async (_, __) => await OnProvinceChangedAsync();
            cmbCityMunicipality.SelectedIndexChanged += async (_, __) => await OnCityChangedAsync();
"""
new_events = """            Load += (_, __) => LoadRegions();
            cmbRegion.SelectedIndexChanged += (_, __) => OnRegionChanged();
            cmbProvince.SelectedIndexChanged += (_, __) => OnProvinceChanged();
            cmbCityMunicipality.SelectedIndexChanged += (_, __) => OnCityChanged();
"""
if old_events not in prefix:
    raise SystemExit("events not found")
prefix = prefix.replace(old_events, new_events)

old_val = """                cmbRegion.SelectedIndex < 0 ||
                cmbProvince.SelectedIndex < 0 ||
                cmbCityMunicipality.SelectedIndex < 0 ||
                cmbBarangay.SelectedIndex < 0 ||
"""
new_val = """                SelectedLocation(cmbRegion) == null ||
                SelectedLocation(cmbProvince) == null ||
                SelectedLocation(cmbCityMunicipality) == null ||
                SelectedLocation(cmbBarangay) == null ||
"""
if old_val not in prefix:
    raise SystemExit("validation not found")
prefix = prefix.replace(old_val, new_val)

old_addr = """                var parts = new[]
                {
                    txtStreetDetails.Text.Trim(),
                    cmbBarangay.SelectedItem as string,
                    cmbCityMunicipality.SelectedItem as string,
                    cmbProvince.SelectedItem as string,
                    cmbRegion.SelectedItem as string
                };"""
new_addr = """                var parts = new[]
                {
                    txtStreetDetails.Text.Trim(),
                    SelectedLocationName(cmbBarangay),
                    SelectedLocationName(cmbCityMunicipality),
                    SelectedLocationName(cmbProvince),
                    SelectedLocationName(cmbRegion)
                };"""
if old_addr not in prefix:
    raise SystemExit("address block not found")
prefix = prefix.replace(old_addr, new_addr)

old_ret = """            return true;
        }

        private string EmployeeAddress
"""
new_ret = """            if (!PhilippineGeoData.IsValidSelection(
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

        private string EmployeeAddress
"""
if old_ret not in prefix:
    raise SystemExit("return true block not found")
prefix = prefix.replace(old_ret, new_ret)

geo = r'''
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
'''

p.write_text(prefix + geo, encoding="utf-8")
updated = p.read_text(encoding="utf-8")
print("Wrote EnrollForm.cs, chars", len(updated))
print("HttpClient", "HttpClient" in updated)
print("psgc.gitlab", "psgc.gitlab" in updated)
print("GetBuiltIn", "GetBuiltIn" in updated)
print("PhilippineGeoData", "PhilippineGeoData" in updated)
