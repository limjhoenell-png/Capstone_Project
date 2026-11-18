<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payroll_settings')) {
            return;
        }

        Schema::table('payroll_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('payroll_settings', 'overtime_multiplier')) {
                $table->decimal('overtime_multiplier', 5, 2)->default(1.5)->after('hours_per_full_day');
            }
        });

        DB::table('payroll_settings')
            ->whereNull('overtime_multiplier')
            ->update(['overtime_multiplier' => 1.5]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('payroll_settings') || ! Schema::hasColumn('payroll_settings', 'overtime_multiplier')) {
            return;
        }

        Schema::table('payroll_settings', function (Blueprint $table): void {
            $table->dropColumn('overtime_multiplier');
        });
    }
};

