<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class SmtpSetting extends Model
{
    protected $fillable = [
        'mailer', 'host', 'port', 'encryption', 'username', 'password',
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
            'mailer' => 'sendmail',
            'port' => 25,
            'encryption' => 'none',
            'from_name' => 'JST Machine Tools',
            'from_email' => 'info@jstmachinetools.com',
            'reply_to_email' => 'info@jstmachinetools.com',
            'is_active' => false,
        ]);
    }

    public function usesSendmail(): bool
    {
        return strtolower((string) ($this->mailer ?: 'smtp')) === 'sendmail';
    }

    public function setPasswordAttribute(?string $value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['password'] = null;

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
        if (! $this->is_active || blank($this->from_email)) {
            return false;
        }

        if ($this->usesSendmail()) {
            return true;
        }

        return filled($this->host) && (int) $this->port > 0;
    }
}
