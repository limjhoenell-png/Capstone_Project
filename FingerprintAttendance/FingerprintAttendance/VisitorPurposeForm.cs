using System;
using System.Drawing;
using System.Windows.Forms;
using FingerprintAttendance.Core;

namespace FingerprintAttendance
{
    public class VisitorPurposeForm : Form
    {
        private readonly ComboBox _cmbPurpose = new ComboBox();
        private readonly TextBox _txtOtherTransaction = new TextBox();
        private readonly Label _lblOtherField = new Label();
        private readonly Panel _panelBody = new Panel();
        private readonly Panel _panelBodyAccent = new Panel();
        private readonly Button _btnSave = new Button();
        private readonly Button _btnCancel = new Button();
        private readonly Label _lblWelcome = new Label();
        private Panel _panelHeader;
        private Panel _headerAccent;

        public string PurposeOfTransaction
        {
            get
            {
                string sel = _cmbPurpose.SelectedItem as string;
                if (string.Equals(sel, "Other", StringComparison.OrdinalIgnoreCase))
                    return PurposeOfVisitCatalog.FormatOtherPurpose(_txtOtherTransaction.Text);
                return sel?.Trim() ?? string.Empty;
            }
        }

        public VisitorPurposeForm(string visitorName)
        {
            Text = "Purpose of Visit";
            StartPosition = FormStartPosition.CenterParent;
            FormBorderStyle = FormBorderStyle.Sizable;
            MaximizeBox = true;
            MinimizeBox = false;
            MinimumSize = new Size(420, 320);
            BackColor = Color.FromArgb(236, 241, 248);
            Font = new Font("Segoe UI", 10F);
            AutoScaleMode = AutoScaleMode.Font;
            AutoScroll = true;
            ClientSize = new Size(760, 520);

            // ── Header ──────────────────────────────────────────────────────────
            _panelHeader = new Panel
            {
                Location = new Point(0, 0),
                Size = new Size(760, 80),
                BackColor = Color.FromArgb(13, 27, 42)
            };

            var pnlHeaderAccentBar = new Panel
            {
                Location = new Point(0, 0),
                Size = new Size(6, 80),
                BackColor = Color.FromArgb(8, 145, 178)
            };

            _lblWelcome.SetBounds(26, 0, 718, 80);
            _lblWelcome.Text = $"Welcome, {visitorName}";
            _lblWelcome.Font = new Font("Segoe UI", 16F, FontStyle.Bold);
            _lblWelcome.ForeColor = Color.FromArgb(240, 246, 255);
            _lblWelcome.BackColor = Color.Transparent;
            _lblWelcome.TextAlign = ContentAlignment.MiddleCenter;
            _lblWelcome.AutoSize = false;
            _lblWelcome.AutoEllipsis = false;
            _lblWelcome.UseMnemonic = false;
            _lblWelcome.Margin = Padding.Empty;

            _panelHeader.Controls.AddRange(new Control[] {
                pnlHeaderAccentBar, _lblWelcome });

            // Teal accent stripe
            _headerAccent = new Panel
            {
                Location = new Point(0, 80),
                Size = new Size(760, 3),
                BackColor = Color.FromArgb(8, 145, 178)
            };

            // ── Body card ───────────────────────────────────────────────────────
            _panelBody.Location = new Point(18, 98);
            _panelBody.Size = new Size(724, 190);
            _panelBody.BackColor = Color.White;

            // Left section accent
            _panelBodyAccent = new Panel
            {
                Location = new Point(0, 0),
                Size = new Size(4, _panelBody.Height),
                BackColor = Color.FromArgb(8, 145, 178)
            };
            _panelBody.Controls.Add(_panelBodyAccent);

            var lblFieldLabel = new Label
            {
                Text = "PURPOSE OF VISIT",
                Left = 20,
                Top = 18,
                Width = 498,
                Height = 18,
                Font = new Font("Segoe UI", 8.5F, FontStyle.Bold),
                ForeColor = Color.FromArgb(100, 116, 139)
            };

            _cmbPurpose.SetBounds(20, 40, 498, 32);
            _cmbPurpose.Font = new Font("Segoe UI", 10.5F);
            _cmbPurpose.DropDownStyle = ComboBoxStyle.DropDownList;
            _cmbPurpose.IntegralHeight = true;
            _cmbPurpose.MaxDropDownItems = 16;
            _cmbPurpose.BackColor = Color.FromArgb(248, 250, 252);

            _lblOtherField.Text = "OTHER TRANSACTION DETAILS";
            _lblOtherField.Left = 20;
            _lblOtherField.Top = 96;
            _lblOtherField.Width = 498;
            _lblOtherField.Height = 18;
            _lblOtherField.Font = new Font("Segoe UI", 8.5F, FontStyle.Bold);
            _lblOtherField.ForeColor = Color.FromArgb(100, 116, 139);
            _lblOtherField.Visible = false;

            _txtOtherTransaction.SetBounds(20, 118, 498, 32);
            _txtOtherTransaction.Font = new Font("Segoe UI", 10.5F);
            _txtOtherTransaction.BorderStyle = BorderStyle.FixedSingle;
            _txtOtherTransaction.BackColor = Color.FromArgb(241, 245, 249);
            _txtOtherTransaction.Enabled = false;
            _txtOtherTransaction.Multiline = false;
            _txtOtherTransaction.ScrollBars = ScrollBars.None;
            _txtOtherTransaction.Visible = false;

            _panelBody.Controls.AddRange(new Control[] {
                lblFieldLabel, _cmbPurpose, _lblOtherField, _txtOtherTransaction });

            PopulatePurposeOptions();
            _cmbPurpose.SelectedIndexChanged += (_, __) => OnPurposeChanged();

            // ── Buttons ─────────────────────────────────────────────────────────
            _btnSave.Text = "Submit";
            _btnSave.Left = 18;
            _btnSave.Top = _panelBody.Bottom + 16;
            _btnSave.Width = 266;
            _btnSave.Height = 48;
            _btnSave.Font = new Font("Segoe UI", 10.5F, FontStyle.Bold);
            _btnSave.BackColor = Color.FromArgb(37, 99, 235);
            _btnSave.ForeColor = Color.White;
            _btnSave.FlatStyle = FlatStyle.Flat;
            _btnSave.DialogResult = DialogResult.OK;
            _btnSave.Cursor = Cursors.Hand;
            _btnSave.FlatAppearance.BorderSize = 0;
            _btnSave.FlatAppearance.MouseOverBackColor = Color.FromArgb(29, 78, 216);
            _btnSave.FlatAppearance.MouseDownBackColor = Color.FromArgb(23, 64, 180);
            _btnSave.Click += (_, __) =>
            {
                if (string.IsNullOrWhiteSpace(PurposeOfTransaction))
                {
                    MessageBox.Show("Please select a purpose of transaction.", "Validation",
                        MessageBoxButtons.OK, MessageBoxIcon.Warning);
                    DialogResult = DialogResult.None;
                }
            };

            _btnCancel.Text = "Cancel";
            _btnCancel.Left = 298;
            _btnCancel.Top = _panelBody.Bottom + 16;
            _btnCancel.Width = 266;
            _btnCancel.Height = 48;
            _btnCancel.Font = new Font("Segoe UI", 10.5F, FontStyle.Bold);
            _btnCancel.BackColor = Color.FromArgb(22, 40, 62);
            _btnCancel.ForeColor = Color.FromArgb(180, 210, 240);
            _btnCancel.FlatStyle = FlatStyle.Flat;
            _btnCancel.DialogResult = DialogResult.Cancel;
            _btnCancel.Cursor = Cursors.Hand;
            _btnCancel.FlatAppearance.BorderSize = 1;
            _btnCancel.FlatAppearance.BorderColor = Color.FromArgb(50, 80, 120);
            _btnCancel.FlatAppearance.MouseOverBackColor = Color.FromArgb(30, 55, 85);
            _btnCancel.FlatAppearance.MouseDownBackColor = Color.FromArgb(15, 35, 60);

            Controls.AddRange(new Control[] {
                _panelHeader, _headerAccent, _panelBody, _btnSave, _btnCancel });

            KeyPreview = true;
            CancelButton = _btnCancel;
            KeyDown += (_, e) =>
            {
                if (e.KeyCode != Keys.Escape) return;
                DialogResult = DialogResult.Cancel;
                Close();
            };

            Resize += (_, __) => LayoutPurpose();
            ResponsiveLayout.FitClientToWorkingArea(this, 760, 520, 620, 420);
            LayoutPurpose();
            DesktopTheme.Apply(this);
        }

