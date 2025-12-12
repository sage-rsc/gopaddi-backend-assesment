<?php

namespace App\Traits;

use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

trait Auditable
{
    /**
     * Get audit service instance.
     *
     * @return AuditService
     */
    protected function audit(): AuditService
    {
        return App::make(AuditService::class);
    }

    /**
     * Get current request instance.
     *
     * @return Request|null
     */
    protected function getRequest(): ?Request
    {
        return request();
    }
}

