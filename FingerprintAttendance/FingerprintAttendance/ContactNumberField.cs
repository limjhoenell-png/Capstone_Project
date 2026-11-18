using System;
using System.Drawing;
using System.Text;
using System.Windows.Forms;

namespace FingerprintAttendance
{
    /// <summary>
    /// Country-code dropdown plus local digits field. Matches existing CONTACT NUMBER row styling.
    /// Philippine numbers default to +63 and local prefix 09, stored as +63 9XXXXXXXXX.
    /// </summary>
    public sealed class ContactNumberField : Panel
    {
        private readonly ComboBox _cmbCountry = new ComboBox();
        private readonly DigitTextBox _txtLocal = new DigitTextBox();
        private bool _suppress;
        private bool _locked;
        private string _lockedStored = string.Empty;

        public ContactNumberField()
        {
            Height = 34;
            BackColor = Color.Transparent;
            TabStop = false;

            _cmbCountry.DropDownStyle = ComboBoxStyle.DropDownList;
            _cmbCountry.IntegralHeight = false;
            _cmbCountry.MaxDropDownItems = 12;
            _cmbCountry.Font = new Font("Segoe UI", 10.5F);
            _cmbCountry.BackColor = Color.FromArgb(248, 250, 252);
            _cmbCountry.FlatStyle = FlatStyle.Standard;

            _txtLocal.BorderStyle = BorderStyle.FixedSingle;
            _txtLocal.BackColor = Color.FromArgb(248, 250, 252);
            _txtLocal.Font = new Font("Segoe UI", 10.5F);
            _txtLocal.MaxLength = 20;

            Controls.Add(_cmbCountry);
            Controls.Add(_txtLocal);

            foreach (var country in CountryCallingCode.All)
                _cmbCountry.Items.Add(country);

            _cmbCountry.SelectedIndex = 0;
            ApplyPhilippinePrefix();

            _cmbCountry.SelectedIndexChanged += (_, __) => OnCountryChanged();
            _txtLocal.KeyPress += TxtLocal_KeyPress;
            _txtLocal.KeyDown += TxtLocal_KeyDown;
            _txtLocal.Enter += (_, __) => ProtectPhilippineCaret();
            _txtLocal.Click += (_, __) => ProtectPhilippineCaret();
            _txtLocal.TextChanged += (_, __) => SanitizeLocalNumber();
            Resize += (_, __) => LayoutChildren();
            LayoutChildren();
        }

        public void ApplyVisitorStyle()
        {
            Height = 34;
            _cmbCountry.Font = new Font("Segoe UI", 10.5F);
            _txtLocal.Font = new Font("Segoe UI", 10.5F);
            LayoutChildren();
        }

        public void ApplyEnrollStyle()
        {
            Height = 32;
            _cmbCountry.Font = new Font("Segoe UI", 10.5F);
            _txtLocal.Font = new Font("Segoe UI", 11F);
            LayoutChildren();
        }

        public string StoredNumber
        {
            get
            {
                if (_locked)
                    return _lockedStored ?? string.Empty;
                TryFormat(out string formatted, out _);
                return formatted ?? string.Empty;
            }
        }

        public bool TryValidate(out string storedNumber, out string error)
        {
            if (_locked)
            {
                storedNumber = _lockedStored ?? string.Empty;
                error = null;
                return !string.IsNullOrWhiteSpace(storedNumber);
            }

            return TryFormat(out storedNumber, out error);
        }

        public bool TryValidateOptional(out string storedNumber, out string error)
        {
            if (_locked)
            {
                storedNumber = _lockedStored ?? string.Empty;
                error = null;
                return true;
            }

            string local = DigitsOnly(_txtLocal.Text);
            var country = SelectedCountry;
            if (string.IsNullOrEmpty(local) || (country != null && country.IsPhilippines && local == "09"))
            {
                storedNumber = string.Empty;
                error = null;
                return true;
            }

            return TryFormat(out storedNumber, out error);
        }

        public void FocusInput()
        {
            _txtLocal.Focus();
            ProtectPhilippineCaret();
        }

        public void SetLockedNumber(string storedNumber)
        {
            LoadStoredNumber(storedNumber);
            _locked = true;
            _lockedStored = FormatOrKeep(storedNumber);
            _cmbCountry.Enabled = false;
            _txtLocal.ReadOnly = true;
            _txtLocal.BackColor = Color.FromArgb(241, 245, 249);
            _cmbCountry.BackColor = Color.FromArgb(241, 245, 249);
        }

