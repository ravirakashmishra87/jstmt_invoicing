<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NumberSeries extends Model
{
    protected $fillable = [
        'document_type', 'prefix', 'suffix', 'separator', 'format_pattern',
        'starting_number', 'current_number', 'zero_padding', 'reset_rule', 'last_reset_key',
    ];
}