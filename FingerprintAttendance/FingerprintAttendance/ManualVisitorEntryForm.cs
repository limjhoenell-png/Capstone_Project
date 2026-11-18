using System;
using System.Collections.Generic;
using System.Drawing;
using System.Drawing.Drawing2D;
using System.Windows.Forms;
using FingerprintAttendance.Core;

namespace FingerprintAttendance
{
    /// <summary>
    /// Paper-style visitor logbook entry: typed details only; no fingerprint is captured (unlike
    /// <see cref="VisitorLogbookEntryForm"/> / New Visitor Entry). Same field layout, including search for saved visitors.
    /// </summary>
    public class ManualVisitorEntryForm : Form
    {
        private readonly DatabaseHelper _db;
        private readonly TextBox _txtFirstName = new TextBox();
        private readonly TextBox _txtMiddleName = new TextBox();
        private readonly TextBox _txtLastName = new TextBox();
        private readonly TextBox _txtNameExtension = new TextBox();
        private readonly AddressInformationPanel _addressPanel = new AddressInformationPanel();
        private readonly ContactNumberField _contactField = new ContactNumberField();
        private readonly ComboBox _cmbPurpose = new ComboBox();
        private readonly TextBox _txtPurposeOther = new TextBox();
        private readonly TextBox _txtSelectedFullName = new TextBox();
        private readonly CheckBox _chkEnrollFingerprint = new CheckBox();
        private readonly CheckBox _chkConsent = new CheckBox();
        private readonly Label _lblPurposeOther = new Label();
        private readonly Label _lblSelectedFullName = new Label();
        private readonly List<Control> _nameFieldControls = new List<Control>();
        private readonly PersonClassificationSelector _classificationSelector =
            new PersonClassificationSelector(selectDefault: false);
        private VisitorProfile _linkedReturningIdentity;
        private Panel _panelScroll;
        private Panel _panelBody;
        private Button _btnSave;
        private Button _btnCancel;
        private Label _lblTitle;
        private Label _lblContact;
        private Label _lblPurpose;
        private ManualEntrySectionPanel _personalInfoSectionCard;
        private ManualEntrySectionPanel _classificationSectionCard;
        private ManualEntrySectionPanel _purposeSectionCard;
        private ManualEntrySectionPanel _enrollmentSectionCard;
        private Panel _consentCard;
        private int _nameFieldsTop;
        private int _nameFieldsBottom;
        private bool _selectedNameLayoutApplied;
        private bool _updatingConsentLayout;
        private Action _updateConsentCardLayout;
        private Panel _panelHeader;
        private readonly ScrollBodyLayout _scrollLayout = new ScrollBodyLayout();

        public string VisitorFullName =>
            _linkedReturningIdentity?.FullName ??
            DatabaseHelper.BuildEmployeeFullName(
                _txtFirstName.Text,
                _txtMiddleName.Text,
                _txtLastName.Text,
                _txtNameExtension.Text);
        public string VisitorAddress => _addressPanel.ComposedAddress;
        public string VisitorContactNumber => _contactField.StoredNumber;
        public string SelectedPersonClassification => _classificationSelector.SelectedValue;
        public bool RegisterFingerprint => _chkEnrollFingerprint.Checked;

        public string PurposeOfVisit
        {
            get
            {
                string selected = _cmbPurpose.SelectedItem as string;
                if (string.Equals(selected, "Other", StringComparison.OrdinalIgnoreCase))
                {
                    return PurposeOfVisitCatalog.FormatOtherPurpose(_txtPurposeOther.Text);
                }

                return selected?.Trim() ?? string.Empty;
            }
        }

        /// <summary>Visitor chosen via search; form fields are filled from profile — manual save still stores no fingerprint id.</summary>
        public VisitorProfile LinkedReturningIdentity => _linkedReturningIdentity;

