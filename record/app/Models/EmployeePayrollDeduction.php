<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeePayrollDeduction extends Model
{
    public const CALCULATION_FIXED = 'fixed_amount';

    public const CALCULATION_PERCENT_GROSS = 'percent_gross';

    /**
     * Preset labels for datalist / UI hints (not enforced in DB).
     *
     * @return array<string, string>
     */
    public static function commonDeductionNames(): array
    {
        return [
            'sss' => 'SSS (Social Security System)',
            'philhealth' => 'PhilHealth',
            'pagibig' => 'Pag-IBIG (HDMF)',
            'withholding_tax' => 'Withholding Tax',
        ];
    }

    protected $fillable = [
        'user_id',
        'name',
        'calculation_type',
        'value',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPercentOfGross(): bool
    {
        return $this->calculation_type === self::CALCULATION_PERCENT_GROSS;
    }
}
