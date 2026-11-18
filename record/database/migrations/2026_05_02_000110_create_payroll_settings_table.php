<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payroll_settings')) {
            return;
        }

        Schema::create('payroll_settings', function (Blueprint $table): void {
            $table->id();
            $table->decimal('hours_per_full_day', 5, 2)->default(8);
            $table->timestamps();
        });

        DB::table('payroll_settings')->insert([
            'hours_per_full_day' => 8,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_settings');
    }
};
