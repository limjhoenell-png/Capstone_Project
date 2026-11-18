using System;
using System.Collections.Generic;
using System.Drawing;
using System.Windows.Forms;
using FingerprintAttendance.Core;

namespace FingerprintAttendance
{
    /// <summary>
    /// Logbook entry for individuals claiming barangay documents on behalf of another person.
    /// At least one verification option must be completed before the entry can be saved (release requirement).
    /// </summary>
    public class DocumentClaimOnBehalfEntryForm : Form
    {
        private readonly DatabaseHelper _db;
        private readonly TextBox _txtClaimantFirstName = new TextBox();
        private readonly TextBox _txtClaimantMiddleName = new TextBox();
        private readonly TextBox _txtClaimantLastName = new TextBox();
        private readonly TextBox _txtClaimantNameExtension = new TextBox();
        private readonly AddressInformationPanel _addressPanel = new AddressInformationPanel();
        private readonly ContactNumberField _contactField = new ContactNumberField();
        private readonly TextBox _txtPrincipalFirstName = new TextBox();
        private readonly TextBox _txtPrincipalMiddleName = new TextBox();
        private readonly TextBox _txtPrincipalLastName = new TextBox();
        private readonly TextBox _txtPrincipalNameExtension = new TextBox();
        private readonly ComboBox _cmbDocument = new ComboBox();
        private readonly TextBox _txtDocumentOther = new TextBox();
        private readonly Label _lblDocumentOther = new Label();
        private readonly TextBox _txtNotes = new TextBox();
        private readonly CheckBox _chkAuthLetter = new CheckBox();
        private readonly CheckBox _chkValidIds = new CheckBox();
        private readonly PersonClassificationSelector _claimantClassification =
            new PersonClassificationSelector(selectDefault: false);
        private readonly PersonClassificationSelector _principalClassification =
            new PersonClassificationSelector(selectDefault: false);
        private readonly List<Control> _stretchFields = new List<Control>();
        private VisitorProfile _linkedClaimantIdentity;
        private Panel _panelHeader;
        private Label _lblTitle;
        private Label _lblSub;
        private Panel _panelScroll;
        private Panel _panelBody;
        private Panel _requirementCard;
        private Label _lblReqTitle;
        private Label _lblReqBody;
        private Label _lblMustCheck;
        private Button _btnSave;
        private Button _btnCancel;
        private int _documentSectionBottomY;
        private Label _lblNotes;
        private bool _layoutBusy;

        public string ClaimantFullName =>
            DatabaseHelper.BuildEmployeeFullName(
                _txtClaimantFirstName.Text,
                _txtClaimantMiddleName.Text,
                _txtClaimantLastName.Text,
                _txtClaimantNameExtension.Text);
        public string ClaimantAddress => _addressPanel.ComposedAddress;
        public string ClaimantContact => _contactField.StoredNumber;
        public string ClaimantClassification => _claimantClassification.SelectedValue;
        public string PrincipalFullName =>
            DatabaseHelper.BuildEmployeeFullName(
                _txtPrincipalFirstName.Text,
                _txtPrincipalMiddleName.Text,
                _txtPrincipalLastName.Text,
                _txtPrincipalNameExtension.Text);
        public string PrincipalClassification => _principalClassification.SelectedValue;
        public VisitorProfile LinkedClaimantIdentity => _linkedClaimantIdentity;
        public bool AuthorizationLetterVerified => _chkAuthLetter.Checked;
        public bool ValidIdsVerified => _chkValidIds.Checked;
        public bool RegisterFingerprint => false;

        /// <summary>Human-readable summary stored in <c>purpose_of_transaction</c>.</summary>
        public string PurposeSummary
        {
            get
            {
                string doc = SelectedDocumentDescription();
                string notes = _txtNotes.Text.Trim();
                string noteSuffix = string.IsNullOrWhiteSpace(notes) ? "" : $" Notes: {notes}";
                return $"Claim documents on behalf — Document owner: {PrincipalFullName}; Document(s): {doc}.{noteSuffix}";
            }
        }

