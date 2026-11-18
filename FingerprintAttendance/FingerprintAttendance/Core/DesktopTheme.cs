using System;
using System.Drawing;
using System.Windows.Forms;

namespace FingerprintAttendance.Core
{
    internal static class DesktopTheme
    {
        internal static readonly Color Page = Color.FromArgb(240, 244, 255);
        internal static readonly Color Surface = Color.White;
        internal static readonly Color SurfaceAlt = Color.FromArgb(248, 250, 255);
        internal static readonly Color Border = Color.FromArgb(228, 234, 245);
        internal static readonly Color BorderStrong = Color.FromArgb(200, 213, 239);
        internal static readonly Color Primary = Color.FromArgb(37, 99, 235);
        internal static readonly Color PrimaryHover = Color.FromArgb(29, 78, 216);
        internal static readonly Color PrimaryLight = Color.FromArgb(219, 234, 254);
        internal static readonly Color SectionHeader = Color.FromArgb(10, 17, 40);
        internal static readonly Color SaveAction = Color.FromArgb(6, 78, 59);
        internal static readonly Color Sidebar = Color.FromArgb(13, 20, 38);
        internal static readonly Color Text = Color.FromArgb(17, 24, 39);
        internal static readonly Color TextSecondary = Color.FromArgb(75, 85, 99);
        internal static readonly Color TextMuted = Color.FromArgb(107, 114, 128);

        private static readonly Font BodyFont = new Font("Segoe UI", 9.5F, FontStyle.Bold);
        private static readonly Font FieldFont = new Font("Segoe UI", 10F, FontStyle.Bold);
        private static readonly Font ButtonFont = new Font("Segoe UI", 9.5F, FontStyle.Bold);
        private static readonly Color LegacyPage = Color.FromArgb(236, 241, 248);
        private static readonly Color LegacySurfaceAlt = Color.FromArgb(248, 250, 252);
        private static readonly Color LegacyHeader = Color.FromArgb(13, 27, 42);
        private static readonly Color LegacyLightText = Color.FromArgb(240, 246, 255);
        private static readonly Color LegacyMutedHeaderText = Color.FromArgb(100, 140, 185);

        internal static void Apply(Form form)
        {
            if (form == null)
                throw new ArgumentNullException(nameof(form));

            form.BackColor = Page;
            form.Font = BodyFont;
            ApplyToTree(form);
        }

        private static void ApplyToTree(Control control)
        {
            control.FontChanged -= EnforceBoldFont;
            control.FontChanged += EnforceBoldFont;
            if (control is Button button)
            {
                button.TextChanged -= EnforceSpecialActionButtonStyle;
                button.TextChanged += EnforceSpecialActionButtonStyle;
                button.BackColorChanged -= EnforceSpecialActionButtonStyle;
                button.BackColorChanged += EnforceSpecialActionButtonStyle;
                button.ForeColorChanged -= EnforceSpecialActionButtonStyle;
                button.ForeColorChanged += EnforceSpecialActionButtonStyle;
                button.MouseEnter -= EnforceSpecialActionButtonStyle;
                button.MouseEnter += EnforceSpecialActionButtonStyle;
            }
            if (control is Label label)
            {
                label.TextChanged -= EnforceSectionHeaderStyle;
                label.TextChanged += EnforceSectionHeaderStyle;
                label.BackColorChanged -= EnforceSectionHeaderStyle;
                label.BackColorChanged += EnforceSectionHeaderStyle;
                label.ForeColorChanged -= EnforceSectionHeaderStyle;
                label.ForeColorChanged += EnforceSectionHeaderStyle;
                label.MouseEnter -= EnforceSectionHeaderStyle;
                label.MouseEnter += EnforceSectionHeaderStyle;
            }
            if (control is Panel headerPanel)
            {
                headerPanel.BackColorChanged -= EnforceHeaderPanelStyle;
                headerPanel.BackColorChanged += EnforceHeaderPanelStyle;
            }
            StyleControl(control);
            EnforceBoldFont(control, EventArgs.Empty);
            control.ControlAdded -= ControlAdded;
            control.ControlAdded += ControlAdded;

            foreach (Control child in control.Controls)
                ApplyToTree(child);
        }

        private static void ControlAdded(object sender, ControlEventArgs e)
        {
            ApplyToTree(e.Control);
        }