        public ManualVisitorEntryForm(DatabaseHelper db, VisitorProfile preLinkedProfile = null)
        {
            _db = db;

            Text = "Manual Transaction Entry";
            StartPosition = FormStartPosition.CenterParent;
            FormBorderStyle = FormBorderStyle.FixedDialog;
            MaximizeBox = false;
            MinimizeBox = false;
            BackColor = Color.FromArgb(244, 247, 250);
            Font = new Font("Segoe UI", 10F);
            ClientSize = new Size(900, 840);
            AutoScroll = false;

            _panelHeader = new Panel
            {
                Dock = DockStyle.Top,
                Height = 72,
                BackColor = Color.FromArgb(10, 17, 40),
                Padding = new Padding(4, 0, 0, 0)
            };
            _lblTitle = new Label
            {
                Text = "Manual Transaction Entry",
                Dock = DockStyle.Fill,
                Font = new Font("Segoe UI", 16F, FontStyle.Bold),
                ForeColor = Color.White,
                BackColor = Color.Transparent,
                TextAlign = ContentAlignment.MiddleCenter
            };
            _panelHeader.Controls.Add(_lblTitle);

            _panelScroll = new Panel
            {
                Dock = DockStyle.Fill,
                AutoScroll = true,
                BackColor = Color.FromArgb(244, 247, 250)
            };

            _panelBody = new ManualEntryCardPanel
            {
                Left = 18,
                Top = 18,
                Width = 680,
                Height = 560,
                BackColor = Color.White
            };

            int y = 16;
            var btnFind = new Button
            {
                Text = "Find Saved Person...",
                Left = 18,
                Top = y,
                Width = 200,
                Height = 40,
                Font = new Font("Segoe UI", 9.5F, FontStyle.Bold),
                BackColor = Color.FromArgb(29, 97, 231),
                ForeColor = Color.White,
                FlatStyle = FlatStyle.Flat,
                Cursor = Cursors.Hand
            };
            btnFind.FlatAppearance.BorderSize = 0;
            btnFind.FlatAppearance.MouseOverBackColor = Color.FromArgb(24, 81, 199);
            btnFind.FlatAppearance.MouseDownBackColor = Color.FromArgb(20, 67, 169);
            btnFind.Click += (_, __) =>
            {
                using var find = new VisitorSearchDialog(_db);
                if (find.ShowDialog(this) != DialogResult.OK || find.SelectedProfile == null)
                    return;
                ApplyLinkedVisitorIdentity(find.SelectedProfile);
            };
            _panelBody.Controls.Add(btnFind);
            y += 48;

            var personalInfoHeading = new Label
            {
                Text = "PERSONAL INFORMATION",
                Left = 18,
                Top = y,
                Width = 644,
                Height = 34,
                Font = new Font("Segoe UI", 8.5F, FontStyle.Bold),
                ForeColor = Color.FromArgb(8, 125, 160),
                BackColor = Color.FromArgb(246, 249, 254),
                TextAlign = ContentAlignment.MiddleLeft,
                Padding = new Padding(14, 0, 10, 0)
            };
            _panelBody.Controls.Add(personalInfoHeading);
            y += 46;

            int personalInfoTop = personalInfoHeading.Top - 6;
            _nameFieldsTop = y;
            AddNameFields(_panelBody, ref y);
            _nameFieldsBottom = y;
            int personalInfoHeight = y - personalInfoTop - 10;

            _lblSelectedFullName.Text = "FULL NAME";
            _lblSelectedFullName.SetBounds(18, _nameFieldsTop, 644, 18);
            _lblSelectedFullName.Font = new Font("Segoe UI", 8.5F, FontStyle.Bold);
            _lblSelectedFullName.ForeColor = Color.FromArgb(82, 102, 132);
            _lblSelectedFullName.Visible = false;
            _txtSelectedFullName.SetBounds(18, _nameFieldsTop + 22, 644, 32);
            _txtSelectedFullName.ReadOnly = true;
            _txtSelectedFullName.BorderStyle = BorderStyle.FixedSingle;
            _txtSelectedFullName.Font = new Font("Segoe UI", 11F);
            _txtSelectedFullName.ForeColor = Color.FromArgb(27, 46, 74);
            _txtSelectedFullName.BackColor = Color.FromArgb(241, 245, 249);
            _txtSelectedFullName.Visible = false;
            _panelBody.Controls.Add(_lblSelectedFullName);
            _panelBody.Controls.Add(_txtSelectedFullName);

            _classificationSelector.Left = 18;
            _classificationSelector.Top = y;
            _classificationSelector.Width = 644;
            _panelBody.Controls.Add(_classificationSelector);
            y += _classificationSelector.Height + 16;
            _addressPanel.Left = 0;
            _addressPanel.Top = y;
            _addressPanel.Width = 680;
            _panelBody.Controls.Add(_addressPanel);
            y += _addressPanel.Height + 16;
            AddContactField(_panelBody, ref y);
            AddPurposeField(_panelBody, ref y);
            AddFingerprintEnrollmentSection();
            _personalInfoSectionCard = AddSectionCard(personalInfoTop, personalInfoHeight);
            _classificationSectionCard = AddSectionCard(_classificationSelector.Top - 8, _classificationSelector.Height + 16);
            _addressPanel.Paint += DrawManualCardBorder;
            AddSectionCard(_lblContact.Top - 10, _contactField.Bottom - _lblContact.Top + 20);
            _purposeSectionCard = AddSectionCard(
                _lblPurpose.Top - 10,
                _cmbPurpose.Bottom - _lblPurpose.Top + 20);

            _btnSave = new Button
            {
                Text = "Save Logbook Entry",
                Left = 18,
                Top = _panelBody.Bottom + 14,
                Width = 333,
                Height = 50,
                Font = new Font("Segoe UI", 10.5F, FontStyle.Bold),
                BackColor = Color.FromArgb(37, 99, 235),
                ForeColor = Color.White,
                FlatStyle = FlatStyle.Flat,
                DialogResult = DialogResult.OK
            };
            _btnSave.FlatAppearance.BorderSize = 0;
            ApplyRoundedButton(_btnSave);
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
            ApplyRoundedButton(_btnCancel);

            _panelScroll.Controls.Add(_panelBody);
            Controls.Add(_panelScroll);
            Controls.Add(_panelHeader);
            ResponsiveLayout.PinActionButtons(this, _btnSave, _btnCancel);
            Controls.SetChildIndex(_panelScroll, 0);
            Controls.SetChildIndex(_panelHeader, Controls.Count - 1);
            _scrollLayout.Attach(this, _panelScroll, _panelBody, _addressPanel, _panelHeader, null);
            CancelButton = _btnCancel;

            PopulatePurposeOptions();
            if (_cmbPurpose.Items.Count > 0)
                _cmbPurpose.SelectedIndex = 0;
            _cmbPurpose.SelectedIndexChanged += (_, __) => OnPurposeChanged();
            OnPurposeChanged();

            if (preLinkedProfile != null)
                ApplyLinkedVisitorIdentity(preLinkedProfile);
            DesktopTheme.Apply(this);
            ApplyManualEntryVisualStyle(btnFind, personalInfoHeading);
        }