        public DocumentClaimOnBehalfEntryForm(DatabaseHelper db)
        {
            _db = db;
            Text = "Claim Documents On Behalf";
            StartPosition = FormStartPosition.CenterParent;
            FormBorderStyle = FormBorderStyle.Sizable;
            MaximizeBox = true;
            MinimizeBox = true;
            SizeGripStyle = SizeGripStyle.Show;
            MinimumSize = new Size(640, 520);
            BackColor = Color.FromArgb(236, 241, 248);
            Font = new Font("Segoe UI", 10F);
            ClientSize = new Size(900, 840);
            AutoScroll = false;
            DoubleBuffered = true;

            _panelHeader = new Panel
            {
                Dock = DockStyle.Top,
                Height = 92,
                BackColor = Color.FromArgb(13, 27, 42)
            };
            var pnlAccent = new Panel
            {
                Dock = DockStyle.Left,
                Width = 6,
                BackColor = Color.FromArgb(180, 83, 9)
            };
            _lblTitle = new Label
            {
                Text = "Claim Documents On Behalf",
                Left = 24,
                Top = 12,
                Width = 670,
                Height = 34,
                Font = new Font("Segoe UI", 15F, FontStyle.Bold),
                ForeColor = Color.FromArgb(240, 246, 255),
                BackColor = Color.Transparent
            };
            _lblSub = new Label
            {
                Text = "For claimants picking up documents for someone else. Documents must not be released until at least one verification option has been completed (authorization letter OR valid IDs).",
                Left = 24,
                Top = 48,
                Width = 670,
                Height = 40,
                Font = new Font("Segoe UI", 9F),
                ForeColor = Color.FromArgb(154, 180, 209),
                BackColor = Color.Transparent
            };
            _panelHeader.Controls.Add(pnlAccent);
            _panelHeader.Controls.Add(_lblTitle);
            _panelHeader.Controls.Add(_lblSub);

            _panelScroll = new Panel
            {
                Dock = DockStyle.Fill,
                AutoScroll = true,
                BackColor = Color.FromArgb(236, 241, 248)
            };

            _panelBody = new Panel
            {
                Left = 18,
                Top = 18,
                Width = 680,
                Height = 800,
                BackColor = Color.White
            };

            int y = 22;
            var btnFindClaimant = new Button
            {
                Text = "Find Existing Claimant...",
                Left = 18,
                Top = y,
                Width = 220,
                Height = 36,
                Font = new Font("Segoe UI", 9.5F, FontStyle.Bold),
                BackColor = Color.FromArgb(241, 245, 249),
                ForeColor = Color.FromArgb(30, 64, 175),
                FlatStyle = FlatStyle.Flat,
                Cursor = Cursors.Hand
            };
            btnFindClaimant.FlatAppearance.BorderColor = Color.FromArgb(199, 210, 254);
            btnFindClaimant.Click += (_, __) =>
            {
                using var find = new VisitorSearchDialog(_db);
                if (find.ShowDialog(this) == DialogResult.OK && find.SelectedProfile != null)
                    ApplyLinkedClaimantIdentity(find.SelectedProfile);
            };
            _panelBody.Controls.Add(btnFindClaimant);
            y += 46;
            AddNameFields(
                _panelBody,
                "CLAIMANT — PERSON RECEIVING DOCUMENTS",
                _txtClaimantFirstName,
                _txtClaimantMiddleName,
                _txtClaimantLastName,
                _txtClaimantNameExtension,
                ref y);
            ConfigureClassificationSelector(_claimantClassification, _panelBody, ref y);
            _addressPanel.Left = 0;
            _addressPanel.Top = y;
            _addressPanel.Width = 680;
            _panelBody.Controls.Add(_addressPanel);
            y += _addressPanel.Height + 8;
            AddContactField(_panelBody, ref y);
            AddNameFields(
                _panelBody,
                "DOCUMENT OWNER — PERSON ON WHOSE BEHALF",
                _txtPrincipalFirstName,
                _txtPrincipalMiddleName,
                _txtPrincipalLastName,
                _txtPrincipalNameExtension,
                ref y);
            ConfigureClassificationSelector(_principalClassification, _panelBody, ref y);
            AddDocumentField(_panelBody, ref y);

            _lblNotes = new Label
            {
                Text = "ADDITIONAL NOTES (OPTIONAL)",
                Left = 18,
                Top = y,
                Width = 640,
                Height = 28,
                Font = new Font("Segoe UI", 8.5F, FontStyle.Bold),
                ForeColor = Color.White,
                BackColor = Color.FromArgb(10, 17, 40),
                Padding = new Padding(10, 0, 8, 0),
                TextAlign = ContentAlignment.MiddleLeft
            };
            _txtNotes.Left = 18;
            _txtNotes.Top = y + 36;
            _txtNotes.Width = 644;
            _txtNotes.Height = 60;
            _txtNotes.Multiline = true;
            _txtNotes.ScrollBars = ScrollBars.Vertical;
            _txtNotes.BorderStyle = BorderStyle.FixedSingle;
            _txtNotes.BackColor = Color.FromArgb(248, 250, 252);
            _txtNotes.Font = new Font("Segoe UI", 10F);
            _panelBody.Controls.Add(_lblNotes);
            _panelBody.Controls.Add(_txtNotes);
            _stretchFields.Add(_lblNotes);
            _stretchFields.Add(_txtNotes);

            _requirementCard = new Panel
            {
                Left = 18,
                Top = y + 110,
                Width = 644,
                Height = 200,
                BackColor = Color.FromArgb(255, 251, 235),
                BorderStyle = BorderStyle.FixedSingle
            };
            _lblReqTitle = new Label
            {
                Text = "Verification required before document release",
                Left = 14,
                Top = 12,
                Width = 614,
                Height = 22,
                Font = new Font("Segoe UI", 10F, FontStyle.Bold),
                ForeColor = Color.FromArgb(120, 53, 15)
            };
            _lblReqBody = new Label
            {
                Left = 14,
                Top = 38,
                Width = 614,
                Height = 52,
                Text = "Staff must verify either a valid authorization letter OR valid government-issued identification before any document may be released. Check an item only after you have physically verified the originals or authenticated copies according to barangay policy.",
                Font = new Font("Segoe UI", 8.5F),
                ForeColor = Color.FromArgb(69, 48, 21)
            };
            _chkAuthLetter.Left = 14;
            _chkAuthLetter.Top = 96;
            _chkAuthLetter.Width = 614;
            _chkAuthLetter.Text = "Authorization letter presented and verified";
            _chkAuthLetter.Font = new Font("Segoe UI", 9F, FontStyle.Bold);
            _chkValidIds.Left = 14;
            _chkValidIds.Top = 128;
            _chkValidIds.Width = 614;
            _chkValidIds.Text = "Valid IDs verified (claimant and document owner as applicable)";
            _chkValidIds.Font = new Font("Segoe UI", 9F, FontStyle.Bold);
            _lblMustCheck = new Label
            {
                Left = 14,
                Top = 164,
                Width = 614,
                Height = 28,
                Text = "Complete at least one of the two items above to save this log entry.",
                Font = new Font("Segoe UI", 8.5F, FontStyle.Italic),
                ForeColor = Color.FromArgb(146, 64, 14)
            };
            _requirementCard.Controls.AddRange(new Control[]
            {
                _lblReqTitle, _lblReqBody, _chkAuthLetter, _chkValidIds, _lblMustCheck
            });
            _panelBody.Controls.Add(_requirementCard);

            _btnSave = new Button
            {
                Text = "Save Document Claim Entry",
                Left = 18,
                Top = _panelBody.Bottom + 14,
                Width = 333,
                Height = 50,
                Font = new Font("Segoe UI", 10.5F, FontStyle.Bold),
                BackColor = Color.FromArgb(180, 83, 9),
                ForeColor = Color.White,
                FlatStyle = FlatStyle.Flat,
                DialogResult = DialogResult.OK
            };
            _btnSave.FlatAppearance.BorderSize = 0;
            _btnSave.Click += (_, __) =>
            {
                if (!ValidateInputs())
                    DialogResult = DialogResult.None;
            };

            _btnCancel = new Button
            {
                Text = "Cancel",
                Left = 365,
                Top = _panelBody.Bottom + 14,
                Width = 333,
                Height = 50,
                Font = new Font("Segoe UI", 10.5F, FontStyle.Bold),
                BackColor = Color.FromArgb(22, 40, 62),
                ForeColor = Color.FromArgb(180, 210, 240),
                FlatStyle = FlatStyle.Flat,
                DialogResult = DialogResult.Cancel
            };
            _btnCancel.FlatAppearance.BorderSize = 1;
            _btnCancel.FlatAppearance.BorderColor = Color.FromArgb(50, 80, 120);

            _panelScroll.Controls.AddRange(new Control[] { _panelBody, _btnSave, _btnCancel });

            PopulateDocuments();
            if (_cmbDocument.Items.Count > 0)
                _cmbDocument.SelectedIndex = 0;
            _cmbDocument.SelectedIndexChanged += (_, __) => ReflowDocumentSection();
            ReflowDocumentSection();

            Controls.AddRange(new Control[] { _panelScroll, _panelHeader });
            ResponsiveLayout.FitClientToWorkingArea(this, 900, 840, 640, 520);
            CancelButton = _btnCancel;

            _panelScroll.Resize += (_, __) => ApplyResponsiveLayout();
            Resize += (_, __) => ApplyResponsiveLayout();
            Shown += (_, __) => ApplyResponsiveLayout();
            DesktopTheme.Apply(this);
            ApplyClaimFormVisualStyle(btnFindClaimant);
        }

