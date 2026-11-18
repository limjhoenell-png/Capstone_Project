using System;
using System.Collections.Generic;
using System.IO;
using System.IO.Compression;
using System.Reflection;
using System.Text;

namespace FingerprintAttendance.Core
{
    /// <summary>
    /// Offline Philippine Standard Geographic Code (PSGC) lookup.
    /// Data is loaded from local TSV files shipped with the application.
    /// </summary>
    public static class PhilippineGeoData
    {
        private static readonly object Sync = new object();
        private static bool _loaded;
        private static string _loadError;
        private static List<GeoItem> _regions = new List<GeoItem>();
        private static readonly Dictionary<string, List<GeoItem>> ProvincesByRegion =
            new Dictionary<string, List<GeoItem>>(StringComparer.Ordinal);
        private static readonly Dictionary<string, List<GeoItem>> CitiesByProvince =
            new Dictionary<string, List<GeoItem>>(StringComparer.Ordinal);
        private static readonly Dictionary<string, List<GeoItem>> BarangaysByCity =
            new Dictionary<string, List<GeoItem>>(StringComparer.Ordinal);

        public static string LoadError
        {
            get
            {
                EnsureLoaded();
                return _loadError;
            }
        }

        public static List<GeoItem> GetRegions()
        {
            EnsureLoaded();
            return Copy(_regions);
        }

        public static List<GeoItem> GetProvinces(string regionCode)
        {
            EnsureLoaded();
            return Lookup(ProvincesByRegion, regionCode);
        }

        public static List<GeoItem> GetCities(string provinceCode)
        {
            EnsureLoaded();
            var cities = Lookup(CitiesByProvince, provinceCode);
            // PSA lists a parent "City of Manila" row (80600) with no barangays.
            // Barangays belong to the 14 Manila districts, so hide empty parent rows.
            cities.RemoveAll(city => !HasBarangays(city.Code));
            return cities;
        }

        public static List<GeoItem> GetBarangays(string cityCode)
        {
            EnsureLoaded();
            return Lookup(BarangaysByCity, cityCode);
        }

        public static bool IsValidSelection(string regionCode, string provinceCode, string cityCode, string barangayCode)
        {
            EnsureLoaded();
            if (string.IsNullOrWhiteSpace(regionCode) ||
                string.IsNullOrWhiteSpace(provinceCode) ||
                string.IsNullOrWhiteSpace(cityCode) ||
                string.IsNullOrWhiteSpace(barangayCode))
            {
                return false;
            }

            if (!ContainsCode(ProvincesByRegion, regionCode, provinceCode))
                return false;
            if (!ContainsCode(CitiesByProvince, provinceCode, cityCode) || !HasBarangays(cityCode))
                return false;
            if (!ContainsCode(BarangaysByCity, cityCode, barangayCode))
                return false;
            return true;
        }

        private static bool HasBarangays(string cityCode)
        {
            return BarangaysByCity.TryGetValue(cityCode, out var list) && list != null && list.Count > 0;
        }

        private static void EnsureLoaded()
        {
            if (_loaded) return;
            lock (Sync)
            {
                if (_loaded) return;
                try
                {
                    LoadFromDisk();
                    _loadError = null;
                }
                catch (Exception ex)
                {
                    _regions = new List<GeoItem>();
                    ProvincesByRegion.Clear();
                    CitiesByProvince.Clear();
                    BarangaysByCity.Clear();
                    _loadError = ex.Message;
                }
                _loaded = true;
            }
        }

