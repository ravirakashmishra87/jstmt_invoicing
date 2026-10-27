<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProformaCharge extends Model
{
    protected $fillable = [
        'proforma_id', 'charge_type_id', 'name', 'hsn_sac', 'amount', 'tax_rate', 'tax_amount',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
        ];
    }

    public function proforma(): BelongsTo
    {
        return $this->belongsTo(Proforma::class);
    }

    public function chargeType(): BelongsTo
    {
        return $this->belongsTo(ChargeType::class);
    }
}
