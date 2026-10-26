<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierAddress extends Model
{
    protected $fillable = [
        'supplier_id', 'type', 'address_line1', 'address_line2', 'city', 'district',
        'state_id', 'state_code', 'pin', 'country', 'is_default',
    ];

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }
}