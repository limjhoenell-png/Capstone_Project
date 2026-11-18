<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('user_notices', 'payslip_id')) {
            Schema::table('user_notices', function (Blueprint $table): void {
                $table->foreignId('payslip_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('payslips')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('user_notices', 'payslip_id')) {
            Schema::table('user_notices', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('payslip_id');
            });
        }
    }
};
