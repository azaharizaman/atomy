<?php

declare(strict_types=1);

namespace Nexus\Laravel\Idempotency\Contracts;

use Symfony\Component\HttpFoundation\Response;

/**
 * Decodes a stored {@see \Nexus\Idempotency\ValueObjects\ResultEnvelope} payload (v1 JSON) into an HTTP response.
 */
interface ReplayResponseFactoryInterface
{
    public function fromPayloadString(string $payload): Response;
}
