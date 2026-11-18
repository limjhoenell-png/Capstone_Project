using System;
using System.Drawing;
using System.Windows.Forms;

namespace FingerprintAttendance
{
    internal sealed class FilledAccentRadioButton : Control
    {
        private const int IndicatorSize = 14;
        private const int IndicatorTextGap = 8;
        private static readonly Color DefaultSelectedColor = Color.FromArgb(10, 17, 40);
        private static readonly Color DefaultUnselectedBorderColor = Color.FromArgb(100, 116, 139);
        private static readonly Color DefaultOptionTextColor = Color.FromArgb(30, 41, 59);
        private bool _checked;
        private Color _selectedColor = DefaultSelectedColor;
        private Color _unselectedBorderColor = DefaultUnselectedBorderColor;
        private Color _optionTextColor = DefaultOptionTextColor;

        public FilledAccentRadioButton()
        {
            AccessibleRole = AccessibleRole.RadioButton;
            TabStop = true;
            SetStyle(
                ControlStyles.UserPaint |
                ControlStyles.AllPaintingInWmPaint |
                ControlStyles.OptimizedDoubleBuffer |
                ControlStyles.ResizeRedraw |
                ControlStyles.SupportsTransparentBackColor,
                true);
        }

        public event EventHandler CheckedChanged;

        public bool Checked
        {
            get => _checked;
            set
            {
                if (_checked == value)
                    return;

                _checked = value;
                Invalidate();
                CheckedChanged?.Invoke(this, EventArgs.Empty);
            }
        }

        public override Size GetPreferredSize(Size proposedSize)
        {
            Size textSize = TextRenderer.MeasureText(
                Text ?? string.Empty,
                Font,
                new Size(int.MaxValue, int.MaxValue),
                TextFormatFlags.NoPadding | TextFormatFlags.SingleLine);

            return new Size(
                Padding.Horizontal + IndicatorSize + IndicatorTextGap + textSize.Width,
                Padding.Vertical + Math.Max(IndicatorSize, textSize.Height));
        }

        protected override AccessibleObject CreateAccessibilityInstance()
        {
            return new RadioOptionAccessibleObject(this);
        }

        protected override void OnPaint(PaintEventArgs e)
        {
            Graphics graphics = e.Graphics;
            graphics.SmoothingMode = System.Drawing.Drawing2D.SmoothingMode.AntiAlias;

            int indicatorX = Padding.Left;
            int indicatorY = Padding.Top + Math.Max(0, (ClientSize.Height - Padding.Vertical - IndicatorSize) / 2);
            var indicatorBounds = new Rectangle(indicatorX, indicatorY, IndicatorSize, IndicatorSize);

            using (var fill = new SolidBrush(Checked ? _selectedColor : Parent?.BackColor ?? SystemColors.Window))
            using (var border = new Pen(Checked ? _selectedColor : _unselectedBorderColor, 1.5F))
            {
                graphics.FillEllipse(fill, indicatorBounds);
                graphics.DrawEllipse(border, indicatorBounds);
            }

            int textLeft = indicatorBounds.Right + IndicatorTextGap;
            var textBounds = new Rectangle(
                textLeft,
                Padding.Top,
                Math.Max(0, ClientSize.Width - textLeft - Padding.Right),
                Math.Max(0, ClientSize.Height - Padding.Vertical));
            TextRenderer.DrawText(
                graphics,
                Text,
                Font,
                textBounds,
                _optionTextColor,
                TextFormatFlags.NoPadding |
                TextFormatFlags.SingleLine |
                TextFormatFlags.VerticalCenter |
                TextFormatFlags.EndEllipsis);

            if (Focused && ShowFocusCues)
            {
                Rectangle focusBounds = Rectangle.Inflate(textBounds, -1, -2);
                ControlPaint.DrawFocusRectangle(graphics, focusBounds);
            }
        }

        internal void ApplyOptionStyle(Color foreground, Color selectedColor, Color unselectedBorderColor)
        {
            _optionTextColor = foreground;
            _selectedColor = selectedColor;
            _unselectedBorderColor = unselectedBorderColor;
            Invalidate();
        }

        protected override void OnClick(EventArgs e)
        {
            Checked = true;
            base.OnClick(e);
        }

        protected override void OnKeyDown(KeyEventArgs e)
        {
            if (e.KeyCode == Keys.Space || e.KeyCode == Keys.Enter)
            {
                Checked = true;
                e.Handled = true;
                e.SuppressKeyPress = true;
            }
            else if (e.KeyCode == Keys.Left || e.KeyCode == Keys.Right)
            {
                SelectAdjacentOption(e.KeyCode == Keys.Right ? 1 : -1);
                e.Handled = true;
                e.SuppressKeyPress = true;
            }

            base.OnKeyDown(e);
        }

        private void SelectAdjacentOption(int direction)
        {
            if (Parent == null)
                return;

            var options = new System.Collections.Generic.List<FilledAccentRadioButton>();
            foreach (Control control in Parent.Controls)
            {
                if (control is FilledAccentRadioButton option)
                    options.Add(option);
            }

            int currentIndex = options.IndexOf(this);
            if (currentIndex < 0 || options.Count < 2)
                return;

            int nextIndex = (currentIndex + direction + options.Count) % options.Count;
            options[nextIndex].Focus();
            options[nextIndex].Checked = true;
        }

        private sealed class RadioOptionAccessibleObject : ControlAccessibleObject
        {
            private readonly FilledAccentRadioButton _owner;

            public RadioOptionAccessibleObject(FilledAccentRadioButton owner)
                : base(owner)
            {
                _owner = owner;
            }

