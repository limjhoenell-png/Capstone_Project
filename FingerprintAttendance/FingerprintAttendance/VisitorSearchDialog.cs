using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Drawing;
using System.Windows.Forms;
using FingerprintAttendance.Core;

namespace FingerprintAttendance
{
    /// <summary>
    /// Search saved person identities so staff can reuse an existing profile for a return visit.
    /// <see cref="Form.ShowDialog"/> returns <see cref="DialogResult.Retry"/> when the user chooses Register Logbook (open manual entry without a pre-selected row).
    /// </summary>
    public class VisitorSearchDialog : Form
    {
        private readonly DatabaseHelper _db;
        private readonly TextBox _txtSearch = new TextBox();
        private readonly Button _btnSearch = new Button();
        private readonly DataGridView _grid = new DataGridView();
        private readonly Panel _paginationPanel = new Panel();
        private readonly Timer _searchDebounce = new Timer();
        private readonly BindingSource _gridBinding = new BindingSource();
        private const int PageSize = 25;
        private const int VisiblePageNumberCount = 5;
        private int _currentPage;
        private int _totalRows;

        public VisitorProfile SelectedProfile { get; private set; }

        public VisitorSearchDialog(DatabaseHelper db)
        {
            _db = db;
            Text = "Find Saved Person";
            StartPosition = FormStartPosition.CenterParent;
            FormBorderStyle = FormBorderStyle.Sizable;
            MaximizeBox = true;
            MinimizeBox = true;
            MinimumSize = new Size(560, 420);
            ClientSize = new Size(900, 840);
            AutoScaleMode = AutoScaleMode.Font;
            BackColor = Color.FromArgb(239, 244, 251);
            Font = new Font("Segoe UI", 10F);

            var header = new Panel
            {
                Dock = DockStyle.Top,
                Height = 122,
                BackColor = Color.FromArgb(10, 17, 40),
                Padding = Padding.Empty,
                MinimumSize = new Size(0, 122)
            };
            var headerTitle = new Label
            {
                Text = "Find Saved Person",
                Dock = DockStyle.Fill,
                TextAlign = ContentAlignment.MiddleCenter,
                Padding = Padding.Empty,
                Font = new Font("Segoe UI", 16F, FontStyle.Bold),
                ForeColor = Color.White,
                BackColor = Color.Transparent
            };
            var headerDescription = new Label
            {
                Text = "Search by name, contact, or address. Select an existing person to reuse their saved details and classification.",
                Dock = DockStyle.Fill,
                TextAlign = ContentAlignment.MiddleCenter,
                Padding = new Padding(16, 4, 16, 10),
                Font = new Font("Segoe UI", 9.5F, FontStyle.Bold),
                ForeColor = Color.White,
                BackColor = Color.Transparent
            };
            var headerLayout = new TableLayoutPanel
            {
                Dock = DockStyle.Fill,
                ColumnCount = 1,
                RowCount = 2,
                BackColor = Color.Transparent,
                Margin = Padding.Empty,
                Padding = Padding.Empty
            };
            headerLayout.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 100F));
            headerLayout.RowStyles.Add(new RowStyle(SizeType.Absolute, 46F));
            headerLayout.RowStyles.Add(new RowStyle(SizeType.Percent, 100F));
            headerLayout.Controls.Add(headerTitle, 0, 0);
            headerLayout.Controls.Add(headerDescription, 0, 1);
            header.Controls.Add(headerLayout);

            var panelSearch = new Panel
            {
                Dock = DockStyle.Top,
                Height = 82,
                Padding = new Padding(20, 10, 20, 12),
                BackColor = Color.FromArgb(255, 255, 255)
            };
            var searchCaption = new Label
            {
                Text = "SEARCH SAVED PEOPLE",
                Dock = DockStyle.Top,
                Height = 20,
                Padding = new Padding(0, 0, 0, 2),
                Font = new Font("Segoe UI", 8.5F, FontStyle.Bold),
                ForeColor = Color.FromArgb(75, 96, 125),
                BackColor = Color.Transparent
            };
            var searchRow = new Panel
            {
                Dock = DockStyle.Fill,
                BackColor = Color.FromArgb(246, 249, 254)
            };
            _txtSearch.Dock = DockStyle.Fill;
            _txtSearch.Margin = Padding.Empty;
            _txtSearch.Height = 36;
            _btnSearch.Text = "Search";
            _btnSearch.Dock = DockStyle.Right;
            _btnSearch.Width = 132;
            _btnSearch.Height = 36;
            _btnSearch.BackColor = Color.FromArgb(37, 99, 235);
            _btnSearch.ForeColor = Color.White;
            _btnSearch.FlatStyle = FlatStyle.Flat;
            _btnSearch.FlatAppearance.BorderSize = 0;
            _btnSearch.FlatAppearance.MouseOverBackColor = Color.FromArgb(29, 78, 216);
            _btnSearch.Font = new Font("Segoe UI", 9.5F, FontStyle.Bold);
            _btnSearch.Cursor = Cursors.Hand;
            _btnSearch.Click += (_, __) =>
            {
                _searchDebounce.Stop();
                _currentPage = 0;
                RunSearch();
            };
            searchRow.Controls.Add(_txtSearch);
            searchRow.Controls.Add(_btnSearch);
            panelSearch.Controls.Add(searchRow);
            panelSearch.Controls.Add(searchCaption);