        private void LayoutPurpose()
        {
            int width = Math.Max(320, ClientSize.Width);
            int height = Math.Max(240, ClientSize.Height);
            if (_panelHeader != null)
            {
                _panelHeader.Width = width;
                _lblWelcome.SetBounds(6, 0, Math.Max(120, width - 6), _panelHeader.Height);
                FitWelcomeText();
            }
            if (_headerAccent != null)
                _headerAccent.Width = width;

            _panelBody.Width = Math.Max(280, width - 36);
            int fieldWidth = Math.Max(160, _panelBody.Width - 40);
            _cmbPurpose.Width = fieldWidth;
            _lblOtherField.Width = fieldWidth;
            _txtOtherTransaction.Width = fieldWidth;

            _panelBody.Height = Math.Max(
                string.Equals(_cmbPurpose.SelectedItem as string, "Other", StringComparison.OrdinalIgnoreCase) ? 246 : 190,
                height - _panelBody.Top - 112);
            _panelBodyAccent.Height = _panelBody.Height;

            int buttonWidth = Math.Max(120, (_panelBody.Width - 14) / 2);
            _btnSave.SetBounds(18, _panelBody.Bottom + 16, buttonWidth, 48);
            _btnCancel.SetBounds(18 + buttonWidth + 14, _btnSave.Top, buttonWidth, 48);
            AutoScrollMinSize = new Size(0, _btnCancel.Bottom + 18);
        }