            public override AccessibleStates State
            {
                get
                {
                    AccessibleStates state = base.State | AccessibleStates.Focusable | AccessibleStates.Selectable;
                    if (_owner.Checked)
                        state |= AccessibleStates.Checked;
                    return state;
                }
            }
        }
    }

    internal enum PersonClassification
    {
        Visitor,
        Resident,
        Personnel
    }

    internal sealed class PersonClassificationSelector : Panel
    {
        private readonly FilledAccentRadioButton _visitor = CreateOption("Visitor");
        private readonly FilledAccentRadioButton _resident = CreateOption("Resident");
        private readonly FilledAccentRadioButton _personnel = CreateOption("Personnel");
        private readonly PersonClassificationHeaderLabel _title;
        private static readonly Color DefaultOptionColor = Color.FromArgb(30, 41, 59);

        public PersonClassificationSelector(bool residentFirst = false, bool selectDefault = true)
        {
            Height = 48;
            BackColor = Color.Transparent;

            _title = new PersonClassificationHeaderLabel
            {
                Text = "PERSON CLASSIFICATION",
                Dock = DockStyle.Top,
                Height = 22,
                Font = new Font("Segoe UI", 8.5F, FontStyle.Bold),
                ForeColor = Color.White,
                BackColor = Color.FromArgb(10, 17, 40),
                Padding = new Padding(8, 0, 0, 0),
                TextAlign = ContentAlignment.MiddleLeft
            };
            _title.ForeColorChanged += EnsureTitleContrast;

            var options = new FlowLayoutPanel
            {
                Dock = DockStyle.Fill,
                FlowDirection = FlowDirection.LeftToRight,
                WrapContents = false,
                BackColor = Color.Transparent,
                Margin = Padding.Empty,
                Padding = Padding.Empty
            };
            options.Controls.Add(residentFirst ? (Control)_resident : _visitor);
            options.Controls.Add(residentFirst ? (Control)_visitor : _resident);
            options.Controls.Add(_personnel);
            Controls.Add(options);
            Controls.Add(_title);
            _visitor.CheckedChanged += OnOptionCheckedChanged;
            _resident.CheckedChanged += OnOptionCheckedChanged;
            _personnel.CheckedChanged += OnOptionCheckedChanged;
            if (selectDefault)
                _visitor.Checked = true;
        }

        private sealed class PersonClassificationHeaderLabel : Label
        {
            protected override void OnPaint(PaintEventArgs e)
            {
                e.Graphics.Clear(BackColor);
                var textBounds = new Rectangle(
                    Padding.Left,
                    Padding.Top,
                    Math.Max(0, ClientSize.Width - Padding.Horizontal),
                    Math.Max(0, ClientSize.Height - Padding.Vertical));
                TextRenderer.DrawText(
                    e.Graphics,
                    Text,
                    Font,
                    textBounds,
                    Color.FromArgb(255, 255, 255),
                    TextFormatFlags.Left |
                    TextFormatFlags.VerticalCenter |
                    TextFormatFlags.SingleLine |
                    TextFormatFlags.EndEllipsis);
            }
        }

        internal void ApplySectionHeaderStyle(Color background, Color foreground)
        {
            _title.BackColor = background;
            _title.ForeColor = foreground;
            EnsureTitleContrast(this, EventArgs.Empty);
            if (!_title.Font.Bold)
                _title.Font = new Font(_title.Font, _title.Font.Style | FontStyle.Bold);
        }

        internal void ApplyOptionStyle(Color foreground, Color selectedColor, Color unselectedBorderColor)
        {
            foreach (Control control in Controls)
            {
                if (!(control is FlowLayoutPanel options))
                    continue;

                options.BackColor = Color.Transparent;
                foreach (Control option in options.Controls)
                {
                    if (!(option is FilledAccentRadioButton radio))
                        continue;

                    radio.ApplyOptionStyle(foreground, selectedColor, unselectedBorderColor);
                }
            }
        }

        private void EnsureTitleContrast(object sender, EventArgs e)
        {
            if (_title.ForeColor != Color.White)
                _title.ForeColor = Color.White;
        }

        public bool HasSelection => _visitor.Checked || _resident.Checked || _personnel.Checked;

        public PersonClassification SelectedClassification
        {
            get
            {
                if (_resident.Checked) return PersonClassification.Resident;
                if (_personnel.Checked) return PersonClassification.Personnel;
                return PersonClassification.Visitor;
            }
            set
            {
                _visitor.Checked = value == PersonClassification.Visitor;
                _resident.Checked = value == PersonClassification.Resident;
                _personnel.Checked = value == PersonClassification.Personnel;
            }
        }

        public string SelectedValue => HasSelection ? SelectedClassification.ToString() : null;

        private void OnOptionCheckedChanged(object sender, EventArgs e)
        {
            if (!(sender is FilledAccentRadioButton selected) || !selected.Checked)
                return;

            if (selected != _visitor) _visitor.Checked = false;
            if (selected != _resident) _resident.Checked = false;
            if (selected != _personnel) _personnel.Checked = false;
        }

        private static FilledAccentRadioButton CreateOption(string text)
        {
            return new FilledAccentRadioButton
            {
                Text = text,
                AutoSize = true,
                Font = new Font("Segoe UI", 9.5F, FontStyle.Bold),
                ForeColor = DefaultOptionColor,
                BackColor = Color.Transparent,
                Margin = new Padding(0, 3, 18, 0),
                Cursor = Cursors.Hand
            };
        }
    }
}
