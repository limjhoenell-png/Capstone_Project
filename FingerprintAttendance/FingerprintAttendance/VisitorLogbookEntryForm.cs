using System;
using System.Drawing;
using System.Windows.Forms;
using FingerprintAttendance.Core;

namespace FingerprintAttendance
{
    public class VisitorLogbookEntryForm : Form
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
        private readonly Label _lblPurposeOther = new Label();
        private readonly PersonClassificationSelector _classificationSelector =
            new PersonClassificationSelector(residentFirst: true, selectDefault: false);
        private readonly CheckBox _chkConsent = new CheckBox();
        private readonly bool _isReturningVisitor;
        private VisitorProfile _linkedReturningIdentity;
        private Panel _panelScroll;
        private Panel _panelBody;
        private Panel _consentCard;
        private Button _btnSave;
        private Button _btnCancel;
        private int _purposeSectionBottomY;
        private Label _lblTitle;
        private Label _lblSubtitle;
        private Panel _panelHeader;
        private readonly ScrollBodyLayout _scrollLayout = new ScrollBodyLayout();

        public string VisitorFullName =>
            DatabaseHelper.BuildEmployeeFullName(
                _txtFirstName.Text,
                _txtMiddleName.Text,
                _txtLastName.Text,
                _txtNameExtension.Text);
        public string VisitorAddress => _addressPanel.ComposedAddress;
        public string VisitorContactNumber => _contactField.StoredNumber;
        public string SelectedPersonClassification => _classificationSelector.SelectedValue;
        public string PurposeOfVisit
        {
            get
            {
                string selected = _cmbPurpose.SelectedItem as string;
                if (string.Equals(selected, "Other", StringComparison.OrdinalIgnoreCase))
                    return PurposeOfVisitCatalog.FormatOtherPurpose(_txtPurposeOther.Text);

                return selected?.Trim() ?? string.Empty;
            }
        }
        /// <summary>Visitor chosen via search (reuse identity). Mutually exclusive with passing <paramref name="profile"/> from fingerprint flow.</summary>
        public VisitorProfile LinkedReturningIdentity => _linkedReturningIdentity;

        public bool RegisterFingerprint
        {
            get
            {
                if (_linkedReturningIdentity != null)
                    return !_linkedReturningIdentity.HasFingerprintOnFile && _chkConsent.Checked;
                if (_isReturningVisitor)
                    return false;
                return _chkConsent.Checked;
            }
        }

        public VisitorLogbookEntryForm(DatabaseHelper db, VisitorProfile profile = null)
        {
            _db = db;
            _isReturningVisitor = profile != null;

            Text = _isReturningVisitor ? "Returning Visitor Logbook Entry" : "New Transaction Entry";
            StartPosition = FormStartPosition.CenterParent;
            FormBorderStyle = FormBorderStyle.FixedDialog;
            MaximizeBox = false;
            MinimizeBox = false;
            BackColor = Color.FromArgb(236, 241, 248);
            Font = new Font("Segoe UI", 10F);
            ClientSize = new Size(900, 840);
            AutoScroll = false;

            _panelHeader = new Panel
            {
                Dock = DockStyle.Top,
                Height = _isReturningVisitor ? 100 : 136,
                BackColor = Color.FromArgb(13, 27, 42)
            };
            _lblTitle = new Label
            {
                Text = _isReturningVisitor ? "Returning Visitor Logbook" : "Biometric Logbook Registration",
                Left = 0,
                Top = 10,
                Width = ClientSize.Width,
                Height = 34,
                Font = new Font("Segoe UI", 15F, FontStyle.Bold),
                ForeColor = Color.FromArgb(240, 246, 255),
                BackColor = Color.Transparent,
                TextAlign = ContentAlignment.MiddleCenter
            };
            _panelHeader.Resize += (_, __) => _lblTitle.Width = _panelHeader.ClientSize.Width;
            _lblSubtitle = new Label
            {
                Text = _isReturningVisitor
                    ? "Visitor identified by fingerprint. Update purpose and destination."
                    : string.Empty,
                Left = 24,
                Top = 50,
                Width = 670,
                Height = _isReturningVisitor ? 40 : 0,
                Font = new Font("Segoe UI", 9F),
                ForeColor = Color.FromArgb(100, 140, 185),
                BackColor = Color.Transparent
            };
            _lblSubtitle.Visible = _isReturningVisitor;
            _panelHeader.Controls.AddRange(new Control[] { _lblTitle, _lblSubtitle });

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
                Height = 560,
                BackColor = Color.White
            };

