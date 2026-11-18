<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitorAccount extends Model
{
    protected $fillable = [
        'full_name',
        'address',
        'contact_number',
    ];
}
