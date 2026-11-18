<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLogbookEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'entry_at' => ['required', 'date'],
            'purpose_of_transaction' => ['required', 'string', 'max:5000'],
            'contact_number' => ['required', 'string'],
        ];
    }
}