        private void ApplyManualEntryVisualStyle(Button btnFind, Label personalInfoHeading)
        {
            BackColor = Color.FromArgb(244, 247, 250);
            _panelScroll.BackColor = Color.FromArgb(244, 247, 250);
            _panelHeader.BackColor = Color.FromArgb(10, 17, 40);
            _lblTitle.ForeColor = Color.White;
            _lblTitle.Font = new Font("Segoe UI", 16F, FontStyle.Bold);
            _lblTitle.TextAlign = ContentAlignment.MiddleCenter;
            _panelBody.BackColor = Color.White;

            btnFind.BackColor = Color.FromArgb(29, 97, 231);
            btnFind.ForeColor = Color.White;
            btnFind.FlatAppearance.BorderSize = 0;
            btnFind.FlatAppearance.MouseOverBackColor = Color.FromArgb(24, 81, 199);
            btnFind.FlatAppearance.MouseDownBackColor = Color.FromArgb(20, 67, 169);
            personalInfoHeading.ForeColor = Color.FromArgb(8, 125, 160);
            personalInfoHeading.BackColor = Color.FromArgb(246, 249, 254);

            _btnSave.BackColor = Color.FromArgb(29, 97, 231);
            _btnSave.ForeColor = Color.White;
            _btnSave.FlatStyle = FlatStyle.Flat;
            _btnSave.FlatAppearance.BorderSize = 0;
            _btnSave.FlatAppearance.MouseOverBackColor = Color.FromArgb(24, 81, 199);
            _btnSave.FlatAppearance.MouseDownBackColor = Color.FromArgb(20, 67, 169);
            _btnCancel.BackColor = Color.White;
            _btnCancel.ForeColor = Color.FromArgb(49, 68, 96);
            _btnCancel.FlatStyle = FlatStyle.Flat;
            _btnCancel.FlatAppearance.BorderSize = 1;
            _btnCancel.FlatAppearance.BorderColor = Color.FromArgb(199, 214, 235);
            _btnCancel.FlatAppearance.MouseOverBackColor = Color.FromArgb(235, 243, 253);
            _btnCancel.FlatAppearance.MouseDownBackColor = Color.FromArgb(220, 233, 250);

            ApplyManualEntryControlStyle(_panelBody);
            _panelBody.BackColor = Color.White;
            personalInfoHeading.ForeColor = Color.FromArgb(8, 125, 160);
            personalInfoHeading.BackColor = Color.FromArgb(246, 249, 254);
            _classificationSectionCard.BackColor = Color.White;
            _classificationSelector.BackColor = Color.White;
            _classificationSelector.ApplySectionHeaderStyle(
                Color.FromArgb(10, 17, 40),
                Color.White);
            _classificationSelector.ApplyOptionStyle(
                Color.FromArgb(30, 41, 59),
                Color.FromArgb(10, 17, 40),
                Color.FromArgb(100, 116, 139));

            _panelHeader.Paint += DrawManualHeaderAccent;
            ApplyRoundedButton(btnFind);
            foreach (Control control in Controls)
            {
                if (control is Panel footer && footer.Dock == DockStyle.Bottom && footer != _panelHeader)
                {
                    footer.BackColor = Color.FromArgb(244, 247, 250);
                    footer.Paint += DrawManualCardBorder;
                }
            }
        }

        private static void ApplyManualEntryControlStyle(Control parent)
        {
            foreach (Control control in parent.Controls)
            {
                if (control is Label label &&
                    label.ForeColor == Color.FromArgb(100, 116, 139))
                {
                    label.ForeColor = Color.FromArgb(82, 102, 132);
                }
                else if (control is TextBox textBox)
                {
                    textBox.ForeColor = Color.FromArgb(27, 46, 74);
                    if (!textBox.ReadOnly)
                    {
                        textBox.BackColor = textBox.Enabled
                            ? Color.FromArgb(248, 251, 255)
                            : Color.FromArgb(241, 245, 249);
                        textBox.Enter += (_, __) =>
                        {
                            if (textBox.Enabled && !textBox.ReadOnly)
                                textBox.BackColor = Color.FromArgb(235, 245, 255);
                        };
                        textBox.Leave += (_, __) =>
                        {
                            textBox.BackColor = textBox.Enabled
                                ? Color.FromArgb(248, 251, 255)
                                : Color.FromArgb(241, 245, 249);
                        };
                    }
                }
                else if (control is ComboBox comboBox)
                {
                    comboBox.ForeColor = Color.FromArgb(27, 46, 74);
                    comboBox.BackColor = Color.FromArgb(248, 251, 255);
                    comboBox.Enter += (_, __) =>
                    {
                        if (comboBox.Enabled)
                            comboBox.BackColor = Color.FromArgb(235, 245, 255);
                    };
                    comboBox.Leave += (_, __) =>
                    {
                        comboBox.BackColor = Color.FromArgb(248, 251, 255);
                    };
                }
                else if (control is RadioButton || control is CheckBox)
                {
                    control.ForeColor = Color.FromArgb(35, 55, 84);
                }
                else if (control is Panel panel && panel.BorderStyle == BorderStyle.FixedSingle)
                {
                    panel.BorderStyle = BorderStyle.None;
                    panel.BackColor = Color.FromArgb(246, 249, 254);
                    panel.Paint += DrawManualCardBorder;
                }

                if (control.HasChildren)
                    ApplyManualEntryControlStyle(control);
            }
        }

        private static void DrawManualCardBorder(object sender, PaintEventArgs e)
        {
            var panel = (Panel)sender;
            Rectangle bounds = panel.ClientRectangle;
            if (bounds.Width < 2 || bounds.Height < 2)
                return;

            using (var shadowPen = new Pen(Color.FromArgb(18, 54, 83, 128)))
                e.Graphics.DrawLine(shadowPen, 1, bounds.Height - 2, bounds.Width - 2, bounds.Height - 2);
            using (var borderPen = new Pen(Color.FromArgb(216, 228, 244)))
                e.Graphics.DrawRectangle(borderPen, 0, 0, bounds.Width - 1, bounds.Height - 1);
        }

        private static void DrawManualHeaderAccent(object sender, PaintEventArgs e)
        {
            using (var accentBrush = new SolidBrush(Color.FromArgb(0, 210, 255)))
                e.Graphics.FillRectangle(accentBrush, 0, 0, 4, ((Panel)sender).Height);
        }

