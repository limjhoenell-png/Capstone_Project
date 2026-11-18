<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\User;
use App\Support\AttendanceTimeWindow;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceRecordingService
{
    public function saveDaily(array $data): Attendance
    {
        for ($attempt = 0; $attempt < 2; $attempt++) {
            try {
                return DB::transaction(function () use ($data): Attendance {
                    $attendance = Attendance::query()
                        ->where('user_id', $data['user_id'])
                        ->where('attendance_date', $data['attendance_date'])
                        ->lockForUpdate()
                        ->first();

                    if ($attendance === null) {
                        $attendance = new Attendance([
                            'user_id' => $data['user_id'],
                            'attendance_date' => $data['attendance_date'],
                        ]);
                    }

                    foreach (['time_in', 'time_out', 'remarks'] as $field) {
                        if (array_key_exists($field, $data)) {
                            $attendance->{$field} = $data[$field] ?? null;
                        }
                    }

                    $attendance->recalculateTotalHours();
                    $attendance->save();

                    return $attendance;
                }, 3);
            } catch (UniqueConstraintViolationException $exception) {
                if ($attempt === 1) {
                    throw $exception;
                }
            }
        }

        throw new \RuntimeException('Unable to save the daily attendance record.');
    }

    public function recordPunch(User $employee, Carbon $punchedAt): Attendance
    {
        for ($attempt = 0; $attempt < 2; $attempt++) {
            try {
                return DB::transaction(function () use ($employee, $punchedAt): Attendance {
                    $attendance = Attendance::query()
                        ->where('user_id', $employee->id)
                        ->whereDate('attendance_date', $punchedAt->toDateString())
                        ->lockForUpdate()
                        ->first();

                    if ($attendance === null) {
                        if (! AttendanceTimeWindow::allows($punchedAt)) {
                            throw ValidationException::withMessages([
                                'punched_at' => 'Time-In must be between 05:00 AM and 04:59 PM.',
                            ]);
                        }

                        $attendance = new Attendance([
                            'user_id' => $employee->id,
                            'attendance_date' => $punchedAt->toDateString(),
                            'time_in' => $punchedAt,
                        ]);
                    } elseif ($attendance->time_in === null) {
                        if (! AttendanceTimeWindow::allows($punchedAt)) {
                            throw ValidationException::withMessages([
                                'punched_at' => 'Time-In must be between 05:00 AM and 04:59 PM.',
                            ]);
                        }

                        $attendance->time_in = $punchedAt;
                    } elseif ($attendance->time_out === null) {
                        $attendance->time_out = $punchedAt;
                    } else {
                        throw ValidationException::withMessages([
                            'punch' => 'This employee already has both punches recorded for that date.',
                        ]);
                    }

                    $attendance->recalculateTotalHours();
                    $attendance->save();

                    return $attendance;
                }, 3);
            } catch (UniqueConstraintViolationException $exception) {
                if ($attempt === 1) {
                    throw $exception;
                }
            }
        }

        throw new \RuntimeException('Unable to save the attendance punch.');
    }
}
