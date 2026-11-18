<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'monthly_incentive')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->decimal('monthly_incentive', 12, 2)->default(0);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'monthly_incentive')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('monthly_incentive');
            });
        }
    }
};
