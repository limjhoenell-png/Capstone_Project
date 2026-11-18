<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollPrintSetting extends Model
{
    protected $fillable = [
        'barangay_name',
        'city_name',
        'report_title',
        'source_fund',
        'payroll_number_format',
        'period_covered_label',
        'payroll_number_label',
        'source_fund_label',
        'date_prepared_label',
        'prepared_date_description',
        'punong_barangay_name',
        'punong_barangay_designation',
        'appropriations_chair_name',
        'appropriations_chair_designation',
        'treasurer_name',
        'treasurer_designation',
        'approved_by_name',
        'approved_by_designation',
        'prepared_by_name',
        'prepared_by_designation',
    ];

    public static function current(): self
    {
        /** @var self */
        return static::query()->firstOrCreate([], [
            'barangay_name' => 'Barangay San Jose',
            'city_name' => 'Digos City',
            'report_title' => 'Payroll',
            'source_fund' => '',
            'payroll_number_format' => 'PAY-{year}{month}',
            'period_covered_label' => 'Period Covered',
            'payroll_number_label' => 'Payroll No.',
            'source_fund_label' => 'Source of Fund',
            'date_prepared_label' => 'Date Prepared',
            'prepared_date_description' => 'Prepared this {date}.',
            'punong_barangay_name' => '',
            'punong_barangay_designation' => 'Punong Barangay',
            'appropriations_chair_name' => '',
            'appropriations_chair_designation' => 'Chairman Committee on Appropriations',
            'treasurer_name' => '',
            'treasurer_designation' => 'Barangay Treasurer',
            'approved_by_name' => '',
            'approved_by_designation' => 'Approved By',
            'prepared_by_name' => '',
            'prepared_by_designation' => 'Prepared By',
        ]);
    }
}
