<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class SmtpSetting extends Model
{
    protected $fillable = [
        'host', 'port', 'encryption', 'username', 'password',
        'from_name', 'from_email', 'reply_to_email', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'port' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'port' => 587,
            'encryption' => 'tls',
            'is_active' => false,
        ]);
    }

    public function setPasswordAttribute(?string $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $this->attributes['password'] = Crypt::encryptString($value);
    }

    public function plainPassword(): ?string
    {
        $value = $this->attributes['password'] ?? null;
        if (! $value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            return null;
        }
    }

    public function isConfigured(): bool
    {
        return $this->is_active
            && filled($this->host)
            && filled($this->from_email)
            && (int) $this->port > 0;
    }
}
