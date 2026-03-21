<?php

declare(strict_types=1);

namespace Nexus\Idempotency\Tests\Unit\Services;

use DateTimeImmutable;
use Nexus\Idempotency\Domain\IdempotencyPolicy;
use Nexus\Idempotency\Enums\BeginOutcome;
use Nexus\Idempotency\Exceptions\IdempotencyCompletionException;
use Nexus\Idempotency\Exceptions\IdempotencyFingerprintConflictException;
use Nexus\Idempotency\Services\IdempotencyService;
use Nexus\Idempotency\Services\InMemoryIdempotencyStore;
use Nexus\Idempotency\Tests\Support\FixedClock;
use Nexus\Idempotency\ValueObjects\ClientKey;
use Nexus\Idempotency\ValueObjects\OperationRef;
use Nexus\Idempotency\ValueObjects\RequestFingerprint;
use Nexus\Idempotency\ValueObjects\ResultEnvelope;
use Nexus\Idempotency\ValueObjects\TenantId;
use PHPUnit\Framework\TestCase;

final class IdempotencyServiceTest extends TestCase
{
    public function testBeginFirstExecutionReturnsFirstExecution(): void
    {
        $clock = new FixedClock(new DateTimeImmutable('2026-03-21 12:00:00'));
        $store = new InMemoryIdempotencyStore();
        $policy = IdempotencyPolicy::default();
        $service = new IdempotencyService($store, $clock, $policy);

        $tenant = new TenantId('tenant-a');
        $op = new OperationRef('rfq.create');
        $key = new ClientKey('idem-1');
        $fp = new RequestFingerprint('sha256-aaa');

        $decision = $service->begin($tenant, $op, $key, $fp);

        $this->assertSame(BeginOutcome::FirstExecution, $decision->outcome);
        $this->assertNull($decision->replayResult);
        $this->assertNotNull($decision->record);
    }

    public function testBeginReplayReturnsStoredReplay(): void
    {
        $clock = new FixedClock(new DateTimeImmutable('2026-03-21 12:00:00'));
        $store = new InMemoryIdempotencyStore();
        $policy = IdempotencyPolicy::default();
        $service = new IdempotencyService($store, $clock, $policy);

        $tenant = new TenantId('tenant-a');
        $op = new OperationRef('rfq.create');
        $key = new ClientKey('idem-1');
        $fp = new RequestFingerprint('sha256-aaa');

        $service->begin($tenant, $op, $key, $fp);
        $result = new ResultEnvelope('{"id":"rfq-1"}');
        $service->complete($tenant, $op, $key, $fp, $result);

        $decision = $service->begin($tenant, $op, $key, $fp);

        $this->assertSame(BeginOutcome::Replay, $decision->outcome);
        $this->assertNotNull($decision->replayResult);
        $this->assertSame('{"id":"rfq-1"}', $decision->replayResult->payload);
    }

    public function testFingerprintConflictOnBegin(): void
    {
        $clock = new FixedClock(new DateTimeImmutable('2026-03-21 12:00:00'));
        $store = new InMemoryIdempotencyStore();
        $policy = IdempotencyPolicy::default();
        $service = new IdempotencyService($store, $clock, $policy);

        $tenant = new TenantId('tenant-a');
        $op = new OperationRef('rfq.create');
        $key = new ClientKey('idem-1');
        $fp1 = new RequestFingerprint('sha256-aaa');
        $fp2 = new RequestFingerprint('sha256-bbb');

        $service->begin($tenant, $op, $key, $fp1);

        $this->expectException(IdempotencyFingerprintConflictException::class);
        $service->begin($tenant, $op, $key, $fp2);
    }

