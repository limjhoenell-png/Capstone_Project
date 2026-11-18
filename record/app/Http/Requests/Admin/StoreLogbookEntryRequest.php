<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreLogbookEntryRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'transaction_type' => $this->input('transaction_type', 'manual_transaction'),
        ]);
    }

    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $rules = [
            'transaction_type' => ['required', 'in:manual_transaction,claim_documents_on_behalf'],
            'first_name' => ['required', 'string', 'max:60'],
            'last_name' => ['required', 'string', 'max:60'],
            'middle_name' => ['nullable', 'string', 'max:60'],
            'name_extension' => ['nullable', 'string', 'max:20'],
            'person_category' => ['required', 'in:Visitor,Resident,Personnel'],
            'is_international_address' => ['nullable', 'boolean'],
            'address_other' => ['nullable', 'required_if:is_international_address,1', 'string', 'max:255'],
            'region_code' => ['nullable', 'required_unless:is_international_address,1', 'string', 'size:2'],
            'province_code' => ['nullable', 'required_unless:is_international_address,1', 'string', 'size:4'],
            'city_code' => ['nullable', 'required_unless:is_international_address,1', 'string', 'size:6'],
            'barangay_code' => ['nullable', 'required_unless:is_international_address,1', 'string', 'size:9'],
            'purok_street' => ['nullable', 'string', 'max:100'],
            'country_code' => ['required', 'string'],
            'contact_number' => ['required', 'string'],
        ];

        if ($this->input('transaction_type') === 'claim_documents_on_behalf') {
            return array_merge($rules, [
                'purpose_choice' => ['prohibited'],
                'claim_document' => ['required', 'string', 'in:Request for Barangay Clearance,Request for Certificate of Indigency,Request for Certificate of Residency,Business Permit Clearance,Other'],
                'claim_document_other' => ['nullable', 'required_if:claim_document,Other', 'string', 'max:255'],
                'additional_notes' => ['nullable', 'string', 'max:5000'],
                'document_owner_first_name' => ['required', 'string', 'max:60'],
                'document_owner_last_name' => ['required', 'string', 'max:60'],
                'document_owner_middle_name' => ['nullable', 'string', 'max:60'],
                'document_owner_name_extension' => ['nullable', 'string', 'max:20'],
                'represented_person_classification' => ['required', 'in:Visitor,Resident,Personnel'],
                'authorization_letter_verified' => ['accepted'],
                'valid_id_verified' => ['accepted'],
            ]);
        }

        return array_merge($rules, [
            'claim_document' => ['prohibited'],
            'claim_document_other' => ['prohibited'],
            'additional_notes' => ['prohibited'],
            'document_owner_first_name' => ['prohibited'],
            'document_owner_last_name' => ['prohibited'],
            'document_owner_middle_name' => ['prohibited'],
            'document_owner_name_extension' => ['prohibited'],
            'represented_person_classification' => ['prohibited'],
            'authorization_letter_verified' => ['prohibited'],
            'valid_id_verified' => ['prohibited'],
            'purpose_choice' => ['required', 'string', 'max:255'],
            'purpose_other' => ['nullable', 'required_if:purpose_choice,Other', 'string', 'max:5000'],
        ]);
    }
}
