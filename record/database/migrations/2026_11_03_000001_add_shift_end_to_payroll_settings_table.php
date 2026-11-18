<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payroll_settings') || Schema::hasColumn('payroll_settings', 'shift_end')) {
            return;
        }

        Schema::table('payroll_settings', function (Blueprint $table): void {
            $table->time('shift_end')->default('17:00:00');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('payroll_settings') || ! Schema::hasColumn('payroll_settings', 'shift_end')) {
            return;
        }

        Schema::table('payroll_settings', function (Blueprint $table): void {
            $table->dropColumn('shift_end');
        });
    }
};
