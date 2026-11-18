<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('attendances')) {
            return;
        }

        if (! Schema::hasColumn('attendances', 'total_hours')) {
            Schema::table('attendances', function (Blueprint $table): void {
                $table->decimal('total_hours', 8, 2)->default(0);
            });
        }

        $duplicateDays = DB::table('attendances')
            ->select('user_id', 'attendance_date')
            ->groupBy('user_id', 'attendance_date')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicateDays as $duplicateDay) {
            $rows = DB::table('attendances')
                ->where('user_id', $duplicateDay->user_id)
                ->where('attendance_date', $duplicateDay->attendance_date)
                ->orderBy('id')
                ->get();
            $primary = $rows->first();
            $timeIns = $rows->pluck('time_in')->filter();
            $timeOuts = $rows->pluck('time_out')->filter();
            $timeIn = $timeIns->sort()->first();
            $timeOut = $timeOuts->sort()->last();
            $remarks = $rows->pluck('remarks')
                ->filter(fn ($remark): bool => trim((string) $remark) !== '')
                ->unique()
                ->implode(' | ');
            $hours = 0;
            if ($timeIn !== null && $timeOut !== null) {
                $start = Carbon::parse($timeIn);
                $end = Carbon::parse($timeOut);
                if ($end->lessThanOrEqualTo($start)) {
                    $end->addDay();
                }
                $hours = round($start->diffInMinutes($end) / 60, 2);
            }

            DB::table('attendances')->where('id', $primary->id)->update([
                'time_in' => $timeIn,
                'time_out' => $timeOut,
                'total_hours' => $hours,
                'remarks' => $remarks !== '' ? $remarks : null,
            ]);

            DB::table('attendances')
                ->where('user_id', $duplicateDay->user_id)
                ->where('attendance_date', $duplicateDay->attendance_date)
                ->where('id', '!=', $primary->id)
                ->delete();
        }

        DB::table('attendances')
            ->select('id', 'time_in', 'time_out')
            ->orderBy('id')
            ->chunkById(500, function ($rows): void {
                foreach ($rows as $row) {
                    $hours = 0;
                    if ($row->time_in !== null && $row->time_out !== null) {
                        $start = Carbon::parse($row->time_in);
                        $end = Carbon::parse($row->time_out);
                        if ($end->lessThanOrEqualTo($start)) {
                            $end->addDay();
                        }
                        $hours = round($start->diffInMinutes($end) / 60, 2);
                    }

                    DB::table('attendances')->where('id', $row->id)->update(['total_hours' => $hours]);
                }
            });

        if (! Schema::hasIndex('attendances', ['user_id', 'attendance_date'])) {
            Schema::table('attendances', function (Blueprint $table): void {
                $table->unique(['user_id', 'attendance_date'], 'attendances_user_date_unique');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('attendances')) {
            return;
        }

        if (Schema::hasIndex('attendances', ['user_id', 'attendance_date'])) {
            Schema::table('attendances', function (Blueprint $table): void {
                $table->dropUnique('attendances_user_date_unique');
            });
        }

        if (Schema::hasColumn('attendances', 'total_hours')) {
            Schema::table('attendances', function (Blueprint $table): void {
                $table->dropColumn('total_hours');
            });
        }
    }
};
