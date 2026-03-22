<?php

declare(strict_types=1);

namespace Nexus\Idempotency\Tests\Unit\Domain;

use Nexus\Idempotency\Domain\IdempotencyPolicy;
use Nexus\Idempotency\Exceptions\IdempotencyKeyInvalidException;
use PHPUnit\Framework\TestCase;

final class IdempotencyPolicyTest extends TestCase
{
    public function testRejectsNegativePendingTtl(): void
    {
        $this->expectException(IdempotencyKeyInvalidException::class);
        new IdempotencyPolicy(pendingTtlSeconds: -1);
    }

    public function testRejectsNegativeCompletedTtl(): void
    {
        $this->expectException(IdempotencyKeyInvalidException::class);
        new IdempotencyPolicy(
            pendingTtlSeconds: 100,
            expireCompletedAfterSeconds: -1,
        );
    }
}
