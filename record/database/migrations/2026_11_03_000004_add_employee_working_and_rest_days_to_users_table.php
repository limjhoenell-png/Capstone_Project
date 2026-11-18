<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'working_days')) {
                $table->json('working_days')->nullable();
            }

            if (! Schema::hasColumn('users', 'employee_rest_days')) {
                $table->json('employee_rest_days')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'employee_rest_days')) {
                $table->dropColumn('employee_rest_days');
            }

            if (Schema::hasColumn('users', 'working_days')) {
                $table->dropColumn('working_days');
            }
        });
    }
};
