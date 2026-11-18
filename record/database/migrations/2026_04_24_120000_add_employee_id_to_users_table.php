<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'employee_id')) {
                $table->unsignedInteger('employee_id')->nullable()->index()->after('id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'employee_id')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('employee_id');
        });
    }
};
