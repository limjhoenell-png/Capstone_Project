<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->extendUsers();
        $this->extendPayrollSettings();
        $this->extendPayslips();
        $this->createCalendarTables();
    }

    public function down(): void
    {
    }

    private function extendUsers(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'full_name')) {
                $table->string('full_name')->nullable();
            }
            if (! Schema::hasColumn('users', 'department')) {
                $table->string('department')->nullable();
            }
        });
    }

    private function extendPayrollSettings(): void
    {
        if (! Schema::hasTable('payroll_settings')) {
            return;
        }

        Schema::table('payroll_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('payroll_settings', 'shift_start')) {
                $table->time('shift_start')->default('09:00:00');
            }
            if (! Schema::hasColumn('payroll_settings', 'grace_minutes')) {
                $table->unsignedSmallInteger('grace_minutes')->default(15);
            }
            if (! Schema::hasColumn('payroll_settings', 'night_start')) {
                $table->time('night_start')->default('22:00:00');
            }
            if (! Schema::hasColumn('payroll_settings', 'night_end')) {
                $table->time('night_end')->default('06:00:00');
            }
            if (! Schema::hasColumn('payroll_settings', 'night_differential_rate')) {
                $table->decimal('night_differential_rate', 5, 2)->default(0.10);
            }
            if (! Schema::hasColumn('payroll_settings', 'rest_days')) {
                $table->string('rest_days', 32)->default('0');
            }
            if (! Schema::hasColumn('payroll_settings', 'rest_day_multiplier')) {
                $table->decimal('rest_day_multiplier', 4, 2)->default(1.30);
            }
            if (! Schema::hasColumn('payroll_settings', 'regular_holiday_multiplier')) {
                $table->decimal('regular_holiday_multiplier', 4, 2)->default(2.00);
            }
            if (! Schema::hasColumn('payroll_settings', 'special_holiday_multiplier')) {
                $table->decimal('special_holiday_multiplier', 4, 2)->default(1.30);
            }
            if (! Schema::hasColumn('payroll_settings', 'apply_statutory')) {
                $table->boolean('apply_statutory')->default(false);
            }
        });
    }

    private function extendPayslips(): void
    {
        if (! Schema::hasTable('payslips')) {
            return;
        }

        Schema::table('payslips', function (Blueprint $table): void {
            if (! Schema::hasColumn('payslips', 'status')) {
                $table->string('status', 16)->nullable();
            }
            if (! Schema::hasColumn('payslips', 'kind')) {
                $table->string('kind', 32)->default('regular');
            }
            if (! Schema::hasColumn('payslips', 'night_diff_hours')) {
                $table->decimal('night_diff_hours', 12, 4)->default(0);
            }
            if (! Schema::hasColumn('payslips', 'night_diff_pay')) {
                $table->decimal('night_diff_pay', 12, 2)->default(0);
            }
            if (! Schema::hasColumn('payslips', 'holiday_pay')) {
                $table->decimal('holiday_pay', 12, 2)->default(0);
            }
            if (! Schema::hasColumn('payslips', 'rest_day_pay')) {
                $table->decimal('rest_day_pay', 12, 2)->default(0);
            }
            if (! Schema::hasColumn('payslips', 'undertime_hours')) {
                $table->decimal('undertime_hours', 12, 4)->default(0);
            }
            if (! Schema::hasColumn('payslips', 'leave_days')) {
                $table->unsignedSmallInteger('leave_days')->default(0);
            }
            if (! Schema::hasColumn('payslips', 'approved_at')) {
                $table->timestamp('approved_at')->nullable();
            }
            if (! Schema::hasColumn('payslips', 'published_at')) {
                $table->timestamp('published_at')->nullable();
            }
        });
    }

    private function createCalendarTables(): void
    {
        if (! Schema::hasTable('holidays')) {
            Schema::create('holidays', function (Blueprint $table): void {
                $table->id();
                $table->date('holiday_date')->unique();
                $table->string('name');
                $table->string('kind', 16)->default('regular');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('user_notices')) {
            Schema::create('user_notices', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('title');
                $table->text('body');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }
    }
};