        private string SelectedDocumentDescription()
        {
            string selected = _cmbDocument.SelectedItem as string;
            if (string.Equals(selected, "Other", StringComparison.OrdinalIgnoreCase))
            {
                string other = _txtDocumentOther.Text.Trim();
                return string.IsNullOrWhiteSpace(other) ? "(unspecified)" : other;
            }
            return selected?.Trim() ?? string.Empty;
        }

        private void ApplyClaimFormVisualStyle(Button btnFindClaimant)
        {
            BackColor = Color.FromArgb(244, 247, 250);
            _panelScroll.BackColor = Color.FromArgb(244, 247, 250);
            _panelHeader.BackColor = Color.FromArgb(232, 241, 253);
            _panelBody.BackColor = Color.White;
            _lblTitle.ForeColor = Color.FromArgb(21, 43, 74);
            _lblSub.ForeColor = Color.FromArgb(78, 103, 137);

            foreach (Control control in _panelHeader.Controls)
            {
                if (control is Panel accent && accent.Dock == DockStyle.Left)
                    accent.BackColor = Color.FromArgb(0, 210, 255);
            }

            btnFindClaimant.BackColor = Color.FromArgb(29, 97, 231);
            btnFindClaimant.ForeColor = Color.White;
            btnFindClaimant.FlatAppearance.BorderSize = 0;
            btnFindClaimant.FlatAppearance.MouseOverBackColor = Color.FromArgb(24, 81, 199);
            btnFindClaimant.FlatAppearance.MouseDownBackColor = Color.FromArgb(20, 67, 169);

            _btnSave.BackColor = Color.FromArgb(6, 78, 59);
            _btnSave.ForeColor = Color.White;
            _btnSave.FlatAppearance.BorderSize = 0;
            _btnSave.FlatAppearance.MouseOverBackColor = Color.FromArgb(4, 60, 45);
            _btnSave.FlatAppearance.MouseDownBackColor = Color.FromArgb(2, 44, 34);
            _btnCancel.BackColor = Color.FromArgb(127, 29, 29);
            _btnCancel.ForeColor = Color.White;
            _btnCancel.FlatAppearance.BorderSize = 0;
            _btnCancel.FlatAppearance.MouseOverBackColor = Color.FromArgb(104, 22, 22);
            _btnCancel.FlatAppearance.MouseDownBackColor = Color.FromArgb(69, 10, 10);

            ApplyClaimControlStyle(_panelBody);
            _panelBody.BackColor = Color.White;
            _panelHeader.Paint += DrawClaimHeaderAccent;
            _panelBody.Paint += DrawClaimBodyBorder;

            foreach (Control control in Controls)
            {
                if (control is Panel footer && footer.Dock == DockStyle.Bottom && footer != _panelHeader)
                    footer.BackColor = Color.FromArgb(244, 247, 250);
            }
        }

