using System;
using System.Collections.Generic;
using System.Drawing;
using System.Windows.Forms;

namespace FingerprintAttendance.Core
{
    /// <summary>
    /// Keeps WinForms windows inside the current monitor and stretches fixed-width forms.
    /// </summary>
    internal static class ResponsiveLayout
    {
        public static Rectangle WorkingAreaFor(Form form)
        {
            if (form != null && form.Owner != null && !form.Owner.IsDisposed)
                return Screen.FromControl(form.Owner).WorkingArea;
            if (form != null && form.IsHandleCreated)
                return Screen.FromControl(form).WorkingArea;
            return Screen.PrimaryScreen.WorkingArea;
        }

        /// <summary>
        /// Shrinks a form so it fits the monitor working area, then centers it.
        /// Safe to call from a constructor; sizing runs on Load.
        /// </summary>
        public static void FitClientToWorkingArea(
            Form form,
            int preferredClientWidth,
            int preferredClientHeight,
            int minimumClientWidth,
            int minimumClientHeight)
        {
            if (form == null) return;

            void Apply(object sender, EventArgs e)
            {
                var target = (Form)sender;
                target.Load -= Apply;
                if (target.IsDisposed || target.WindowState == FormWindowState.Maximized)
                    return;

                Rectangle work = WorkingAreaFor(target);
                int maxClientW = Math.Max(320, work.Width - 24);
                int maxClientH = Math.Max(240, work.Height - 40);
                int clientW = Math.Min(Math.Max(preferredClientWidth, 320), maxClientW);
                int clientH = Math.Min(Math.Max(preferredClientHeight, 240), maxClientH);
                int minW = Math.Min(minimumClientWidth, clientW);
                int minH = Math.Min(minimumClientHeight, clientH);

                target.ClientSize = new Size(clientW, clientH);
                int chromeW = Math.Max(0, target.Width - target.ClientSize.Width);
                int chromeH = Math.Max(0, target.Height - target.ClientSize.Height);
                target.MinimumSize = new Size(minW + chromeW, minH + chromeH);

                target.StartPosition = FormStartPosition.Manual;
                int left = work.Left + Math.Max(0, (work.Width - target.Width) / 2);
                int top = work.Top + Math.Max(0, (work.Height - target.Height) / 2);
                target.Location = new Point(left, top);
            }

            form.Load += Apply;
        }

        /// <summary>If the window is larger than the current monitor, shrink and center it.</summary>
        public static void ClampToWorkingArea(Form form)
        {
            if (form == null || form.WindowState == FormWindowState.Maximized)
                return;

            Rectangle work = WorkingAreaFor(form);
            int width = Math.Min(form.Width, Math.Max(form.MinimumSize.Width, work.Width - 16));
            int height = Math.Min(form.Height, Math.Max(form.MinimumSize.Height, work.Height - 16));
            if (width != form.Width || height != form.Height)
                form.Size = new Size(width, height);

            int left = work.Left + Math.Max(0, (work.Width - form.Width) / 2);
            int top = work.Top + Math.Max(0, (work.Height - form.Height) / 2);
            if (form.Left < work.Left || form.Right > work.Right || form.Top < work.Top || form.Bottom > work.Bottom)
                form.Location = new Point(left, top);
        }

