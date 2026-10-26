<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use Auditable, SoftDeletes;

    protected $fillable = [
        'supplier_code', 'legal_name', 'trade_name', 'gstin', 'pan', 'gst_registration_type',
        'contact_person', 'mobile', 'email', 'bank_name', 'bank_account', 'bank_ifsc',
        'notes', 'is_active', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(SupplierAddress::class);
    }
}