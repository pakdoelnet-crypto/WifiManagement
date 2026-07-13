<?php

namespace App\Traits;

use App\Observers\AuditLogObserver;

trait Auditable
{
    /**
     * Boot the Auditable trait for a model.
     */
    public static function bootAuditable(): void
    {
        static::whenBooted(function () {
            static::observe(AuditLogObserver::class);
        });
    }
}