            _grid.Dock = DockStyle.Fill;
            _grid.ReadOnly = true;
            _grid.AllowUserToAddRows = false;
            _grid.RowHeadersVisible = false;
            _grid.SelectionMode = DataGridViewSelectionMode.FullRowSelect;
            _grid.MultiSelect = false;
            _grid.AutoGenerateColumns = false;
            _grid.AutoSizeColumnsMode = DataGridViewAutoSizeColumnsMode.Fill;
            _grid.Columns.Add(new DataGridViewTextBoxColumn { Name = "FullName", HeaderText = "Full Name", DataPropertyName = "FullName", FillWeight = 160 });
            _grid.Columns.Add(new DataGridViewTextBoxColumn { Name = "PersonClassification", HeaderText = "Category", DataPropertyName = "PersonClassification", FillWeight = 75 });
            _grid.Columns.Add(new DataGridViewTextBoxColumn { Name = "LastVisit", HeaderText = "Last Visit", DataPropertyName = "LastVisitDisplay", FillWeight = 100 });
            _grid.BackgroundColor = Color.White;
            _grid.DataSource = _gridBinding;

            _paginationPanel.Dock = DockStyle.Bottom;
            _paginationPanel.Height = 52;
            _paginationPanel.BackColor = Color.FromArgb(248, 250, 253);

            var panelGrid = new Panel
            {
                Dock = DockStyle.Fill,
                Padding = new Padding(20, 4, 20, 10),
                BackColor = Color.FromArgb(239, 244, 251)
            };
            panelGrid.Controls.Add(_grid);
            panelGrid.Controls.Add(_paginationPanel);
            _paginationPanel.BringToFront();

            var panelFooter = new Panel
            {
                Dock = DockStyle.Bottom,
                Height = 64,
                Padding = new Padding(20, 8, 20, 12),
                BackColor = Color.FromArgb(239, 244, 251)
            };
            var btnRegisterLogbook = new Button
            {
                Text = "Register Logbook",
                Width = 176,
                Height = 40,
                Left = 18,
                Top = 8,
                BackColor = Color.White,
                ForeColor = Color.FromArgb(8, 125, 160),
                FlatStyle = FlatStyle.Flat,
                Cursor = Cursors.Hand
            };
            btnRegisterLogbook.FlatAppearance.BorderSize = 1;
            btnRegisterLogbook.FlatAppearance.BorderColor = Color.FromArgb(116, 204, 225);
            btnRegisterLogbook.FlatAppearance.MouseOverBackColor = Color.FromArgb(235, 250, 255);
            btnRegisterLogbook.Font = new Font("Segoe UI", 9F, FontStyle.Bold);

            var btnOk = new Button
            {
                Text = "Use Selected Person",
                Width = 200,
                Height = 40,
                Left = 530,
                Top = 8,
                BackColor = Color.FromArgb(37, 99, 235),
                ForeColor = Color.White,
                FlatStyle = FlatStyle.Flat,
                Font = new Font("Segoe UI", 9F, FontStyle.Bold),
                Cursor = Cursors.Hand
            };
            btnOk.FlatAppearance.BorderSize = 0;
            btnOk.FlatAppearance.MouseOverBackColor = Color.FromArgb(29, 78, 216);

