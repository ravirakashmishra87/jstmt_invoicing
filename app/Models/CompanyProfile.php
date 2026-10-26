<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyProfile extends Model
{
    protected $table = 'company_profile';

    protected $fillable = [
        'name', 'trade_name', 'address_line1', 'address_line2', 'city', 'district',
        'state_id', 'state_code', 'pin', 'country', 'gstin', 'pan', 'udyam_no',
        'phone', 'email', 'website', 'logo_path', 'brand_logo_path', 'signature_path',
        'stamp_path', 'declaration', 'jurisdiction', 'footer_text',
        'quotation_validity_days', 'stock_valuation', 'prevent_negative_stock',
        'credit_limit_hard_block', 'overdue_interest_enabled', 'overdue_interest_rate',
        'eway_bill_threshold', 'round_off_rule', 'books_locked_upto',
    ];

    protected function casts(): array
    {
        return [
            'prevent_negative_stock' => 'boolean',
            'credit_limit_hard_block' => 'boolean',
            'overdue_interest_enabled' => 'boolean',
            'overdue_interest_rate' => 'decimal:2',
            'eway_bill_threshold' => 'decimal:2',
            'books_locked_upto' => 'date',
        ];
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }
}