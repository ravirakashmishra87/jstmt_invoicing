<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentTerm extends Model
{
    protected $fillable = [
        'name', 'advance_percent', 'balance_percent', 'due_days', 'milestone', 'description', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'advance_percent' => 'decimal:2',
            'balance_percent' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}