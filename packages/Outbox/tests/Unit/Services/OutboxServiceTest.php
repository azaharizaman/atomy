<?php

declare(strict_types=1);

namespace Nexus\Outbox\Tests\Unit\Services;

use DateTimeImmutable;
use DateTimeZone;
use Nexus\Outbox\Domain\OutboxEnqueueCommand;
use Nexus\Outbox\Enums\OutboxRecordStatus;
use Nexus\Outbox\Exceptions\OutboxClaimExpiredException;
use Nexus\Outbox\Exceptions\OutboxClaimTokenMismatchException;
use Nexus\Outbox\Exceptions\OutboxInvalidTransitionException;
use Nexus\Outbox\Exceptions\OutboxNotFoundException;
use Nexus\Outbox\Services\InMemoryOutboxStore;
use Nexus\Outbox\Services\OutboxService;
use Nexus\Outbox\Tests\Support\FixedClock;
use Nexus\Outbox\ValueObjects\ClaimToken;
use Nexus\Outbox\ValueObjects\DedupKey;
use Nexus\Outbox\ValueObjects\EventTypeRef;
use Nexus\Outbox\ValueObjects\FailureReason;
use Nexus\Outbox\ValueObjects\OutboxMessageId;
use Nexus\Outbox\ValueObjects\TenantId;
use PHPUnit\Framework\TestCase;

final class OutboxServiceTest extends TestCase
{
    private function utc(string $spec): DateTimeImmutable
    {
        return new DateTimeImmutable($spec, new DateTimeZone('UTC'));
    }

    public function testEnqueueInsertsThenReturnsDuplicateForSameDedupKey(): void
    {
        $clock = new FixedClock($this->utc('2026-03-22T12:00:00+00:00'));
        $store = new InMemoryOutboxStore();
        $service = new OutboxService($store, $store, $clock);

        $cmd = new OutboxEnqueueCommand(
            new TenantId('t1'),
            new DedupKey('evt-1'),
            new EventTypeRef('Example\Event'),
            ['a' => 1],
            ['src' => 'test'],
            'corr-1',
            'cause-1',
            $clock->now(),
        );

        $first = $service->enqueue($cmd);
        self::assertTrue($first->wasInserted);
        self::assertSame(OutboxRecordStatus::Pending, $first->record->status);

        $second = $service->enqueue($cmd);
        self::assertFalse($second->wasInserted);
        self::assertSame($first->record->id->value, $second->record->id->value);
    }

    public function testClaimNextPendingProcessesOldestFirst(): void
    {
        $clock = new FixedClock($this->utc('2026-03-22T12:00:00+00:00'));
        $store = new InMemoryOutboxStore();
        $service = new OutboxService($store, $store, $clock);

        $service->enqueue(new OutboxEnqueueCommand(
            new TenantId('t1'),
            new DedupKey('a'),
            new EventTypeRef('A'),
            [],
            [],
            null,
            null,
            $this->utc('2026-03-22T10:00:00+00:00'),
        ));
        $service->enqueue(new OutboxEnqueueCommand(
            new TenantId('t1'),
            new DedupKey('b'),
            new EventTypeRef('B'),
            [],
            [],
            null,
            null,
            $this->utc('2026-03-22T09:00:00+00:00'),
        ));

        $leaseEnd = $this->utc('2026-03-22T12:05:00+00:00');
        $claimed = $service->claimNextPending(new TenantId('t1'), $leaseEnd);
        self::assertNotNull($claimed);
        self::assertSame('b', $claimed->dedupKey->value);
        self::assertSame(OutboxRecordStatus::Sending, $claimed->status);
        self::assertNotNull($claimed->claimToken);
        self::assertEquals($leaseEnd, $claimed->claimExpiresAt);
    }

    public function testMarkSentCompletesSendingRecord(): void
    {
        $clock = new FixedClock($this->utc('2026-03-22T12:00:00+00:00'));
        $store = new InMemoryOutboxStore();
        $service = new OutboxService($store, $store, $clock);

        $service->enqueue(new OutboxEnqueueCommand(
            new TenantId('t1'),
            new DedupKey('x'),
            new EventTypeRef('X'),
            [],
            [],
            null,
            null,
            $clock->now(),
        ));

        $claimed = $service->claimNextPending(new TenantId('t1'), $this->utc('2026-03-22T12:10:00+00:00'));
        self::assertNotNull($claimed);
        $token = $claimed->claimToken;
        self::assertNotNull($token);

        $clock->setNow($this->utc('2026-03-22T12:01:00+00:00'));
        $service->markSent(new TenantId('t1'), $claimed->id, $token);

        $loaded = $store->findById(new TenantId('t1'), $claimed->id);
        self::assertNotNull($loaded);
        self::assertSame(OutboxRecordStatus::Sent, $loaded->status);
    }

