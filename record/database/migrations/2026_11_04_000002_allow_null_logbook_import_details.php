<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        $grammar = DB::connection()->getQueryGrammar();

        foreach (['logbook', 'logbook_entries'] as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            $columns = collect(Schema::getColumns($tableName))->keyBy('name');

            foreach ([
                'address',
                'contact_number',
                'purpose_of_transaction',
                'person_category',
                'person_classification',
                'transaction_type',
                'fingerprint_id',
                'represented_person_name',
                'represented_person_classification',
                'additional_notes',
                'is_authorized_representative',
                'authorization_letter_verified',
                'valid_id_verified',
            ] as $name) {
                $column = $columns->get($name);
                if ($column === null || $column['nullable']) {
                    continue;
                }

                $type = (string) ($column['type'] ?? '');
                if (! preg_match('/^[a-zA-Z0-9_(), ]+$/', $type)) {
                    throw new RuntimeException("Unable to safely update the {$tableName}.{$name} column.");
                }

                $sql = sprintf(
                    'ALTER TABLE %s MODIFY COLUMN %s %s',
                    $grammar->wrapTable($tableName),
                    $grammar->wrap($name),
                    $type,
                );

                if (! empty($column['collation'])) {
                    $collation = (string) $column['collation'];
                    if (! preg_match('/^[a-zA-Z0-9_]+$/', $collation)) {
                        throw new RuntimeException("Unable to safely preserve the {$tableName}.{$name} collation.");
                    }
                    $sql .= ' COLLATE '.$collation;
                }
                $sql .= ' NULL';

                $default = $column['default'] ?? null;
                if ($default !== null) {
                    $sql .= ' DEFAULT '.($default instanceof Expression
                        ? $default->getValue($grammar)
                        : DB::connection()->getPdo()->quote((string) $default));
                }

                if (! empty($column['comment'])) {
                    $sql .= ' COMMENT '.DB::connection()->getPdo()->quote((string) $column['comment']);
                }

                DB::statement($sql);
            }
        }
    }

    public function down(): void
    {
        // Keep nullable columns to preserve imported NULL values on rollback.
    }
};
