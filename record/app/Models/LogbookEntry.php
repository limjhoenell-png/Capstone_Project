<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogbookEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'entry_at',
        'description',
        'person_category',
        'full_name',
        'address',
        'contact_number',
        'purpose_of_transaction',
        'transaction_type',
        'is_authorized_representative',
        'authorization_letter_verified',
        'valid_id_verified',
        'represented_person_name',
        'represented_person_classification',
        'additional_notes',
    ];

    protected function casts(): array
    {
        return [
            'entry_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