        private static GraphicsPath CreateRoundedPath(Rectangle bounds, int radius)
        {
            int diameter = Math.Min(radius * 2, Math.Min(bounds.Width, bounds.Height));
            var path = new GraphicsPath();
            path.AddArc(bounds.Left, bounds.Top, diameter, diameter, 180, 90);
            path.AddArc(bounds.Right - diameter, bounds.Top, diameter, diameter, 270, 90);
            path.AddArc(bounds.Right - diameter, bounds.Bottom - diameter, diameter, diameter, 0, 90);
            path.AddArc(bounds.Left, bounds.Bottom - diameter, diameter, diameter, 90, 90);
            path.CloseFigure();
            return path;
        }

        private ManualEntrySectionPanel AddSectionCard(int top, int height)
        {
            var card = new ManualEntrySectionPanel
            {
                Left = 10,
                Top = Math.Max(0, top),
                Width = 660,
                Height = Math.Max(42, height),
                BackColor = Color.White
            };
            _panelBody.Controls.Add(card);
            card.SendToBack();
            return card;
        }

        private static void ApplyRoundedButton(Button button)
        {
            void UpdateRegion(object sender, EventArgs e)
            {
                if (button.Width < 2 || button.Height < 2)
                    return;

                using (GraphicsPath path = CreateRoundedPath(
                    new Rectangle(0, 0, button.Width, button.Height), 10))
                {
                    Region previous = button.Region;
                    button.Region = new Region(path);
                    previous?.Dispose();
                }
            }

            button.Resize += UpdateRegion;
            UpdateRegion(button, EventArgs.Empty);
        }

        private void ApplyLinkedVisitorIdentity(VisitorProfile profile)
        {
            if (profile == null) return;

            _linkedReturningIdentity = profile;
            _chkEnrollFingerprint.Checked = false;
            _chkConsent.Checked = false;
            _chkEnrollFingerprint.Enabled = !profile.HasFingerprintOnFile;
            _chkEnrollFingerprint.Text = profile.HasFingerprintOnFile
                ? "Fingerprint already enrolled"
                : "Enroll this Fingerprint";
            ApplySelectedPersonNameLayout(profile.FullName);
            if (Enum.TryParse(profile.PersonClassification, true, out PersonClassification classification))
                _classificationSelector.SelectedClassification = classification;
            _classificationSelector.Enabled = false;
            int previousAddressBottom = _addressPanel.Bottom;
            _addressPanel.SetLockedAddress(profile.Address, compact: true);
            int followingControlsOffset = _addressPanel.Bottom - previousAddressBottom;
            if (followingControlsOffset != 0)
            {
                foreach (Control control in _panelBody.Controls)
                {
                    if (control != _addressPanel && control.Top >= previousAddressBottom)
                        control.Top += followingControlsOffset;
                }
                _panelBody.Height = Math.Max(0, _panelBody.Height + followingControlsOffset);
                _scrollLayout.Apply();
            }
            _contactField.SetLockedNumber(profile.ContactNumber);
            foreach (TextBox textBox in new[] { _txtFirstName, _txtMiddleName, _txtLastName, _txtNameExtension })
            {
                textBox.ReadOnly = true;
                textBox.BackColor = Color.FromArgb(241, 245, 249);
            }

            Text = "Manual Transaction Entry";
            _lblTitle.Text = "Manual Transaction Entry";
        }

        private void ApplySelectedPersonNameLayout(string fullName)
        {
            if (_selectedNameLayoutApplied)
            {
                _txtSelectedFullName.Text = fullName ?? string.Empty;
                return;
            }

            int compactNameBottom = _txtSelectedFullName.Bottom + 12;
            int verticalOffset = compactNameBottom - _nameFieldsBottom;
            foreach (Control control in _nameFieldControls)
                control.Visible = false;

            _txtSelectedFullName.Text = fullName ?? string.Empty;
            _lblSelectedFullName.Visible = true;
            _txtSelectedFullName.Visible = true;

            foreach (Control control in _panelBody.Controls)
            {
                if (control.Top >= _nameFieldsBottom || control == _classificationSectionCard)
                    control.Top += verticalOffset;
            }

            _personalInfoSectionCard.Height += verticalOffset;
            _panelBody.Height = Math.Max(0, _panelBody.Height + verticalOffset);
            _selectedNameLayoutApplied = true;
            _scrollLayout.Apply();
        }

        private void AddNameFields(Control parent, ref int y)
        {
            const int left = 18;
            const int columnGap = 18;
            const int fieldWidth = 313;

            var lblFirstName = new Label
            {
                Text = "FIRST NAME",
                Left = left,
                Top = y,
                Width = fieldWidth,
                Height = 18,
                Font = new Font("Segoe UI", 8.5F, FontStyle.Bold),
                ForeColor = Color.FromArgb(100, 116, 139)
            };
            y += 24;
            ConfigureNameTextBox(_txtFirstName, left, y, fieldWidth, 100);
            parent.Controls.Add(lblFirstName);
            parent.Controls.Add(_txtFirstName);
            _nameFieldControls.Add(lblFirstName);
            _nameFieldControls.Add(_txtFirstName);

            var lblLastName = new Label
            {
                Text = "LAST NAME",
                Left = left + fieldWidth + columnGap,
                Top = y - 24,
                Width = fieldWidth,
                Height = 18,
                Font = new Font("Segoe UI", 8.5F, FontStyle.Bold),
                ForeColor = Color.FromArgb(100, 116, 139)
            };
            ConfigureNameTextBox(_txtLastName, left + fieldWidth + columnGap, y, fieldWidth, 100);
            parent.Controls.Add(lblLastName);
            parent.Controls.Add(_txtLastName);
            _nameFieldControls.Add(lblLastName);
            _nameFieldControls.Add(_txtLastName);
            y += 38;

            var lblMiddleName = new Label
            {
                Text = "MIDDLE NAME (OPTIONAL)",
                Left = left,
                Top = y,
                Width = fieldWidth,
                Height = 18,
                Font = new Font("Segoe UI", 8.5F, FontStyle.Bold),
                ForeColor = Color.FromArgb(100, 116, 139)
            };
            y += 24;
            ConfigureNameTextBox(_txtMiddleName, left, y, fieldWidth, 100);
            parent.Controls.Add(lblMiddleName);
            parent.Controls.Add(_txtMiddleName);
            _nameFieldControls.Add(lblMiddleName);
            _nameFieldControls.Add(_txtMiddleName);

            var lblNameExtension = new Label
            {
                Text = "NAME EXTENSION (OPTIONAL: JR., SR., 1ST, 2ND, ETC.)",
                Left = left + fieldWidth + columnGap,
                Top = y - 24,
                Width = fieldWidth,
                Height = 18,
                Font = new Font("Segoe UI", 8.5F, FontStyle.Bold),
                ForeColor = Color.FromArgb(100, 116, 139)
            };
            ConfigureNameTextBox(_txtNameExtension, left + fieldWidth + columnGap, y, fieldWidth, 20);
            parent.Controls.Add(lblNameExtension);
            parent.Controls.Add(_txtNameExtension);
            _nameFieldControls.Add(lblNameExtension);
            _nameFieldControls.Add(_txtNameExtension);
            y += 38;
        }

