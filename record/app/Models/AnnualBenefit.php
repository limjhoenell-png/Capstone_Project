<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AnnualBenefit extends Model
{
    protected $fillable = [
        'benefit_type',
        'name',
        'amount',
        'payment_date',
        'calculation_year',
        'selected_months',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_date' => 'date',
            'selected_months' => 'array',
        ];
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('amount')->withTimestamps();
    }
}