        private void ApplyClaimControlStyle(Control parent)
        {
            foreach (Control control in parent.Controls)
            {
                if (control == _requirementCard)
                    continue;

                if (control is Label label &&
                    label.ForeColor == Color.FromArgb(100, 116, 139))
                {
                    label.ForeColor = Color.FromArgb(82, 102, 132);
                }
                else if (control is TextBox textBox)
                {
                    textBox.ForeColor = Color.FromArgb(27, 46, 74);
                    if (!textBox.ReadOnly)
                        textBox.BackColor = textBox.Enabled
                            ? Color.FromArgb(248, 251, 255)
                            : Color.FromArgb(241, 245, 249);
                }
                else if (control is ComboBox comboBox)
                {
                    comboBox.ForeColor = Color.FromArgb(27, 46, 74);
                    comboBox.BackColor = Color.FromArgb(248, 251, 255);
                }
                else if (control is RadioButton || control is CheckBox)
                {
                    control.ForeColor = Color.FromArgb(35, 55, 84);
                }

                if (control.HasChildren)
                    ApplyClaimControlStyle(control);
            }
        }

        private static void DrawClaimHeaderAccent(object sender, PaintEventArgs e)
        {
            using (var accentBrush = new SolidBrush(Color.FromArgb(0, 210, 255)))
                e.Graphics.FillRectangle(accentBrush, 0, 0, 4, ((Panel)sender).Height);
        }

