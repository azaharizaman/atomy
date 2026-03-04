<?php

declare(strict_types=1);

namespace Nexus\Audit\Contracts;

interface SignerInterface
{
    public function sign(string $payload, string $keyId): string;

    public function verify(string $payload, string $signature, string $keyId): bool;
}