        private static void EnforceBoldFont(object sender, EventArgs e)
        {
            if (!(sender is Control control) || control.Font == null || control.Font.Bold)
                return;

            control.Font = new Font(control.Font, control.Font.Style | FontStyle.Bold);
        }

        private static void EnforceSpecialActionButtonStyle(object sender, EventArgs e)
        {
            if (sender is Button button)
            {
                string text = (button.Text ?? string.Empty).Trim();
                if (string.Equals(text, "Cancel", StringComparison.OrdinalIgnoreCase))
                    StyleCancelButton(button);
                else if (string.Equals(text, "Proceed to Biometric Enrollment", StringComparison.OrdinalIgnoreCase))
                    StyleBiometricEnrollmentButton(button);
                else if (IsSaveAction(text))
                    StyleSaveButton(button);
            }
        }

        private static void EnforceSectionHeaderStyle(object sender, EventArgs e)
        {
            if (!(sender is Label label))
                return;

            if (IsInsideHeaderPanel(label))
                StyleHeaderLabel(label);
            else if (IsSectionHeaderLabel(label))
                StyleSectionHeader(label);
        }

        private static void EnforceHeaderPanelStyle(object sender, EventArgs e)
        {
            if (sender is Panel panel && IsHeaderPanel(panel) && panel.BackColor != SectionHeader)
                panel.BackColor = SectionHeader;
        }

        private static void StyleControl(Control control)
        {
            if (control is Form)
                return;

            if (control is Panel panel)
            {
                if (IsHeaderPanel(panel))
                    panel.BackColor = SectionHeader;
                else if (!IsInSidebar(panel) && panel.BackColor == LegacyHeader)
                    panel.BackColor = Surface;
                else if (panel.BackColor == LegacyPage)
                    panel.BackColor = Page;
                else if (panel.BackColor == LegacySurfaceAlt)
                    panel.BackColor = SurfaceAlt;
                else if (panel.BackColor == SystemColors.Control)
                    panel.BackColor = Surface;

            }

            if (control is DataGridView grid)
            {
                grid.BackgroundColor = Surface;
                grid.BorderStyle = BorderStyle.None;
                grid.GridColor = Border;
                grid.EnableHeadersVisualStyles = false;
                grid.ColumnHeadersHeight = 42;
                grid.ColumnHeadersDefaultCellStyle.BackColor = SurfaceAlt;
                grid.ColumnHeadersDefaultCellStyle.ForeColor = TextSecondary;
                grid.ColumnHeadersDefaultCellStyle.SelectionBackColor = SurfaceAlt;
                grid.ColumnHeadersDefaultCellStyle.Font = new Font("Segoe UI", 8.5F, FontStyle.Bold);
                grid.ColumnHeadersDefaultCellStyle.Padding = new Padding(12, 0, 0, 0);
                grid.DefaultCellStyle.BackColor = Surface;
                grid.DefaultCellStyle.ForeColor = Text;
                grid.DefaultCellStyle.SelectionBackColor = Color.FromArgb(219, 234, 254);
                grid.DefaultCellStyle.SelectionForeColor = Text;
                grid.DefaultCellStyle.Padding = new Padding(12, 0, 0, 0);
                grid.AlternatingRowsDefaultCellStyle.BackColor = SurfaceAlt;
                grid.RowTemplate.Height = 40;
                grid.CellBorderStyle = DataGridViewCellBorderStyle.SingleHorizontal;
                grid.RowHeadersVisible = false;
                grid.Font = FieldFont;
                grid.ColumnAdded -= GridColumnAdded;
                grid.ColumnAdded += GridColumnAdded;
                grid.DataBindingComplete -= GridDataBindingComplete;
                grid.DataBindingComplete += GridDataBindingComplete;
                ApplyBoldGridTypography(grid);
                return;
            }

            if (control is Button button)
            {
                StyleButton(button);
                return;
            }

            if (control is TextBoxBase textBox)
            {
                textBox.Font = FieldFont;
                textBox.ForeColor = Text;
                textBox.BackColor = textBox.Enabled ? Surface : SurfaceAlt;
                return;
            }

            if (control is ComboBox comboBox)
            {
                comboBox.Font = FieldFont;
                comboBox.ForeColor = Text;
                comboBox.BackColor = Surface;
                return;
            }

            if (control is DateTimePicker dateTimePicker)
            {
                dateTimePicker.Font = FieldFont;
                dateTimePicker.CalendarTitleBackColor = Primary;
                dateTimePicker.CalendarTitleForeColor = Color.White;
                return;
            }

            if (control is Label label)
            {
                if (IsInsideHeaderPanel(label))
                {
                    StyleHeaderLabel(label);
                    return;
                }

                if (IsSectionHeaderLabel(label))
                {
                    StyleSectionHeader(label);
                    return;
                }

                if (label.ForeColor == LegacyLightText)
                    label.ForeColor = Text;
                else if (label.ForeColor == LegacyMutedHeaderText ||
                         label.ForeColor == Color.FromArgb(154, 180, 209))
                    label.ForeColor = TextSecondary;
                if (label.ForeColor == Color.FromArgb(100, 116, 139))
                    label.ForeColor = TextSecondary;
                if (label.BackColor == Color.FromArgb(239, 246, 255))
                    label.BackColor = PrimaryLight;
            }

            if (control.Font != null &&
                !string.Equals(control.Font.FontFamily.Name, "Segoe UI", StringComparison.OrdinalIgnoreCase) &&
                !string.Equals(control.Font.FontFamily.Name, "Consolas", StringComparison.OrdinalIgnoreCase))
            {
                control.Font = new Font("Segoe UI", control.Font.SizeInPoints, control.Font.Style);
            }
            else if (control.Font == null)
            {
                control.Font = BodyFont;
            }
        }

