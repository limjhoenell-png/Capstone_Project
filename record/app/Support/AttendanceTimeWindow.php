<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class AttendanceTimeWindow
{
    public const MAX_PAYABLE_HOURS = 8.0;

    public const MAX_PAYABLE_MINUTES = 480;

    public static function allows(string|CarbonInterface $timeIn): bool
    {
        $timeIn = $timeIn instanceof CarbonInterface ? $timeIn : Carbon::parse($timeIn);
        $clock = $timeIn->format('H:i:s');

        return $clock >= '05:00:00' && $clock < '17:00:00';
    }
}
