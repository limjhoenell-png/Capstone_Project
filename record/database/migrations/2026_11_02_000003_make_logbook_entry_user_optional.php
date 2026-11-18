<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('logbook_entries') && Schema::hasColumn('logbook_entries', 'user_id')) {
            Schema::table('logbook_entries', function (Blueprint $table): void {
                $table->integer('user_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('logbook_entries') || ! Schema::hasColumn('logbook_entries', 'user_id')) {
            return;
        }

        if (DB::table('logbook_entries')->whereNull('user_id')->exists()) {
            throw new RuntimeException('Cannot make logbook_entries.user_id required while manual entries have no linked user.');
        }

        Schema::table('logbook_entries', function (Blueprint $table): void {
            $table->integer('user_id')->nullable(false)->change();
        });
    }
};
