<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAttachment extends Model
{
    protected $fillable = ['product_id', 'path', 'original_name', 'type'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}