            int y = 16;
            var personalInfoHeading = new Label
            {
                Text = "PERSONAL INFORMATION",
                Left = 18,
                Top = y,
                Width = 644,
                Height = 32,
                Font = new Font("Segoe UI", 8.5F, FontStyle.Bold),
                ForeColor = Color.White,
                BackColor = Color.FromArgb(10, 17, 40),
                TextAlign = ContentAlignment.MiddleLeft,
                Padding = new Padding(14, 0, 10, 0)
            };
            _panelBody.Controls.Add(personalInfoHeading);
            y += 46;
            AddNameFields(_panelBody, ref y);
            _classificationSelector.Left = 18;
            _classificationSelector.Top = y;
            _classificationSelector.Width = 644;
            _panelBody.Controls.Add(_classificationSelector);
            y += _classificationSelector.Height + 8;
            _addressPanel.Left = 0;
            _addressPanel.Top = y;
            _addressPanel.Width = 680;
            _panelBody.Controls.Add(_addressPanel);
            y += _addressPanel.Height + 8;
            AddContactField(_panelBody, ref y);
            AddPurposeField(_panelBody, ref y);

            const string ra10173FullTitle =
                "AN ACT PROTECTING INDIVIDUAL PERSONAL INFORMATION IN INFORMATION AND COMMUNICATIONS SYSTEMS IN THE GOVERNMENT AND THE PRIVATE SECTOR, CREATING FOR THIS PURPOSE A NATIONAL PRIVACY COMMISSION, AND FOR OTHER PURPOSES";

            string consentBodyText;
            string consentStatement;
            string consentAccessibleName;
            if (_isReturningVisitor)
            {
                consentBodyText =
                    "Fingerprint registration is optional and is used only to identify you more quickly on future visits. Personal data is handled in line with RA 10173 and applicable issuances of the National Privacy Commission.";
                consentStatement =
                    "I have read the foregoing, understand how my fingerprint data will be used, and voluntarily consent to optional fingerprint registration for this logbook, in accordance with RA 10173.";
                consentAccessibleName = "Consent to optional fingerprint registration";
            }
            else
            {
                consentBodyText =
                    "The barangay records your fingerprint so the next time you visit, front desk staff can verify you in seconds for fast service without retyping your details each time. Registration on this screen is required before your logbook entry can be saved. Personal data is handled in line with RA 10173 and applicable issuances of the National Privacy Commission.";
                consentStatement =
                    "I have read the foregoing, understand how my fingerprint data will be used, and voluntarily consent to required fingerprint registration for this logbook entry, in accordance with RA 10173.";
                consentAccessibleName = "Consent to required fingerprint registration";
            }