    public function testMarkFailedThenScheduleRetry(): void
    {
        $clock = new FixedClock($this->utc('2026-03-22T12:00:00+00:00'));
        $store = new InMemoryOutboxStore();
        $service = new OutboxService($store, $store, $clock);

        $service->enqueue(new OutboxEnqueueCommand(
            new TenantId('t1'),
            new DedupKey('y'),
            new EventTypeRef('Y'),
            [],
            [],
            null,
            null,
            $clock->now(),
        ));

        $claimed = $service->claimNextPending(new TenantId('t1'), $this->utc('2026-03-22T12:10:00+00:00'));
        self::assertNotNull($claimed);
        $token = $claimed->claimToken;
        self::assertNotNull($token);

        $service->markFailed(new TenantId('t1'), $claimed->id, $token, new FailureReason('broker down'));

        $failed = $store->findById(new TenantId('t1'), $claimed->id);
        self::assertNotNull($failed);
        self::assertSame(OutboxRecordStatus::Failed, $failed->status);
        self::assertSame('broker down', $failed->failureReason);

        $clock->setNow($this->utc('2026-03-22T12:02:00+00:00'));
        $service->scheduleRetry(new TenantId('t1'), $claimed->id);

        $pending = $store->findById(new TenantId('t1'), $claimed->id);
        self::assertNotNull($pending);
        self::assertSame(OutboxRecordStatus::Pending, $pending->status);
        self::assertSame(1, $pending->attemptCount);
    }

    public function testMarkSentThrowsWhenClaimExpired(): void
    {
        $clock = new FixedClock($this->utc('2026-03-22T12:00:00+00:00'));
        $store = new InMemoryOutboxStore();
        $service = new OutboxService($store, $store, $clock);

        $service->enqueue(new OutboxEnqueueCommand(
            new TenantId('t1'),
            new DedupKey('z'),
            new EventTypeRef('Z'),
            [],
            [],
            null,
            null,
            $clock->now(),
        ));

        $claimed = $service->claimNextPending(new TenantId('t1'), $this->utc('2026-03-22T12:05:00+00:00'));
        self::assertNotNull($claimed);
        $token = $claimed->claimToken;
        self::assertNotNull($token);

        $clock->setNow($this->utc('2026-03-22T12:06:00+00:00'));

        $this->expectException(OutboxClaimExpiredException::class);
        $service->markSent(new TenantId('t1'), $claimed->id, $token);
    }

    public function testCompletionThrowsOnTokenMismatch(): void
    {
        $clock = new FixedClock($this->utc('2026-03-22T12:00:00+00:00'));
        $store = new InMemoryOutboxStore();
        $service = new OutboxService($store, $store, $clock);

        $service->enqueue(new OutboxEnqueueCommand(
            new TenantId('t1'),
            new DedupKey('tok'),
            new EventTypeRef('T'),
            [],
            [],
            null,
            null,
            $clock->now(),
        ));

        $claimed = $service->claimNextPending(new TenantId('t1'), $this->utc('2026-03-22T12:10:00+00:00'));
        self::assertNotNull($claimed);

        $this->expectException(OutboxClaimTokenMismatchException::class);
        $service->markSent(new TenantId('t1'), $claimed->id, new ClaimToken(str_repeat('a', 32)));
    }

    public function testFindByIdReturnsNullForWrongTenant(): void
    {
        $clock = new FixedClock($this->utc('2026-03-22T12:00:00+00:00'));
        $store = new InMemoryOutboxStore();
        $service = new OutboxService($store, $store, $clock);

        $r = $service->enqueue(new OutboxEnqueueCommand(
            new TenantId('tenant-a'),
            new DedupKey('only-a'),
            new EventTypeRef('A'),
            [],
            [],
            null,
            null,
            $clock->now(),
        ));

        self::assertNull($store->findById(new TenantId('tenant-b'), $r->record->id));
    }

    public function testScheduleRetryFromPendingThrows(): void
    {
        $clock = new FixedClock($this->utc('2026-03-22T12:00:00+00:00'));
        $store = new InMemoryOutboxStore();
        $service = new OutboxService($store, $store, $clock);

        $r = $service->enqueue(new OutboxEnqueueCommand(
            new TenantId('t1'),
            new DedupKey('p'),
            new EventTypeRef('P'),
            [],
            [],
            null,
            null,
            $clock->now(),
        ));

        $this->expectException(OutboxInvalidTransitionException::class);
        $service->scheduleRetry(new TenantId('t1'), $r->record->id);
    }

    public function testMarkSentThrowsWhenNotFound(): void
    {
        $clock = new FixedClock($this->utc('2026-03-22T12:00:00+00:00'));
        $store = new InMemoryOutboxStore();
        $service = new OutboxService($store, $store, $clock);

        $this->expectException(OutboxNotFoundException::class);
        $service->markSent(new TenantId('t1'), new OutboxMessageId(str_repeat('f', 32)), new ClaimToken(str_repeat('b', 32)));
    }
}
