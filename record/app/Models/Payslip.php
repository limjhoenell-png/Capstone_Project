<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payslip extends Model
{
    protected $fillable = [
        'user_id',
        'period_start',
        'period_end',
        'days_worked',
        'total_hours',
        'regular_hours',
        'overtime_hours',
        'equivalent_days',
        'pay_rate_type',
        'pay_rate_amount',
        'gross_pay',
        'regular_pay',
        'overtime_pay',
        'deductions_total',
        'deduction_items',
        'earning_items',
        'net_pay',
        'employee_display_name',
        'employee_identifier',
        'processed_at',
        'status',
        'kind',
        'night_diff_hours',
        'night_diff_pay',
        'holiday_pay',
        'rest_day_pay',
        'undertime_hours',
        'approved_at',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'total_hours' => 'decimal:4',
            'regular_hours' => 'decimal:4',
            'overtime_hours' => 'decimal:4',
            'equivalent_days' => 'decimal:4',
            'pay_rate_amount' => 'decimal:2',
            'gross_pay' => 'decimal:2',
            'regular_pay' => 'decimal:2',
            'overtime_pay' => 'decimal:2',
            'deductions_total' => 'decimal:2',
            'net_pay' => 'decimal:2',
            'deduction_items' => 'array',
            'earning_items' => 'array',
            'processed_at' => 'datetime',
            'night_diff_hours' => 'decimal:4',
            'night_diff_pay' => 'decimal:2',
            'holiday_pay' => 'decimal:2',
            'rest_day_pay' => 'decimal:2',
            'undertime_hours' => 'decimal:4',
            'approved_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function isVisibleToEmployee(): bool
    {
        return $this->status === null || in_array($this->status, ['approved', 'paid'], true);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