        private static void LoadFromDisk()
        {
            string dataDir = ResolveDataDirectory();
            _regions = LoadSimpleItems(Path.Combine(dataDir, "regions.tsv"));
            LoadChildren(Path.Combine(dataDir, "provinces.tsv"), ProvincesByRegion);
            LoadChildren(Path.Combine(dataDir, "muncities.tsv"), CitiesByProvince);
            LoadChildrenGzip(Path.Combine(dataDir, "barangays.tsv.gz"), BarangaysByCity);

            SortInPlace(_regions);
            foreach (var pair in ProvincesByRegion)
                SortInPlace(pair.Value);
            foreach (var pair in CitiesByProvince)
                SortInPlace(pair.Value);
            foreach (var pair in BarangaysByCity)
                SortInPlace(pair.Value);
        }

        private static string ResolveDataDirectory()
        {
            var candidates = new[]
            {
                Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "Data", "psgc"),
                Path.Combine(Path.GetDirectoryName(Assembly.GetExecutingAssembly().Location) ?? string.Empty, "Data", "psgc"),
                Path.GetFullPath(Path.Combine(AppDomain.CurrentDomain.BaseDirectory, @"..\..\Data\psgc"))
            };

            foreach (string candidate in candidates)
            {
                if (File.Exists(Path.Combine(candidate, "regions.tsv")) &&
                    File.Exists(Path.Combine(candidate, "barangays.tsv.gz")))
                {
                    return candidate;
                }
            }

            throw new FileNotFoundException(
                "Local Philippine geography files were not found. Expected Data\\psgc\\regions.tsv and Data\\psgc\\barangays.tsv.gz next to the application.");
        }

        private static List<GeoItem> LoadSimpleItems(string path)
        {
            var items = new List<GeoItem>();
            foreach (string line in File.ReadAllLines(path, Encoding.UTF8))
            {
                if (string.IsNullOrWhiteSpace(line)) continue;
                int tab = line.IndexOf('\t');
                if (tab <= 0) continue;
                items.Add(new GeoItem(line.Substring(0, tab), line.Substring(tab + 1)));
            }
            return items;
        }

        private static void LoadChildren(string path, Dictionary<string, List<GeoItem>> target)
        {
            foreach (string line in File.ReadAllLines(path, Encoding.UTF8))
                AddChildLine(target, line);
        }

        private static void LoadChildrenGzip(string path, Dictionary<string, List<GeoItem>> target)
        {
            using var fs = File.OpenRead(path);
            using var gzip = new GZipStream(fs, CompressionMode.Decompress);
            using var reader = new StreamReader(gzip, Encoding.UTF8);
            string line;
            while ((line = reader.ReadLine()) != null)
                AddChildLine(target, line);
        }

        private static void AddChildLine(Dictionary<string, List<GeoItem>> target, string line)
        {
            if (string.IsNullOrWhiteSpace(line)) return;
            string[] parts = line.Split('\t');
            if (parts.Length < 3) return;
            string parent = parts[1];
            var item = new GeoItem(parts[0], parts[2]);
            if (!target.TryGetValue(parent, out var list))
            {
                list = new List<GeoItem>();
                target[parent] = list;
            }
            list.Add(item);
        }

        private static List<GeoItem> Lookup(Dictionary<string, List<GeoItem>> source, string parentCode)
        {
            if (string.IsNullOrWhiteSpace(parentCode))
                return new List<GeoItem>();
            return source.TryGetValue(parentCode, out var list) ? Copy(list) : new List<GeoItem>();
        }

        private static bool ContainsCode(Dictionary<string, List<GeoItem>> source, string parentCode, string childCode)
        {
            if (!source.TryGetValue(parentCode, out var list))
                return false;
            for (int i = 0; i < list.Count; i++)
            {
                if (string.Equals(list[i].Code, childCode, StringComparison.Ordinal))
                    return true;
            }
            return false;
        }

        private static List<GeoItem> Copy(List<GeoItem> source)
        {
            return source == null ? new List<GeoItem>() : new List<GeoItem>(source);
        }

        private static void SortInPlace(List<GeoItem> items)
        {
            items.Sort((a, b) => string.Compare(a.Name, b.Name, StringComparison.CurrentCultureIgnoreCase));
        }
    }
}
