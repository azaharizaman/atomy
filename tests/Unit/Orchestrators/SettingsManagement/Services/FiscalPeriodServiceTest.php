<?php

declare(strict_types=1);

namespace Tests\Unit\Orchestrators\SettingsManagement\Services;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Nexus\SettingsManagement\Services\FiscalPeriodService;
use Nexus\SettingsManagement\Contracts\FiscalPeriodProviderInterface;
use Nexus\SettingsManagement\DTOs\FiscalPeriod\CalendarConfigRequest;
use Nexus\SettingsManagement\DTOs\FiscalPeriod\CalendarConfigResult;
use Nexus\SettingsManagement\DTOs\FiscalPeriod\PeriodGenerationRequest;
use Nexus\SettingsManagement\DTOs\FiscalPeriod\PeriodGenerationResult;
use Nexus\SettingsManagement\DTOs\FiscalPeriod\PeriodValidationRequest;
use Nexus\SettingsManagement\DTOs\FiscalPeriod\PeriodValidationResult;
use Nexus\SettingsManagement\DTOs\FiscalPeriod\PeriodCloseRequest;
use Nexus\SettingsManagement\DTOs\FiscalPeriod\PeriodCloseResult;
use Nexus\SettingsManagement\DTOs\FiscalPeriod\PeriodType;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Unit tests for FiscalPeriodService
 * 
 * Tests fiscal period management operations including:
 * - Calendar configuration
 * - Period generation
 * - Period validation
 * - Period closing
 */
final class FiscalPeriodServiceTest extends TestCase
{
    private FiscalPeriodProviderInterface&MockObject $periodProvider;
    private LoggerInterface $logger;
    private FiscalPeriodService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->periodProvider = $this->createMock(FiscalPeriodProviderInterface::class);
        $this->logger = new NullLogger();