        private static void DrawClaimBodyBorder(object sender, PaintEventArgs e)
        {
            Rectangle bounds = ((Panel)sender).ClientRectangle;
            if (bounds.Width < 2 || bounds.Height < 2)
                return;

            using (var shadowPen = new Pen(Color.FromArgb(18, 54, 83, 128)))
                e.Graphics.DrawLine(shadowPen, 1, bounds.Height - 2, bounds.Width - 2, bounds.Height - 2);
            using (var borderPen = new Pen(Color.FromArgb(216, 228, 244)))
                e.Graphics.DrawRectangle(borderPen, 0, 0, bounds.Width - 1, bounds.Height - 1);
        }

        private void AddNameFields(
            Control parent,
            string sectionLabel,
            TextBox firstName,
            TextBox middleName,
            TextBox lastName,
            TextBox nameExtension,
            ref int y)
        {
            var lblSection = new Label
            {
                Text = sectionLabel,
                Left = 18,
                Top = y,
                Width = 640,
                Height = 28,
                Font = new Font("Segoe UI", 8.5F, FontStyle.Bold),
                ForeColor = Color.White,
                BackColor = Color.FromArgb(10, 17, 40),
                Padding = new Padding(10, 0, 8, 0),
                TextAlign = ContentAlignment.MiddleLeft
            };
            y += 36;
            parent.Controls.Add(lblSection);

            const int left = 24;
            const int nameFieldWidth = 266;
            const int secondColumnLeft = 308;
            const int rowGap = 14;
            int firstRowLabelHeight = Math.Max(
                MeasureNameLabelHeight("FIRST NAME", nameFieldWidth),
                MeasureNameLabelHeight("LAST NAME", nameFieldWidth));

            AddNameField(parent, "FIRST NAME", firstName, left, y, nameFieldWidth, 100, firstRowLabelHeight);
            AddNameField(parent, "LAST NAME", lastName, secondColumnLeft, y, nameFieldWidth, 100, firstRowLabelHeight);
            y += firstRowLabelHeight + 8 + 34 + rowGap;

            const string extensionLabel = "NAME EXTENSION (OPTIONAL: JR., SR., 1ST, 2ND, ETC.)";
            int secondRowLabelHeight = Math.Max(
                MeasureNameLabelHeight("MIDDLE NAME (OPTIONAL)", nameFieldWidth),
                MeasureNameLabelHeight(extensionLabel, nameFieldWidth));
            AddNameField(parent, "MIDDLE NAME (OPTIONAL)", middleName, left, y, nameFieldWidth, 100, secondRowLabelHeight);
            AddNameField(
                parent,
                extensionLabel,
                nameExtension,
                secondColumnLeft,
                y,
                nameFieldWidth,
                20,
                secondRowLabelHeight);
            y += secondRowLabelHeight + 8 + 34 + 14;
        }

