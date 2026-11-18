<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payslips')) {
            return;
        }

        Schema::create('payslips', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->unsignedSmallInteger('days_worked')->default(0);
            $table->decimal('total_hours', 12, 4)->default(0);
            $table->decimal('regular_hours', 12, 4)->default(0);
            $table->decimal('overtime_hours', 12, 4)->default(0);
            $table->decimal('equivalent_days', 12, 4)->default(0);
            $table->string('pay_rate_type', 16);
            $table->decimal('pay_rate_amount', 12, 2)->nullable();
            $table->decimal('gross_pay', 12, 2)->default(0);
            $table->decimal('regular_pay', 12, 2)->default(0);
            $table->decimal('overtime_pay', 12, 2)->default(0);
            $table->decimal('deductions_total', 12, 2)->default(0);
            $table->json('deduction_items')->nullable();
            $table->decimal('net_pay', 12, 2)->default(0);
            $table->string('employee_display_name');
            $table->string('employee_identifier')->nullable();
            $table->timestamp('processed_at')->useCurrent();
            $table->timestamps();

            $table->unique(['user_id', 'period_start', 'period_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslips');
    }
};
