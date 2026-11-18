<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    protected $table = 'attendances';

    protected $fillable = [
        'user_id',
        'attendance_date',
        'time_in',
        'time_out',
        'total_hours',
        'remarks',
    ];

    protected static function booted(): void
    {
        static::saving(function (Attendance $attendance): void {
            $attendance->recalculateTotalHours();
        });
    }

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'time_in' => 'datetime',
            'time_out' => 'datetime',
            'total_hours' => 'decimal:2',
        ];
    }

    public function recalculateTotalHours(): void
    {
        if ($this->time_in === null || $this->time_out === null) {
            $this->total_hours = 0;

            return;
        }

        $timeOut = $this->time_out->copy();
        if ($timeOut->lessThanOrEqualTo($this->time_in)) {
            $timeOut->addDay();
        }

        $this->total_hours = round($this->time_in->diffInMinutes($timeOut) / 60, 2);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
