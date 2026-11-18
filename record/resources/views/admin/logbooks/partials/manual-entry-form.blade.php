@php
    $purposeChoices = $purposeOptions;
    $contactCountryCodes = $contactCountryCodes ?? app(\App\Services\LogbookTransferService::class)->contactCountryCodes();
    $purposeChoice = old('purpose_choice', $purposeChoices[0] ?? '');
    $purposeOther = old('purpose_other', '');
    $internationalAddress = old('is_international_address') === '1';
    $defaultAddressCodes = [
        'region' => old('region_code', '11'),
        'province' => old('province_code', '1124'),
        'city' => old('city_code', '112403'),
        'barangay' => old('barangay_code', '112403003'),
    ];
    $entryMode = old('transaction_type') === 'claim_documents_on_behalf'
        ? 'claim_documents_on_behalf'
        : 'manual_transaction';
@endphp

<style>
    .manual-entry-form {
        width: 100%;
        max-width: 860px;
        margin: 0 auto;
    }
    .manual-entry-card {
        width: 100%;
        padding: 22px;
    }
    .manual-entry-title {
        margin: 0 0 20px;
        font-size: 21px;
    }
    .manual-entry-heading {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 20px;
    }
    .manual-entry-heading .manual-entry-title { margin: 0; }
    .manual-entry-mode-select { width: min(100%, 360px); }
    .manual-entry-section {
        margin: 18px 0 10px;
        padding: 9px 12px;
        border-left: 4px solid var(--c-primary, #2563eb);
        background: var(--c-surface-2, #f1f5f9);
        color: var(--c-text);
        font-size: 13px;
        font-weight: 800;
        letter-spacing: .03em;
        text-transform: uppercase;
    }
    .manual-entry-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0 16px;
    }
    .manual-entry-full {
        grid-column: 1 / -1;
    }
    .manual-entry-radio-row {
        display: flex;
        flex-wrap: wrap;
        gap: 12px 24px;
        padding: 4px 0 12px;
    }
    .manual-entry-radio {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        color: var(--c-text);
        font-weight: 600;
    }
    html[data-theme="dark"] .manual-entry-radio {
        color: #fff;
    }
    .manual-entry-radio input[type="radio"] {
        accent-color: #06b6d4;
    }
    .manual-entry-checkbox {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0 0 14px;
        color: var(--c-text);
    }
    .manual-entry-contact {
        display: grid;
        grid-template-columns: minmax(190px, .75fr) minmax(0, 1.25fr);
        gap: 8px;
    }
    .manual-entry-actions {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        margin-top: 18px;
    }
    .manual-entry-actions .btn {
        min-height: 42px;
        justify-content: center;
        min-width: 180px;
    }
    .manual-entry-save {
        color: #fff;
        background: #065f46;
        border-color: #065f46;
    }
    .manual-entry-save:hover {
        color: #fff;
        background: #064e3b;
        border-color: #064e3b;
    }
    .manual-entry-cancel {
        color: #fff;
        background: #7f1d1d;
        border-color: #7f1d1d;
    }
    .manual-entry-cancel:hover {
        color: #fff;
        background: #6b1a1a;
        border-color: #6b1a1a;
    }
    .manual-entry-error {
        margin: 0 0 14px;
        color: var(--c-danger, #b91c1c);
    }
    .manual-entry-claim-fields {
        margin-top: 20px;
        padding: 16px;
        border: 1px solid var(--c-border);
        border-radius: var(--radius-md);
        background: var(--c-surface-2);
    }
    .manual-entry-verification {
        margin-top: 18px;
        padding: 14px 16px;
        border: 1px solid #f59e0b;
        border-left: 4px solid #f59e0b;
        border-radius: var(--radius-md);
        background: rgba(245, 158, 11, .1);
        color: var(--c-text);
    }
    .manual-entry-verification strong { display: block; margin-bottom: 10px; }
    .manual-entry-claim-save {
        background: #065f46;
        border-color: #065f46;
        color: #fff;
    }
    .manual-entry-claim-save:hover {
        background: #064e3b;
        border-color: #064e3b;
        color: #fff;
    }
    .manual-entry-form.is-claim-mode .manual-entry-cancel {
        background: #450a0a;
        border-color: #450a0a;
    }
    .manual-entry-form.is-claim-mode .manual-entry-cancel:hover {
        background: #7f1d1d;
        border-color: #7f1d1d;
    }
    html[data-theme="dark"] .manual-entry-claim-fields,
    html[data-theme="dark"] .manual-entry-verification {
        color: #fff;
    }
    html[data-theme="dark"] .manual-entry-claim-fields .form-label,
    html[data-theme="dark"] .manual-entry-verification label {
        color: #fff;
    }
    @media (max-width: 640px) {
        .manual-entry-card { padding: 16px; }
        .manual-entry-grid { grid-template-columns: minmax(0, 1fr); }
        .manual-entry-full { grid-column: auto; }
        .manual-entry-contact { grid-template-columns: minmax(0, 1fr); }
        .manual-entry-actions { flex-direction: column; }
        .manual-entry-actions .btn { width: 100%; }
    }
</style>

<form method="POST" action="{{ route('admin.logbooks.store') }}" class="manual-entry-form {{ $entryMode === 'claim_documents_on_behalf' ? 'is-claim-mode' : '' }}" data-manual-entry-form>
    @csrf
    <input type="hidden" name="transaction_type" value="{{ $entryMode }}" data-entry-mode-value>
    <div class="card manual-entry-card">
        <div class="manual-entry-heading">
            <h1 class="manual-entry-title" data-entry-mode-title>{{ $entryMode === 'claim_documents_on_behalf' ? 'Claim Documents On Behalf' : 'Manual Transaction Entry' }}</h1>
            <label class="form-group" style="margin:0;">
                <span class="form-label">Entry type</span>
                <select class="form-control manual-entry-mode-select" aria-label="Logbook entry type" data-entry-mode>
                    <option value="manual_transaction" @selected($entryMode === 'manual_transaction')>Manual Transaction Entry</option>
                    <option value="claim_documents_on_behalf" @selected($entryMode === 'claim_documents_on_behalf')>Claim Documents On Behalf (On Behalf of an Individual)</option>
                </select>
            </label>
        </div>

        @if($errors->any())
            <div class="manual-entry-error" role="alert">
                <p>Please correct the following before saving:</p>
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <h2 class="manual-entry-section" data-manual-heading="Personal Information" data-claim-heading="Claimant — Person Receiving Documents">{{ $entryMode === 'claim_documents_on_behalf' ? 'Claimant — Person Receiving Documents' : 'Personal Information' }}</h2>
        <div class="manual-entry-grid">
            <div class="form-group">
                <label class="form-label" for="first-name">First Name</label>
                <input class="form-control" id="first-name" name="first_name" type="text"
                       value="{{ old('first_name') }}" maxlength="60" autocomplete="given-name" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="last-name">Last Name</label>
                <input class="form-control" id="last-name" name="last_name" type="text"
                       value="{{ old('last_name') }}" maxlength="60" autocomplete="family-name" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="middle-name">Middle Name (Optional)</label>
                <input class="form-control" id="middle-name" name="middle_name" type="text"
                       value="{{ old('middle_name') }}" maxlength="60" autocomplete="additional-name">
            </div>
            <div class="form-group">
                <label class="form-label" for="name-extension">Name Extension (Optional: Jr., Sr., 1st, etc.)</label>
                <input class="form-control" id="name-extension" name="name_extension" type="text"
                       value="{{ old('name_extension') }}" maxlength="20" autocomplete="honorific-suffix">
            </div>
        </div>

        <h2 class="manual-entry-section" data-manual-heading="Person Classification" data-claim-heading="Claimant Classification">{{ $entryMode === 'claim_documents_on_behalf' ? 'Claimant Classification' : 'Person Classification' }}</h2>
        <div class="manual-entry-radio-row" role="radiogroup" aria-label="Person classification">
            @foreach(['Visitor', 'Resident', 'Personnel'] as $classification)
                <label class="manual-entry-radio">
                    <input type="radio" name="person_category" value="{{ $classification }}"
                           @checked(old('person_category', 'Visitor') === $classification) required>
                    {{ $classification }}
                </label>
            @endforeach
        </div>

        <h2 class="manual-entry-section" data-manual-heading="Address Information" data-claim-heading="Claimant Address Information">{{ $entryMode === 'claim_documents_on_behalf' ? 'Claimant Address Information' : 'Address Information' }}</h2>
        <label class="manual-entry-checkbox">
            <input type="checkbox" name="is_international_address" value="1"
                   @checked($internationalAddress) data-international-address>
            <span>Other — Check this if your address is outside the Philippines.</span>
        </label>

        <div data-domestic-address @if($internationalAddress) hidden @endif>
            <div class="manual-entry-grid">
                <div class="form-group manual-entry-full">
                    <label class="form-label" for="region-code">Region</label>
                    <select class="form-control" id="region-code" name="region_code" required data-address-level="region">
                        <option value="">Loading regions…</option>
                    </select>
                </div>
                <div class="form-group manual-entry-full">
                    <label class="form-label" for="province-code">Province</label>
                    <select class="form-control" id="province-code" name="province_code" required disabled data-address-level="province">
                        <option value="">Select a region first</option>
                    </select>
                </div>
                <div class="form-group manual-entry-full">
                    <label class="form-label" for="city-code">City / Municipality</label>
                    <select class="form-control" id="city-code" name="city_code" required disabled data-address-level="city">
                        <option value="">Select a province first</option>
                    </select>
                </div>
                <div class="form-group manual-entry-full">
                    <label class="form-label" for="barangay-code">Barangay</label>
                    <select class="form-control" id="barangay-code" name="barangay_code" required disabled data-address-level="barangay">
                        <option value="">Select a city / municipality first</option>
                    </select>
                </div>
                <div class="form-group manual-entry-full">
                    <label class="form-label" for="purok-street">Purok / Street / House No.</label>
                    <input class="form-control" id="purok-street" name="purok_street" type="text"
                           value="{{ old('purok_street') }}" maxlength="100" autocomplete="street-address">
                </div>
            </div>
        </div>
        <div class="form-group" data-international-address-fields @if(!$internationalAddress) hidden @endif>
            <label class="form-label" for="address-other">International / Other Address</label>
            <textarea class="form-control" id="address-other" name="address_other" rows="3" maxlength="255"
                      @required($internationalAddress)>{{ old('address_other') }}</textarea>
        </div>

        <h2 class="manual-entry-section" data-manual-heading="Contact Number" data-claim-heading="Claimant Contact Number">{{ $entryMode === 'claim_documents_on_behalf' ? 'Claimant Contact Number' : 'Contact Number' }}</h2>
        <div class="manual-entry-contact">
            <select class="form-control" name="country_code" aria-label="Contact country code" data-country-code>
                @foreach($contactCountryCodes as $code => $country)
                    <option value="{{ $code }}" @selected(old('country_code', '+63') === $code)>{{ $country }} ({{ $code }})</option>
                @endforeach
            </select>
            <input class="form-control" type="tel" name="contact_number" value="{{ old('contact_number') }}"
                   placeholder="09266952238" inputmode="numeric" pattern="[0-9]{11}" maxlength="11"
                   aria-describedby="manual-contact-help" required data-contact-number>
        </div>
        <small id="manual-contact-help" data-contact-help>Enter exactly 11 digits, including the leading 0.</small>

        <section data-manual-purpose-fields @if($entryMode !== 'manual_transaction') hidden @endif>
            <h2 class="manual-entry-section">Purpose of Visit</h2>
            <div class="form-group">
                <label class="form-label" for="purpose-choice">Purpose of Visit / Transaction</label>
                <select class="form-control" id="purpose-choice" name="purpose_choice" required data-purpose-choice>
                    @foreach($purposeChoices as $choice)
                        <option value="{{ $choice }}" @selected($purposeChoice === $choice)>{{ $choice }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" data-purpose-other-group @if($purposeChoice !== 'Other') hidden @endif>
                <label class="form-label" for="purpose-other">Other Purpose</label>
                <input class="form-control" id="purpose-other" name="purpose_other" type="text"
                       value="{{ $purposeOther }}" maxlength="5000" @required($purposeChoice === 'Other') data-purpose-other>
            </div>
        </section>

        <section class="manual-entry-claim-fields" data-claim-fields @if($entryMode !== 'claim_documents_on_behalf') hidden @endif>
            <h2 class="manual-entry-section" style="margin-top:0;">Document Owner — Person on Whose Behalf</h2>
            <div class="manual-entry-grid">
                <div class="form-group">
                    <label class="form-label" for="document-owner-first-name">First Name</label>
                    <input class="form-control" id="document-owner-first-name" name="document_owner_first_name" type="text"
                           value="{{ old('document_owner_first_name') }}" maxlength="60" autocomplete="off" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="document-owner-last-name">Last Name</label>
                    <input class="form-control" id="document-owner-last-name" name="document_owner_last_name" type="text"
                           value="{{ old('document_owner_last_name') }}" maxlength="60" autocomplete="off" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="document-owner-middle-name">Middle Name (Optional)</label>
                    <input class="form-control" id="document-owner-middle-name" name="document_owner_middle_name" type="text"
                           value="{{ old('document_owner_middle_name') }}" maxlength="60" autocomplete="off">
                </div>
                <div class="form-group">
                    <label class="form-label" for="document-owner-name-extension">Name Extension (Optional)</label>
                    <input class="form-control" id="document-owner-name-extension" name="document_owner_name_extension" type="text"
                           value="{{ old('document_owner_name_extension') }}" maxlength="20" autocomplete="off">
                </div>
            </div>
            <h3 class="manual-entry-section">Document Owner Classification</h3>
            <div class="manual-entry-radio-row" role="radiogroup" aria-label="Document owner classification">
                @foreach(['Visitor', 'Resident', 'Personnel'] as $classification)
                    <label class="manual-entry-radio">
                        <input type="radio" name="represented_person_classification" value="{{ $classification }}"
                               @checked(old('represented_person_classification', 'Resident') === $classification) required>
                        {{ $classification }}
                    </label>
                @endforeach
            </div>

            <h2 class="manual-entry-section">Document(s) Being Claimed</h2>
            <div class="form-group">
                <label class="form-label" for="claim-document">Document type</label>
                <select class="form-control" id="claim-document" name="claim_document" required data-claim-document>
                    @foreach(['Request for Barangay Clearance', 'Request for Certificate of Indigency', 'Request for Certificate of Residency', 'Business Permit Clearance', 'Other'] as $document)
                        <option value="{{ $document }}" @selected(old('claim_document', 'Request for Barangay Clearance') === $document)>{{ $document }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" data-claim-document-other-group @if(old('claim_document') !== 'Other') hidden @endif>
                <label class="form-label" for="claim-document-other">Other document</label>
                <input class="form-control" id="claim-document-other" name="claim_document_other" type="text"
                       value="{{ old('claim_document_other') }}" maxlength="255" @required(old('claim_document') === 'Other') data-claim-document-other>
            </div>
            <div class="form-group">
                <label class="form-label" for="additional-notes">Additional Notes (Optional)</label>
                <textarea class="form-control" id="additional-notes" name="additional_notes" rows="3" maxlength="5000">{{ old('additional_notes') }}</textarea>
            </div>

            <div class="manual-entry-verification">
                <strong>Verification Required Before Document Release</strong>
                <label class="manual-entry-checkbox">
                    <input type="checkbox" name="authorization_letter_verified" value="1"
                           @checked(old('authorization_letter_verified')) required>
                    <span>Authorization letter presented and verified</span>
                </label>
                <label class="manual-entry-checkbox" style="margin-bottom:0;">
                    <input type="checkbox" name="valid_id_verified" value="1"
                           @checked(old('valid_id_verified')) required>
                    <span>Valid IDs verified (claimant and document owner as applicable)</span>
                </label>
            </div>
        </div>

        <div class="manual-entry-actions">
            <button class="btn manual-entry-save {{ $entryMode === 'claim_documents_on_behalf' ? 'manual-entry-claim-save' : '' }}" type="submit" data-manual-submit>{{ $entryMode === 'claim_documents_on_behalf' ? 'Save Document Claim Entry' : 'Save Logbook Entry' }}</button>
            <a class="btn btn-danger manual-entry-cancel" href="{{ route('admin.barangay-logbook.logs') }}">Cancel</a>
        </div>
    </div>
</form>

<script>
    (() => {
        const form = document.querySelector('[data-manual-entry-form]');
        if (!form) return;

        const regionSelect = form.querySelector('[data-address-level="region"]');
        const provinceSelect = form.querySelector('[data-address-level="province"]');
        const citySelect = form.querySelector('[data-address-level="city"]');
        const barangaySelect = form.querySelector('[data-address-level="barangay"]');
        const domesticFields = [regionSelect, provinceSelect, citySelect, barangaySelect];
        const internationalCheckbox = form.querySelector('[data-international-address]');
        const domesticAddress = form.querySelector('[data-domestic-address]');
        const internationalFields = form.querySelector('[data-international-address-fields]');
        const internationalInput = form.querySelector('#address-other');
        const submitButton = form.querySelector('[data-manual-submit]');
        const contactCode = form.querySelector('[data-country-code]');
        const contactInput = form.querySelector('[data-contact-number]');
        const contactHelp = form.querySelector('[data-contact-help]');
        const purposeSelect = form.querySelector('[data-purpose-choice]');
        const otherPurposeGroup = form.querySelector('[data-purpose-other-group]');
        const otherPurposeInput = form.querySelector('[data-purpose-other]');
        const entryModeSelect = form.querySelector('[data-entry-mode]');
        const entryModeValue = form.querySelector('[data-entry-mode-value]');
        const entryModeTitle = form.querySelector('[data-entry-mode-title]');
        const manualPurposeFields = form.querySelector('[data-manual-purpose-fields]');
        const claimFields = form.querySelector('[data-claim-fields]');
        const claimDocumentSelect = form.querySelector('[data-claim-document]');
        const claimDocumentOtherGroup = form.querySelector('[data-claim-document-other-group]');
        const claimDocumentOtherInput = form.querySelector('[data-claim-document-other]');
        const saveButton = form.querySelector('[data-manual-submit]');
        const defaultCodes = @json($defaultAddressCodes);

        const setPanelEnabled = (panel, enabled) => {
            panel.querySelectorAll('input, select, textarea').forEach((input) => {
                input.disabled = !enabled;
            });
        };
        const updateEntryMode = () => {
            const isClaim = entryModeSelect.value === 'claim_documents_on_behalf';
            entryModeValue.value = entryModeSelect.value;
            entryModeTitle.textContent = isClaim ? 'Claim Documents On Behalf' : 'Manual Transaction Entry';
            form.classList.toggle('is-claim-mode', isClaim);
            manualPurposeFields.hidden = isClaim;
            claimFields.hidden = !isClaim;
            setPanelEnabled(manualPurposeFields, !isClaim);
            setPanelEnabled(claimFields, isClaim);
            form.querySelectorAll('[data-manual-heading]').forEach((heading) => {
                heading.textContent = isClaim ? heading.dataset.claimHeading : heading.dataset.manualHeading;
            });
            saveButton.textContent = isClaim ? 'Save Document Claim Entry' : 'Save Logbook Entry';
            saveButton.classList.toggle('manual-entry-claim-save', isClaim);
            updateClaimDocument();
        };
        const updateAddressMode = () => {
            const isInternational = internationalCheckbox.checked;
            domesticAddress.hidden = isInternational;
            internationalFields.hidden = !isInternational;
            internationalInput.required = isInternational;
            domesticFields.forEach((select) => {
                select.required = !isInternational;
                select.disabled = isInternational || select.options.length < 2;
            });
        };
        internationalCheckbox.addEventListener('change', updateAddressMode);

        const setOptions = (select, items, placeholder, selectedCode = '') => {
            select.replaceChildren(new Option(placeholder, ''));
            items.forEach((item) => {
                const option = new Option(item.label, item.code);
                select.add(option);
            });
            select.disabled = items.length === 0;
            if (selectedCode && items.some((item) => item.code === selectedCode)) {
                select.value = selectedCode;
            }
        };

        const loadLocations = async () => {
            submitButton.disabled = true;
            try {
                const response = await fetch(@json(asset('data/philippine-geography-2024.json')), {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                });
                if (!response.ok) throw new Error(`Local geographic dataset request failed (${response.status}).`);

                const data = await response.json();
                if (!Array.isArray(data.regions) || !Array.isArray(data.provinces)
                    || !Array.isArray(data.cities) || !Array.isArray(data.barangays)) {
                    throw new Error('The local geographic dataset is invalid.');
                }

                const fillBarangays = (cityCode, selectedCode = '') => setOptions(
                    barangaySelect,
                    data.barangays
                        .filter((barangay) => barangay.cityMunCode === cityCode)
                        .map((barangay) => ({ code: barangay.code, label: barangay.name })),
                    'Select a barangay',
                    selectedCode,
                );
                const fillCities = (provinceCode, selectedCode = '') => {
                    const cities = data.cities
                        .filter((city) => city.province === provinceCode)
                        .map((city) => ({ code: city.code, label: city.name }));
                    setOptions(citySelect, cities, 'Select a city / municipality', selectedCode);
                    fillBarangays(citySelect.value, '');
                };
                const fillProvinces = (regionCode, selectedProvince = '', selectedCity = '', selectedBarangay = '') => {
                    const provinces = data.provinces
                        .filter((province) => province.region === regionCode)
                        .map((province) => ({ code: province.code, label: province.name }));
                    setOptions(provinceSelect, provinces, 'Select a province', selectedProvince);
                    fillCities(provinceSelect.value, selectedCity);
                    fillBarangays(citySelect.value, selectedBarangay);
                };
                const regions = data.regions.map((region) => ({
                    code: region.code,
                    label: `${region.designation} (${region.name})`
                }));
                setOptions(regionSelect, regions, 'Select a region', defaultCodes.region);
                fillProvinces(
                    regionSelect.value,
                    defaultCodes.province,
                    defaultCodes.city,
                    defaultCodes.barangay,
                );

                regionSelect.addEventListener('change', () => fillProvinces(regionSelect.value));
                provinceSelect.addEventListener('change', () => fillCities(provinceSelect.value));
                citySelect.addEventListener('change', () => fillBarangays(citySelect.value));
                updateAddressMode();
                submitButton.disabled = false;
            } catch (error) {
                console.error('Unable to load the local Philippine geographic dataset:', error);
                const errorNotice = document.createElement('p');
                errorNotice.className = 'manual-entry-error';
                errorNotice.setAttribute('role', 'alert');
                errorNotice.textContent = 'Address options could not be loaded. Please contact an administrator.';
                form.querySelector('.manual-entry-title').after(errorNotice);
                submitButton.disabled = true;
            }
        };

        const updateContactRules = () => {
            const philippines = contactCode.value === '+63';
            contactInput.maxLength = philippines ? 11 : 15;
            contactInput.pattern = philippines ? '[0-9]{11}' : '[0-9]{4,15}';
            contactInput.placeholder = philippines ? '09266952238' : 'Digits only';
            contactHelp.textContent = philippines
                ? 'Enter exactly 11 digits, including the leading 0.'
                : 'Enter 4 to 15 digits, without spaces or symbols.';
        };
        contactInput.addEventListener('input', () => {
            contactInput.value = contactInput.value.replace(/\D/g, '').slice(0, contactInput.maxLength);
        });
        contactCode.addEventListener('change', updateContactRules);
        updateContactRules();

        const updatePurpose = () => {
            const isOther = purposeSelect.value === 'Other';
            otherPurposeGroup.hidden = !isOther;
            otherPurposeInput.required = isOther;
            otherPurposeInput.disabled = !isOther;
        };
        const updateClaimDocument = () => {
            const isOther = claimDocumentSelect.value === 'Other';
            claimDocumentOtherGroup.hidden = !isOther;
            claimDocumentOtherInput.required = isOther && entryModeSelect.value === 'claim_documents_on_behalf';
            claimDocumentOtherInput.disabled = !isOther || entryModeSelect.value !== 'claim_documents_on_behalf';
        };
        entryModeSelect.addEventListener('change', updateEntryMode);
        claimDocumentSelect.addEventListener('change', updateClaimDocument);
        purposeSelect.addEventListener('change', updatePurpose);
        updatePurpose();
        updateEntryMode();
        updateAddressMode();
        loadLocations();
    })();
</script>
