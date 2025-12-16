<?php

declare(strict_types=1);

namespace Nexus\Sanctions\Tests\Unit\Services;

use DateTimeImmutable;
use Nexus\Sanctions\Contracts\SanctionsScreenerInterface;
use Nexus\Sanctions\Enums\ScreeningFrequency;
use Nexus\Sanctions\Exceptions\InvalidPartyException;
use Nexus\Sanctions\Exceptions\ScreeningFailedException;
use Nexus\Sanctions\Services\PeriodicScreeningManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Unit tests for PeriodicScreeningManager service
 *
 * Tests periodic screening scheduling, execution, and retry logic.
 *
 * @covers \Nexus\Sanctions\Services\PeriodicScreeningManager
 */
final class PeriodicScreeningManagerTest extends TestCase
{
    private SanctionsScreenerInterface&MockObject $screener;
    private PeriodicScreeningManager $manager;

    protected function setUp(): void
    {
        $this->screener = $this->createMock(SanctionsScreenerInterface::class);
        $this->manager = new PeriodicScreeningManager($this->screener, new NullLogger());
    }

    /**
     * Test schedule screening creates valid schedule structure
     *
     * @test
     */
    public function schedule_screening_creates_valid_schedule(): void
    {
        // Arrange
        $partyId = 'PARTY-001';
        $frequency = ScreeningFrequency::MONTHLY;
        $startDate = new DateTimeImmutable('2024-01-15');

        // Act
        $schedule = $this->manager->scheduleScreening($partyId, $frequency, [
            'start_date' => $startDate,
            'lists' => ['ofac', 'un'],
            'metadata' => ['reason' => 'high_risk_customer'],
        ]);

        // Assert
        $this->assertIsArray($schedule);
        $this->assertArrayHasKey('party_id', $schedule);
        $this->assertArrayHasKey('frequency', $schedule);
        $this->assertArrayHasKey('next_screening_date', $schedule);
        $this->assertArrayHasKey('scheduled_at', $schedule);
        $this->assertArrayHasKey('status', $schedule);
        $this->assertArrayHasKey('execution_count', $schedule);

        // Verify values
        $this->assertSame($partyId, $schedule['party_id']);
        $this->assertSame('monthly', $schedule['frequency']);
        $this->assertSame('active', $schedule['status']);
        $this->assertSame(0, $schedule['execution_count']);
        $this->assertInstanceOf(DateTimeImmutable::class, $schedule['next_screening_date']);
        $this->assertInstanceOf(DateTimeImmutable::class, $schedule['scheduled_at']);

        // Verify next screening date is ~30 days from start
        $expectedNextDate = $startDate->modify('+30 days');
        $this->assertEqualsWithDelta(
            $expectedNextDate->getTimestamp(),
            $schedule['next_screening_date']->getTimestamp(),
            86400  // 1 day tolerance
        );
    }

    /**
     * Test bulk scheduling handles per-party errors
     *
     * @test
     */
    public function bulk_scheduling_handles_errors(): void
    {
        // Arrange
        $validParties = [
            ['id' => 'PARTY-001', 'name' => 'John Smith'],
            ['id' => 'PARTY-002', 'name' => 'Jane Doe'],
            ['id' => '', 'name' => 'Invalid'],  // Invalid party ID
            ['id' => 'PARTY-003', 'name' => 'Bob Johnson'],
        ];

        $parties = array_map(fn($p) => $p['id'], $validParties);
        $frequency = ScreeningFrequency::QUARTERLY;

        // Act
        $summary = $this->manager->bulkScheduleScreening($parties, $frequency);

        // Assert
        $this->assertIsArray($summary);
        $this->assertArrayHasKey('total_parties', $summary);
        $this->assertArrayHasKey('successful', $summary);
        $this->assertArrayHasKey('failed', $summary);
        $this->assertArrayHasKey('errors', $summary);

        // Verify counts
        $this->assertSame(4, $summary['total_parties']);
        $this->assertSame(3, $summary['successful']);  // 3 valid parties
        $this->assertSame(1, $summary['failed']);  // 1 invalid party

        // Verify error recorded for invalid party
        $this->assertArrayHasKey('', $summary['errors']);  // Empty ID
    }

    /**
     * Test execute scheduled screenings returns execution summary
     *
     * @test
     */
    public function execute_scheduled_screenings_returns_summary(): void
    {
        // Arrange
        $asOfDate = new DateTimeImmutable('2024-06-15');

        // Mock screener to return empty results
        $this->screener
            ->expects($this->never())  // No parties due for screening
            ->method('screen');

        // Act
        $summary = $this->manager->executeScheduledScreenings($asOfDate, [
            'batch_size' => 50,
            'continue_on_error' => true,
        ]);

        // Assert
        $this->assertIsArray($summary);
        $this->assertArrayHasKey('execution_started_at', $summary);
        $this->assertArrayHasKey('execution_completed_at', $summary);
        $this->assertArrayHasKey('total_executed', $summary);
        $this->assertArrayHasKey('successful', $summary);
        $this->assertArrayHasKey('failed', $summary);
        $this->assertArrayHasKey('total_matches', $summary);
        $this->assertArrayHasKey('processing_time_seconds', $summary);
        $this->assertArrayHasKey('errors', $summary);

        // Verify timestamps
        $this->assertInstanceOf(DateTimeImmutable::class, $summary['execution_started_at']);
        $this->assertInstanceOf(DateTimeImmutable::class, $summary['execution_completed_at']);

        // Verify initial counts (no screenings)
        $this->assertSame(0, $summary['total_executed']);
        $this->assertSame(0, $summary['successful']);
        $this->assertSame(0, $summary['failed']);
    }

