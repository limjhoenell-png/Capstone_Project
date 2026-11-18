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
        if (! Schema::hasTable('attendance_log')) {
            return;
        }

        foreach (['id', 'user_id', 'entry_type', 'log_time'] as $column) {
            if (! Schema::hasColumn('attendance_log', $column)) {
                throw new RuntimeException("Cannot archive attendance_log: missing {$column} column.");
            }
        }

        if (! Schema::hasTable('attendances')) {
            throw new RuntimeException('Cannot drop attendance_log because attendances does not exist.');
        }

        if (! Schema::hasTable('attendance_log_archive')) {
            Schema::create('attendance_log_archive', function (Blueprint $table): void {
                $table->integer('id')->primary();
                $table->integer('user_id');
                $table->string('entry_type', 10);
                $table->dateTime('log_time');
                $table->timestamp('archived_at')->useCurrent();
            });
        }

        DB::table('attendance_log')
            ->select(['id', 'user_id', 'entry_type', 'log_time'])
            ->orderBy('id')
            ->chunkById(500, function ($rows): void {
                $archiveRows = $rows->map(fn (object $row): array => [
                    'id' => $row->id,
                    'user_id' => $row->user_id,
                    'entry_type' => $row->entry_type,
                    'log_time' => $row->log_time,
                    'archived_at' => now(),
                ])->all();

                DB::table('attendance_log_archive')->insertOrIgnore($archiveRows);
            });

        $employees = DB::table('users')
            ->where('role', 'employee')
            ->get(['id', 'employee_id']);
        $byUserId = $employees->keyBy(fn (object $employee): string => (string) $employee->id);
        $byEmployeeId = $employees
            ->filter(fn (object $employee): bool => $employee->employee_id !== null && $employee->employee_id !== '')
            ->keyBy(fn (object $employee): string => (string) $employee->employee_id);

        $dailyLogs = DB::table('attendance_log')
            ->select('user_id')
            ->selectRaw('DATE(log_time) AS attendance_date')
            ->selectRaw("MIN(CASE WHEN UPPER(entry_type) = 'IN' THEN log_time END) AS time_in")
            ->selectRaw("MAX(CASE WHEN UPPER(entry_type) = 'OUT' THEN log_time END) AS time_out")
            ->groupBy('user_id')
            ->groupByRaw('DATE(log_time)')
            ->get();

        foreach ($dailyLogs as $dailyLog) {
            if ($dailyLog->time_in === null && $dailyLog->time_out === null) {
                continue;
            }

            $rawUserId = (string) $dailyLog->user_id;
            $userById = $byUserId->get($rawUserId);
            $userByEmployeeId = $byEmployeeId->get($rawUserId);

            if ($userById !== null && $userByEmployeeId !== null && $userById->id !== $userByEmployeeId->id) {
                throw new RuntimeException("Cannot safely map attendance_log user_id {$rawUserId} to one employee.");
            }

            $employee = $userByEmployeeId ?? $userById;
            if ($employee === null) {
                continue;
            }

            $attendance = DB::table('attendances')
                ->where('user_id', $employee->id)
                ->where('attendance_date', $dailyLog->attendance_date)
                ->first();

            $timeIn = $attendance?->time_in ?? $dailyLog->time_in;
            $timeOut = $attendance?->time_out ?? $dailyLog->time_out;
            $hours = 0;

            if ($timeIn !== null && $timeOut !== null) {
                $start = Carbon::parse($timeIn);
                $end = Carbon::parse($timeOut);
                if ($end->lessThanOrEqualTo($start)) {
                    $end->addDay();
                }
                $hours = round($start->diffInMinutes($end) / 60, 2);
            }

            $values = [
                'time_in' => $timeIn,
                'time_out' => $timeOut,
                'total_hours' => $hours,
                'updated_at' => now(),
            ];

            if ($attendance === null) {
                DB::table('attendances')->insert($values + [
                    'user_id' => $employee->id,
                    'attendance_date' => $dailyLog->attendance_date,
                    'remarks' => null,
                    'created_at' => now(),
                ]);
            } else {
                DB::table('attendances')->where('id', $attendance->id)->update($values);
            }
        }

        Schema::drop('attendance_log');
    }

    public function down(): void
    {
        if (! Schema::hasTable('attendance_log')) {
            Schema::create('attendance_log', function (Blueprint $table): void {
                $table->increments('id');
                $table->integer('user_id');
                $table->string('entry_type', 10);
                $table->dateTime('log_time')->useCurrent();
            });
        }

        if (! Schema::hasTable('attendance_log_archive')) {
            return;
        }

        DB::table('attendance_log_archive')
            ->select(['id', 'user_id', 'entry_type', 'log_time'])
            ->orderBy('id')
            ->chunkById(500, function ($rows): void {
                DB::table('attendance_log')->insertOrIgnore(
                    $rows->map(fn (object $row): array => [
                        'id' => $row->id,
                        'user_id' => $row->user_id,
                        'entry_type' => $row->entry_type,
                        'log_time' => $row->log_time,
                    ])->all()
                );
            });

        Schema::drop('attendance_log_archive');
    }
};