            var btnCancel = new Button
            {
                Text = "Cancel",
                Width = 116,
                Height = 40,
                Left = 398,
                Top = 8,
                DialogResult = DialogResult.Cancel,
                BackColor = Color.White,
                ForeColor = Color.FromArgb(58, 76, 101),
                FlatStyle = FlatStyle.Flat
            };
            btnCancel.FlatAppearance.BorderSize = 1;
            btnCancel.FlatAppearance.BorderColor = Color.FromArgb(207, 218, 234);
            btnCancel.FlatAppearance.MouseOverBackColor = Color.FromArgb(241, 246, 252);
            btnCancel.Font = new Font("Segoe UI", 9F, FontStyle.Bold);

            btnRegisterLogbook.Click += (_, __) =>
            {
                SelectedProfile = null;
                DialogResult = DialogResult.Retry;
                Close();
            };

            btnOk.Click += (_, __) =>
            {
                if (!TryCommitSelection())
                {
                    MessageBox.Show("Select a person row first.", "Selection",
                        MessageBoxButtons.OK, MessageBoxIcon.Information);
                    return;
                }
                DialogResult = DialogResult.OK;
                Close();
            };

            btnRegisterLogbook.Dock = DockStyle.Left;
            var footerActions = new FlowLayoutPanel
            {
                Dock = DockStyle.Fill,
                FlowDirection = FlowDirection.RightToLeft,
                WrapContents = false,
                Padding = new Padding(0, 4, 0, 0)
            };
            btnOk.Margin = new Padding(8, 0, 0, 0);
            btnCancel.Margin = new Padding(8, 0, 0, 0);
            footerActions.Controls.Add(btnOk);
            footerActions.Controls.Add(btnCancel);
            panelFooter.Controls.Add(footerActions);
            panelFooter.Controls.Add(btnRegisterLogbook);

            Controls.Add(panelGrid);
            Controls.Add(panelSearch);
            Controls.Add(header);
            Controls.Add(panelFooter);

            _grid.CellDoubleClick += (_, e) =>
            {
                if (e.RowIndex < 0) return;
                if (TryCommitSelection())
                {
                    DialogResult = DialogResult.OK;
                    Close();
                }
            };

            CancelButton = btnCancel;
            ResponsiveLayout.FitClientToWorkingArea(this, 900, 840, 560, 420);

            _searchDebounce.Interval = 180;
            _searchDebounce.Tick += (_, __) =>
            {
                _searchDebounce.Stop();
                _currentPage = 0;
                RunSearch();
            };

            _txtSearch.TextChanged += (_, __) =>
            {
                _searchDebounce.Stop();
                _currentPage = 0;
                _searchDebounce.Start();
            };
            _txtSearch.KeyDown += (_, e) =>
            {
                if (e.KeyCode == Keys.Enter)
                {
                    e.SuppressKeyPress = true;
                    _searchDebounce.Stop();
                    _currentPage = 0;
                    RunSearch();
                }
            };