        private static int MeasureNameLabelHeight(string text, int width)
        {
            using (var font = new Font("Segoe UI", 8.25F, FontStyle.Bold))
            {
                Size measured = TextRenderer.MeasureText(
                    text,
                    font,
                    new Size(Math.Max(1, width - 4), int.MaxValue),
                    TextFormatFlags.WordBreak | TextFormatFlags.TextBoxControl);
                return Math.Max(20, measured.Height + 2);
            }
        }

        private static void AddNameField(
            Control parent,
            string labelText,
            TextBox textBox,
            int left,
            int y,
            int width,
            int maxLength,
            int labelHeight)
        {
            var label = new Label
            {
                Text = labelText,
                Left = left,
                Top = y,
                Width = width,
                Height = labelHeight,
                Font = new Font("Segoe UI", 8.25F, FontStyle.Bold),
                ForeColor = Color.FromArgb(82, 102, 132),
                TextAlign = ContentAlignment.MiddleLeft
            };
            textBox.Left = left;
            textBox.Top = y + labelHeight + 8;
            textBox.Width = width;
            textBox.Height = 34;
            textBox.Font = new Font("Segoe UI", 11F);
            textBox.BorderStyle = BorderStyle.FixedSingle;
            textBox.BackColor = Color.FromArgb(248, 251, 255);
            textBox.CharacterCasing = CharacterCasing.Upper;
            textBox.MaxLength = maxLength;
            parent.Controls.Add(label);
            parent.Controls.Add(textBox);
        }

        private void AddContactField(Control parent, ref int y)
        {
            var lbl = new Label
            {
                Text = "CONTACT NUMBER",
                Left = 18,
                Top = y,
                Width = 640,
                Height = 28,
                Font = new Font("Segoe UI", 8.5F, FontStyle.Bold),
                ForeColor = Color.White,
                BackColor = Color.FromArgb(10, 17, 40),
                Padding = new Padding(10, 0, 8, 0),
                TextAlign = ContentAlignment.MiddleLeft
            };
            y += 36;
            _contactField.ApplyVisitorStyle();
            _contactField.Left = 18;
            _contactField.Top = y;
            _contactField.Width = 644;
            y += 50;
            parent.Controls.Add(lbl);
            parent.Controls.Add(_contactField);
            _stretchFields.Add(lbl);
            _stretchFields.Add(_contactField);
        }

