<?php

declare(strict_types=1);

namespace Nexus\Laravel\Idempotency\Http;

use Illuminate\Http\Request;

trait Idempotency
{
    protected function getIdempotencyRequest(Request $request): ?IdempotencyRequest
    {
        return $request->attributes->get('idempotency_request');
    }
}
