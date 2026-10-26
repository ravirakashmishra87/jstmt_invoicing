<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSpecItem extends Model
{
    protected $fillable = ['spec_group_id', 'label', 'value', 'sort_order'];

    public function group(): BelongsTo
    {
        return $this->belongsTo(ProductSpecGroup::class, 'spec_group_id');
    }
}