        private void AddDocumentField(Control parent, ref int y)
        {
            var lbl = new Label
            {
                Text = "DOCUMENT(S) BEING CLAIMED",
                Left = 18,
                Top = y,
                Width = 640,
                Height = 28,
                Font = new Font("Segoe UI", 8.5F, FontStyle.Bold),
                ForeColor = Color.White,
                BackColor = Color.FromArgb(10, 17, 40),
                Padding = new Padding(10, 0, 8, 0),
                TextAlign = ContentAlignment.MiddleLeft
            };
            y += 36;

            _cmbDocument.Left = 18;
            _cmbDocument.Top = y;
            _cmbDocument.Width = 644;
            _cmbDocument.Height = 34;
            _cmbDocument.Font = new Font("Segoe UI", 10.5F);
            _cmbDocument.DropDownStyle = ComboBoxStyle.DropDownList;
            _cmbDocument.BackColor = Color.FromArgb(248, 250, 252);
            y += 50;

            _documentSectionBottomY = y;
            _lblDocumentOther.Text = "SPECIFY OTHER DOCUMENT(S)";
            _lblDocumentOther.Left = 18;
            _lblDocumentOther.Top = y;
            _lblDocumentOther.Width = 640;
            _lblDocumentOther.Height = 18;
            _lblDocumentOther.Font = new Font("Segoe UI", 8.5F, FontStyle.Bold);
            _lblDocumentOther.ForeColor = Color.FromArgb(100, 116, 139);
            _lblDocumentOther.Visible = false;
            y += 22;

            _txtDocumentOther.Left = 18;
            _txtDocumentOther.Top = y;
            _txtDocumentOther.Width = 644;
            _txtDocumentOther.Height = 52;
            _txtDocumentOther.Multiline = true;
            _txtDocumentOther.ScrollBars = ScrollBars.Vertical;
            _txtDocumentOther.BorderStyle = BorderStyle.FixedSingle;
            _txtDocumentOther.BackColor = Color.FromArgb(241, 245, 249);
            _txtDocumentOther.Font = new Font("Segoe UI", 10F);
            _txtDocumentOther.Enabled = false;
            _txtDocumentOther.Visible = false;
            y += 64;

            parent.Controls.Add(lbl);
            parent.Controls.Add(_cmbDocument);
            parent.Controls.Add(_lblDocumentOther);
            parent.Controls.Add(_txtDocumentOther);
            _stretchFields.Add(lbl);
            _stretchFields.Add(_cmbDocument);
            _stretchFields.Add(_lblDocumentOther);
            _stretchFields.Add(_txtDocumentOther);
        }

        private void ReflowDocumentSection()
        {
            bool isOther = string.Equals(_cmbDocument.SelectedItem as string, "Other", StringComparison.OrdinalIgnoreCase);
            _txtDocumentOther.Enabled = isOther;
            _txtDocumentOther.Visible = isOther;
            _lblDocumentOther.Visible = isOther;
            _txtDocumentOther.BackColor = isOther ? Color.FromArgb(248, 250, 252) : Color.FromArgb(241, 245, 249);
            if (!isOther)
                _txtDocumentOther.Clear();

            ApplyResponsiveLayout();
        }

