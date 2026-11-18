<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    public const KIND_REGULAR = 'regular';

    public const KIND_SPECIAL = 'special';

    protected $fillable = [
        'holiday_date',
        'name',
        'kind',
    ];

    protected function casts(): array
    {
        return [
            'holiday_date' => 'date',
        ];
    }
}
