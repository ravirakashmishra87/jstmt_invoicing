<?php

namespace App\Traits;

use App\Models\AuditLog;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            $model->writeAudit('created', null, $model->getAttributes());
        });

        static::updated(function ($model) {
            $model->writeAudit('updated', $model->getOriginal(), $model->getChanges());
        });

        static::deleted(function ($model) {
            $model->writeAudit('deleted', $model->getOriginal(), null);
        });
    }

    protected function writeAudit(string $event, ?array $old, ?array $new): void
    {
        try {
            AuditLog::create([
                'user_id' => auth()->id(),
                'auditable_type' => static::class,
                'auditable_id' => $this->getKey(),
                'event' => $event,
                'old_values' => $old,
                'new_values' => $new,
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);
        } catch (\Throwable) {
            // Never break business flow due to audit failure
        }
    }
}