        private void ApplyResponsiveLayout()
        {
            if (_layoutBusy || _panelScroll == null || _panelBody == null || _btnSave == null || _btnCancel == null)
                return;

            _layoutBusy = true;
            try
            {
                int headerInner = Math.Max(120, _panelHeader.ClientSize.Width - 30);
                _lblTitle.Width = headerInner;
                _lblSub.Width = headerInner;

                int viewW = _panelScroll.ClientSize.Width;
                int bodyW = Math.Max(560, viewW - 36);
                _panelBody.Left = 18;
                _panelBody.Top = 18;
                _panelBody.Width = bodyW;
                _addressPanel.Width = bodyW;

                int inner = Math.Max(200, bodyW - 36);
                foreach (var control in _stretchFields)
                    control.Width = inner;

                _requirementCard.Width = inner;
                int reqInner = Math.Max(100, inner - 28);
                _lblReqTitle.Width = reqInner;
                _lblReqBody.Width = reqInner;
                _chkAuthLetter.Width = reqInner;
                _chkValidIds.Width = reqInner;
                _lblMustCheck.Width = reqInner;

                bool isOther = string.Equals(_cmbDocument.SelectedItem as string, "Other", StringComparison.OrdinalIgnoreCase);
                int optionalOtherHeight = isOther ? 78 : 0;
                int notesTop = _documentSectionBottomY + optionalOtherHeight + 12;
                _lblNotes.Top = notesTop;
                _txtNotes.Top = notesTop + 36;
                _requirementCard.Top = notesTop + 110;

                int naturalBodyHeight = _requirementCard.Bottom + 20;
                const int buttonHeight = 50;
                const int gap = 14;
                const int bottomPad = 24;
                int chromeBelowBody = gap + buttonHeight + bottomPad;
                int availableBody = _panelScroll.ClientSize.Height - _panelBody.Top - chromeBelowBody;
                _panelBody.Height = Math.Max(naturalBodyHeight, availableBody);

                int btnWidth = Math.Max(140, (bodyW - gap) / 2);
                int buttonsTop = _panelBody.Bottom + gap;
                _btnSave.SetBounds(18, buttonsTop, btnWidth, buttonHeight);
                _btnCancel.SetBounds(18 + btnWidth + gap, buttonsTop, btnWidth, buttonHeight);

                _panelScroll.AutoScrollMinSize = new Size(0, _btnCancel.Bottom + bottomPad);
            }
            finally
            {
                _layoutBusy = false;
            }
        }

        private void PopulateDocuments()
        {
            _cmbDocument.Items.Clear();
            _cmbDocument.Items.AddRange(new object[]
            {
                "Barangay Clearance",
                "Certificate of Indigency",
                "Certificate of Residency",
                "Business Permit / Clearance",
                "Barangay ID / Certification",
                "Other"
            });
        }

        private bool ValidateInputs()
        {
            if (string.IsNullOrWhiteSpace(ClaimantFullName) ||
                !_addressPanel.IsComplete)
            {
                MessageBox.Show("Please complete all claimant contact fields.", "Validation",
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

            if (string.IsNullOrWhiteSpace(PrincipalFullName))
            {
                MessageBox.Show("Enter the full name of the document owner (the person on whose behalf documents are claimed).", "Validation",
                    MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return false;
            }

            if (_cmbDocument.SelectedIndex < 0 || string.IsNullOrWhiteSpace(SelectedDocumentDescription()) ||
                SelectedDocumentDescription() == "(unspecified)")
            {
                MessageBox.Show("Select the document type being claimed. If you chose Other, describe the document.", "Validation",
                    MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return false;
            }

            if (!_chkAuthLetter.Checked && !_chkValidIds.Checked)
            {
                MessageBox.Show(
                    "Before saving this entry and releasing documents, complete at least one verification option: authorization letter OR valid IDs.",
                    "Requirements not met",
                    MessageBoxButtons.OK,
                    MessageBoxIcon.Warning);
                return false;
            }

            return true;
        }

        private void ApplyLinkedClaimantIdentity(VisitorProfile profile)
        {
            if (profile == null) return;

            _linkedClaimantIdentity = profile;
            _txtClaimantFirstName.Text = profile.FullName;
            _addressPanel.SetLockedAddress(profile.Address);
            _contactField.SetLockedNumber(profile.ContactNumber);
            if (Enum.TryParse(profile.PersonClassification, true, out PersonClassification classification))
                _claimantClassification.SelectedClassification = classification;
            _claimantClassification.Enabled = false;

            foreach (TextBox textBox in new[]
            {
                _txtClaimantFirstName, _txtClaimantMiddleName, _txtClaimantLastName, _txtClaimantNameExtension
            })
            {
                textBox.ReadOnly = true;
                textBox.BackColor = Color.FromArgb(241, 245, 249);
            }
        }

        private void ConfigureClassificationSelector(
            PersonClassificationSelector selector,
            Control parent,
            ref int y)
        {
            selector.Left = 18;
            selector.Top = y;
            selector.Width = 644;
            parent.Controls.Add(selector);
            _stretchFields.Add(selector);
            y += selector.Height + 8;
        }
    }
}
