<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('logbook_entries')) {
            return;
        }

        $columns = [
            'full_name' => fn (Blueprint $table) => $table->string('full_name', 150)->nullable(),
            'address' => fn (Blueprint $table) => $table->string('address', 255)->nullable(),
            'contact_number' => fn (Blueprint $table) => $table->string('contact_number', 50)->nullable(),
            'purpose_of_transaction' => fn (Blueprint $table) => $table->text('purpose_of_transaction')->nullable(),
            'transaction_type' => fn (Blueprint $table) => $table->string('transaction_type', 100)->nullable(),
            'is_authorized_representative' => fn (Blueprint $table) => $table->boolean('is_authorized_representative')->nullable(),
            'authorization_letter_verified' => fn (Blueprint $table) => $table->boolean('authorization_letter_verified')->nullable(),
            'valid_id_verified' => fn (Blueprint $table) => $table->boolean('valid_id_verified')->nullable(),
            'represented_person_name' => fn (Blueprint $table) => $table->string('represented_person_name', 150)->nullable(),
        ];

        $missingColumns = array_diff_key($columns, array_flip(Schema::getColumnListing('logbook_entries')));
        if ($missingColumns !== []) {
            Schema::table('logbook_entries', function (Blueprint $table) use ($missingColumns): void {
                foreach ($missingColumns as $definition) {
                    $definition($table);
                }
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('logbook_entries')) {
            return;
        }

        $columns = [
            'full_name',
            'address',
            'contact_number',
            'purpose_of_transaction',
            'transaction_type',
            'is_authorized_representative',
            'authorization_letter_verified',
            'valid_id_verified',
            'represented_person_name',
        ];
        $existing = array_values(array_filter(
            $columns,
            fn (string $column): bool => Schema::hasColumn('logbook_entries', $column),
        ));

        if ($existing !== []) {
            Schema::table('logbook_entries', function (Blueprint $table) use ($existing): void {
                $table->dropColumn($existing);
            });
        }
    }
};
