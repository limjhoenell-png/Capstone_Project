<form method="POST" action="{{ $action }}">
    @csrf
    @if($method !== 'POST') @method($method) @endif
    @php
        $purposeChoices = $purposeOptions ?? [
            'Request for Barangay Clearance',
            'Request for Certificate of Indigency',
            'Request for Certificate of Residency',
            'Filing a Complaint (Reklamo)',
            'Blotter Report (Incident Report)',
            'Mediation / Settlement (Barangay Hearing)',
            'Request for Assistance (Financial / Medical)',
            'Business Permit Clearance',
            'General Inquiry / Concern',
            'Other',
        ];
        $currentPurpose = old(
            'purpose_of_transaction',
            data_get($legacyEntry ?? null, 'purpose_of_transaction', data_get($entry ?? null, 'purpose_of_transaction', data_get($entry ?? null, 'description', '')))
        );
        $purposeChoice = old('purpose_choice');
        if ($purposeChoice === null) {
            $purposeChoice = in_array($currentPurpose, $purposeChoices, true)
                ? $currentPurpose
                : ($currentPurpose !== '' ? 'Other' : $purposeChoices[0]);
        }
        $purposeOther = old('purpose_other', $purposeChoice === 'Other' && $currentPurpose !== 'Other' ? $currentPurpose : '');
        $purposeValue = $purposeChoice === 'Other' ? $purposeOther : $purposeChoice;
        $contactCountryCodes = $contactCountryCodes ?? app(\App\Services\LogbookTransferService::class)->contactCountryCodes();
        $storedContactNumber = (string) old(
            'contact_number',
            data_get($legacyEntry ?? null, 'contact_number', data_get($entry ?? null, 'contact_number', ''))
        );
        $contactCountryCode = old('country_code', '+63');
        if (! session()->hasOldInput('country_code')) {
            foreach (array_keys($contactCountryCodes) as $supportedCode) {
                if (str_starts_with($storedContactNumber, $supportedCode)) {
                    $contactCountryCode = $supportedCode;
                    $storedContactNumber = substr($storedContactNumber, strlen($supportedCode));
                    break;
                }
            }
        }
        $contactDigits = preg_replace('/\D+/', '', $storedContactNumber) ?? '';
    @endphp

    <div class="card" style="max-width:600px;">
        <div class="card-title">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="color:var(--c-primary)">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
            </svg>
            Logbook Entry
        </div>

        @if(!empty($useLegacyLogbook))
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="form-group" style="grid-column:span 2">
                    <label class="form-label">Employee (optional)</label>
                    <select class="form-control" name="user_id">
                        <option value="">Manual entry</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}"
                                    @selected((string) old('user_id') === (string) $employee->id)>
                                {{ $employee->name ?? $employee->full_name }}
                                @if(!empty($employee->employee_id)) (ID: {{ $employee->employee_id }}) @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group" style="grid-column:span 2">
                    <label class="form-label">Saved visitor</label>
                    <select class="form-control" name="visitor_account_id" id="visitor-account-id">
                        <option value="">None</option>
                        @foreach($visitors ?? [] as $visitor)
                            <option value="{{ $visitor->id }}"
                                    data-name="{{ $visitor->full_name }}"
                                    data-address="{{ $visitor->address }}"
                                    data-contact="{{ $visitor->contact_number }}"
                                    @selected((string) old('visitor_account_id') === (string) $visitor->id)>
                                {{ $visitor->full_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group" style="grid-column:span 2">
                    <label class="form-label">Full Name</label>
                    <input class="form-control" type="text" name="full_name"
                           value="{{ old('full_name', $legacyEntry->full_name ?? '') }}"
                           placeholder="Visitor/employee full name">
                </div>

                <div class="form-group" style="grid-column:span 2">
                    <label class="form-label">Address</label>
                    <input class="form-control" type="text" name="address"
                           value="{{ old('address', $legacyEntry->address ?? '') }}"
                           placeholder="Home address" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Contact Number</label>
                    <div style="display:grid;grid-template-columns:minmax(150px, .8fr) minmax(0, 1.2fr);gap:8px;">
                        <select class="form-control" name="country_code" aria-label="Contact country code" data-country-code>
                            @foreach($contactCountryCodes as $code => $country)
                                <option value="{{ $code }}" @selected($contactCountryCode === $code)>{{ $country }} ({{ $code }})</option>
                            @endforeach
                        </select>
                        <input class="form-control" type="tel" name="contact_number"
                               value="{{ $contactDigits }}" placeholder="09266952238" inputmode="numeric"
                               pattern="[0-9]{11}" maxlength="11" required data-contact-number>
                    </div>
                    <small data-contact-help>Enter exactly 11 digits, including the leading 0.</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Fingerprint ID</label>
                    <input class="form-control" type="number" name="fingerprint_id"
                           value="{{ old('fingerprint_id', $legacyEntry->fingerprint_id ?? '') }}"
                           placeholder="If no employee selected">
                </div>

                <div class="form-group" style="grid-column:span 2">
                    <label class="form-label">Purpose of Transaction</label>
                    <select class="form-control" id="purpose-choice" name="purpose_choice" data-purpose-choice>
                        @foreach($purposeChoices as $choice)
                            <option value="{{ $choice }}" @selected($purposeChoice === $choice)>{{ $choice }}</option>
                        @endforeach
                    </select>
                    <div class="form-group" id="purpose-other-group" data-purpose-other-group style="margin-top:10px;{{ $purposeChoice === 'Other' ? '' : 'display:none;' }}">
                        <label class="form-label" for="purpose-other">Custom purpose</label>
                        <input class="form-control" type="text" id="purpose-other" name="purpose_other"
                               value="{{ $purposeOther }}" placeholder="Enter the purpose of this transaction"
                               @required($purposeChoice === 'Other') data-purpose-other>
                    </div>
                    <input type="hidden" name="purpose_of_transaction" value="{{ $purposeValue }}" data-purpose-value required>
                </div>

                <div class="form-group">
                    <label class="form-label">Transaction type</label>
                    <input class="form-control" type="text" name="transaction_type" maxlength="100"
                           value="{{ old('transaction_type', $legacyEntry->transaction_type ?? '') }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Representing</label>
                    <input class="form-control" type="text" name="represented_person_name" maxlength="150"
                           value="{{ old('represented_person_name', $legacyEntry->represented_person_name ?? '') }}">
                </div>
                <div class="form-group">
                    <label class="form-label"><input type="checkbox" name="is_authorized_representative" value="1" @checked(old('is_authorized_representative', $legacyEntry->is_authorized_representative ?? false))> Authorized representative</label>
                </div>
                <div class="form-group">
                    <label class="form-label"><input type="checkbox" name="authorization_letter_verified" value="1" @checked(old('authorization_letter_verified', $legacyEntry->authorization_letter_verified ?? false))> Authorization letter verified</label>
                </div>
                <div class="form-group">
                    <label class="form-label"><input type="checkbox" name="valid_id_verified" value="1" @checked(old('valid_id_verified', $legacyEntry->valid_id_verified ?? false))> Valid ID verified</label>
                </div>

                <div class="form-group" style="grid-column:span 2">
                    <label class="form-label">Time In</label>
                    <input class="form-control" type="datetime-local" name="time_in"
                           value="{{ old('time_in', isset($legacyEntry?->time_in) ? \Illuminate\Support\Carbon::parse($legacyEntry->time_in)->format('Y-m-d\TH:i') : '') }}"
                           required>
                </div>
            </div>
        @else
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="form-group" style="grid-column:span 2">
                    <label class="form-label">Employee</label>
                    <select class="form-control" name="user_id" required>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}"
                                    @selected((string) old('user_id', $entry?->user_id) === (string) $employee->id)>
                                {{ $employee->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group" style="grid-column:span 2">
                    <label class="form-label">Entry Date / Time</label>
                    <input class="form-control" type="datetime-local" name="entry_at"
                           value="{{ old('entry_at', optional($entry?->entry_at)->format('Y-m-d\TH:i')) }}"
                           required>
                </div>

                <div class="form-group" style="grid-column:span 2">
                    <label class="form-label">Contact Number</label>
                    <div style="display:grid;grid-template-columns:minmax(150px, .8fr) minmax(0, 1.2fr);gap:8px;">
                        <select class="form-control" name="country_code" aria-label="Contact country code" data-country-code>
                            @foreach($contactCountryCodes as $code => $country)
                                <option value="{{ $code }}" @selected($contactCountryCode === $code)>{{ $country }} ({{ $code }})</option>
                            @endforeach
                        </select>
                        <input class="form-control" type="tel" name="contact_number"
                               value="{{ $contactDigits }}" placeholder="09266952238" inputmode="numeric"
                               pattern="[0-9]{11}" maxlength="11" required data-contact-number>
                    </div>
                    <small data-contact-help>Enter exactly 11 digits, including the leading 0.</small>
                </div>

                <div class="form-group" style="grid-column:span 2">
                    <label class="form-label">Purpose of Transaction</label>
                    <select class="form-control" name="purpose_choice" data-purpose-choice>
                        @foreach($purposeChoices as $choice)
                            <option value="{{ $choice }}" @selected($purposeChoice === $choice)>{{ $choice }}</option>
                        @endforeach
                    </select>
                    <div class="form-group" data-purpose-other-group style="margin-top:10px;{{ $purposeChoice === 'Other' ? '' : 'display:none;' }}">
                        <label class="form-label">Custom purpose</label>
                        <input class="form-control" type="text" name="purpose_other"
                               value="{{ $purposeOther }}" placeholder="Enter the purpose of this transaction"
                               @required($purposeChoice === 'Other') data-purpose-other>
                    </div>
                    <input type="hidden" name="purpose_of_transaction" value="{{ $purposeValue }}" data-purpose-value required>
                </div>
            </div>
        @endif

        <div style="display:flex;gap:10px;margin-top:4px;">
            <button class="btn btn-primary" type="submit">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
                Save Entry
            </button>
            <a class="btn btn-danger" href="{{ route('admin.barangay-logbook.logs') }}">Cancel</a>
        </div>
    </div>
</form>
<script>
    document.querySelectorAll('form').forEach((form) => {
        const countryCode = form.querySelector('[data-country-code]');
        const contactNumber = form.querySelector('[data-contact-number]');
        const contactHelp = form.querySelector('[data-contact-help]');
        const updateContactRules = () => {
            if (!countryCode || !contactNumber) return;
            const isPhilippines = countryCode.value === '+63';
            contactNumber.maxLength = isPhilippines ? 11 : 15;
            contactNumber.pattern = isPhilippines ? '[0-9]{11}' : '[0-9]{4,15}';
            contactNumber.placeholder = isPhilippines ? '09266952238' : 'Digits only';
            if (contactHelp) {
                contactHelp.textContent = isPhilippines
                    ? 'Enter exactly 11 digits, including the leading 0.'
                    : 'Enter 4 to 15 digits, without spaces or symbols.';
            }
        };
        countryCode?.addEventListener('change', updateContactRules);
        updateContactRules();
    });

    document.querySelectorAll('form').forEach((form) => {
        const choice = form.querySelector('[data-purpose-choice]');
        const customGroup = form.querySelector('[data-purpose-other-group]');
        const customInput = form.querySelector('[data-purpose-other]');
        const purposeValue = form.querySelector('[data-purpose-value]');
        if (choice && customGroup && customInput && purposeValue) {
            const updatePurpose = () => {
                const isOther = choice.value === 'Other';
                customGroup.style.display = isOther ? '' : 'none';
                customInput.required = isOther;
                purposeValue.value = isOther ? customInput.value.trim() : choice.value;
            };
            choice.addEventListener('change', updatePurpose);
            customInput.addEventListener('input', updatePurpose);
            updatePurpose();
        }
    });

    document.getElementById('visitor-account-id')?.addEventListener('change', function () {
        const option = this.selectedOptions[0];
        if (!option || !option.dataset.name) return;
        const form = this.form;
        if (form.full_name) form.full_name.value = option.dataset.name;
        if (form.address) form.address.value = option.dataset.address || '';
        if (form.contact_number) {
            const savedNumber = option.dataset.contact || '';
            const codeSelect = form.querySelector('[data-country-code]');
            let nationalNumber = savedNumber;
            if (codeSelect) {
                for (const code of Array.from(codeSelect.options).map((countryOption) => countryOption.value).sort((a, b) => b.length - a.length)) {
                    if (savedNumber.startsWith(code)) {
                        codeSelect.value = code;
                        nationalNumber = savedNumber.slice(code.length);
                        break;
                    }
                }
                codeSelect.dispatchEvent(new Event('change'));
            }
            form.contact_number.value = nationalNumber.replace(/\D/g, '');
        }
    });
</script>