            FormClosed += (_, __) => _searchDebounce.Dispose();
            DesktopTheme.Apply(this);
            ApplyDialogVisualStyle(header, headerTitle, headerDescription, panelSearch, searchRow,
                searchCaption, panelGrid, panelFooter, btnRegisterLogbook, btnOk, btnCancel);
            Shown += (_, __) => RunSearch();
        }

        private void ApplyDialogVisualStyle(
            Panel header,
            Label headerTitle,
            Label headerDescription,
            Panel panelSearch,
            Panel searchRow,
            Label searchCaption,
            Panel panelGrid,
            Panel panelFooter,
            Button btnRegisterLogbook,
            Button btnOk,
            Button btnCancel)
        {
            BackColor = Color.FromArgb(239, 244, 251);
            header.BackColor = Color.FromArgb(10, 17, 40);
            headerTitle.ForeColor = Color.White;
            headerTitle.TextAlign = ContentAlignment.MiddleCenter;
            headerTitle.Font = new Font("Segoe UI", 16F, FontStyle.Bold);
            headerDescription.ForeColor = Color.White;
            headerDescription.TextAlign = ContentAlignment.MiddleCenter;
            headerDescription.Font = new Font("Segoe UI", 9.5F, FontStyle.Bold);
            panelSearch.BackColor = Color.White;
            searchCaption.ForeColor = Color.FromArgb(75, 96, 125);
            searchRow.BackColor = Color.FromArgb(246, 249, 254);
            _txtSearch.BackColor = Color.FromArgb(246, 249, 254);
            _txtSearch.ForeColor = Color.FromArgb(27, 46, 74);
            _txtSearch.Font = new Font("Segoe UI", 10F);
            _txtSearch.BorderStyle = BorderStyle.FixedSingle;
            panelGrid.BackColor = Color.FromArgb(239, 244, 251);
            _paginationPanel.BackColor = Color.FromArgb(248, 250, 253);
            panelFooter.BackColor = Color.FromArgb(239, 244, 251);
            btnRegisterLogbook.FlatAppearance.BorderSize = 1;

            _grid.BackgroundColor = Color.White;
            _grid.GridColor = Color.FromArgb(228, 235, 245);
            _grid.BorderStyle = BorderStyle.None;
            _grid.EnableHeadersVisualStyles = false;
            _grid.ColumnHeadersHeight = 42;
            _grid.ColumnHeadersDefaultCellStyle.BackColor = Color.FromArgb(232, 241, 253);
            _grid.ColumnHeadersDefaultCellStyle.ForeColor = Color.FromArgb(67, 87, 116);
            _grid.ColumnHeadersDefaultCellStyle.SelectionBackColor = Color.FromArgb(232, 241, 253);
            _grid.ColumnHeadersDefaultCellStyle.Font = new Font("Segoe UI", 8.5F, FontStyle.Bold);
            _grid.ColumnHeadersDefaultCellStyle.Padding = new Padding(12, 0, 0, 0);
            _grid.DefaultCellStyle.BackColor = Color.White;
            _grid.DefaultCellStyle.ForeColor = Color.FromArgb(27, 46, 74);
            _grid.DefaultCellStyle.SelectionBackColor = Color.FromArgb(220, 237, 255);
            _grid.DefaultCellStyle.SelectionForeColor = Color.FromArgb(21, 43, 74);
            _grid.DefaultCellStyle.Padding = new Padding(12, 0, 0, 0);
            _grid.AlternatingRowsDefaultCellStyle.BackColor = Color.FromArgb(248, 251, 255);
            _grid.RowTemplate.Height = 42;
            _grid.CellBorderStyle = DataGridViewCellBorderStyle.SingleHorizontal;
            _grid.Font = new Font("Segoe UI", 9.5F);

            btnRegisterLogbook.BackColor = Color.White;
            btnRegisterLogbook.ForeColor = Color.FromArgb(8, 125, 160);
            btnRegisterLogbook.FlatAppearance.BorderColor = Color.FromArgb(116, 204, 225);
            btnRegisterLogbook.FlatAppearance.MouseOverBackColor = Color.FromArgb(235, 250, 255);
            btnOk.BackColor = Color.FromArgb(37, 99, 235);
            btnOk.ForeColor = Color.White;
            btnOk.FlatAppearance.MouseOverBackColor = Color.FromArgb(29, 78, 216);
            btnCancel.BackColor = Color.White;
            btnCancel.ForeColor = Color.FromArgb(58, 76, 101);
            btnCancel.FlatAppearance.BorderColor = Color.FromArgb(207, 218, 234);
            btnCancel.FlatAppearance.MouseOverBackColor = Color.FromArgb(241, 246, 252);

            StyleDarkBlueAction(_btnSearch);
            StyleDarkBlueAction(btnRegisterLogbook);
            panelSearch.Paint += DrawCardEdge;
            panelGrid.Paint += DrawCardEdge;
            header.Paint += DrawHeaderAccent;
        }

        private static void DrawCardEdge(object sender, PaintEventArgs e)
        {
            var panel = (Panel)sender;
            Rectangle bounds = panel.ClientRectangle;
            if (bounds.Width < 2 || bounds.Height < 2)
                return;

            using (var shadowPen = new Pen(Color.FromArgb(24, 58, 86, 128)))
                e.Graphics.DrawLine(shadowPen, 1, bounds.Height - 2, bounds.Width - 2, bounds.Height - 2);
            using (var borderPen = new Pen(Color.FromArgb(218, 228, 242)))
                e.Graphics.DrawRectangle(borderPen, 0, 0, bounds.Width - 1, bounds.Height - 1);
        }

        private static void DrawHeaderAccent(object sender, PaintEventArgs e)
        {
            using (var accentBrush = new SolidBrush(Color.FromArgb(34, 181, 220)))
                e.Graphics.FillRectangle(accentBrush, 0, 0, 4, ((Panel)sender).Height);
        }

        private bool TryCommitSelection()
        {
            object rowItem = _grid.CurrentRow?.DataBoundItem;
            if (!(rowItem is VisitorPickVm vm))
                return false;

            SelectedProfile = new VisitorProfile
            {
                FingerprintId = vm.FingerprintId,
                PersonId = vm.PersonId,
                PersonClassification = vm.PersonClassification,
                FullName = vm.FullName,
                Address = vm.Address,
                ContactNumber = vm.ContactNumber,
                OfficeOrPersonToVisit = string.Empty,
                HasFingerprintOnFile = vm.HasFingerprintOnFile
            };
            return true;
        }

        private void RunSearch()
        {
            string q = _txtSearch.Text?.Trim() ?? string.Empty;
            List<VisitorIdentityPickRow> rows;
            try
            {
                rows = _db.SearchVisitorIdentitiesForReturnVisit(q, _currentPage, PageSize, out _totalRows);
                int lastPage = Math.Max(0, (int)Math.Ceiling(_totalRows / (double)PageSize) - 1);
                if (_currentPage > lastPage)
                {
                    _currentPage = lastPage;
                    rows = _db.SearchVisitorIdentitiesForReturnVisit(q, _currentPage, PageSize, out _totalRows);
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show("Person search failed: " + ex.Message, "Find Saved Person",
                    MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            var vms = new BindingList<VisitorPickVm>();
            foreach (var r in rows)
            {
                string lastVisit = r.LastVisit == DateTime.MinValue
                    ? ""
                    : r.LastVisit.ToString("yyyy-MM-dd hh:mm tt");
                vms.Add(new VisitorPickVm
                {
                    FingerprintId = r.FingerprintId,
                    PersonId = r.PersonId,
                    PersonClassification = r.PersonClassification,
                    FullName = r.FullName,
                    Address = r.Address,
                    ContactNumber = r.ContactNumber,
                    LastVisitDisplay = lastVisit,
                    HasFingerprintOnFile = r.HasFingerprintOnFile,
                    FingerprintStatus = r.HasFingerprintOnFile ? "On file" : "Not enrolled"
                });
            }

            _gridBinding.DataSource = vms;
            _grid.Refresh();
            UpdatePagination();
        }

        private void UpdatePagination()
        {
            _paginationPanel.SuspendLayout();
            try
            {
                while (_paginationPanel.Controls.Count > 0)
                {
                    Control oldControl = _paginationPanel.Controls[0];
                    _paginationPanel.Controls.RemoveAt(0);
                    oldControl.Dispose();
                }
                int pageCount = Math.Max(1, (int)Math.Ceiling(_totalRows / (double)PageSize));
                _currentPage = Math.Min(_currentPage, pageCount - 1);

                var pages = new FlowLayoutPanel
                {
                    Dock = DockStyle.Fill,
                    FlowDirection = FlowDirection.LeftToRight,
                    WrapContents = false,
                    AutoSize = false,
                    BackColor = Color.Transparent,
                    Padding = new Padding(0, 6, 0, 0),
                    Margin = Padding.Empty
                };
                var previous = CreatePageButton("Previous", _currentPage > 0);
                previous.Width = 104;
                previous.Click += (_, __) =>
                {
                    if (_currentPage == 0) return;
                    _currentPage--;
                    RunSearch();
                };
                pages.Controls.Add(previous);

                int startPage = Math.Max(0, _currentPage - VisiblePageNumberCount / 2);
                int endPage = Math.Min(pageCount, startPage + VisiblePageNumberCount);
                startPage = Math.Max(0, endPage - VisiblePageNumberCount);
                for (int page = startPage; page < endPage; page++)
                {
                    int pageIndex = page;
                    var pageButton = CreatePageButton((page + 1).ToString(), true);
                    pageButton.Width = 36;
                    pageButton.Click += (_, __) =>
                    {
                        _currentPage = pageIndex;
                        RunSearch();
                    };
                    pages.Controls.Add(pageButton);
                }

                var next = CreatePageButton("Next", _currentPage < pageCount - 1);
                next.Width = 76;
                next.Click += (_, __) =>
                {
                    if (_currentPage >= pageCount - 1) return;
                    _currentPage++;
                    RunSearch();
                };
                pages.Controls.Add(next);
                _paginationPanel.Controls.Add(pages);
                foreach (Control control in pages.Controls)
                {
                    if (!(control is Button button))
                        continue;

                    bool isCurrentPage = int.TryParse(button.Text, out int pageNumber) &&
                        pageNumber == _currentPage + 1;
                    StyleDarkBlueAction(button);
                    button.FlatAppearance.BorderSize = isCurrentPage ? 2 : 1;
                    button.FlatAppearance.BorderColor = isCurrentPage
                        ? Color.FromArgb(72, 198, 239)
                        : Color.FromArgb(42, 75, 118);
                }
            }
            finally
            {
                _paginationPanel.ResumeLayout(true);
            }
        }

        private static Button CreatePageButton(string text, bool enabled)
        {
            var button = new PaginationButton
            {
                Text = text,
                Height = 32,
                Enabled = enabled,
                Margin = new Padding(3, 0, 3, 0),
                Padding = new Padding(4, 0, 4, 0),
                FlatStyle = FlatStyle.Flat,
                BackColor = Color.FromArgb(22, 52, 92),
                ForeColor = Color.White,
                Font = new Font("Segoe UI", 8.5F, FontStyle.Bold),
                Cursor = enabled ? Cursors.Hand : Cursors.Default
            };
            StyleDarkBlueAction(button);
            return button;
        }

        private static void StyleDarkBlueAction(Button button)
        {
            Color darkBlue = Color.FromArgb(22, 52, 92);
            button.UseVisualStyleBackColor = false;
            button.BackColor = darkBlue;
            button.ForeColor = Color.White;
            button.FlatStyle = FlatStyle.Flat;
            button.FlatAppearance.BorderColor = Color.FromArgb(42, 75, 118);
            button.FlatAppearance.MouseOverBackColor = Color.FromArgb(31, 69, 116);
            button.FlatAppearance.MouseDownBackColor = Color.FromArgb(14, 38, 72);
        }

        private sealed class PaginationButton : Button
        {
            private bool _isHovered;
            private bool _isPressed;

            public PaginationButton()
            {
                FlatStyle = FlatStyle.Flat;
                UseVisualStyleBackColor = false;
                SetStyle(ControlStyles.UserPaint | ControlStyles.AllPaintingInWmPaint |
                    ControlStyles.OptimizedDoubleBuffer, true);
            }

            protected override void OnMouseEnter(EventArgs e)
            {
                _isHovered = true;
                Invalidate();
                base.OnMouseEnter(e);
            }

            protected override void OnMouseLeave(EventArgs e)
            {
                _isHovered = false;
                _isPressed = false;
                Invalidate();
                base.OnMouseLeave(e);
            }

            protected override void OnMouseDown(MouseEventArgs e)
            {
                if (Enabled && e.Button == MouseButtons.Left)
                    _isPressed = true;
                Invalidate();
                base.OnMouseDown(e);
            }

            protected override void OnMouseUp(MouseEventArgs e)
            {
                _isPressed = false;
                Invalidate();
                base.OnMouseUp(e);
            }

            protected override void OnEnabledChanged(EventArgs e)
            {
                ForeColor = Color.White;
                Invalidate();
                base.OnEnabledChanged(e);
            }

            protected override void OnPaint(PaintEventArgs e)
            {
                Color background = _isPressed
                    ? Color.FromArgb(14, 38, 72)
                    : _isHovered
                        ? Color.FromArgb(31, 69, 116)
                        : Color.FromArgb(22, 52, 92);

                using (var backgroundBrush = new SolidBrush(background))
                    e.Graphics.FillRectangle(backgroundBrush, ClientRectangle);

                int borderSize = FlatAppearance.BorderSize;
                if (borderSize > 0)
                {
                    using (var borderPen = new Pen(FlatAppearance.BorderColor, borderSize))
                    {
                        Rectangle borderBounds = ClientRectangle;
                        borderBounds.Width -= 1;
                        borderBounds.Height -= 1;
                        e.Graphics.DrawRectangle(borderPen, borderBounds);
                    }
                }

                TextRenderer.DrawText(
                    e.Graphics,
                    Text,
                    Font,
                    ClientRectangle,
                    Color.White,
                    TextFormatFlags.HorizontalCenter |
                    TextFormatFlags.VerticalCenter |
                    TextFormatFlags.EndEllipsis |
                    TextFormatFlags.SingleLine);
            }
        }

        private sealed class VisitorPickVm
        {
            public int FingerprintId { get; set; }
            public int PersonId { get; set; }
            public string PersonClassification { get; set; }
            public string FullName { get; set; }
            public string Address { get; set; }
            public string ContactNumber { get; set; }
            public string LastVisitDisplay { get; set; }
            public bool HasFingerprintOnFile { get; set; }
            public string FingerprintStatus { get; set; }
        }
    }
}
