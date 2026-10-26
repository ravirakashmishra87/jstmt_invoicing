<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceCharge extends Model
{
    protected $fillable = [
        'invoice_id', 'charge_type_id', 'name', 'hsn_sac', 'amount', 'tax_rate', 'tax_amount',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}