<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'employment_start_date')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->date('employment_start_date')->nullable();
            });
        }

        DB::table('users')
            ->where('role', 'employee')
            ->whereNull('employment_start_date')
            ->update(['employment_start_date' => DB::raw('DATE(created_at)')]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'employment_start_date')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('employment_start_date');
            });
        }
    }
};