        public void LoadStoredNumber(string storedNumber)
        {
            _suppress = true;
            try
            {
                if (!CountryCallingCode.TryParse(storedNumber, out var country, out string localDigits))
                {
                    _cmbCountry.SelectedIndex = 0;
                    _txtLocal.Text = string.IsNullOrWhiteSpace(storedNumber)
                        ? "09"
                        : NormalizePhilippineLocal(DigitsOnly(storedNumber));
                    return;
                }

                int index = IndexOfCountry(country);
                if (index >= 0)
                    _cmbCountry.SelectedIndex = index;

                _txtLocal.Text = localDigits;
            }
            finally
            {
                _suppress = false;
            }
        }

        private int IndexOfCountry(CountryCallingCode country)
        {
            if (country == null) return -1;
            for (int i = 0; i < _cmbCountry.Items.Count; i++)
            {
                var item = (CountryCallingCode)_cmbCountry.Items[i];
                if (item.Name == country.Name && item.DialCode == country.DialCode)
                    return i;
            }
            return -1;
        }

        private CountryCallingCode SelectedCountry
        {
            get { return _cmbCountry.SelectedItem as CountryCallingCode; }
        }

        private void LayoutChildren()
        {
            const int gap = 8;
            int comboWidth = Math.Min(248, Math.Max(168, Width / 3));
            _cmbCountry.SetBounds(0, 0, comboWidth, Height);
            int localLeft = comboWidth + gap;
            _txtLocal.SetBounds(localLeft, 0, Math.Max(80, Width - localLeft), Height);
        }

        private void OnCountryChanged()
        {
            if (_suppress || _locked) return;

            var country = SelectedCountry;
            if (country != null && country.IsPhilippines)
            {
                ApplyPhilippinePrefix();
                return;
            }

            string digits = DigitsOnly(_txtLocal.Text);
            if (digits == "09" || digits == "0")
                SetLocalText(string.Empty, 0);
            else if (digits.StartsWith("09", StringComparison.Ordinal) && digits.Length == 11)
                SetLocalText(digits.Substring(1), digits.Length - 1);
        }

        private void TxtLocal_KeyPress(object sender, KeyPressEventArgs e)
        {
            if (char.IsControl(e.KeyChar))
                return;
            if (!char.IsDigit(e.KeyChar))
                e.Handled = true;
        }

        private void TxtLocal_KeyDown(object sender, KeyEventArgs e)
        {
            if (_locked) return;
            var country = SelectedCountry;
            if (country == null || !country.IsPhilippines)
                return;

            if (_txtLocal.SelectionLength > 0)
            {
                if ((e.KeyCode == Keys.Back || e.KeyCode == Keys.Delete) &&
                    _txtLocal.SelectionStart < 2)
                {
                    e.SuppressKeyPress = true;
                    e.Handled = true;
                    int from = Math.Max(2, _txtLocal.SelectionStart);
                    int to = _txtLocal.SelectionStart + _txtLocal.SelectionLength;
                    if (to > from)
                    {
                        string next = _txtLocal.Text.Remove(from, to - from);
                        SetLocalText(NormalizePhilippineLocal(DigitsOnly(next)), from);
                    }
                }
                return;
            }

            if (e.KeyCode == Keys.Back && _txtLocal.SelectionStart <= 2)
            {
                e.SuppressKeyPress = true;
                e.Handled = true;
            }
            else if (e.KeyCode == Keys.Delete && _txtLocal.SelectionStart < 2)
            {
                e.SuppressKeyPress = true;
                e.Handled = true;
            }
            else if (e.KeyCode == Keys.Left && _txtLocal.SelectionStart <= 2)
            {
                e.Handled = true;
                _txtLocal.SelectionStart = 2;
            }
            else if (e.KeyCode == Keys.Home)
            {
                e.Handled = true;
                _txtLocal.SelectionStart = 2;
            }
        }

        private void ProtectPhilippineCaret()
        {
            if (_locked || _suppress) return;
            var country = SelectedCountry;
            if (country == null || !country.IsPhilippines) return;
            if (_txtLocal.SelectionLength > 0) return;
            if (_txtLocal.SelectionStart < 2)
                _txtLocal.SelectionStart = Math.Max(2, _txtLocal.Text.Length);
        }

        private void SanitizeLocalNumber()
        {
            if (_suppress || _locked) return;

            string digits = DigitsOnly(_txtLocal.Text);
            var country = SelectedCountry;
            int caret = _txtLocal.SelectionStart;

            if (country != null && country.IsPhilippines)
                digits = NormalizePhilippineLocal(digits);

            int maxLen = country == null ? 15 : country.MaxLocalLength;
            if (digits.Length > maxLen)
                digits = digits.Substring(0, maxLen);

            if (_txtLocal.Text == digits)
            {
                ProtectPhilippineCaret();
                return;
            }

            int removed = _txtLocal.Text.Length - digits.Length;
            int minCaret = country != null && country.IsPhilippines ? 2 : 0;
            SetLocalText(digits, Math.Max(minCaret, caret - Math.Max(0, removed)));
        }