        private static void ConfigureNameTextBox(TextBox textBox, int left, int top, int width, int maxLength)
        {
            textBox.Left = left;
            textBox.Top = top;
            textBox.Width = width;
            textBox.Height = 32;
            textBox.Font = new Font("Segoe UI", 11F);
            textBox.BorderStyle = BorderStyle.FixedSingle;
            textBox.BackColor = Color.FromArgb(248, 250, 252);
            textBox.CharacterCasing = CharacterCasing.Upper;
            textBox.MaxLength = maxLength;
        }

        private void AddContactField(Control parent, ref int y)
        {
            _lblContact = new Label
            {
                Text = "CONTACT NUMBER (OPTIONAL)",
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
            y += 60;
            parent.Controls.Add(_lblContact);
            parent.Controls.Add(_contactField);
        }

        private void AddPurposeField(Control parent, ref int y)
        {
            _lblPurpose = new Label
            {
                Text = "PURPOSE OF VISIT",
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

            _cmbPurpose.Left = 18;
            _cmbPurpose.Top = y;
            _cmbPurpose.Width = 644;
            _cmbPurpose.Height = 34;
            _cmbPurpose.Font = new Font("Segoe UI", 10.5F);
            _cmbPurpose.DropDownStyle = ComboBoxStyle.DropDownList;
            _cmbPurpose.IntegralHeight = true;
            _cmbPurpose.MaxDropDownItems = 16;
            _cmbPurpose.BackColor = Color.FromArgb(248, 250, 252);
            y += 50;

            _lblPurposeOther.Text = "OTHER PURPOSE DETAILS";
            _lblPurposeOther.Left = 18;
            _lblPurposeOther.Top = y;
            _lblPurposeOther.Width = 640;
            _lblPurposeOther.Height = 28;
            _lblPurposeOther.Font = new Font("Segoe UI", 8.5F, FontStyle.Bold);
            _lblPurposeOther.ForeColor = Color.White;
            _lblPurposeOther.BackColor = Color.FromArgb(10, 17, 40);
            _lblPurposeOther.Padding = new Padding(10, 0, 8, 0);
            _lblPurposeOther.TextAlign = ContentAlignment.MiddleLeft;
            _lblPurposeOther.Visible = false;
            y += 36;

            _txtPurposeOther.Left = 18;
            _txtPurposeOther.Top = y;
            _txtPurposeOther.Width = 644;
            _txtPurposeOther.Height = 74;
            _txtPurposeOther.Multiline = true;
            _txtPurposeOther.ScrollBars = ScrollBars.Vertical;
            _txtPurposeOther.BorderStyle = BorderStyle.FixedSingle;
            _txtPurposeOther.BackColor = Color.FromArgb(241, 245, 249);
            _txtPurposeOther.Font = new Font("Segoe UI", 10.5F);
            _txtPurposeOther.Enabled = false;
            _txtPurposeOther.Visible = false;
            y += 88;

            parent.Controls.Add(_lblPurpose);
            parent.Controls.Add(_cmbPurpose);
            parent.Controls.Add(_lblPurposeOther);
            parent.Controls.Add(_txtPurposeOther);
        }

        private void AddFingerprintEnrollmentSection()
        {
            _chkEnrollFingerprint.Text = "Enroll this Fingerprint";
            _chkEnrollFingerprint.Left = 30;
            _chkEnrollFingerprint.Top = _cmbPurpose.Bottom + 10;
            _chkEnrollFingerprint.Width = 620;
            _chkEnrollFingerprint.Height = 30;
            _chkEnrollFingerprint.Font = new Font("Segoe UI", 9.5F, FontStyle.Bold);
            _chkEnrollFingerprint.ForeColor = Color.FromArgb(35, 55, 84);
            _chkEnrollFingerprint.AccessibleDescription =
                "Optional fingerprint registration for faster check-ins on future visits.";
            _chkEnrollFingerprint.CheckedChanged += (_, __) =>
            {
                if (!_chkEnrollFingerprint.Checked)
                    _chkConsent.Checked = false;
                UpdateEnrollmentLayout();
            };
            _panelBody.Controls.Add(_chkEnrollFingerprint);
            _enrollmentSectionCard = AddSectionCard(
                _chkEnrollFingerprint.Top - 6,
                _chkEnrollFingerprint.Height + 12);

            const string ra10173FullTitle =
                "AN ACT PROTECTING INDIVIDUAL PERSONAL INFORMATION IN INFORMATION AND COMMUNICATIONS SYSTEMS IN THE GOVERNMENT AND THE PRIVATE SECTOR, CREATING FOR THIS PURPOSE A NATIONAL PRIVACY COMMISSION, AND FOR OTHER PURPOSES";
            const string consentBodyText =
                "Fingerprint registration is optional and is used only to identify you more quickly on future visits. Personal data is handled in line with RA 10173 and applicable issuances of the National Privacy Commission.";
            const string consentStatement =
                "I have read the foregoing, understand how my fingerprint data will be used, and voluntarily consent to optional fingerprint registration for this logbook, in accordance with RA 10173.";
            Color consentBackground = Color.FromArgb(255, 247, 237);

            _consentCard = new Panel
            {
                Left = 18,
                Top = _chkEnrollFingerprint.Bottom + 8,
                Width = 644,
                Height = 300,
                BackColor = consentBackground,
                BorderStyle = BorderStyle.None,
                Padding = new Padding(16, 14, 16, 16),
                Visible = false
            };

            var lblConsentTitle = new Label
            {
                Text = "Data Privacy Act of 2012 (RA 10173) Consent",
                AutoSize = true,
                MaximumSize = new Size(600, 0),
                Margin = new Padding(0, 0, 0, 8),
                Font = new Font("Segoe UI", 10.5F, FontStyle.Bold),
                ForeColor = Color.FromArgb(30, 41, 59)
            };
            var lblStatuteIntro = new Label
            {
                Text = "Republic Act No. 10173",
                AutoSize = true,
                MaximumSize = new Size(600, 0),
                Margin = new Padding(0, 0, 0, 8),
                Font = new Font("Segoe UI", 8F, FontStyle.Bold),
                ForeColor = Color.FromArgb(100, 116, 139)
            };
            var pnlStatute = new Panel
            {
                Height = 100,
                Margin = new Padding(0, 0, 0, 16),
                BackColor = consentBackground,
                BorderStyle = BorderStyle.None,
                Padding = new Padding(12, 10, 12, 10)
            };
            var txtRa10173Title = new RichTextBox
            {
                ReadOnly = true,
                BorderStyle = BorderStyle.None,
                Multiline = true,
                WordWrap = true,
                ScrollBars = RichTextBoxScrollBars.None,
                Dock = DockStyle.Fill,
                BackColor = pnlStatute.BackColor,
                ForeColor = Color.FromArgb(71, 85, 105),
                Font = new Font("Segoe UI", 8F),
                Text = ra10173FullTitle,
                TabStop = false,
                Cursor = Cursors.Default
            };
            ApplyJustifiedAlignment(txtRa10173Title);
            pnlStatute.Controls.Add(txtRa10173Title);

            var lblBodyHeading = new Label
            {
                Text = "How we use fingerprint data",
                AutoSize = true,
                MaximumSize = new Size(600, 0),
                Margin = new Padding(0, 0, 0, 6),
                Font = new Font("Segoe UI", 8F, FontStyle.Bold),
                ForeColor = Color.FromArgb(51, 65, 85)
            };
            var txtConsentBody = new RichTextBox
            {
                ReadOnly = true,
                BorderStyle = BorderStyle.None,
                Multiline = true,
                WordWrap = true,
                ScrollBars = RichTextBoxScrollBars.None,
                Height = 56,
                Margin = new Padding(0, 0, 0, 14),
                BackColor = _consentCard.BackColor,
                ForeColor = Color.FromArgb(51, 65, 85),
                Font = new Font("Segoe UI", 8.75F),
                Text = consentBodyText,
                TabStop = false,
                Cursor = Cursors.Default
            };
            ApplyJustifiedAlignment(txtConsentBody);

            var pnlConsentCheck = new Panel
            {
                Height = 56,
                Margin = new Padding(0, 4, 0, 0),
                BackColor = consentBackground
            };
            _chkConsent.Text = string.Empty;
            _chkConsent.Size = new Size(18, 18);
            _chkConsent.Location = new Point(0, 2);
            _chkConsent.TabIndex = 0;
            _chkConsent.AccessibleName = "Consent to optional fingerprint registration";
            _chkConsent.AccessibleDescription = consentStatement;
            var lblConsentCheck = new Label
            {
                Text = consentStatement,
                Left = 28,
                Top = 0,
                Width = 584,
                AutoSize = true,
                MaximumSize = new Size(584, 0),
                Font = new Font("Segoe UI", 8.85F),
                ForeColor = Color.FromArgb(30, 41, 59),
                Cursor = Cursors.Hand,
                BackColor = Color.Transparent
            };
            lblConsentCheck.Click += (_, __) => { _chkConsent.Checked = !_chkConsent.Checked; };
            pnlConsentCheck.Controls.Add(_chkConsent);
            pnlConsentCheck.Controls.Add(lblConsentCheck);

            var tlpConsent = new TableLayoutPanel
            {
                Dock = DockStyle.Top,
                AutoSize = true,
                AutoSizeMode = AutoSizeMode.GrowAndShrink,
                ColumnCount = 1,
                RowCount = 6,
                BackColor = _consentCard.BackColor,
                Margin = Padding.Empty,
                Padding = Padding.Empty
            };
            tlpConsent.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 100F));
            for (int row = 0; row < tlpConsent.RowCount; row++)
                tlpConsent.RowStyles.Add(new RowStyle(SizeType.AutoSize));

            tlpConsent.Controls.Add(lblConsentTitle, 0, 0);
            tlpConsent.Controls.Add(lblStatuteIntro, 0, 1);
            tlpConsent.Controls.Add(pnlStatute, 0, 2);
            tlpConsent.Controls.Add(lblBodyHeading, 0, 3);
            tlpConsent.Controls.Add(txtConsentBody, 0, 4);
            tlpConsent.Controls.Add(pnlConsentCheck, 0, 5);
            lblConsentTitle.Dock = DockStyle.Top;
            lblStatuteIntro.Dock = DockStyle.Top;
            pnlStatute.Dock = DockStyle.Fill;
            lblBodyHeading.Dock = DockStyle.Top;
            txtConsentBody.Dock = DockStyle.Fill;
            pnlConsentCheck.Dock = DockStyle.Fill;
            _consentCard.Controls.Add(tlpConsent);
            pnlStatute.Paint += DrawConsentCardBorder;
            _consentCard.Paint += DrawConsentCardBorder;
            _panelBody.Controls.Add(_consentCard);

            _updateConsentCardLayout = () =>
            {
                if (_consentCard.IsDisposed || _updatingConsentLayout)
                    return;

                _updatingConsentLayout = true;
                try
                {
                    int contentWidth = Math.Max(160,
                        _consentCard.ClientSize.Width - _consentCard.Padding.Horizontal);
                    int statuteTextWidth = Math.Max(120,
                        contentWidth - pnlStatute.Padding.Horizontal - 4);
                    int consentTextWidth = Math.Max(120, contentWidth - lblConsentCheck.Left);
                    lblConsentTitle.MaximumSize = new Size(contentWidth, 0);
                    lblStatuteIntro.MaximumSize = new Size(contentWidth, 0);
                    lblBodyHeading.MaximumSize = new Size(contentWidth, 0);
                    txtRa10173Title.Height =
                        MeasureWrappedTextHeight(ra10173FullTitle, txtRa10173Title.Font, statuteTextWidth) + 10;
                    pnlStatute.Height = Math.Max(72,
                        txtRa10173Title.Height + pnlStatute.Padding.Vertical + 2);
                    txtConsentBody.Height = Math.Max(48,
                        MeasureWrappedTextHeight(consentBodyText, txtConsentBody.Font, contentWidth - 4) + 12);
                    lblConsentCheck.Width = consentTextWidth;
                    lblConsentCheck.MaximumSize = new Size(consentTextWidth, 0);
                    pnlConsentCheck.Height = Math.Max(36,
                        MeasureWrappedTextHeight(consentStatement, lblConsentCheck.Font, consentTextWidth) + 10);
                    tlpConsent.Width = contentWidth;
                    tlpConsent.PerformLayout();
                    tlpConsent.Height = Math.Max(1,
                        tlpConsent.GetPreferredSize(new Size(contentWidth, 0)).Height);
                    _consentCard.Height = tlpConsent.Height + _consentCard.Padding.Vertical;
                }
                finally
                {
                    _updatingConsentLayout = false;
                }
            };
            _consentCard.Resize += (_, __) => UpdateConsentCardSize();
            UpdateConsentCardSize();
        }