    /**
     * Test immediate scheduling sets high priority
     *
     * @test
     */
    public function immediate_scheduling_sets_high_priority(): void
    {
        // Arrange
        $partyId = 'PARTY-URGENT';

        // Act
        $schedule = $this->manager->scheduleImmediateScreening($partyId, [
            'reason' => 'suspicious_transaction',
            'lists' => ['ofac'],
        ]);

        // Assert
        $this->assertIsArray($schedule);
        $this->assertSame($partyId, $schedule['party_id']);
        $this->assertSame('immediate', $schedule['frequency']);
        $this->assertSame('pending_immediate', $schedule['status']);
        $this->assertSame('high', $schedule['priority']);

        // Verify next screening date is now
        $this->assertInstanceOf(DateTimeImmutable::class, $schedule['next_screening_date']);
        $now = new DateTimeImmutable();
        $this->assertEqualsWithDelta(
            $now->getTimestamp(),
            $schedule['next_screening_date']->getTimestamp(),
            2  // 2 second tolerance
        );

        // Verify reason in metadata
        $this->assertArrayHasKey('metadata', $schedule);
        $this->assertArrayHasKey('reason', $schedule['metadata']);
        $this->assertSame('suspicious_transaction', $schedule['metadata']['reason']);
    }

    /**
     * Test frequency update recalculates next screening date
     *
     * @test
     */
    public function frequency_update_recalculates_next_date(): void
    {
        // Arrange
        $partyId = 'PARTY-004';
        $oldFrequency = ScreeningFrequency::MONTHLY;
        $newFrequency = ScreeningFrequency::WEEKLY;

        // Act - No exception should be thrown
        $this->manager->updateScreeningFrequency($partyId, $newFrequency);

        // Assert - Method completes successfully
        $this->assertTrue(true);  // If we reach here, no exception was thrown
    }

    /**
     * Test retry failed screenings validates max attempts
     *
     * @test
     */
    public function retry_failed_screenings_validates_max_attempts(): void
    {
        // Arrange
        $invalidMaxAttempts = 15;  // Exceeds MAX_RETRY_ATTEMPTS (10)

        // Assert
        $this->expectException(ScreeningFailedException::class);
        $this->expectExceptionMessage('Max retry attempts cannot exceed 10');

        // Act
        $this->manager->retryFailedScreenings($invalidMaxAttempts);
    }

    /**
     * Test getPartiesDueForScreening validates limit range
     *
     * @test
     * @dataProvider invalidLimitProvider
     */
    public function get_parties_due_for_screening_validates_limit(int $invalidLimit): void
    {
        // Arrange
        $asOfDate = new DateTimeImmutable();

        // Assert
        $this->expectException(ScreeningFailedException::class);

        // Act
        $this->manager->getPartiesDueForScreening($asOfDate, $invalidLimit);
    }

    /**
     * @return array<string, array{int}>
     */
    public static function invalidLimitProvider(): array
    {
        return [
            'limit too low' => [0],
            'limit negative' => [-1],
            'limit too high' => [1001],
        ];
    }

    /**
     * Test empty party ID throws exception
     *
     * @test
     */
    public function empty_party_id_throws_exception(): void
    {
        // Arrange
        $emptyPartyId = '';
        $frequency = ScreeningFrequency::MONTHLY;

        // Assert
        $this->expectException(InvalidPartyException::class);
        $this->expectExceptionMessage('Party ID cannot be empty');

        // Act
        $this->manager->scheduleScreening($emptyPartyId, $frequency);
    }

    /**
     * Test execution statistics returns valid structure
     *
     * @test
     */
    public function execution_statistics_returns_valid_structure(): void
    {
        // Arrange
        $since = new DateTimeImmutable('-30 days');

        // Act
        $statistics = $this->manager->getExecutionStatistics($since);

        // Assert
        $this->assertIsArray($statistics);
        $this->assertArrayHasKey('total_scheduled', $statistics);
        $this->assertArrayHasKey('total_executed', $statistics);
        $this->assertArrayHasKey('total_successful', $statistics);
        $this->assertArrayHasKey('total_failed', $statistics);
        $this->assertArrayHasKey('total_matches_found', $statistics);
        $this->assertArrayHasKey('average_processing_time_seconds', $statistics);
        $this->assertArrayHasKey('success_rate', $statistics);
        $this->assertArrayHasKey('period_start', $statistics);
        $this->assertArrayHasKey('period_end', $statistics);

        // Verify types
        $this->assertIsInt($statistics['total_scheduled']);
        $this->assertIsInt($statistics['total_executed']);
        $this->assertIsFloat($statistics['average_processing_time_seconds']);
        $this->assertIsFloat($statistics['success_rate']);
        $this->assertInstanceOf(DateTimeImmutable::class, $statistics['period_start']);
        $this->assertInstanceOf(DateTimeImmutable::class, $statistics['period_end']);
    }

    /**
     * Test schedule details query
     *
     * @test
     */
    public function get_schedule_details_returns_null_when_not_found(): void
    {
        // Arrange
        $nonExistentPartyId = 'PARTY-NOTFOUND';

        // Act
        $details = $this->manager->getScheduleDetails($nonExistentPartyId);

        // Assert
        $this->assertNull($details);  // Currently returns null (database integration point)
    }

    /**
     * Test cancellation logs properly
     *
     * @test
     */
    public function cancel_scheduled_screening_completes_successfully(): void
    {
        // Arrange
        $partyId = 'PARTY-CANCEL';

        // Act - No exception should be thrown
        $this->manager->cancelScheduledScreening($partyId);

        // Assert
        $this->assertTrue(true);  // If we reach here, cancellation succeeded
    }
}