        private void ApplyPhilippinePrefix()
        {
            _suppress = true;
            try
            {
                ApplyPhilippinePrefixCore();
            }
            finally
            {
                _suppress = false;
            }
        }

        private void ApplyPhilippinePrefixCore()
        {
            string digits = NormalizePhilippineLocal(DigitsOnly(_txtLocal.Text));
            SetLocalText(digits, Math.Max(2, digits.Length));
        }

        internal static string NormalizePhilippineLocal(string digits)
        {
            if (string.IsNullOrEmpty(digits))
                return "09";

            if (digits.StartsWith("63", StringComparison.Ordinal) && digits.Length >= 11)
            {
                string intl = digits.Substring(2);
                if (intl.StartsWith("0", StringComparison.Ordinal))
                    digits = intl;
                else if (intl.StartsWith("9", StringComparison.Ordinal))
                    digits = "0" + intl;
            }

            // Default "09" plus a full 10-digit national number (9171234567) or local 11-digit (0917...).
            if (digits.StartsWith("09", StringComparison.Ordinal) && digits.Length > 11)
            {
                string rest = digits.Substring(2);
                if (rest.StartsWith("09", StringComparison.Ordinal))
                    digits = rest;
                else if (rest.StartsWith("9", StringComparison.Ordinal))
                    digits = "0" + rest;
            }

            if (digits.StartsWith("9", StringComparison.Ordinal) && !digits.StartsWith("09", StringComparison.Ordinal))
                digits = "0" + digits;

            if (digits == "0" || digits == "09")
                return "09";

            if (!digits.StartsWith("09", StringComparison.Ordinal))
            {
                if (digits.StartsWith("0", StringComparison.Ordinal))
                    digits = "09" + digits.TrimStart('0');
                else
                    digits = "09" + digits;
            }

            if (!digits.StartsWith("09", StringComparison.Ordinal))
                digits = "09";

            if (digits.Length > 11)
                digits = digits.Substring(0, 11);

            return digits;
        }

        private bool TryFormat(out string storedNumber, out string error)
        {
            storedNumber = string.Empty;
            var country = SelectedCountry;
            if (country == null)
            {
                error = "Please select a country calling code.";
                return false;
            }

            string local = DigitsOnly(_txtLocal.Text);
            if (!country.IsValidLocal(local, out error))
                return false;

            storedNumber = country.ToStoredNumber(local);
            error = null;
            return true;
        }

        private string FormatOrKeep(string storedNumber)
        {
            if (CountryCallingCode.TryParse(storedNumber, out var country, out string local))
                return country.ToStoredNumber(local);
            return (storedNumber ?? string.Empty).Trim();
        }

        private void SetLocalText(string text, int caret)
        {
            _suppress = true;
            try
            {
                _txtLocal.Text = text ?? string.Empty;
                int max = _txtLocal.Text.Length;
                _txtLocal.SelectionStart = Math.Max(0, Math.Min(max, caret));
            }
            finally
            {
                _suppress = false;
            }
        }

        internal static string DigitsOnly(string value)
        {
            if (string.IsNullOrEmpty(value))
                return string.Empty;
            var sb = new StringBuilder(value.Length);
            for (int i = 0; i < value.Length; i++)
            {
                if (char.IsDigit(value[i]))
                    sb.Append(value[i]);
            }
            return sb.ToString();
        }

        private sealed class DigitTextBox : TextBox
        {
            protected override void WndProc(ref Message m)
            {
                const int WmPaste = 0x0302;
                if (m.Msg == WmPaste)
                {
                    if (Clipboard.ContainsText())
                    {
                        string incoming = DigitsOnly(Clipboard.GetText());
                        int start = SelectionStart;
                        int length = SelectionLength;
                        string next = Text.Remove(start, length).Insert(start, incoming);
                        Text = next;
                        SelectionStart = Math.Min(Text.Length, start + incoming.Length);
                    }
                    return;
                }
                base.WndProc(ref m);
            }
        }
    }