        private void UpdateConsentCardSize()
        {
            if (_consentCard == null || _consentCard.IsDisposed)
                return;

            _consentCard.Width = Math.Max(160, _panelBody.ClientSize.Width - 36);
            _updateConsentCardLayout?.Invoke();
            if (_chkEnrollFingerprint.Checked)
            {
                _panelBody.Height = _consentCard.Bottom + 20;
                _panelScroll.AutoScrollMinSize = new Size(0, _panelBody.Bottom + 24);
            }
        }

        private static void ApplyJustifiedAlignment(RichTextBox textBox)
        {
            textBox.Rtf = textBox.Rtf.Replace("\\pard", "\\pard\\qj");
        }

        private static void DrawConsentCardBorder(object sender, PaintEventArgs e)
        {
            var panel = (Panel)sender;
            Rectangle bounds = panel.ClientRectangle;
            if (bounds.Width < 2 || bounds.Height < 2)
                return;

            using (var shadowPen = new Pen(Color.FromArgb(18, 54, 83, 128)))
                e.Graphics.DrawLine(shadowPen, 1, bounds.Height - 2, bounds.Width - 2, bounds.Height - 2);
            using (var borderPen = new Pen(Color.FromArgb(216, 228, 244)))
                e.Graphics.DrawRectangle(borderPen, 0, 0, bounds.Width - 1, bounds.Height - 1);
        }

