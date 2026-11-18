<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['logbook', 'logbook_entries'] as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            $columns = [];
            if (! Schema::hasColumn($tableName, 'represented_person_classification')) {
                $columns[] = 'represented_person_classification';
            }
            if (! Schema::hasColumn($tableName, 'additional_notes')) {
                $columns[] = 'additional_notes';
            }

            if ($columns === []) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($columns): void {
                foreach ($columns as $column) {
                    $table->text($column)->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        // These shared tables may already have contained either column before this migration.
        // Leaving them in place avoids dropping pre-existing data on rollback.
    }
};
