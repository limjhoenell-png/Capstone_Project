<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ensure logbook_entries exists when the DB was never migrated or the table was dropped.
     */
    public function up(): void
    {
        if (Schema::hasTable('logbook_entries')) {
            return;
        }

        Schema::create('logbook_entries', function (Blueprint $table): void {
            $table->id();
            $table->integer('user_id')->index();
            $table->dateTime('entry_at')->index();
            $table->text('description');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Intentionally empty: do not drop; table may pre-exist or hold data.
    }
};
