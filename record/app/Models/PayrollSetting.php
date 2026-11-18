<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollSetting extends Model
{
    protected $fillable = [
        'hours_per_full_day',
        'overtime_multiplier',
        'shift_start',
        'shift_end',
        'grace_minutes',
        'late_penalty_increment_minutes',
        'late_penalty_amount',
        'rest_days',
        'rest_day_multiplier',
        'regular_holiday_multiplier',
        'special_holiday_multiplier',
        'apply_statutory',
    ];

    protected function casts(): array
    {
        return [
            'hours_per_full_day' => 'decimal:2',
            'overtime_multiplier' => 'decimal:2',
            'grace_minutes' => 'integer',
            'late_penalty_increment_minutes' => 'integer',
            'late_penalty_amount' => 'decimal:2',
            'rest_day_multiplier' => 'decimal:2',
            'regular_holiday_multiplier' => 'decimal:2',
            'special_holiday_multiplier' => 'decimal:2',
            'apply_statutory' => 'boolean',
        ];
    }

    /**
     * @return list<int>
     */
    public function restDayNumbers(): array
    {
        $parts = preg_split('/\s*,\s*/', (string) ($this->rest_days ?? '0')) ?: [];
        $days = [];
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            $days[] = (int) $part;
        }

        return $days === [] ? [0] : $days;
    }

    public static function current(): self
    {
        $row = static::query()->first();
        if ($row !== null) {
            return $row;
        }

        /** @var self $created */
        $created = static::query()->create([
            'hours_per_full_day' => 8,
            'overtime_multiplier' => 1.25,
            'shift_start' => '08:00:00',
            'shift_end' => '17:00:00',
            'grace_minutes' => 15,
            'late_penalty_increment_minutes' => 5,
            'late_penalty_amount' => 0,
            'rest_days' => '0',
            'rest_day_multiplier' => 1.30,
            'regular_holiday_multiplier' => 2.00,
            'special_holiday_multiplier' => 1.30,
            'apply_statutory' => false,
        ]);

        return $created;
    }
}