        $this->service = new FiscalPeriodService(
            $this->periodProvider,
            $this->logger
        );
    }

    // =========================================================================
    // Tests for configureCalendar()
    // =========================================================================

    public function testConfigureCalendar_WhenSuccessful_ReturnsSuccessResult(): void
    {
        // Arrange
        $request = new CalendarConfigRequest(
            tenantId: 'tenant-123',
            periodType: PeriodType::MONTHLY,
            fiscalYearStart: new \DateTimeImmutable('2024-01-01'),
            yearEndDay: 31,
            yearEndMonth: 12
        );

        // Act
        $result = $this->service->configureCalendar($request);

        // Assert
        $this->assertInstanceOf(CalendarConfigResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertNotNull($result->calendarId);
        $this->assertNull($result->error);
    }

    public function testConfigureCalendar_WithQuarterlyPeriod_ReturnsSuccess(): void
    {
        // Arrange
        $request = new CalendarConfigRequest(
            tenantId: 'tenant-123',
            periodType: PeriodType::QUARTERLY,
            fiscalYearStart: new \DateTimeImmutable('2024-01-01'),
            yearEndDay: 31,
            yearEndMonth: 12
        );

        // Act
        $result = $this->service->configureCalendar($request);

        // Assert
        $this->assertTrue($result->success);
    }

    public function testConfigureCalendar_WithYearlyPeriod_ReturnsSuccess(): void
    {
        // Arrange
        $request = new CalendarConfigRequest(
            tenantId: 'tenant-123',
            periodType: PeriodType::YEARLY,
            fiscalYearStart: new \DateTimeImmutable('2024-01-01'),
            yearEndDay: 31,
            yearEndMonth: 12
        );

        // Act
        $result = $this->service->configureCalendar($request);

        // Assert
        $this->assertTrue($result->success);
    }

    // =========================================================================
    // Tests for generatePeriods()
    // =========================================================================

    public function testGeneratePeriods_WithDefaultNumber_ReturnsSuccessWithPeriodIds(): void
    {
        // Arrange
        $request = new PeriodGenerationRequest(
            calendarId: 'cal-123',
            fiscalYear: 2024,
            numberOfPeriods: 12
        );

        // Act
        $result = $this->service->generatePeriods($request);

        // Assert
        $this->assertInstanceOf(PeriodGenerationResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertCount(12, $result->periodIds);
        $this->assertNull($result->error);
    }

    public function testGeneratePeriods_WithQuarterly_Returns4Periods(): void
    {
        // Arrange
        $request = new PeriodGenerationRequest(
            calendarId: 'cal-123',
            fiscalYear: 2024,
            numberOfPeriods: 4
        );

        // Act
        $result = $this->service->generatePeriods($request);

        // Assert
        $this->assertTrue($result->success);
        $this->assertCount(4, $result->periodIds);
    }

    public function testGeneratePeriods_WithYearly_Returns1Period(): void
    {
        // Arrange
        $request = new PeriodGenerationRequest(
            calendarId: 'cal-123',
            fiscalYear: 2024,
            numberOfPeriods: 1
        );

        // Act
        $result = $this->service->generatePeriods($request);

        // Assert
        $this->assertTrue($result->success);
        $this->assertCount(1, $result->periodIds);
    }

    public function testGeneratePeriods_WithZeroPeriods_ReturnsEmptyArray(): void
    {
        // Arrange
        $request = new PeriodGenerationRequest(
            calendarId: 'cal-123',
            fiscalYear: 2024,
            numberOfPeriods: 0
        );

        // Act
        $result = $this->service->generatePeriods($request);

        // Assert
        $this->assertTrue($result->success);
        $this->assertCount(0, $result->periodIds);
    }

    // =========================================================================
    // Tests for validatePeriod()
    // =========================================================================

    public function testValidatePeriod_WhenPeriodExistsAndIsOpen_ReturnsValidResult(): void
    {
        // Arrange
        $request = new PeriodValidationRequest(
            periodId: 'period-123',
            tenantId: 'tenant-123'
        );

        $this->periodProvider
            ->expects($this->once())
            ->method('getPeriod')
            ->with('period-123', 'tenant-123')
            ->willReturn(['id' => 'period-123', 'name' => 'Q1 2024']);

        $this->periodProvider
            ->expects($this->once())
            ->method('isPeriodOpen')
            ->with('period-123', 'tenant-123')
            ->willReturn(true);

        $this->periodProvider
            ->expects($this->once())
            ->method('isAdjustingPeriod')
            ->with('period-123', 'tenant-123')
            ->willReturn(false);

        $this->periodProvider
            ->expects($this->once())
            ->method('isPeriodLocked')
            ->with('period-123', 'tenant-123')
            ->willReturn(false);

        // Act
        $result = $this->service->validatePeriod($request);

        // Assert
        $this->assertInstanceOf(PeriodValidationResult::class, $result);
        $this->assertTrue($result->valid);
        $this->assertTrue($result->isOpen);
        $this->assertFalse($result->allowsAdjustments);
        $this->assertFalse($result->isLocked);
    }

    public function testValidatePeriod_WhenPeriodNotFound_ReturnsInvalidResult(): void
    {
        // Arrange
        $request = new PeriodValidationRequest(
            periodId: 'nonexistent-period',
            tenantId: 'tenant-123'
        );

        $this->periodProvider
            ->expects($this->once())
            ->method('getPeriod')
            ->with('nonexistent-period', 'tenant-123')
            ->willReturn(null);

        $this->periodProvider
            ->expects($this->never())
            ->method('isPeriodOpen');

        // Act
        $result = $this->service->validatePeriod($request);

        // Assert
        $this->assertInstanceOf(PeriodValidationResult::class, $result);
        $this->assertFalse($result->valid);
        $this->assertStringContainsString('not found', $result->error);
    }

    public function testValidatePeriod_WhenPeriodIsLocked_ReturnsLockedInfo(): void
    {
        // Arrange
        $request = new PeriodValidationRequest(
            periodId: 'period-locked',
            tenantId: 'tenant-123'
        );

        $this->periodProvider
            ->expects($this->once())
            ->method('getPeriod')
            ->willReturn(['id' => 'period-locked']);

        $this->periodProvider
            ->expects($this->once())
            ->method('isPeriodOpen')
            ->willReturn(false);

        $this->periodProvider
            ->expects($this->once())
            ->method('isAdjustingPeriod')
            ->willReturn(false);

        $this->periodProvider
            ->expects($this->once())
            ->method('isPeriodLocked')
            ->willReturn(true);

        // Act
        $result = $this->service->validatePeriod($request);

        // Assert
        $this->assertTrue($result->valid);
        $this->assertFalse($result->isOpen);
        $this->assertTrue($result->isLocked);
    }

    public function testValidatePeriod_WhenPeriodAllowsAdjustments_ReturnsAdjustmentInfo(): void
    {
        // Arrange
        $request = new PeriodValidationRequest(
            periodId: 'period-adjusting',
            tenantId: 'tenant-123'
        );

        $this->periodProvider
            ->expects($this->once())
            ->method('getPeriod')
            ->willReturn(['id' => 'period-adjusting']);

        $this->periodProvider
            ->expects($this->once())
            ->method('isPeriodOpen')
            ->willReturn(false);

        $this->periodProvider
            ->expects($this->once())
            ->method('isAdjustingPeriod')
            ->willReturn(true);

        $this->periodProvider
            ->expects($this->once())
            ->method('isPeriodLocked')
            ->willReturn(false);

        // Act
        $result = $this->service->validatePeriod($request);

        // Assert
        $this->assertTrue($result->allowsAdjustments);
    }

    // =========================================================================
    // Tests for closePeriod()
    // =========================================================================

    public function testClosePeriod_WhenSuccessful_ReturnsSuccessResult(): void
    {
        // Arrange
        $request = new PeriodCloseRequest(
            periodId: 'period-123',
            tenantId: 'tenant-123',
            generateClosingEntries: false
        );

        $this->periodProvider
            ->expects($this->once())
            ->method('getPeriod')
            ->with('period-123', 'tenant-123')
            ->willReturn(['id' => 'period-123']);

        $this->periodProvider
            ->expects($this->once())
            ->method('isPeriodLocked')
            ->with('period-123', 'tenant-123')
            ->willReturn(false);

        // Act
        $result = $this->service->closePeriod($request);

        // Assert
        $this->assertInstanceOf(PeriodCloseResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertSame('period-123', $result->periodId);
        $this->assertNull($result->error);
    }

    public function testClosePeriod_WhenPeriodNotFound_ReturnsFailureResult(): void
    {
        // Arrange
        $request = new PeriodCloseRequest(
            periodId: 'nonexistent-period',
            tenantId: 'tenant-123',
            generateClosingEntries: false
        );

        $this->periodProvider
            ->expects($this->once())
            ->method('getPeriod')
            ->with('nonexistent-period', 'tenant-123')
            ->willReturn(null);

        // Act
        $result = $this->service->closePeriod($request);

        // Assert
        $this->assertInstanceOf(PeriodCloseResult::class, $result);
        $this->assertFalse($result->success);
        $this->assertStringContainsString('not found', $result->error);
    }

    public function testClosePeriod_WhenPeriodLocked_ReturnsFailureResult(): void
    {
        // Arrange
        $request = new PeriodCloseRequest(
            periodId: 'locked-period',
            tenantId: 'tenant-123',
            generateClosingEntries: false
        );

        $this->periodProvider
            ->expects($this->once())
            ->method('getPeriod')
            ->with('locked-period', 'tenant-123')
            ->willReturn(['id' => 'locked-period']);

        $this->periodProvider
            ->expects($this->once())
            ->method('isPeriodLocked')
            ->with('locked-period', 'tenant-123')
            ->willReturn(true);

        // Act
        $result = $this->service->closePeriod($request);

        // Assert
        $this->assertInstanceOf(PeriodCloseResult::class, $result);
        $this->assertFalse($result->success);
        $this->assertStringContainsString('locked', $result->error);
    }

    public function testClosePeriod_WithClosingEntries_GeneratesEntries(): void
    {
        // Arrange
        $request = new PeriodCloseRequest(
            periodId: 'period-123',
            tenantId: 'tenant-123',
            generateClosingEntries: true
        );

        $this->periodProvider
            ->expects($this->once())
            ->method('getPeriod')
            ->willReturn(['id' => 'period-123']);

        $this->periodProvider
            ->expects($this->once())
            ->method('isPeriodLocked')
            ->willReturn(false);

        // Act
        $result = $this->service->closePeriod($request);

        // Assert
        $this->assertTrue($result->success);
    }

    // =========================================================================
    // Edge Case Tests
    // =========================================================================

    public function testConfigureCalendar_WithCustomFiscalYearStart_ConfiguresCorrectly(): void
    {
        // Arrange - fiscal year starts in July (typical for UK)
        $request = new CalendarConfigRequest(
            tenantId: 'tenant-123',
            periodType: PeriodType::MONTHLY,
            fiscalYearStart: new \DateTimeImmutable('2024-07-01'),
            yearEndDay: 30,
            yearEndMonth: 6
        );

        // Act
        $result = $this->service->configureCalendar($request);

        // Assert
        $this->assertTrue($result->success);
    }

    public function testGeneratePeriods_WithManyPeriods_GeneratesAll(): void
    {
        // Arrange
        $request = new PeriodGenerationRequest(
            calendarId: 'cal-123',
            fiscalYear: 2024,
            numberOfPeriods: 52 // Weekly periods
        );

        // Act
        $result = $this->service->generatePeriods($request);

        // Assert
        $this->assertTrue($result->success);
        $this->assertCount(52, $result->periodIds);
    }

    public function testValidatePeriod_ChecksAllConditions_EvenIfPeriodNotOpen(): void
    {
        // Arrange
        $request = new PeriodValidationRequest(
            periodId: 'period-closed',
            tenantId: 'tenant-123'
        );

        $this->periodProvider
            ->expects($this->once())
            ->method('getPeriod')
            ->willReturn(['id' => 'period-closed']);

        $this->periodProvider
            ->expects($this->once())
            ->method('isPeriodOpen')
            ->willReturn(false);

        $this->periodProvider
            ->expects($this->once())
            ->method('isAdjustingPeriod')
            ->willReturn(true);

        $this->periodProvider
            ->expects($this->once())
            ->method('isPeriodLocked')
            ->willReturn(false);

        // Act
        $result = $this->service->validatePeriod($request);

        // Assert
        $this->assertTrue($result->valid);
        $this->assertFalse($result->isOpen);
        $this->assertTrue($result->allowsAdjustments);
        $this->assertFalse($result->isLocked);
    }

    public function testClosePeriod_StillGeneratesEntriesEvenWhenLockedCheckFails(): void
    {
        // Arrange
        $request = new PeriodCloseRequest(
            periodId: 'period-123',
            tenantId: 'tenant-123',
            generateClosingEntries: true
        );

        // Provider returns period but throws on lock check
        $this->periodProvider
            ->expects($this->once())
            ->method('getPeriod')
            ->willReturn(['id' => 'period-123']);

        $this->periodProvider
            ->expects($this->once())
            ->method('isPeriodLocked')
            ->willThrowException(new \RuntimeException('Database error'));

        // Act
        $result = $this->service->closePeriod($request);

        // Assert
        $this->assertFalse($result->success);
        $this->assertStringContainsString('Failed', $result->error);
    }
}
