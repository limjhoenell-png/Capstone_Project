# One-off generator: extract barangays from EnrollForm and emit a partial class.
from pathlib import Path

root = Path(r"c:\Users\Vannydraxbading\source\repos\FingerprintAttendance\FingerprintAttendance")
src = (root / "EnrollForm.cs").read_text(encoding="utf-8")
start = src.find("        private static List<GeoItem> GetBuiltInBarangays")
end = src.find("        private Task OnRegionChangedAsync()")
if start < 0 or end < 0:
    raise SystemExit("Could not find barangay method bounds")
body = src[start:end]
body = body.replace("        private static List<GeoItem> GetBuiltInBarangays",
                    "        public static List<GeoItem> GetBarangays")
# de-indent by 0 (keep as method body of a class)
out = """using System.Collections.Generic;

namespace FingerprintAttendance.Core
{
    public static partial class PhilippineGeoData
    {
""" + body.replace("        ", "        ", 1) + """    }
}
"""
# The body already has 8-space indent for method; class wants 8-space for method too.
(root / "Core" / "PhilippineGeoData.Barangays.cs").write_text(out, encoding="utf-8")
print("Wrote Barangays, chars", len(out))
