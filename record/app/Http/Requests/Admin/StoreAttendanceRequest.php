<?php

namespace App\Http\Requests\Admin;

use App\Support\AttendanceTimeWindow;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class StoreAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'attendance_date' => ['required', 'date'],
            'time_in' => [
                'bail',
                'nullable',
                'date',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value !== null && ! AttendanceTimeWindow::allows(Carbon::parse($value))) {
                        $fail('Time-In must be between 05:00 AM and 04:59 PM.');
                    }
                },
            ],
            'time_out' => ['nullable', 'date', 'after_or_equal:time_in'],
            'remarks' => ['nullable', 'string', 'max:255'],
        ];
    }
}
