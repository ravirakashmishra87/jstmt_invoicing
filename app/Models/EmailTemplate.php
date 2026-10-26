<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $fillable = [
        'code', 'name', 'document_type', 'subject', 'body', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public static function forType(string $documentType): ?self
    {
        return static::query()
            ->where('document_type', $documentType)
            ->where('is_active', true)
            ->orderBy('id')
            ->first();
    }

    /**
     * @param  array<string, string|null>  $vars
     */
    public function renderSubject(array $vars): string
    {
        return $this->replace($this->subject, $vars);
    }

    /**
     * @param  array<string, string|null>  $vars
     */
    public function renderBody(array $vars): string
    {
        return $this->replace($this->body, $vars);
    }

    /**
     * @param  array<string, string|null>  $vars
     */
    private function replace(string $text, array $vars): string
    {
        foreach ($vars as $key => $value) {
            $text = str_replace('{{'.$key.'}}', (string) ($value ?? ''), $text);
        }

        return $text;
    }
}
