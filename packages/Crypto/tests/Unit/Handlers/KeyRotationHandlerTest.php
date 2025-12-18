<?php

declare(strict_types=1);

namespace Nexus\Crypto\Tests\Unit\Handlers;

use DateTimeImmutable;
use Nexus\Crypto\Contracts\KeyRotationServiceInterface;
use Nexus\Crypto\Enums\SymmetricAlgorithm;
use Nexus\Crypto\Handlers\KeyRotationHandler;
use Nexus\Crypto\ValueObjects\EncryptionKey;
use Nexus\Scheduler\Enums\JobStatus;
use Nexus\Scheduler\Enums\JobType;
use Nexus\Scheduler\ValueObjects\JobResult;
use Nexus\Scheduler\ValueObjects\ScheduledJob;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(KeyRotationHandler::class)]
final class KeyRotationHandlerTest extends TestCase
{
    private KeyRotationServiceInterface&MockObject $keyRotationService;
    private LoggerInterface&MockObject $logger;
    private KeyRotationHandler $handler;

    protected function setUp(): void
    {
        $this->keyRotationService = $this->createMock(KeyRotationServiceInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->handler = new KeyRotationHandler(
            $this->keyRotationService,
            $this->logger
        );
    }

    // =========================================================================
    // SUPPORTS TESTS
    // =========================================================================

    #[Test]
    public function supports_returns_false_for_standard_job_types(): void
    {
        // Test all standard JobType enum values - none should match 'crypto_key_rotation'
        foreach (JobType::cases() as $jobType) {
            $this->assertFalse(
                $this->handler->supports($jobType),
                "Handler should not support {$jobType->value}"
            );
        }
    }

    // =========================================================================
    // HANDLE TESTS - SUCCESS SCENARIOS
    // =========================================================================

    #[Test]
    public function handle_returns_success_when_no_keys_expiring(): void
    {
        $job = $this->createScheduledJob();

        $this->keyRotationService->expects($this->once())
            ->method('findExpiringKeys')
            ->with(7) // default warning days
            ->willReturn([]);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Key rotation check started', $this->anything());

        $result = $this->handler->handle($job);

        $this->assertTrue($result->success);
        $this->assertIsArray($result->output);
        $this->assertSame(0, $result->output['rotatedCount']);
        $this->assertEmpty($result->output['rotatedKeys']);
    }

    #[Test]
    public function handle_rotates_expiring_keys_successfully(): void
    {
        $job = $this->createScheduledJob(['warningDays' => 14]);

        $expiringKeyIds = ['key-1', 'key-2'];
        $newKey = new EncryptionKey(
            key: base64_encode(random_bytes(32)),
            algorithm: SymmetricAlgorithm::AES256GCM,
            createdAt: new DateTimeImmutable(),
            expiresAt: new DateTimeImmutable('+90 days'),
        );

        $this->keyRotationService->expects($this->once())
            ->method('findExpiringKeys')
            ->with(14)
            ->willReturn($expiringKeyIds);

        $this->keyRotationService->expects($this->exactly(2))
            ->method('rotateKey')
            ->willReturnOnConsecutiveCalls($newKey, $newKey);

        $result = $this->handler->handle($job);

        $this->assertTrue($result->success);
        $this->assertSame(2, $result->output['rotatedCount']);
        $this->assertCount(2, $result->output['rotatedKeys']);
    }

    // =========================================================================
    // HANDLE TESTS - FAILURE SCENARIOS
    // =========================================================================

    #[Test]
    public function handle_returns_failure_with_retry_when_all_rotations_fail(): void
    {
        $job = $this->createScheduledJob();

        $this->keyRotationService->expects($this->once())
            ->method('findExpiringKeys')
            ->willReturn(['key-1', 'key-2']);

        $this->keyRotationService->expects($this->exactly(2))
            ->method('rotateKey')
            ->willThrowException(new \RuntimeException('Rotation failed'));

        $this->logger->expects($this->exactly(2))
            ->method('error')
            ->with('Key rotation failed', $this->anything());

        $result = $this->handler->handle($job);

        $this->assertFalse($result->success);
        $this->assertSame('All key rotations failed', $result->error);
        $this->assertTrue($result->shouldRetry);
        $this->assertSame(600, $result->retryDelaySeconds);
    }

    #[Test]
    public function handle_returns_partial_failure_with_retry_when_some_rotations_fail(): void
    {
        $job = $this->createScheduledJob();

        $newKey = new EncryptionKey(
            key: base64_encode(random_bytes(32)),
            algorithm: SymmetricAlgorithm::AES256GCM,
            createdAt: new DateTimeImmutable(),
            expiresAt: new DateTimeImmutable('+90 days'),
        );

        $this->keyRotationService->expects($this->once())
            ->method('findExpiringKeys')
            ->willReturn(['key-1', 'key-2', 'key-3']);

        $this->keyRotationService->expects($this->exactly(3))
            ->method('rotateKey')
            ->willReturnCallback(function (string $keyId) use ($newKey) {
                if ($keyId === 'key-2') {
                    throw new \RuntimeException('Rotation failed for key-2');
                }
                return $newKey;
            });

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Key rotation completed with errors', $this->anything());

        $result = $this->handler->handle($job);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('1 key(s) failed to rotate', $result->error);
        $this->assertTrue($result->shouldRetry);
        $this->assertSame(300, $result->retryDelaySeconds);
    }

    #[Test]
    public function handle_catches_catastrophic_exception_and_returns_failure(): void
    {
        $job = $this->createScheduledJob();

        $this->keyRotationService->expects($this->once())
            ->method('findExpiringKeys')
            ->willThrowException(new \RuntimeException('Database connection failed'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Key rotation job failed catastrophically', $this->anything());

        $result = $this->handler->handle($job);

        $this->assertFalse($result->success);
        $this->assertSame('Database connection failed', $result->error);
        $this->assertTrue($result->shouldRetry);
        $this->assertSame(600, $result->retryDelaySeconds);
    }

    // =========================================================================
    // HANDLE TESTS - PAYLOAD PROCESSING
    // =========================================================================

    #[Test]
    public function handle_uses_default_warning_days_when_not_in_payload(): void
    {
        $job = $this->createScheduledJob([]);

        $this->keyRotationService->expects($this->once())
            ->method('findExpiringKeys')
            ->with(7) // Default value
            ->willReturn([]);

        $this->handler->handle($job);
    }

    #[Test]
    public function handle_uses_custom_warning_days_from_payload(): void
    {
        $job = $this->createScheduledJob(['warningDays' => 30]);

        $this->keyRotationService->expects($this->once())
            ->method('findExpiringKeys')
            ->with(30)
            ->willReturn([]);

        $this->handler->handle($job);
    }

    // =========================================================================
    // CREATE DAILY SCHEDULE TESTS
    // =========================================================================

    #[Test]
    public function create_daily_schedule_returns_correct_structure(): void
    {
        $schedule = KeyRotationHandler::createDailySchedule();

        $this->assertArrayHasKey('jobType', $schedule);
        $this->assertSame('crypto_key_rotation', $schedule['jobType']);

        $this->assertArrayHasKey('targetId', $schedule);
        $this->assertSame('system', $schedule['targetId']);

        $this->assertArrayHasKey('runAt', $schedule);
        $this->assertInstanceOf(DateTimeImmutable::class, $schedule['runAt']);

        $this->assertArrayHasKey('recurrence', $schedule);
        $this->assertSame('daily', $schedule['recurrence']['type']);
        $this->assertSame(1, $schedule['recurrence']['interval']);

        $this->assertArrayHasKey('payload', $schedule);
        $this->assertSame(7, $schedule['payload']['warningDays']);

        $this->assertArrayHasKey('maxRetries', $schedule);
        $this->assertSame(3, $schedule['maxRetries']);
    }

    #[Test]
    public function create_daily_schedule_uses_custom_warning_days(): void
    {
        $schedule = KeyRotationHandler::createDailySchedule(30);

        $this->assertSame(30, $schedule['payload']['warningDays']);
    }

    #[Test]
    #[DataProvider('warningDaysProvider')]
    public function create_daily_schedule_accepts_various_warning_days(int $warningDays): void
    {
        $schedule = KeyRotationHandler::createDailySchedule($warningDays);

        $this->assertSame($warningDays, $schedule['payload']['warningDays']);
    }

    /**
     * @return array<string, array{int}>
     */
    public static function warningDaysProvider(): array
    {
        return [
            '1 day' => [1],
            '7 days' => [7],
            '14 days' => [14],
            '30 days' => [30],
            '90 days' => [90],
        ];
    }

    // =========================================================================
    // LOGGING TESTS
    // =========================================================================

    #[Test]
    public function handle_logs_successful_key_rotations(): void
    {
        $job = $this->createScheduledJob();

        $newKey = new EncryptionKey(
            key: base64_encode(random_bytes(32)),
            algorithm: SymmetricAlgorithm::AES256GCM,
            createdAt: new DateTimeImmutable(),
            expiresAt: new DateTimeImmutable('+90 days'),
        );

        $this->keyRotationService->expects($this->once())
            ->method('findExpiringKeys')
            ->willReturn(['key-1']);

        $this->keyRotationService->expects($this->once())
            ->method('rotateKey')
            ->willReturn($newKey);

        // Expecting two info logs: start and successful rotation
        $this->logger->expects($this->exactly(2))
            ->method('info');

        $this->handler->handle($job);
    }

    #[Test]
    public function handle_output_includes_duration(): void
    {
        $job = $this->createScheduledJob();

        $this->keyRotationService->expects($this->once())
            ->method('findExpiringKeys')
            ->willReturn([]);

        $result = $this->handler->handle($job);

        $this->assertArrayHasKey('duration', $result->output);
        $this->assertIsFloat($result->output['duration']);
        $this->assertGreaterThanOrEqual(0, $result->output['duration']);
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Create a mock ScheduledJob for testing
     *
     * @param array<string, mixed> $payload
     */
    private function createScheduledJob(array $payload = []): ScheduledJob
    {
        // ULID format: 26 alphanumeric characters, Crockford's Base32
        // Example valid ULID: 01ARZ3NDEKTSV4RRFFQ69G5FAV
        return new ScheduledJob(
            id: '01ARZ3NDEKTSV4RRFFQ69G5FAV',
            jobType: JobType::DATA_CLEANUP, // Using any valid type - handler checks value
            targetId: '01ARZ3NDEKTSV4RRFFQ69G5FAW',
            runAt: new DateTimeImmutable(),
            status: JobStatus::PENDING,
            payload: $payload,
        );
    }
}
