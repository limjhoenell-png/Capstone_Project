<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('annual_benefits')) {
            $columns = [];
            if (! Schema::hasColumn('annual_benefits', 'benefit_type')) {
                $columns[] = 'benefit_type';
            }
            if (! Schema::hasColumn('annual_benefits', 'calculation_year')) {
                $columns[] = 'calculation_year';
            }
            if (! Schema::hasColumn('annual_benefits', 'selected_months')) {
                $columns[] = 'selected_months';
            }

            if ($columns !== []) {
                Schema::table('annual_benefits', function (Blueprint $table) use ($columns): void {
                    if (in_array('benefit_type', $columns, true)) {
                        $table->string('benefit_type', 20)->default('Other');
                    }
                    if (in_array('calculation_year', $columns, true)) {
                        $table->unsignedSmallInteger('calculation_year')->nullable();
                    }
                    if (in_array('selected_months', $columns, true)) {
                        $table->json('selected_months')->nullable();
                    }
                });
            }
        }

        if (Schema::hasTable('annual_benefit_user')
            && ! Schema::hasColumn('annual_benefit_user', 'amount')) {
            Schema::table('annual_benefit_user', function (Blueprint $table): void {
                $table->decimal('amount', 12, 2)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('annual_benefit_user')
            && Schema::hasColumn('annual_benefit_user', 'amount')) {
            Schema::table('annual_benefit_user', function (Blueprint $table): void {
                $table->dropColumn('amount');
            });
        }

        if (Schema::hasTable('annual_benefits')) {
            $columns = array_values(array_filter(
                ['benefit_type', 'calculation_year', 'selected_months'],
                static fn (string $column): bool => Schema::hasColumn('annual_benefits', $column),
            ));

            if ($columns !== []) {
                Schema::table('annual_benefits', function (Blueprint $table) use ($columns): void {
                    $table->dropColumn($columns);
                });
            }
        }
    }
};