        private static int MeasureWrappedTextHeight(string text, Font font, int width)
        {
            Size measured = TextRenderer.MeasureText(
                text,
                font,
                new Size(Math.Max(80, width), int.MaxValue),
                TextFormatFlags.WordBreak | TextFormatFlags.TextBoxControl);
            return measured.Height;
        }

        private void UpdateEnrollmentLayout()
        {
            if (_consentCard == null || _panelBody == null)
                return;

            int purposeBottom = _txtPurposeOther.Visible
                ? _txtPurposeOther.Bottom
                : _cmbPurpose.Bottom;
            _purposeSectionCard.Height = purposeBottom - _lblPurpose.Top + 20;
            _chkEnrollFingerprint.Top = purposeBottom + 18;
            _enrollmentSectionCard.SetBounds(
                10,
                _chkEnrollFingerprint.Top - 8,
                660,
                _chkEnrollFingerprint.Height + 16);
            _consentCard.Top = _chkEnrollFingerprint.Bottom + 12;
            _consentCard.Visible = _chkEnrollFingerprint.Checked;
            UpdateConsentCardSize();

            int contentBottom = _chkEnrollFingerprint.Checked
                ? _consentCard.Bottom + 4
                : _chkEnrollFingerprint.Bottom + 16;
            _panelBody.Height = contentBottom + 20;
            _scrollLayout.Apply();
        }

        private void OnPurposeChanged()
        {
            bool isOther = string.Equals(_cmbPurpose.SelectedItem as string, "Other", StringComparison.OrdinalIgnoreCase);
            _txtPurposeOther.Enabled = isOther;
            _txtPurposeOther.Visible = isOther;
            _lblPurposeOther.Visible = isOther;
            _txtPurposeOther.BackColor = isOther ? Color.FromArgb(248, 250, 252) : Color.FromArgb(241, 245, 249);
            if (!isOther)
                _txtPurposeOther.Clear();

            if (_purposeSectionCard != null)
                UpdateEnrollmentLayout();
        }

