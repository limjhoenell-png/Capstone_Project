<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'hours_per_day')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->decimal('hours_per_day', 5, 2)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'hours_per_day')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('hours_per_day');
            });
        }
    }
};