        private void FitWelcomeText()
        {
            float fontSize = 16F;
            while (fontSize > 6F)
            {
                using (var candidate = new Font(_lblWelcome.Font.FontFamily, fontSize, FontStyle.Bold))
                {
                    int textWidth = TextRenderer.MeasureText(
                        _lblWelcome.Text,
                        candidate,
                        Size.Empty,
                        TextFormatFlags.NoPadding | TextFormatFlags.SingleLine).Width;
                    if (textWidth <= _lblWelcome.ClientSize.Width)
                        break;
                }

                fontSize -= 0.5F;
            }

            if (Math.Abs(_lblWelcome.Font.SizeInPoints - fontSize) > 0.4F)
                _lblWelcome.Font = new Font(_lblWelcome.Font.FontFamily, fontSize, FontStyle.Bold);
            _lblWelcome.TextAlign = ContentAlignment.MiddleCenter;
        }

        private void OnPurposeChanged()
        {
            bool isOther = string.Equals(_cmbPurpose.SelectedItem as string, "Other", StringComparison.OrdinalIgnoreCase);
            _txtOtherTransaction.Enabled = isOther;
            _txtOtherTransaction.Visible = isOther;
            _lblOtherField.Visible = isOther;
            _txtOtherTransaction.BackColor = isOther
                ? Color.FromArgb(248, 250, 252)
                : Color.FromArgb(241, 245, 249);

            _txtOtherTransaction.Multiline = isOther;
            _txtOtherTransaction.ScrollBars = isOther ? ScrollBars.Vertical : ScrollBars.None;
            _txtOtherTransaction.SetBounds(20, 118, Math.Max(160, _panelBody.Width - 40), isOther ? 96 : 32);
            _panelBody.Height = isOther ? 246 : 114;
            LayoutPurpose();

            if (!isOther) _txtOtherTransaction.Clear();
        }

        private void PopulatePurposeOptions()
        {
            _cmbPurpose.Items.Clear();
            _cmbPurpose.Items.AddRange(PurposeOfVisitCatalog.GetOptions());
        }
    }
}