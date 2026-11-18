<?php

return [

    /*
    | Philippine statutory employee shares used when payroll settings
    | have "apply statutory deductions" turned on.
    |
    | SSS follows the 2025 schedule: monthly salary credit from 5,000 to 35,000
    | in 500 increments, employee share 5% of the salary credit.
    | PhilHealth 2025: employee share is half of 5% (2.5%), floor 10,000 and
    | ceiling 100,000 monthly basic salary.
    | Pag-IBIG: 1% when monthly pay is 1,500 or below, otherwise 2% of pay
    | up to a 10,000 monthly compensation base (200 maximum).
    | Withholding tax uses the TRAIN monthly brackets on compensation after
    | the employee SSS, PhilHealth, and Pag-IBIG shares.
    */

    'sss' => [
        'rate' => 0.05,
        'min_msc' => 5000,
        'max_msc' => 35000,
        'step' => 500,
        'floor_cutoff' => 5250,
    ],

    'philhealth' => [
        'employee_rate' => 0.025,
        'floor' => 10000,
        'ceiling' => 100000,
    ],

    'pagibig' => [
        'low_salary' => 1500,
        'low_rate' => 0.01,
        'rate' => 0.02,
        'max_base' => 10000,
    ],

    'withholding_monthly' => [
        ['up_to' => 20833, 'base_tax' => 0, 'rate' => 0, 'excess_over' => 0],
        ['up_to' => 33332, 'base_tax' => 0, 'rate' => 0.15, 'excess_over' => 20833],
        ['up_to' => 66666, 'base_tax' => 1875, 'rate' => 0.20, 'excess_over' => 33333],
        ['up_to' => 166666, 'base_tax' => 8541.80, 'rate' => 0.25, 'excess_over' => 66667],
        ['up_to' => 666666, 'base_tax' => 33541.80, 'rate' => 0.30, 'excess_over' => 166667],
        ['up_to' => null, 'base_tax' => 183541.80, 'rate' => 0.35, 'excess_over' => 666667],
    ],

];