        private static void GridColumnAdded(object sender, DataGridViewColumnEventArgs e)
        {
            if (sender is DataGridView grid)
                ApplyBoldColumnTypography(grid, e.Column);
        }

        private static void GridDataBindingComplete(object sender, DataGridViewBindingCompleteEventArgs e)
        {
            if (!(sender is DataGridView grid))
                return;

            ApplyBoldGridTypography(grid);
        }

        private static void ApplyBoldGridTypography(DataGridView grid)
        {
            grid.Font = BoldFont(grid.Font);
            grid.DefaultCellStyle.Font = BoldFont(grid.DefaultCellStyle.Font ?? grid.Font);
            grid.AlternatingRowsDefaultCellStyle.Font =
                BoldFont(grid.AlternatingRowsDefaultCellStyle.Font ?? grid.DefaultCellStyle.Font);
            grid.RowsDefaultCellStyle.Font =
                BoldFont(grid.RowsDefaultCellStyle.Font ?? grid.DefaultCellStyle.Font);
            grid.RowTemplate.DefaultCellStyle.Font =
                BoldFont(grid.RowTemplate.DefaultCellStyle.Font ?? grid.DefaultCellStyle.Font);
            grid.ColumnHeadersDefaultCellStyle.Font =
                BoldFont(grid.ColumnHeadersDefaultCellStyle.Font ?? grid.Font);
            grid.RowHeadersDefaultCellStyle.Font =
                BoldFont(grid.RowHeadersDefaultCellStyle.Font ?? grid.Font);

            foreach (DataGridViewColumn column in grid.Columns)
                ApplyBoldColumnTypography(grid, column);
        }

        private static void ApplyBoldColumnTypography(DataGridView grid, DataGridViewColumn column)
        {
            column.DefaultCellStyle.Font =
                BoldFont(column.DefaultCellStyle.Font ?? grid.DefaultCellStyle.Font ?? grid.Font);
            column.HeaderCell.Style.Font =
                BoldFont(column.HeaderCell.Style.Font ?? grid.ColumnHeadersDefaultCellStyle.Font ?? grid.Font);
        }

        private static Font BoldFont(Font font)
        {
            return font == null || font.Bold
                ? font
                : new Font(font, font.Style | FontStyle.Bold);
        }

