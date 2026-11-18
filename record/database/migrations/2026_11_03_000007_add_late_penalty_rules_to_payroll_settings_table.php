<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payroll_settings')) {
            return;
        }

        Schema::table('payroll_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('payroll_settings', 'late_penalty_increment_minutes')) {
                $table->unsignedSmallInteger('late_penalty_increment_minutes')->default(5);
            }
            if (! Schema::hasColumn('payroll_settings', 'late_penalty_amount')) {
                $table->decimal('late_penalty_amount', 12, 2)->default(0);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('payroll_settings')) {
            return;
        }

        Schema::table('payroll_settings', function (Blueprint $table): void {
            $columns = [];
            if (Schema::hasColumn('payroll_settings', 'late_penalty_increment_minutes')) {
                $columns[] = 'late_penalty_increment_minutes';
            }
            if (Schema::hasColumn('payroll_settings', 'late_penalty_amount')) {
                $columns[] = 'late_penalty_amount';
            }
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
