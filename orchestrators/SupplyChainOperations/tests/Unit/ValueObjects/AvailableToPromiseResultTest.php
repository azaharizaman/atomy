<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Tests\Unit\ValueObjects;

use DateTimeImmutable;
use Nexus\SupplyChainOperations\ValueObjects\AvailableToPromiseResult;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AvailableToPromiseResult value object.
 *
 * @covers \Nexus\SupplyChainOperations\ValueObjects\AvailableToPromiseResult
 */
final class AvailableToPromiseResultTest extends TestCase
{
    public function test_constructor_creates_result_with_all_properties(): void
    {
        // Arrange
        $date = new DateTimeImmutable('2024-12-25');

        // Act
        $result = new AvailableToPromiseResult(
            promisedDate: $date,
            confidence: 0.85,
            availableNow: false,
            requiresProcurement: true,
            estimatedLeadTimeDays: 14,
            shortageQuantity: 50.0,
            metadata: ['vendor_id' => 'vendor-001']
        );

        // Assert
        $this->assertSame($date, $result->promisedDate);
        $this->assertSame(0.85, $result->confidence);
        $this->assertFalse($result->availableNow);
        $this->assertTrue($result->requiresProcurement);
        $this->assertSame(14, $result->estimatedLeadTimeDays);
        $this->assertSame(50.0, $result->shortageQuantity);
        $this->assertSame(['vendor_id' => 'vendor-001'], $result->metadata);
    }

    public function test_constructor_throws_exception_for_invalid_confidence(): void
    {
        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Confidence must be between 0.0 and 1.0');

        // Act
        new AvailableToPromiseResult(
            promisedDate: new DateTimeImmutable(),
            confidence: 1.5, // Invalid: > 1.0
            availableNow: true,
            requiresProcurement: false
        );
    }

    public function test_constructor_throws_exception_for_negative_confidence(): void
    {
        // Assert
        $this->expectException(\InvalidArgumentException::class);

        // Act
        new AvailableToPromiseResult(
            promisedDate: new DateTimeImmutable(),
            confidence: -0.5, // Invalid: < 0.0
            availableNow: true,
            requiresProcurement: false
        );
    }

    public function test_get_promised_date_string_formats_correctly(): void
    {
        // Arrange
        $result = new AvailableToPromiseResult(
            promisedDate: new DateTimeImmutable('2024-12-25 14:30:00'),
            confidence: 0.9,
            availableNow: false,
            requiresProcurement: true
        );

        // Act & Assert
        $this->assertSame('2024-12-25', $result->getPromisedDateString());
        $this->assertSame('25-12-2024', $result->getPromisedDateString('d-m-Y'));
    }

    public function test_get_confidence_percentage_returns_correct_value(): void
    {
        // Arrange
        $result = new AvailableToPromiseResult(
            promisedDate: new DateTimeImmutable(),
            confidence: 0.85,
            availableNow: true,
            requiresProcurement: false
        );

        // Act & Assert
        $this->assertSame(85.0, $result->getConfidencePercentage());
    }

    public function test_is_high_confidence_returns_true_for_high_values(): void
    {
        // Arrange
        $highConfidence = new AvailableToPromiseResult(
            promisedDate: new DateTimeImmutable(),
            confidence: 0.85,
            availableNow: true,
            requiresProcurement: false
        );

        $lowConfidence = new AvailableToPromiseResult(
            promisedDate: new DateTimeImmutable(),
            confidence: 0.75,
            availableNow: true,
            requiresProcurement: false
        );

        // Act & Assert
        $this->assertTrue($highConfidence->isHighConfidence());
        $this->assertFalse($lowConfidence->isHighConfidence());
    }

    public function test_to_array_serializes_all_data(): void
    {
        // Arrange
        $date = new DateTimeImmutable('2024-12-25');
        $result = new AvailableToPromiseResult(
            promisedDate: $date,
            confidence: 0.9,
            availableNow: false,
            requiresProcurement: true,
            estimatedLeadTimeDays: 7,
            shortageQuantity: 25.0,
            metadata: ['key' => 'value']
        );

        // Act
        $array = $result->toArray();

        // Assert
        $this->assertIsArray($array);
        $this->assertSame('2024-12-25T00:00:00+00:00', $array['promised_date']);
        $this->assertSame(0.9, $array['confidence']);
        $this->assertSame(90.0, $array['confidence_percentage']);
        $this->assertFalse($array['available_now']);
        $this->assertTrue($array['requires_procurement']);
        $this->assertSame(7, $array['estimated_lead_time_days']);
        $this->assertSame(25.0, $array['shortage_quantity']);
        $this->assertSame(['key' => 'value'], $array['metadata']);
    }

    public function test_available_now_factory_creates_correct_result(): void
    {
        // Arrange
        $now = new DateTimeImmutable();

        // Act
        $result = AvailableToPromiseResult::availableNow($now);

        // Assert
        $this->assertSame($now, $result->promisedDate);
        $this->assertTrue($result->availableNow);
        $this->assertFalse($result->requiresProcurement);
        $this->assertSame(0.95, $result->confidence);
        $this->assertNull($result->estimatedLeadTimeDays);
        $this->assertNull($result->shortageQuantity);
    }

    public function test_unavailable_factory_creates_fallback_result(): void
    {
        // Act
        $result = AvailableToPromiseResult::unavailable('Out of stock indefinitely');

        // Assert
        $this->assertFalse($result->availableNow);
        $this->assertTrue($result->requiresProcurement);
        $this->assertSame(0.0, $result->confidence);
        $this->assertSame('Out of stock indefinitely', $result->metadata['unavailable_reason']);
    }

    public function test_result_is_immutable(): void
    {
        // Arrange
        $result = new AvailableToPromiseResult(
            promisedDate: new DateTimeImmutable(),
            confidence: 0.9,
            availableNow: true,
            requiresProcurement: false
        );

        // Assert - All properties should be readonly
        // This is enforced by PHP's readonly modifier
        $this->assertInstanceOf(AvailableToPromiseResult::class, $result);
    }
}