        private static void StyleButton(Button button)
        {
            button.Font = ButtonFont;
            button.FlatStyle = FlatStyle.Flat;
            button.UseVisualStyleBackColor = false;
            button.Cursor = Cursors.Hand;

            string text = (button.Text ?? string.Empty).Trim().ToLowerInvariant();
            if (string.Equals(text, "cancel", StringComparison.Ordinal))
            {
                StyleCancelButton(button);
                return;
            }

            if (string.Equals(text, "proceed to biometric enrollment", StringComparison.Ordinal))
            {
                StyleBiometricEnrollmentButton(button);
                return;
            }

            if (IsInSidebar(button))
            {
                button.BackColor = Color.White;
                button.ForeColor = SectionHeader;
                button.FlatAppearance.BorderSize = 0;
                button.FlatAppearance.MouseOverBackColor = Color.FromArgb(226, 232, 240);
                button.FlatAppearance.MouseDownBackColor = Color.FromArgb(203, 213, 225);
                return;
            }

            if (IsSaveAction(button.Text))
            {
                StyleSaveButton(button);
                return;
            }

            if (text.Contains("delete") || text.Contains("remove"))
            {
                button.BackColor = Surface;
                button.ForeColor = Color.FromArgb(220, 38, 38);
                button.FlatAppearance.BorderSize = 1;
                button.FlatAppearance.BorderColor = Color.FromArgb(252, 165, 165);
                button.FlatAppearance.MouseOverBackColor = Color.FromArgb(255, 241, 242);
                return;
            }

            if (IsPrimaryAction(text))
            {
                button.BackColor = Primary;
                button.ForeColor = Color.White;
                button.FlatAppearance.BorderSize = 0;
                button.FlatAppearance.MouseOverBackColor = PrimaryHover;
                button.FlatAppearance.MouseDownBackColor = Color.FromArgb(30, 64, 175);
                return;
            }

            button.BackColor = Surface;
            button.ForeColor = Text;
            button.FlatAppearance.BorderSize = 1;
            button.FlatAppearance.BorderColor = BorderStrong;
            button.FlatAppearance.MouseOverBackColor = SurfaceAlt;
            button.FlatAppearance.MouseDownBackColor = Color.FromArgb(239, 244, 252);
        }

        private static void StyleCancelButton(Button button)
        {
            Color background = Color.FromArgb(127, 29, 29);
            if (button.BackColor != background)
                button.BackColor = background;
            if (button.ForeColor != Color.White)
                button.ForeColor = Color.White;
            if (!button.Font.Bold)
                button.Font = new Font(button.Font, button.Font.Style | FontStyle.Bold);
            button.FlatStyle = FlatStyle.Flat;
            button.UseVisualStyleBackColor = false;
            button.FlatAppearance.BorderSize = 0;
            button.FlatAppearance.MouseOverBackColor = Color.FromArgb(104, 22, 22);
            button.FlatAppearance.MouseDownBackColor = Color.FromArgb(69, 10, 10);
        }

        private static void StyleBiometricEnrollmentButton(Button button)
        {
            Color background = Color.FromArgb(6, 78, 59);
            if (button.BackColor != background)
                button.BackColor = background;
            if (button.ForeColor != Color.White)
                button.ForeColor = Color.White;
            if (!button.Font.Bold)
                button.Font = new Font(button.Font, button.Font.Style | FontStyle.Bold);
            button.FlatStyle = FlatStyle.Flat;
            button.UseVisualStyleBackColor = false;
            button.FlatAppearance.BorderSize = 0;
            button.FlatAppearance.MouseOverBackColor = Color.FromArgb(4, 60, 45);
            button.FlatAppearance.MouseDownBackColor = Color.FromArgb(2, 44, 34);
        }

        private static void StyleSaveButton(Button button)
        {
            if (button.BackColor != SaveAction)
                button.BackColor = SaveAction;
            if (button.ForeColor != Color.White)
                button.ForeColor = Color.White;
            if (!button.Font.Bold)
                button.Font = new Font(button.Font, button.Font.Style | FontStyle.Bold);
            button.FlatStyle = FlatStyle.Flat;
            button.UseVisualStyleBackColor = false;
            button.FlatAppearance.BorderSize = 0;
            button.FlatAppearance.MouseOverBackColor = Color.FromArgb(4, 60, 45);
            button.FlatAppearance.MouseDownBackColor = Color.FromArgb(2, 44, 34);
        }

        private static void StyleSectionHeader(Label label)
        {
            if (label.BackColor != SectionHeader)
                label.BackColor = SectionHeader;
            if (label.ForeColor != Color.White)
                label.ForeColor = Color.White;
            if (label.Font != null && !label.Font.Bold)
                label.Font = new Font(label.Font, label.Font.Style | FontStyle.Bold);
            if (label.TextAlign != ContentAlignment.MiddleLeft)
                label.TextAlign = ContentAlignment.MiddleLeft;

            if (label.Parent is Panel headerPanel &&
                headerPanel.Height <= label.Height + 8 &&
                label.Top <= 4 &&
                headerPanel.BackColor != SectionHeader)
            {
                headerPanel.BackColor = SectionHeader;
            }
        }

