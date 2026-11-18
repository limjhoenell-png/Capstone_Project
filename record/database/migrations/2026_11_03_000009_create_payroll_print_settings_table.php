<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payroll_print_settings')) {
            return;
        }

        Schema::create('payroll_print_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('barangay_name', 120)->default('Barangay San Jose');
            $table->string('city_name', 120)->default('Digos City');
            $table->string('report_title', 120)->default('Payroll');
            $table->string('source_fund', 120)->default('');
            $table->string('payroll_number_format', 64)->default('PAY-{year}{month}');
            $table->string('period_covered_label', 80)->default('Period Covered');
            $table->string('payroll_number_label', 80)->default('Payroll No.');
            $table->string('source_fund_label', 80)->default('Source of Fund');
            $table->string('date_prepared_label', 80)->default('Date Prepared');
            $table->string('prepared_date_description', 180)->default('Prepared this {date}.');
            $table->string('punong_barangay_name', 120)->default('');
            $table->string('punong_barangay_designation', 120)->default('Punong Barangay');
            $table->string('appropriations_chair_name', 120)->default('');
            $table->string('appropriations_chair_designation', 160)->default('Chairman Committee on Appropriations');
            $table->string('treasurer_name', 120)->default('');
            $table->string('treasurer_designation', 120)->default('Barangay Treasurer');
            $table->string('approved_by_name', 120)->default('');
            $table->string('approved_by_designation', 120)->default('Approved By');
            $table->string('prepared_by_name', 120)->default('');
            $table->string('prepared_by_designation', 120)->default('Prepared By');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_print_settings');
    }
};