        private void PopulatePurposeOptions()
        {
            _cmbPurpose.Items.Clear();
            _cmbPurpose.Items.AddRange(PurposeOfVisitCatalog.GetOptions());
        }

        private bool ValidateInputs()
        {
            if (_chkEnrollFingerprint.Checked && !_chkConsent.Checked)
            {
                MessageBox.Show(
                    "Please accept the RA 10173 consent before registering a fingerprint.",
                    "Consent required",
                    MessageBoxButtons.OK,
                    MessageBoxIcon.Warning);
                return false;
            }

            if (string.IsNullOrWhiteSpace(PurposeOfVisit))
            {
                MessageBox.Show("Please select the visitor purpose.", "Validation",
                    MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return false;
            }

            if (_linkedReturningIdentity != null)
                return true;

            if (string.IsNullOrWhiteSpace(VisitorFullName) ||
                !_addressPanel.IsComplete)
            {
                MessageBox.Show("Please complete all required visitor fields.", "Validation",
                    MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return false;
            }

            if (!_contactField.TryValidateOptional(out _, out string contactError))
            {
                MessageBox.Show(contactError, "Validation",
                    MessageBoxButtons.OK, MessageBoxIcon.Warning);
                _contactField.FocusInput();
                return false;
            }

            return true;
        }

        private sealed class ManualEntryCardPanel : Panel
        {
            public ManualEntryCardPanel()
            {
                SetStyle(
                    ControlStyles.UserPaint |
                    ControlStyles.AllPaintingInWmPaint |
                    ControlStyles.OptimizedDoubleBuffer,
                    true);
                UpdateRoundedRegion();
            }

            protected override void OnResize(EventArgs eventArgs)
            {
                base.OnResize(eventArgs);
                UpdateRoundedRegion();
                Invalidate();
            }

            protected override void OnPaint(PaintEventArgs eventArgs)
            {
                base.OnPaint(eventArgs);
                if (ClientSize.Width < 2 || ClientSize.Height < 2)
                    return;

                eventArgs.Graphics.SmoothingMode = SmoothingMode.AntiAlias;
                Rectangle bounds = new Rectangle(0, 0, ClientSize.Width - 1, ClientSize.Height - 1);
                using (GraphicsPath path = CreateRoundedPath(bounds, 10))
                using (var fill = new SolidBrush(BackColor))
                using (var border = new Pen(Color.FromArgb(216, 228, 244)))
                {
                    eventArgs.Graphics.FillPath(fill, path);
                    eventArgs.Graphics.DrawPath(border, path);
                }
                using (var shadow = new Pen(Color.FromArgb(18, 54, 83, 128)))
                    eventArgs.Graphics.DrawLine(shadow, 10, ClientSize.Height - 2, ClientSize.Width - 10, ClientSize.Height - 2);
            }

            private void UpdateRoundedRegion()
            {
                if (ClientSize.Width < 2 || ClientSize.Height < 2)
                    return;

                using (GraphicsPath path = CreateRoundedPath(
                    new Rectangle(0, 0, ClientSize.Width, ClientSize.Height), 10))
                {
                    Region previous = Region;
                    Region = new Region(path);
                    previous?.Dispose();
                }
            }

            private static GraphicsPath CreateRoundedPath(Rectangle bounds, int radius)
            {
                int diameter = Math.Min(radius * 2, Math.Min(bounds.Width, bounds.Height));
                var path = new GraphicsPath();
                path.AddArc(bounds.Left, bounds.Top, diameter, diameter, 180, 90);
                path.AddArc(bounds.Right - diameter, bounds.Top, diameter, diameter, 270, 90);
                path.AddArc(bounds.Right - diameter, bounds.Bottom - diameter, diameter, diameter, 0, 90);
                path.AddArc(bounds.Left, bounds.Bottom - diameter, diameter, diameter, 90, 90);
                path.CloseFigure();
                return path;
            }
        }

        private sealed class ManualEntrySectionPanel : Panel
        {
            public ManualEntrySectionPanel()
            {
                SetStyle(
                    ControlStyles.UserPaint |
                    ControlStyles.AllPaintingInWmPaint |
                    ControlStyles.OptimizedDoubleBuffer,
                    true);
                UpdateRoundedRegion();
            }

            protected override void OnResize(EventArgs eventArgs)
            {
                base.OnResize(eventArgs);
                UpdateRoundedRegion();
                Invalidate();
            }

            protected override void OnPaint(PaintEventArgs eventArgs)
            {
                base.OnPaint(eventArgs);
                if (ClientSize.Width < 2 || ClientSize.Height < 2)
                    return;

                eventArgs.Graphics.SmoothingMode = SmoothingMode.AntiAlias;
                Rectangle bounds = new Rectangle(0, 0, ClientSize.Width - 1, ClientSize.Height - 1);
                using (GraphicsPath path = CreateRoundedPath(bounds, 9))
                using (var fill = new SolidBrush(BackColor))
                using (var border = new Pen(Color.FromArgb(216, 228, 244)))
                {
                    eventArgs.Graphics.FillPath(fill, path);
                    eventArgs.Graphics.DrawPath(border, path);
                }
                using (var shadow = new Pen(Color.FromArgb(16, 54, 83, 128)))
                    eventArgs.Graphics.DrawLine(shadow, 9, ClientSize.Height - 2, ClientSize.Width - 9, ClientSize.Height - 2);
            }

            private void UpdateRoundedRegion()
            {
                if (ClientSize.Width < 2 || ClientSize.Height < 2)
                    return;

                using (GraphicsPath path = CreateRoundedPath(
                    new Rectangle(0, 0, ClientSize.Width, ClientSize.Height), 9))
                {
                    Region previous = Region;
                    Region = new Region(path);
                    previous?.Dispose();
                }
            }
        }
    }
}