            _consentCard = new Panel
            {
                Left = 18,
                Top = y,
                Width = 644,
                Height = 412,
                BackColor = Color.FromArgb(248, 250, 252),
                BorderStyle = BorderStyle.FixedSingle,
                Padding = new Padding(16, 14, 16, 16)
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
                BackColor = Color.FromArgb(241, 245, 249),
                BorderStyle = BorderStyle.FixedSingle,
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
                Font = new Font("Segoe UI", 8F, FontStyle.Regular),
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

            var consentCheckFont = new Font("Segoe UI", 8.85F);
            int consentTextBlockHeight = TextRenderer.MeasureText(
                consentStatement,
                consentCheckFont,
                new Size(584, int.MaxValue),
                TextFormatFlags.WordBreak | TextFormatFlags.TextBoxControl).Height;
            int consentRowHeight = Math.Max(48, consentTextBlockHeight + 14);

            var pnlConsentCheck = new Panel
            {
                Height = consentRowHeight,
                Margin = new Padding(0, 4, 0, 0),
                BackColor = Color.Transparent
            };
            _chkConsent.Text = string.Empty;
            _chkConsent.Size = new Size(18, 18);
            _chkConsent.Location = new Point(0, 2);
            _chkConsent.TabIndex = 0;
            _chkConsent.AccessibleName = consentAccessibleName;
            _chkConsent.AccessibleDescription = consentStatement;

            var lblConsentCheck = new Label
            {
                Text = consentStatement,
                Left = 28,
                Top = 0,
                Width = 584,
                AutoSize = true,
                MaximumSize = new Size(584, 0),
                Font = consentCheckFont,
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

            _panelBody.Controls.Add(_consentCard);
            bool updatingConsentLayout = false;
            Action updateConsentLayout = () =>
            {
                if (updatingConsentLayout || _consentCard.IsDisposed)
                    return;

                updatingConsentLayout = true;
                try
                {
                    int contentWidth = Math.Max(160,
                        _consentCard.ClientSize.Width - _consentCard.Padding.Horizontal);
                    int statuteTextWidth = Math.Max(120, contentWidth - pnlStatute.Padding.Horizontal - 4);
                    int consentTextWidth = Math.Max(120, contentWidth - lblConsentCheck.Left);

                    lblConsentTitle.MaximumSize = new Size(contentWidth, 0);
                    lblStatuteIntro.MaximumSize = new Size(contentWidth, 0);
                    lblBodyHeading.MaximumSize = new Size(contentWidth, 0);
                    txtRa10173Title.Height = MeasureWrappedTextHeight(
                        ra10173FullTitle, txtRa10173Title.Font, statuteTextWidth) + 10;
                    pnlStatute.Height = Math.Max(72,
                        txtRa10173Title.Height + pnlStatute.Padding.Vertical + 2);
                    txtConsentBody.Height = Math.Max(48,
                        MeasureWrappedTextHeight(consentBodyText, txtConsentBody.Font, contentWidth - 4) + 12);

                    lblConsentCheck.Width = consentTextWidth;
                    lblConsentCheck.MaximumSize = new Size(consentTextWidth, 0);
                    int checkTextHeight = MeasureWrappedTextHeight(
                        consentStatement, lblConsentCheck.Font, consentTextWidth);
                    pnlConsentCheck.Height = Math.Max(36, checkTextHeight + 10);

                    tlpConsent.Width = contentWidth;
                    tlpConsent.PerformLayout();
                    int measuredHeight = tlpConsent.GetPreferredSize(new Size(contentWidth, 0)).Height;
                    tlpConsent.Height = Math.Max(1, measuredHeight);
                    _consentCard.Height = tlpConsent.Height + _consentCard.Padding.Vertical;
                    _panelBody.Height = _consentCard.Bottom + 20;
                    _panelScroll.AutoScrollMinSize = new Size(0, _panelBody.Bottom + 24);
                }
                finally
                {
                    updatingConsentLayout = false;
                }
            };
            _consentCard.Resize += (_, __) => updateConsentLayout();
            updateConsentLayout();
            y += _consentCard.Height + 20;
            _panelBody.Height = y;

            _btnSave = new Button
            {
                Text = "Proceed to Biometric Enrollment",
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

            _panelScroll.Controls.Add(_panelBody);
            Controls.Add(_panelScroll);
            Controls.Add(_panelHeader);
            ResponsiveLayout.PinActionButtons(this, _btnSave, _btnCancel);
            Controls.SetChildIndex(_panelScroll, 0);
            Controls.SetChildIndex(_panelHeader, Controls.Count - 1);
            _scrollLayout.Attach(this, _panelScroll, _panelBody, _addressPanel, _panelHeader, _lblSubtitle);
            _panelScroll.Resize += (_, __) => updateConsentLayout();
            updateConsentLayout();
            CancelButton = _btnCancel;

            PopulatePurposeOptions();
            _cmbPurpose.SelectedIndexChanged += (_, __) => OnPurposeChanged();
            OnPurposeChanged();

            if (_isReturningVisitor)
                ApplyLinkedVisitorIdentity(profile, fromFingerprintFlow: true);
            DesktopTheme.Apply(this);
            ApplyEntryFormVisualStyle();
        }

        private void ApplyEntryFormVisualStyle()
        {
            Color sectionHeaderColor = Color.FromArgb(10, 17, 40);
            Color consentBackground = Color.FromArgb(255, 247, 237);
            BackColor = Color.FromArgb(239, 244, 251);
            _panelScroll.BackColor = Color.FromArgb(239, 244, 251);
            _panelHeader.BackColor = sectionHeaderColor;
            _panelBody.BackColor = Color.White;
            _consentCard.BackColor = consentBackground;
            _consentCard.BorderStyle = BorderStyle.None;
            _lblTitle.ForeColor = Color.White;
            _lblSubtitle.ForeColor = Color.White;

            ApplyEntryControlStyle(_panelBody);
            _panelBody.BackColor = Color.White;
            _classificationSelector.ApplySectionHeaderStyle(sectionHeaderColor, Color.White);
            _addressPanel.ApplySectionHeaderStyle(sectionHeaderColor, Color.White);
            ApplyConsentSectionBackground(_consentCard, consentBackground);
            _lblTitle.ForeColor = Color.White;
            _lblSubtitle.ForeColor = Color.White;

            _btnSave.BackColor = Color.FromArgb(37, 99, 235);
            _btnSave.ForeColor = Color.White;
            _btnSave.FlatStyle = FlatStyle.Flat;
            _btnSave.FlatAppearance.BorderSize = 0;
            _btnSave.FlatAppearance.MouseOverBackColor = Color.FromArgb(29, 78, 216);
            _btnSave.FlatAppearance.MouseDownBackColor = Color.FromArgb(30, 64, 175);
            _btnCancel.BackColor = Color.White;
            _btnCancel.ForeColor = Color.FromArgb(49, 68, 96);
            _btnCancel.FlatStyle = FlatStyle.Flat;
            _btnCancel.FlatAppearance.BorderSize = 1;
            _btnCancel.FlatAppearance.BorderColor = Color.FromArgb(199, 214, 235);
            _btnCancel.FlatAppearance.MouseOverBackColor = Color.FromArgb(241, 247, 255);

            foreach (Control control in Controls)
            {
                if (control is Panel footer && footer.Dock == DockStyle.Bottom && footer != _panelHeader)
                {
                    footer.BackColor = Color.FromArgb(239, 244, 251);
                    footer.Paint += DrawEntryCardBorder;
                }
            }

            _panelHeader.Paint += DrawEntryHeaderAccent;
            _panelBody.Paint += DrawEntryCardBorder;
            _consentCard.Paint += DrawEntryCardBorder;
        }

        private static void ApplyConsentSectionBackground(Control parent, Color background)
        {
            if (parent is Panel || parent is TextBoxBase)
                parent.BackColor = background;

            foreach (Control child in parent.Controls)
                ApplyConsentSectionBackground(child, background);
        }

        private static void ApplyEntryControlStyle(Control parent)
        {
            foreach (Control control in parent.Controls)
            {
                if (control is Label label)
                {
                    if (label.Text.IndexOf("PERSON CLASSIFICATION", StringComparison.OrdinalIgnoreCase) >= 0 ||
                        label.Text.IndexOf("ADDRESS INFORMATION", StringComparison.OrdinalIgnoreCase) >= 0)
                    {
                        label.ForeColor = Color.FromArgb(8, 125, 160);
                        label.Font = new Font("Segoe UI", 8.5F, FontStyle.Bold);
                    }
                    else if (label.ForeColor == Color.FromArgb(100, 116, 139))
                    {
                        label.ForeColor = Color.FromArgb(82, 102, 132);
                    }
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
                else if (control is Panel panel && panel.BorderStyle == BorderStyle.FixedSingle)
                {
                    panel.BorderStyle = BorderStyle.None;
                    panel.BackColor = Color.FromArgb(246, 249, 254);
                    panel.Paint += DrawEntryCardBorder;
                }

                if (control.HasChildren)
                    ApplyEntryControlStyle(control);
            }
        }

        private static void DrawEntryCardBorder(object sender, PaintEventArgs e)
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

        private static void DrawEntryHeaderAccent(object sender, PaintEventArgs e)
        {
            using (var accentBrush = new SolidBrush(Color.FromArgb(34, 181, 220)))
                e.Graphics.FillRectangle(accentBrush, 0, 0, 4, ((Panel)sender).Height);
        }

        private static void ApplyJustifiedAlignment(RichTextBox textBox)
        {
            textBox.Rtf = textBox.Rtf.Replace("\\pard", "\\pard\\qj");
        }

        private static int MeasureWrappedTextHeight(string text, Font font, int width)
        {
            Size measured = TextRenderer.MeasureText(
                text,
                font,
                new Size(Math.Max(1, width), int.MaxValue),
                TextFormatFlags.WordBreak | TextFormatFlags.TextBoxControl | TextFormatFlags.NoPadding);
            return Math.Max(font.Height, measured.Height);
        }

        private void ApplyLinkedVisitorIdentity(VisitorProfile profile, bool fromFingerprintFlow = false)
        {
            if (profile == null) return;

            _linkedReturningIdentity = fromFingerprintFlow ? null : profile;
            _txtFirstName.Text = profile.FullName;
            _addressPanel.SetLockedAddress(profile.Address);
            _contactField.SetLockedNumber(profile.ContactNumber);
            foreach (TextBox textBox in new[] { _txtFirstName, _txtMiddleName, _txtLastName, _txtNameExtension })
            {
                textBox.ReadOnly = true;
                textBox.BackColor = Color.FromArgb(241, 245, 249);
            }

            if (fromFingerprintFlow || profile.HasFingerprintOnFile)
            {
                _chkConsent.Checked = true;
                _chkConsent.Enabled = false;
            }
            else
            {
                _chkConsent.Checked = false;
                _chkConsent.Enabled = true;
            }

            if (!fromFingerprintFlow)
            {
                Text = "Returning Visitor (From Records)";
                _lblTitle.Text = "Returning Visitor — Saved Profile Loaded";
            }
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
            var lbl = new Label
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
            y += 50;
            parent.Controls.Add(lbl);
            parent.Controls.Add(_contactField);
        }

        private void AddPurposeField(Control parent, ref int y)
        {
            var lblPurpose = new Label
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

            _purposeSectionBottomY = y;
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

            parent.Controls.Add(lblPurpose);
            parent.Controls.Add(_cmbPurpose);
            parent.Controls.Add(_lblPurposeOther);
            parent.Controls.Add(_txtPurposeOther);
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

            int extraHeight = isOther ? 110 : 0;
            _consentCard.Top = _purposeSectionBottomY + extraHeight;
            _panelBody.Height = _consentCard.Bottom + 20;
            _scrollLayout.Apply();
            _panelScroll.AutoScrollMinSize = new Size(0, _panelBody.Bottom + 24);
        }

        private void PopulatePurposeOptions()
        {
            _cmbPurpose.Items.Clear();
            _cmbPurpose.Items.AddRange(PurposeOfVisitCatalog.GetOptions());
        }

        private bool ValidateInputs()
        {
            if (!_classificationSelector.HasSelection)
            {
                MessageBox.Show("Please select a person classification: Resident, Visitor, or Personnel.", "Classification required",
                    MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return false;
            }

            if (string.IsNullOrWhiteSpace(PurposeOfVisit))
            {
                MessageBox.Show("Please select the visitor purpose.", "Validation",
                    MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return false;
            }

            if (_isReturningVisitor)
                return true;

            if (_linkedReturningIdentity != null)
            {
                if (!_linkedReturningIdentity.HasFingerprintOnFile && !_chkConsent.Checked)
                {
                    MessageBox.Show(
                        "Please accept the RA 10173 consent to register this visitor's fingerprint.",
                        "Consent required",
                        MessageBoxButtons.OK,
                        MessageBoxIcon.Warning);
                    return false;
                }

                return true;
            }

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

            if (!_chkConsent.Checked)
            {
                MessageBox.Show(
                    "You must accept the RA 10173 consent to save. Fingerprint registration is required on this screen so the barangay can offer faster visitor processing on your next visit.",
                    "Consent required",
                    MessageBoxButtons.OK,
                    MessageBoxIcon.Warning);
                return false;
            }

            return true;
        }
    }

    internal static class PersonRegistrationGuard
    {
        public static bool CanCreateNewPerson(DatabaseHelper db, string fullName, string completeAddress)
        {
            if (db == null || !db.IsPersonAlreadyRegistered(fullName, completeAddress))
                return true;

            MessageBox.Show(
                DatabaseHelper.PersonAlreadyRegisteredMessage,
                "Already Registered",
                MessageBoxButtons.OK,
                MessageBoxIcon.Warning);
            return false;
        }
    }
}
