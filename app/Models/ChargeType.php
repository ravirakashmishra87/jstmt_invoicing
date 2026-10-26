<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChargeType extends Model
{
    protected $fillable = ['name', 'code', 'hsn_sac_id', 'tax_rate_id', 'is_taxable', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_taxable' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function hsnSac(): BelongsTo
    {
        return $this->belongsTo(HsnSacCode::class, 'hsn_sac_id');
    }

    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class);
    }
}