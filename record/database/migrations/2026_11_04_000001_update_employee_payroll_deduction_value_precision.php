<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('employee_payroll_deductions')
            && Schema::hasColumn('employee_payroll_deductions', 'value')) {
            DB::table('employee_payroll_deductions')->update([
                'value' => DB::raw('ROUND(value, 2)'),
            ]);

            Schema::table('employee_payroll_deductions', function (Blueprint $table): void {
                $table->decimal('value', 12, 2)->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('employee_payroll_deductions')
            && Schema::hasColumn('employee_payroll_deductions', 'value')) {
            Schema::table('employee_payroll_deductions', function (Blueprint $table): void {
                $table->decimal('value', 12, 4)->change();
            });
        }
    }
};
