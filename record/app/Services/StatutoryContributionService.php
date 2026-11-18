<?php

namespace App\Services;

class StatutoryContributionService
{
    /**
     * Employee statutory deductions for a pay period.
     * Monthly contributions are prorated by period length / 30, except a
     * period that is exactly one calendar month, which uses the full month.
     *
     * @return array{total: float, items: list<array{name: string, calculation_type: string, value: float, amount: float}>}
     */
    public function forGross(float $periodGross, string $periodStart, string $periodEnd): array
    {
        $factor = $this->monthFactor($periodStart, $periodEnd);
        $monthly = $factor > 0 ? $periodGross / $factor : $periodGross;

        $sss = $this->prorate($this->sssShare($monthly), $factor);
        $philhealth = $this->prorate($this->philhealthShare($monthly), $factor);
        $pagibig = $this->prorate($this->pagibigShare($monthly), $factor);
        $taxableMonthly = max(0, $monthly - $this->sssShare($monthly) - $this->philhealthShare($monthly) - $this->pagibigShare($monthly));
        $tax = $this->prorate($this->withholdingTax($taxableMonthly), $factor);

        $items = [
            $this->item('SSS', $sss),
            $this->item('PhilHealth', $philhealth),
            $this->item('Pag-IBIG', $pagibig),
            $this->item('Withholding Tax', $tax),
        ];

        $items = array_values(array_filter($items, fn (array $item): bool => $item['amount'] > 0));

        return [
            'total' => round(array_sum(array_column($items, 'amount')), 2),
            'items' => $items,
        ];
    }

    public function isStatutoryName(string $name): bool
    {
        return preg_match('/sss|philhealth|pag-?ibig|hdmf|withholding|\btax\b/i', $name) === 1;
    }

    private function sssShare(float $monthlyBasic): float
    {
        $config = config('payroll.sss');
        $step = (int) $config['step'];
        $min = (float) $config['min_msc'];
        $max = (float) $config['max_msc'];

        if ($monthlyBasic < (float) $config['floor_cutoff']) {
            $msc = $min;
        } elseif ($monthlyBasic >= ($max - ($step / 2))) {
            $msc = $max;
        } else {
            $msc = round($monthlyBasic / $step) * $step;
            $msc = max($min, min($max, $msc));
        }

        return round($msc * (float) $config['rate'], 2);
    }

    private function philhealthShare(float $monthlyBasic): float
    {
        $config = config('payroll.philhealth');
        $base = min((float) $config['ceiling'], max((float) $config['floor'], $monthlyBasic));

        return round($base * (float) $config['employee_rate'], 2);
    }

    private function pagibigShare(float $monthlyBasic): float
    {
        $config = config('payroll.pagibig');
        if ($monthlyBasic <= (float) $config['low_salary']) {
            return round($monthlyBasic * (float) $config['low_rate'], 2);
        }

        $base = min((float) $config['max_base'], $monthlyBasic);

        return round($base * (float) $config['rate'], 2);
    }

    private function withholdingTax(float $monthlyTaxable): float
    {
        foreach (config('payroll.withholding_monthly') as $bracket) {
            $ceiling = $bracket['up_to'];
            if ($ceiling === null || $monthlyTaxable <= (float) $ceiling) {
                $excess = max(0, $monthlyTaxable - (float) $bracket['excess_over']);

                return round((float) $bracket['base_tax'] + ($excess * (float) $bracket['rate']), 2);
            }
        }

        return 0.0;
    }

    private function monthFactor(string $periodStart, string $periodEnd): float
    {
        $start = \Illuminate\Support\Carbon::parse($periodStart)->startOfDay();
        $end = \Illuminate\Support\Carbon::parse($periodEnd)->startOfDay();
        if ($start->isSameDay($start->copy()->startOfMonth()) && $end->isSameDay($start->copy()->endOfMonth())) {
            return 1.0;
        }

        $days = $start->diffInDays($end) + 1;

        return max(1, $days) / 30;
    }

    private function prorate(float $monthlyAmount, float $factor): float
    {
        return round($monthlyAmount * $factor, 2);
    }

    /**
     * @return array{name: string, calculation_type: string, value: float, amount: float}
     */
    private function item(string $name, float $amount): array
    {
        return [
            'name' => $name,
            'calculation_type' => 'statutory',
            'value' => $amount,
            'amount' => $amount,
        ];
    }
}
