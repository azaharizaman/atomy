<?php

declare(strict_types=1);

namespace Nexus\Scheduler\Tests\Unit\ValueObjects;

use DateTimeImmutable;
use Nexus\Scheduler\Enums\JobType;
use Nexus\Scheduler\ValueObjects\ScheduleDefinition;
use Nexus\Scheduler\ValueObjects\ScheduleRecurrence;
use PHPUnit\Framework\TestCase;

final class ScheduleDefinitionTest extends TestCase
{
    private const VALID_ULID = '01HXYZQ1VABCD234567890MNOP';

    public function test_create_valid_definition(): void
    {
        $runAt = new DateTimeImmutable();
        $definition = new ScheduleDefinition(
            JobType::SEND_REMINDER,
            self::VALID_ULID,
            $runAt
        );

        self::assertSame(JobType::SEND_REMINDER, $definition->jobType);
        self::assertSame(self::VALID_ULID, $definition->targetId);
        self::assertSame($runAt, $definition->runAt);
        self::assertSame(3, $definition->maxRetries); // Default
        self::assertSame(0, $definition->priority); // Default
    }

    public function test_empty_target_id_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Target ID cannot be empty');

        new ScheduleDefinition(
            JobType::SEND_REMINDER,
            '',
            new DateTimeImmutable()
        );
    }

    public function test_invalid_ulid_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Target ID must be a valid ULID');

        new ScheduleDefinition(
            JobType::SEND_REMINDER,
            'invalid-id',
            new DateTimeImmutable()
        );
    }

    public function test_negative_retries_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Maximum retries must be non-negative');

        new ScheduleDefinition(
            JobType::SEND_REMINDER,
            self::VALID_ULID,
            new DateTimeImmutable(),
            maxRetries: -1
        );
    }

    public function test_create_once(): void
    {
        $definition = ScheduleDefinition::once(
            JobType::SEND_REMINDER,
            self::VALID_ULID,
            new DateTimeImmutable()
        );

        self::assertFalse($definition->isRecurring());
        self::assertNotNull($definition->recurrence);
        self::assertFalse($definition->recurrence->isRepeating());
    }

    public function test_create_recurring(): void
    {
        $definition = ScheduleDefinition::recurring(
            JobType::SEND_REMINDER,
            self::VALID_ULID,
            new DateTimeImmutable(),
            ScheduleRecurrence::daily()
        );

        self::assertTrue($definition->isRecurring());
        self::assertTrue($definition->recurrence->isRepeating());
    }

    public function test_with_run_at_returns_new_instance(): void
    {
        $original = ScheduleDefinition::once(
            JobType::SEND_REMINDER,
            self::VALID_ULID,
            new DateTimeImmutable('2024-01-01')
        );

        $newDate = new DateTimeImmutable('2024-01-02');
        $modified = $original->withRunAt($newDate);

        self::assertNotSame($original, $modified);
        self::assertSame($newDate, $modified->runAt);
        self::assertEquals($original->runAt, new DateTimeImmutable('2024-01-01'));
    }

    public function test_with_metadata_merges_data(): void
    {
        $original = new ScheduleDefinition(
            JobType::SEND_REMINDER,
            self::VALID_ULID,
            new DateTimeImmutable(),
            metadata: ['key1' => 'value1']
        );

        $modified = $original->withMetadata(['key2' => 'value2']);

        self::assertNotSame($original, $modified);
        self::assertSame(['key1' => 'value1', 'key2' => 'value2'], $modified->metadata);
        self::assertSame(['key1' => 'value1'], $original->metadata);
    }
}