        private static void StyleHeaderLabel(Label label)
        {
            if (label.ForeColor != Color.White)
                label.ForeColor = Color.White;
            if (label.Font != null && !label.Font.Bold)
                label.Font = new Font(label.Font, label.Font.Style | FontStyle.Bold);
        }

        private static bool IsInsideHeaderPanel(Control control)
        {
            for (Control parent = control?.Parent; parent != null; parent = parent.Parent)
            {
                if (parent is Panel panel && IsHeaderPanel(panel))
                    return true;
            }

            return false;
        }

        private static bool IsHeaderPanel(Panel panel)
        {
            if (panel == null ||
                panel.Height < 60 ||
                panel.Height > 180 ||
                (panel.Dock != DockStyle.Top && panel.Top > 2))
            {
                return false;
            }

            return ContainsHeaderLabel(panel);
        }

        private static bool ContainsHeaderLabel(Control control)
        {
            foreach (Control child in control.Controls)
            {
                if (child is Label label && !string.IsNullOrWhiteSpace(label.Text))
                    return true;
                if (child.HasChildren && ContainsHeaderLabel(child))
                    return true;
            }

            return false;
        }

        private static bool IsSectionHeader(string text)
        {
            string normalized = (text ?? string.Empty).Trim().ToUpperInvariant();
            if (normalized.Length == 0)
                return false;
            if (string.Equals(normalized, "DOCUMENT OWNER CATEGORY", StringComparison.Ordinal))
                return false;

            string[] sectionHeaders =
            {
                "PERSONAL INFORMATION",
                "ADDRESS INFORMATION",
                "PERSON CLASSIFICATION",
                "EMPLOYEE DETAILS",
                "FINGERPRINT ENROLLMENT",
                "CLAIMANT",
                "DOCUMENT OWNER",
                "PERSON RECEIVING DOCUMENTS",
                "PERSON ON WHOSE BEHALF",
                "DOCUMENT(S) BEING CLAIMED",
                "DOCUMENTS BEING CLAIMED",
                "ADDITIONAL NOTES",
                "ATTENDANCE ENTRIES",
                "TRANSACTION LOG ENTRIES"
            };

            foreach (string heading in sectionHeaders)
            {
                if (string.Equals(normalized, heading, StringComparison.Ordinal) ||
                    normalized.StartsWith(heading + " ", StringComparison.Ordinal) ||
                    normalized.StartsWith(heading + " (", StringComparison.Ordinal) ||
                    normalized.StartsWith(heading + " —", StringComparison.Ordinal) ||
                    normalized.StartsWith(heading + " -", StringComparison.Ordinal))
                {
                    return true;
                }
            }

            return false;
        }

        private static bool IsSectionHeaderLabel(Label label)
        {
            if (label == null)
                return false;
            if (IsSectionHeader(label.Text))
                return true;

            return label.Parent is Panel parent &&
                   label.Top <= 4 &&
                   label.Height >= 26 &&
                   parent.Height <= label.Height + 8 &&
                   label.Width >= parent.Width * 0.65;
        }

        private static bool IsSaveAction(string text)
        {
            string normalized = (text ?? string.Empty).Trim();
            return normalized.IndexOf("save", StringComparison.OrdinalIgnoreCase) >= 0 ||
                   normalized.IndexOf("submit", StringComparison.OrdinalIgnoreCase) >= 0;
        }

        private static bool IsPrimaryAction(string text)
        {
            return text.Contains("save") || text.Contains("submit") || text.Contains("apply") ||
                   text.Contains("load") || text.Contains("enroll") || text.Contains("register") ||
                   text.Contains("search") || text.Contains("confirm") || text.Contains("continue") ||
                   text.Contains("capture") || text.Contains("scan") ||
                   text.Contains("credential") || text.Contains("fingerprint") ||
                   text.Contains("log in") || text.Contains("login") ||
                   text.Contains("clock in") || text.Contains("clock out") ||
                   text.Contains("time in") || text.Contains("time out") ||
                   text.Contains("use selected") || text.Contains("new visitor") ||
                   text.Contains("claim documents");
        }

        private static bool IsInSidebar(Control control)
        {
            for (Control current = control; current != null; current = current.Parent)
            {
                if (string.Equals(current.Name, "navigationSidebar", StringComparison.Ordinal))
                    return true;
            }

            return false;
        }
    }
}
