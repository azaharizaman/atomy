<?php

declare(strict_types=1);

namespace Nexus\Outbox\Tests\Unit\Domain;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use Nexus\Outbox\Domain\OutboxEnqueueCommand;
use Nexus\Outbox\ValueObjects\DedupKey;
use Nexus\Outbox\ValueObjects\EventTypeRef;
use Nexus\Outbox\ValueObjects\TenantId;
use PHPUnit\Framework\TestCase;

final class OutboxEnqueueCommandTest extends TestCase
{
    public function testTrimsAndNullsEmptyCorrelationAndCausation(): void
    {
        $cmd = new OutboxEnqueueCommand(
            new TenantId('t1'),
            new DedupKey('d1'),
            new EventTypeRef('E'),
            [],
            [],
            '  ',
            "\t\n",
            new DateTimeImmutable('2026-03-22T12:00:00+00:00', new DateTimeZone('UTC')),
        );

        self::assertNull($cmd->correlationId);
        self::assertNull($cmd->causationId);
    }

    public function testRejectsCorrelationLongerThan255Utf8Characters(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('correlationId exceeds maximum length');

        new OutboxEnqueueCommand(
            new TenantId('t1'),
            new DedupKey('d1'),
            new EventTypeRef('E'),
            [],
            [],
            str_repeat('é', 256),
            null,
            new DateTimeImmutable('2026-03-22T12:00:00+00:00', new DateTimeZone('UTC')),
        );
    }

    public function testRejectsControlCharacterInCausation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('causationId must not contain control characters');

        new OutboxEnqueueCommand(
            new TenantId('t1'),
            new DedupKey('d1'),
            new EventTypeRef('E'),
            [],
            [],
            null,
            "a\x01b",
            new DateTimeImmutable('2026-03-22T12:00:00+00:00', new DateTimeZone('UTC')),
        );
    }
}
