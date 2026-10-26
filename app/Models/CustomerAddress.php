<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerAddress extends Model
{
    protected $fillable = [
        'customer_id', 'type', 'label', 'address_line1', 'address_line2', 'city', 'district',
        'state_id', 'state_code', 'pin', 'country', 'is_default',
    ];

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function fullAddress(): string
    {
        return collect([
            $this->address_line1,
            $this->address_line2,
            $this->city,
            $this->district,
            optional($this->state)->name,
            $this->pin,
            $this->country,
        ])->filter()->implode(', ');
    }
}