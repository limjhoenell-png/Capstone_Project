<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('annual_benefits')) {
            Schema::create('annual_benefits', function (Blueprint $table): void {
                $table->id();
                $table->string('name', 120);
                $table->decimal('amount', 12, 2);
                $table->date('payment_date');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('annual_benefit_user')) {
            Schema::create('annual_benefit_user', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('annual_benefit_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['annual_benefit_id', 'user_id']);
            });
        }

        if (Schema::hasTable('payslips') && ! Schema::hasColumn('payslips', 'earning_items')) {
            Schema::table('payslips', function (Blueprint $table): void {
                $table->json('earning_items')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('payslips') && Schema::hasColumn('payslips', 'earning_items')) {
            Schema::table('payslips', function (Blueprint $table): void {
                $table->dropColumn('earning_items');
            });
        }

        Schema::dropIfExists('annual_benefit_user');
        Schema::dropIfExists('annual_benefits');
    }
};
