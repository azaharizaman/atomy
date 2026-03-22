<?php

declare(strict_types=1);

namespace Nexus\Laravel\Idempotency\Http\Concerns;

use Illuminate\Http\Request;
use Nexus\Laravel\Idempotency\Http\IdempotencyRequest;

trait Idempotency
{
    protected function idempotencyRequest(Request $request): ?IdempotencyRequest
    {
        $value = $request->attributes->get('idempotency_request');

        return $value instanceof IdempotencyRequest ? $value : null;
    }
}