        /// <summary>
        /// Moves Save/Cancel onto a bar pinned to the bottom of the form so they stay visible while the body scrolls.
        /// Call after the form's other docked controls have been added.
        /// </summary>
        public static void PinActionButtons(Form form, Button primary, Button secondary)
        {
            if (form == null || primary == null || secondary == null)
                return;

            primary.Parent?.Controls.Remove(primary);
            secondary.Parent?.Controls.Remove(secondary);

            var footer = new Panel
            {
                Dock = DockStyle.Bottom,
                Height = 74,
                Padding = new Padding(18, 12, 18, 12),
                BackColor = form.BackColor
            };
            var table = new TableLayoutPanel
            {
                Dock = DockStyle.Fill,
                ColumnCount = 2,
                RowCount = 1,
                Margin = Padding.Empty,
                Padding = Padding.Empty
            };
            table.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 50F));
            table.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 50F));
            table.RowStyles.Add(new RowStyle(SizeType.Percent, 100F));

            primary.Dock = DockStyle.Fill;
            secondary.Dock = DockStyle.Fill;
            primary.Margin = new Padding(0, 0, 8, 0);
            secondary.Margin = new Padding(8, 0, 0, 0);
            table.Controls.Add(primary, 0, 0);
            table.Controls.Add(secondary, 1, 0);
            footer.Controls.Add(table);
            form.Controls.Add(footer);
        }
    }

    /// <summary>
    /// Stretches a scrollable card (and the wide fields inside it) to the current form width.
    /// </summary>
    internal sealed class ScrollBodyLayout
    {
        private readonly HashSet<Control> _wideControls = new HashSet<Control>();
        private bool _captured;
        private Form _form;
        private Panel _scroll;
        private Panel _body;
        private Panel _header;
        private Control _address;
        private Label _subtitle;

        public void Attach(
            Form form,
            Panel scroll,
            Panel body,
            Control address,
            Panel header,
            Label subtitle)
        {
            _form = form;
            _scroll = scroll;
            _body = body;
            _address = address;
            _header = header;
            _subtitle = subtitle;

            if (form != null)
            {
                form.AutoScaleMode = AutoScaleMode.Font;
                form.FormBorderStyle = FormBorderStyle.Sizable;
                form.MaximizeBox = true;
                form.SizeGripStyle = SizeGripStyle.Show;
                int preferredW = Math.Max(form.ClientSize.Width, 720);
                int preferredH = Math.Max(form.ClientSize.Height, 640);
                ResponsiveLayout.FitClientToWorkingArea(form, preferredW, preferredH, 520, 420);
            }

            if (scroll != null)
                scroll.Resize += (_, __) => Apply();
            if (form != null)
                form.Shown += (_, __) => Apply();
        }

        public void Apply()
        {
            if (_scroll == null || _body == null || _scroll.IsDisposed)
                return;

            CaptureWideControls();

            if (_header != null && _subtitle != null)
            {
                int headerInner = Math.Max(160, _header.ClientSize.Width - _subtitle.Left - 16);
                _subtitle.AutoSize = true;
                _subtitle.MaximumSize = new Size(headerInner, 0);
                foreach (Control child in _header.Controls)
                {
                    if (child == _subtitle || child.Dock != DockStyle.None)
                        continue;
                    if (child.Left >= 12 && child.Width > 200)
                        child.Width = headerInner;
                }
                _header.Height = Math.Max(72, _subtitle.Bottom + 14);
            }

            int viewW = Math.Max(1, _scroll.ClientSize.Width);
            int bodyW = Math.Max(280, viewW - 36);
            _body.Left = 18;
            _body.Width = bodyW;

            if (_address != null)
                _address.Width = bodyW;

            foreach (Control control in _wideControls)
            {
                if (control == null || control.IsDisposed || control == _address)
                    continue;
                if (control.Parent != _body)
                    continue;
                control.Width = control.Left <= 2
                    ? bodyW
                    : Math.Max(120, bodyW - control.Left - 18);
                RelaxWrappedText(control, Math.Max(80, control.Width - 28));
            }

            _scroll.AutoScrollMinSize = new Size(0, _body.Bottom + 24);
        }

        private void CaptureWideControls()
        {
            if (_captured || _body == null)
                return;

            foreach (Control control in _body.Controls)
            {
                if (control is Button)
                    continue;
                if (control == _address || control.Width >= 480)
                    _wideControls.Add(control);
            }
            _captured = true;
        }

        private static void RelaxWrappedText(Control root, int maxWidth)
        {
            foreach (Control child in root.Controls)
            {
                if (child.AutoSize && child.MaximumSize.Width > 0)
                    child.MaximumSize = new Size(Math.Max(80, maxWidth - child.Left), 0);
                if (child.HasChildren)
                    RelaxWrappedText(child, Math.Max(80, child.ClientSize.Width - 8));
            }
        }
    }
}
