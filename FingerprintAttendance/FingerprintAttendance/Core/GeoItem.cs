namespace FingerprintAttendance.Core
{
    public sealed class GeoItem
    {
        public string Code { get; }
        public string Name { get; }

        public GeoItem(string code, string name)
        {
            Code = code ?? string.Empty;
            Name = name ?? string.Empty;
        }

        public override string ToString()
        {
            return Name;
        }
    }
}