    public function testSecondBeginWhilePendingReturnsInProgress(): void
    {
        $clock = new FixedClock(new DateTimeImmutable('2026-03-21 12:00:00'));
        $store = new InMemoryIdempotencyStore();
        $policy = IdempotencyPolicy::default();
        $service = new IdempotencyService($store, $clock, $policy);

        $tenant = new TenantId('tenant-a');
        $op = new OperationRef('rfq.create');
        $key = new ClientKey('idem-1');
        $fp = new RequestFingerprint('sha256-aaa');

        $first = $service->begin($tenant, $op, $key, $fp);
        $this->assertSame(BeginOutcome::FirstExecution, $first->outcome);

        $second = $service->begin($tenant, $op, $key, $fp);
        $this->assertSame(BeginOutcome::InProgress, $second->outcome);
    }

    public function testFailedAllowsRetryWhenPolicyAllows(): void
    {
        $clock = new FixedClock(new DateTimeImmutable('2026-03-21 12:00:00'));
        $store = new InMemoryIdempotencyStore();
        $policy = new IdempotencyPolicy(pendingTtlSeconds: 3600, allowRetryAfterFail: true);
        $service = new IdempotencyService($store, $clock, $policy);

        $tenant = new TenantId('tenant-a');
        $op = new OperationRef('rfq.create');
        $key = new ClientKey('idem-1');
        $fp = new RequestFingerprint('sha256-aaa');

        $service->begin($tenant, $op, $key, $fp);
        $service->fail($tenant, $op, $key, $fp);

        $again = $service->begin($tenant, $op, $key, $fp);
        $this->assertSame(BeginOutcome::FirstExecution, $again->outcome);
    }

    public function testFailedDoesNotAllowRetryWhenPolicyDisallows(): void
    {
        $clock = new FixedClock(new DateTimeImmutable('2026-03-21 12:00:00'));
        $store = new InMemoryIdempotencyStore();
        $policy = new IdempotencyPolicy(pendingTtlSeconds: 3600, allowRetryAfterFail: false);
        $service = new IdempotencyService($store, $clock, $policy);

        $tenant = new TenantId('tenant-a');
        $op = new OperationRef('rfq.create');
        $key = new ClientKey('idem-1');
        $fp = new RequestFingerprint('sha256-aaa');

        $service->begin($tenant, $op, $key, $fp);
        $service->fail($tenant, $op, $key, $fp);

        $this->expectException(IdempotencyCompletionException::class);
        $service->begin($tenant, $op, $key, $fp);
    }

    public function testTenantIsolation(): void
    {
        $clock = new FixedClock(new DateTimeImmutable('2026-03-21 12:00:00'));
        $store = new InMemoryIdempotencyStore();
        $policy = IdempotencyPolicy::default();
        $service = new IdempotencyService($store, $clock, $policy);

        $op = new OperationRef('rfq.create');
        $key = new ClientKey('idem-same');
        $fp = new RequestFingerprint('sha256-aaa');

        $a = $service->begin(new TenantId('tenant-a'), $op, $key, $fp);
        $b = $service->begin(new TenantId('tenant-b'), $op, $key, $fp);

        $this->assertSame(BeginOutcome::FirstExecution, $a->outcome);
        $this->assertSame(BeginOutcome::FirstExecution, $b->outcome);
    }

    public function testPendingExpiredStartsFresh(): void
    {
        $clock = new FixedClock(new DateTimeImmutable('2026-03-21 12:00:00'));
        $store = new InMemoryIdempotencyStore();
        $policy = new IdempotencyPolicy(pendingTtlSeconds: 60, allowRetryAfterFail: true);
        $service = new IdempotencyService($store, $clock, $policy);

        $tenant = new TenantId('tenant-a');
        $op = new OperationRef('rfq.create');
        $key = new ClientKey('idem-1');
        $fp = new RequestFingerprint('sha256-aaa');

        $service->begin($tenant, $op, $key, $fp);
        $clock->setTime(new DateTimeImmutable('2026-03-21 12:02:00'));

        $decision = $service->begin($tenant, $op, $key, $fp);
        $this->assertSame(BeginOutcome::FirstExecution, $decision->outcome);
    }
}
