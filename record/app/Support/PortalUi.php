<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Blade-friendly formatters for portal tables (legacy MySQL datetimes can break Carbon::parse()).
 */
final class PortalUi
{
    public static function dt(mixed $value, string $format = 'Y-m-d h:i:s A'): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if ($value instanceof \DateTimeInterface) {
            try {
                return Carbon::parse($value)->format($format);
            } catch (\Throwable) {
                return '—';
            }
        }

        try {
            return Carbon::parse((string) $value)->format($format);
        } catch (\Throwable) {
            return '—';
        }
    }

    /** @param  Carbon|string|null  $value */
    public static function calendarDate($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if ($value instanceof \DateTimeInterface) {
            try {
                return Carbon::parse($value)->format('Y-m-d');
            } catch (\Throwable) {
                return '';
            }
        }

        try {
            return Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Throwable) {
            return '';
        }
    }
}
