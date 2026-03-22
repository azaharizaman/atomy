<?php

declare(strict_types=1);

namespace Nexus\Idempotency\Tests\Unit\Services;

use DateTimeImmutable;
use Nexus\Idempotency\Domain\IdempotencyRecord;
use Nexus\Idempotency\Enums\IdempotencyRecordStatus;
use Nexus\Idempotency\Services\InMemoryIdempotencyStore;
use Nexus\Idempotency\ValueObjects\AttemptToken;
use Nexus\Idempotency\ValueObjects\ClientKey;
use Nexus\Idempotency\ValueObjects\OperationRef;
use Nexus\Idempotency\ValueObjects\RequestFingerprint;
use Nexus\Idempotency\ValueObjects\TenantId;
use PHPUnit\Framework\TestCase;

final class InMemoryIdempotencyStoreTest extends TestCase
{
    /**
     * Pipe-delimited keys would collide; JSON tuple keys must not.
     */
    public function testCompositeKeyIsInjectiveForPipeLikeSegments(): void
    {
        $store = new InMemoryIdempotencyStore();
        $now = new DateTimeImmutable('2026-03-21 12:00:00');
        $fp = new RequestFingerprint('fp');

        $tok = new AttemptToken('0123456789abcdef0123456789abcdef');
        $a = new IdempotencyRecord(
            new TenantId('a'),
            new OperationRef('b|c'),
            new ClientKey('d'),
            IdempotencyRecordStatus::Pending,
            $fp,
            $tok,
            null,
            $now,
            $now,
        );
        $b = new IdempotencyRecord(
            new TenantId('a|b'),
            new OperationRef('c'),
            new ClientKey('d'),
            IdempotencyRecordStatus::Pending,
            $fp,
            $tok,
            null,
            $now,
            $now,
        );

        $store->save($a);
        $store->save($b);

        $this->assertSame('a', $store->find(new TenantId('a'), new OperationRef('b|c'), new ClientKey('d'))?->tenantId->value);
        $this->assertSame('a|b', $store->find(new TenantId('a|b'), new OperationRef('c'), new ClientKey('d'))?->tenantId->value);
    }

    public function testClaimPendingInsertsOnceAndReturnsExistingOnSecondClaim(): void
    {
        $store = new InMemoryIdempotencyStore();
        $now = new DateTimeImmutable('2026-03-21 12:00:00');
        $fp = new RequestFingerprint('fp');
        $tokA = new AttemptToken('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa');
        $tokB = new AttemptToken('bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb');

        $first = new IdempotencyRecord(
            new TenantId('t'),
            new OperationRef('op'),
            new ClientKey('k'),
            IdempotencyRecordStatus::Pending,
            $fp,
            $tokA,
            null,
            $now,
            $now,
        );
        $second = new IdempotencyRecord(
            new TenantId('t'),
            new OperationRef('op'),
            new ClientKey('k'),
            IdempotencyRecordStatus::Pending,
            $fp,
            $tokB,
            null,
            $now,
            $now,
        );

        $c1 = $store->claimPending($first);
        $this->assertTrue($c1->claimedNew);
        $this->assertSame($tokA->value, $c1->record->attemptToken->value);

        $c2 = $store->claimPending($second);
        $this->assertFalse($c2->claimedNew);
        $this->assertSame($tokA->value, $c2->record->attemptToken->value);
    }
}
