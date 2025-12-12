<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Enums;

use Nexus\ProcurementOperations\Enums\SOXControlType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SOXControlType::class)]
final class SOXControlTypeTest extends TestCase
{
    #[Test]
    public function all_types_have_valid_values(): void
    {
        $types = SOXControlType::cases();

        $this->assertNotEmpty($types);
        $this->assertCount(8, $types);

        foreach ($types as $type) {
            $this->assertNotEmpty($type->value);
        }
    }

    #[Test]
    #[DataProvider('controlTypeDescriptionProvider')]
    public function getDescription_returns_expected_value(
        SOXControlType $type,
        string $expectedContains,
    ): void {
        $description = $type->getDescription();

        $this->assertStringContainsStringIgnoringCase($expectedContains, $description);
    }

    /**
     * @return iterable<array{SOXControlType, string}>
     */
    public static function controlTypeDescriptionProvider(): iterable
    {
        yield 'PREVENTIVE describes prevention' => [SOXControlType::PREVENTIVE, 'prevent'];
        yield 'DETECTIVE describes detection' => [SOXControlType::DETECTIVE, 'detect'];
        yield 'CORRECTIVE describes correction' => [SOXControlType::CORRECTIVE, 'correct'];
        yield 'ITGC describes IT' => [SOXControlType::ITGC, 'IT'];
        yield 'MANUAL describes manual' => [SOXControlType::MANUAL, 'manual'];
        yield 'AUTOMATED describes automated' => [SOXControlType::AUTOMATED, 'automated'];
    }

    #[Test]
    public function isPreventive_returns_correct_value(): void
    {
        $this->assertTrue(SOXControlType::PREVENTIVE->isPreventive());

        $this->assertFalse(SOXControlType::DETECTIVE->isPreventive());
        $this->assertFalse(SOXControlType::CORRECTIVE->isPreventive());
        $this->assertFalse(SOXControlType::MANUAL->isPreventive());
        $this->assertFalse(SOXControlType::AUTOMATED->isPreventive());
    }

    #[Test]
    public function isDetective_returns_correct_value(): void
    {
        $this->assertTrue(SOXControlType::DETECTIVE->isDetective());

        $this->assertFalse(SOXControlType::PREVENTIVE->isDetective());
        $this->assertFalse(SOXControlType::CORRECTIVE->isDetective());
        $this->assertFalse(SOXControlType::MANUAL->isDetective());
        $this->assertFalse(SOXControlType::AUTOMATED->isDetective());
    }

    #[Test]
    public function isCorrective_returns_correct_value(): void
    {
        $this->assertTrue(SOXControlType::CORRECTIVE->isCorrective());

        $this->assertFalse(SOXControlType::PREVENTIVE->isCorrective());
        $this->assertFalse(SOXControlType::DETECTIVE->isCorrective());
        $this->assertFalse(SOXControlType::MANUAL->isCorrective());
    }

    #[Test]
    public function isAutomated_returns_correct_value(): void
    {
        $this->assertTrue(SOXControlType::AUTOMATED->isAutomated());

        $this->assertFalse(SOXControlType::PREVENTIVE->isAutomated());
        $this->assertFalse(SOXControlType::DETECTIVE->isAutomated());
        $this->assertFalse(SOXControlType::MANUAL->isAutomated());
    }

    #[Test]
    public function isManual_returns_correct_value(): void
    {
        $this->assertTrue(SOXControlType::MANUAL->isManual());

        $this->assertFalse(SOXControlType::PREVENTIVE->isManual());
        $this->assertFalse(SOXControlType::DETECTIVE->isManual());
        $this->assertFalse(SOXControlType::AUTOMATED->isManual());
    }

    #[Test]
    public function isHybrid_returns_correct_value(): void
    {
        $this->assertTrue(SOXControlType::HYBRID->isHybrid());

        $this->assertFalse(SOXControlType::PREVENTIVE->isHybrid());
        $this->assertFalse(SOXControlType::DETECTIVE->isHybrid());
        $this->assertFalse(SOXControlType::MANUAL->isHybrid());
        $this->assertFalse(SOXControlType::AUTOMATED->isHybrid());
    }

    #[Test]
    public function getAuditingImplication_returns_meaningful_string(): void
    {
        foreach (SOXControlType::cases() as $type) {
            $implication = $type->getAuditingImplication();

            $this->assertNotEmpty($implication);
            $this->assertIsString($implication);
        }
    }

    #[Test]
    public function getTestingFrequency_returns_valid_frequency(): void
    {
        $validFrequencies = ['continuous', 'daily', 'weekly', 'monthly', 'quarterly', 'annual'];

        foreach (SOXControlType::cases() as $type) {
            $frequency = $type->getTestingFrequency();

            $this->assertContains(
                $frequency,
                $validFrequencies,
                "Type {$type->value} returned invalid frequency: {$frequency}",
            );
        }
    }

    #[Test]
    public function automated_controls_require_continuous_testing(): void
    {
        $frequency = SOXControlType::AUTOMATED->getTestingFrequency();

        $this->assertEquals('continuous', $frequency);
    }

    #[Test]
    public function manual_controls_require_less_frequent_testing(): void
    {
        $frequency = SOXControlType::MANUAL->getTestingFrequency();

        $this->assertContains($frequency, ['weekly', 'monthly', 'quarterly']);
    }

    #[Test]
    public function getDocumentationRequirement_returns_level(): void
    {
        $validLevels = ['minimal', 'standard', 'comprehensive', 'extensive'];

        foreach (SOXControlType::cases() as $type) {
            $requirement = $type->getDocumentationRequirement();

            $this->assertContains(
                $requirement,
                $validLevels,
                "Type {$type->value} returned invalid documentation requirement: {$requirement}",
            );
        }
    }
}
