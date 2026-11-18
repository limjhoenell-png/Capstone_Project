<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('logbook_entries') && ! Schema::hasColumn('logbook_entries', 'person_category')) {
            Schema::table('logbook_entries', function (Blueprint $table): void {
                $table->string('person_category', 30)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('logbook_entries') && Schema::hasColumn('logbook_entries', 'person_category')) {
            Schema::table('logbook_entries', function (Blueprint $table): void {
                $table->dropColumn('person_category');
            });
        }
    }
};
