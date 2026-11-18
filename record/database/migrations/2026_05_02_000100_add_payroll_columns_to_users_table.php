<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'pay_rate_type')) {
                $table->string('pay_rate_type', 16)->default('daily');
            }
            if (! Schema::hasColumn('users', 'pay_rate')) {
                $table->decimal('pay_rate', 12, 2)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'pay_rate')) {
                $table->dropColumn('pay_rate');
            }
            if (Schema::hasColumn('users', 'pay_rate_type')) {
                $table->dropColumn('pay_rate_type');
            }
        });
    }
};