    internal sealed class CountryCallingCode
    {
        public static readonly CountryCallingCode[] All =
        {
            new CountryCallingCode("Philippines", "63", 11, 11, true),
            new CountryCallingCode("Singapore", "65", 8, 8, false),
            new CountryCallingCode("United States", "1", 10, 10, false),
            new CountryCallingCode("Canada", "1", 10, 10, false),
            new CountryCallingCode("Malaysia", "60", 9, 10, false),
            new CountryCallingCode("Indonesia", "62", 9, 12, false),
            new CountryCallingCode("Thailand", "66", 8, 9, false),
            new CountryCallingCode("Vietnam", "84", 9, 10, false),
            new CountryCallingCode("China", "86", 11, 11, false),
            new CountryCallingCode("Hong Kong", "852", 8, 8, false),
            new CountryCallingCode("Japan", "81", 10, 11, false),
            new CountryCallingCode("South Korea", "82", 9, 11, false),
            new CountryCallingCode("Australia", "61", 9, 9, false),
            new CountryCallingCode("United Kingdom", "44", 10, 11, false),
            new CountryCallingCode("India", "91", 10, 10, false),
            new CountryCallingCode("United Arab Emirates", "971", 9, 9, false),
            new CountryCallingCode("Saudi Arabia", "966", 9, 9, false),
            new CountryCallingCode("Taiwan", "886", 9, 9, false)
        };

        private CountryCallingCode(string name, string dialCode, int minLocalLength, int maxLocalLength, bool isPhilippines)
        {
            Name = name;
            DialCode = dialCode;
            MinLocalLength = minLocalLength;
            MaxLocalLength = maxLocalLength;
            IsPhilippines = isPhilippines;
        }

        public string Name { get; }
        public string DialCode { get; }
        public int MinLocalLength { get; }
        public int MaxLocalLength { get; }
        public bool IsPhilippines { get; }

        public override string ToString()
        {
            return Name + " (+" + DialCode + ")";
        }

        public bool IsValidLocal(string localDigits, out string error)
        {
            if (string.IsNullOrEmpty(localDigits) || (IsPhilippines && localDigits == "09"))
            {
                error = "Please enter a contact number.";
                return false;
            }

            if (IsPhilippines)
            {
                if (localDigits.Length != 11 || !localDigits.StartsWith("09", StringComparison.Ordinal))
                {
                    error = "Philippine mobile numbers must be 11 digits in local format (e.g., 09171234567).";
                    return false;
                }

                error = null;
                return true;
            }

            if (localDigits.Length < MinLocalLength || localDigits.Length > MaxLocalLength)
            {
                error = Name + " numbers must be " + LengthHint() + " digits.";
                return false;
            }

            error = null;
            return true;
        }

        public string ToStoredNumber(string localDigits)
        {
            string national = localDigits ?? string.Empty;
            if (IsPhilippines && national.StartsWith("0", StringComparison.Ordinal))
                national = national.Substring(1);
            return "+" + DialCode + " " + national;
        }

        public static bool TryParse(string storedNumber, out CountryCallingCode country, out string localDigits)
        {
            country = All[0];
            localDigits = string.Empty;
            if (string.IsNullOrWhiteSpace(storedNumber))
                return false;

            string compact = storedNumber.Trim()
                .Replace(" ", "")
                .Replace("-", "")
                .Replace("(", "")
                .Replace(")", "");

            if (compact.StartsWith("+", StringComparison.Ordinal))
                compact = compact.Substring(1);

            CountryCallingCode matched = null;
            for (int i = 0; i < All.Length; i++)
            {
                var candidate = All[i];
                if (!compact.StartsWith(candidate.DialCode, StringComparison.Ordinal))
                    continue;
                if (matched == null || candidate.DialCode.Length > matched.DialCode.Length)
                    matched = candidate;
            }

            if (matched != null)
            {
                country = matched;
                string rest = compact.Substring(matched.DialCode.Length);
                rest = ContactNumberField.DigitsOnly(rest);
                if (matched.IsPhilippines)
                {
                    if (rest.StartsWith("0", StringComparison.Ordinal))
                        localDigits = rest;
                    else
                        localDigits = "0" + rest;
                    localDigits = ContactNumberField.NormalizePhilippineLocal(localDigits);
                }
                else
                {
                    localDigits = rest;
                    if (localDigits.Length > matched.MaxLocalLength)
                        localDigits = localDigits.Substring(0, matched.MaxLocalLength);
                }

                return true;
            }

            string digits = ContactNumberField.DigitsOnly(compact);
            if (digits.StartsWith("09", StringComparison.Ordinal) && digits.Length == 11)
            {
                country = All[0];
                localDigits = digits;
                return true;
            }

            return false;
        }

        private string LengthHint()
        {
            return MinLocalLength == MaxLocalLength
                ? MinLocalLength.ToString()
                : MinLocalLength + "–" + MaxLocalLength;
        }
    }
}
