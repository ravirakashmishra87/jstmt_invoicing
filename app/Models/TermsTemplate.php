<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TermsTemplate extends Model
{
    protected $fillable = ['name', 'document_type', 'content', 'is_default', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}