@if ($employee)
    <style>
        [data-uppercase-position] { text-transform: uppercase; }
    </style>
@endif

<style>
    .employee-password-mismatch { border-color: var(--c-danger) !important; box-shadow: 0 0 0 3px rgba(239, 68, 68, .14) !important; }
    .employee-password-match-error { margin: 6px 0 0; color: var(--c-error-text); font-size: 12px; font-weight: 600; }
    .employee-password-match-error[hidden] { display: none; }
</style>

<form method="POST" action="{{ $action }}" data-employee-password-form>
    @csrf
    @if($method !== 'POST') @method($method) @endif

    <div class="card" style="max-width:680px;">
        <div class="card-title">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="color:var(--c-primary)">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
            </svg>
            Personal Information
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="form-group" style="grid-column:span 2">
                <label class="form-label">Full Name</label>
                <input class="form-control" id="employee-name" name="name" value="{{ old('name', $employee?->name) }}" placeholder="Employee full name" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="employee-email">Email Address</label>
                @if($employee)
                    <input class="form-control" type="email" id="employee-email" name="email" value="{{ old('email', $employee->email) }}" placeholder="Name@biopaylog.com" required>
                @else
                    <div style="display:flex;align-items:center;">
                        <input class="form-control" type="text" id="employee-email-username" name="email_username"
                               value="{{ old('email_username', old('email') ? explode('@', old('email'), 2)[0] : '') }}"
                               placeholder="Email username" maxlength="64" required>
                        <span style="white-space:nowrap;margin-left:8px;color:var(--c-text-secondary);">@biopaylog.com</span>
                    </div>
                    <input type="hidden" name="email" value="{{ old('email') }}">
                @endif
            </div>

            <div class="form-group">
                <label class="form-label">Contact Number</label>
                <input class="form-control" name="contact_number" value="{{ old('contact_number', $employee?->contact_number) }}" placeholder="+63 900 000 0000">
            </div>

            <div class="form-group">
                <label class="form-label" for="employment-start-date">Employment Start Date</label>
                <input class="form-control" type="date" id="employment-start-date" name="employment_start_date"
                       value="{{ old('employment_start_date', $employee?->employment_start_date?->format('Y-m-d')) }}"
                       max="{{ now()->toDateString() }}">
            </div>

            <div class="form-group">
                <label class="form-label">Role</label>
                <select class="form-control" name="role">
                    <option value="employee" @selected(old('role', $employee?->role) === 'employee')>Employee</option>
                    <option value="admin" @selected(old('role', $employee?->role) === 'admin')>Admin</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Position / Job Title</label>
                <input class="form-control" name="position" value="{{ old('position', $employee?->position) }}" placeholder="e.g. Barangay Secretary" @if ($employee) data-uppercase-position @endif>
            </div>

            <div class="form-group">
                <label class="form-label">Department</label>
                <input class="form-control" name="department" value="{{ old('department', $employee?->department) }}" placeholder="e.g. Health">
            </div>

            <div class="form-group">
                <label class="form-label">Device / Fingerprint ID</label>
                <input class="form-control" type="text" name="employee_id" maxlength="64"
                       value="{{ old('employee_id', $employee?->employee_id) }}"
                       placeholder="Fingerprint or device id">
            </div>

            <div class="form-group" style="grid-column:span 2">
                <label style="display:flex;align-items:center;gap:10px;font-weight:700;cursor:pointer;">
                    <input type="hidden" name="can_access_logbook" value="0">
                    <input type="checkbox" name="can_access_logbook" value="1"
                           @checked(old('can_access_logbook', $employee?->can_access_logbook ?? false))>
                    Authorize Logbook Access
                </label>
            </div>
        </div>
    </div>

    <div class="card" style="max-width:680px;margin-top:16px;">
        <div class="card-title">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="color:var(--c-primary)">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
            </svg>
            Security
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="form-group">
                <label class="form-label">Password {{ $employee ? '(leave blank to keep current)' : '' }}</label>
                <input class="form-control" type="password" name="password"
                       placeholder="{{ $employee ? 'Leave blank to keep current' : 'Enter password' }}"
                       {{ $employee ? '' : 'required' }}>
            </div>
            <div class="form-group">
                <label class="form-label">Confirm Password</label>
                <input class="form-control" type="password" name="password_confirmation"
                       aria-describedby="employee-password-match-error"
                       placeholder="Repeat password"
                       {{ $employee ? '' : 'required' }}>
                <p class="employee-password-match-error" id="employee-password-match-error" data-password-match-error role="alert" hidden>Password and Confirm Password must match.</p>
            </div>
        </div>
    </div>

    <div style="display:flex;gap:10px;margin-top:20px;max-width:680px;">
        <button class="btn btn-primary" type="submit">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
            </svg>
            Save Employee
        </button>
        <a class="btn btn-danger" href="{{ route('admin.employees.index') }}">Cancel</a>
    </div>
</form>

@if ($employee)
    <script>
        (function () {
            var positionInput = document.querySelector('[data-uppercase-position]');
            if (!positionInput) {
                return;
            }

            positionInput.addEventListener('input', function () {
                var start = positionInput.selectionStart;
                var end = positionInput.selectionEnd;
                var value = positionInput.value;
                var uppercaseValue = value.toUpperCase();
                if (value === uppercaseValue) {
                    return;
                }

                positionInput.value = uppercaseValue;
                if (start !== null && end !== null) {
                    positionInput.setSelectionRange(
                        value.slice(0, start).toUpperCase().length,
                        value.slice(0, end).toUpperCase().length
                    );
                }
            });
        }());
    </script>
@endif

<script>
    (function () {
        var passwordForm = document.querySelector('[data-employee-password-form]');
        if (passwordForm) {
            var passwordInput = passwordForm.elements.namedItem('password');
            var confirmationInput = passwordForm.elements.namedItem('password_confirmation');
            var matchError = passwordForm.querySelector('[data-password-match-error]');

            function validatePasswordMatch() {
                var mismatch = passwordInput.value !== '' && passwordInput.value !== confirmationInput.value;
                confirmationInput.setCustomValidity(mismatch ? 'Password and Confirm Password must match.' : '');
                confirmationInput.classList.toggle('employee-password-mismatch', mismatch);
                confirmationInput.setAttribute('aria-invalid', mismatch ? 'true' : 'false');
                matchError.hidden = !mismatch;

                return !mismatch;
            }

            passwordForm.addEventListener('input', function (event) {
                if (event.target === passwordInput || event.target === confirmationInput) {
                    validatePasswordMatch();
                }
            });

            passwordForm.addEventListener('submit', function (event) {
                if (!validatePasswordMatch()) {
                    event.preventDefault();
                    confirmationInput.reportValidity();
                }
            });
        }

        var nameInput = document.getElementById('employee-name');
        var usernameInput = document.getElementById('employee-email-username');
        if (!nameInput || !usernameInput) {
            return;
        }

        function usernameFromName(value) {
            return value
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '.')
                .replace(/^\.+|\.+$/g, '')
                .slice(0, 64)
                .replace(/^\.+|\.+$/g, '');
        }

        var usernameWasEdited = usernameInput.value !== '';
        usernameInput.addEventListener('input', function () {
            usernameWasEdited = true;
        });

        nameInput.addEventListener('input', function () {
            if (!usernameWasEdited) {
                usernameInput.value = usernameFromName(nameInput.value);
            }
        });

        if (!usernameWasEdited) {
            usernameInput.value = usernameFromName(nameInput.value);
        }
    })();
</script>
