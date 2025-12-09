<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Enums;

use Nexus\ProcurementOperations\Enums\ApprovalLevel;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ApprovalLevel enum.
 */
final class ApprovalLevelTest extends TestCase
{
    /**
     * Test default thresholds for each level.
     */
    public function test_default_threshold_cents_returns_expected_values(): void
    {
        $this->assertSame(500000, ApprovalLevel::LEVEL_1->defaultThresholdCents());
        $this->assertSame(2500000, ApprovalLevel::LEVEL_2->defaultThresholdCents());
        $this->assertSame(10000000, ApprovalLevel::LEVEL_3->defaultThresholdCents());
    }

    /**
     * Test label returns human-readable description.
     */
    public function test_label_returns_human_readable_description(): void
    {
        $this->assertSame('Direct Manager', ApprovalLevel::LEVEL_1->label());
        $this->assertSame('Department Head', ApprovalLevel::LEVEL_2->label());
        $this->assertSame('Finance Director', ApprovalLevel::LEVEL_3->label());
    }

    /**
     * Test setting key generation.
     */
    public function test_setting_key_returns_correct_format(): void
    {
        $this->assertSame(
            'procurement.approval.threshold_level_1_cents',
            ApprovalLevel::LEVEL_1->settingKey()
        );
        $this->assertSame(
            'procurement.approval.threshold_level_2_cents',
            ApprovalLevel::LEVEL_2->settingKey()
        );
    }

    /**
     * Test next level progression.
     */
    public function test_next_level_returns_correct_progression(): void
    {
        $this->assertSame(ApprovalLevel::LEVEL_2, ApprovalLevel::LEVEL_1->nextLevel());
        $this->assertSame(ApprovalLevel::LEVEL_3, ApprovalLevel::LEVEL_2->nextLevel());
        $this->assertSame(ApprovalLevel::LEVEL_4, ApprovalLevel::LEVEL_3->nextLevel());
        $this->assertSame(ApprovalLevel::LEVEL_5, ApprovalLevel::LEVEL_4->nextLevel());
        $this->assertNull(ApprovalLevel::LEVEL_5->nextLevel());
    }

    /**
     * Test is sufficient for amount with default thresholds.
     */
    public function test_is_sufficient_for_with_default_thresholds(): void
    {
        // Level 1 threshold: $5,000 (500000 cents)
        $this->assertTrue(ApprovalLevel::LEVEL_1->isSufficientFor(400000)); // Under
        $this->assertTrue(ApprovalLevel::LEVEL_1->isSufficientFor(500000)); // Exact
        $this->assertFalse(ApprovalLevel::LEVEL_1->isSufficientFor(600000)); // Over
    }

    /**
     * Test is sufficient for amount with custom thresholds.
     */
    public function test_is_sufficient_for_with_custom_thresholds(): void
    {
        $customThresholds = [
            ApprovalLevel::LEVEL_1->value => 1000000, // $10,000
        ];

        // Uses custom threshold of $10,000
        $this->assertTrue(ApprovalLevel::LEVEL_1->isSufficientFor(900000, $customThresholds));
        $this->assertTrue(ApprovalLevel::LEVEL_1->isSufficientFor(1000000, $customThresholds));
        $this->assertFalse(ApprovalLevel::LEVEL_1->isSufficientFor(1100000, $customThresholds));
    }

    /**
     * Test for amount with default thresholds.
     */
    public function test_for_amount_returns_correct_level(): void
    {
        // Under Level 1 threshold ($5,000)
        $this->assertSame(ApprovalLevel::LEVEL_1, ApprovalLevel::forAmount(300000));

        // At Level 1 threshold
        $this->assertSame(ApprovalLevel::LEVEL_1, ApprovalLevel::forAmount(500000));

        // Between Level 1 and Level 2 ($5,001 - $25,000)
        $this->assertSame(ApprovalLevel::LEVEL_2, ApprovalLevel::forAmount(1500000));

        // Between Level 2 and Level 3 ($25,001 - $100,000)
        $this->assertSame(ApprovalLevel::LEVEL_3, ApprovalLevel::forAmount(5000000));
    }

    /**
     * Test for amount with custom thresholds.
     */
    public function test_for_amount_with_custom_thresholds(): void
    {
        $customThresholds = [
            ApprovalLevel::LEVEL_1->value => 100000,  // $1,000
            ApprovalLevel::LEVEL_2->value => 500000,  // $5,000
            ApprovalLevel::LEVEL_3->value => 2500000, // $25,000
        ];

        $this->assertSame(ApprovalLevel::LEVEL_1, ApprovalLevel::forAmount(50000, $customThresholds));
        $this->assertSame(ApprovalLevel::LEVEL_2, ApprovalLevel::forAmount(200000, $customThresholds));
        $this->assertSame(ApprovalLevel::LEVEL_3, ApprovalLevel::forAmount(1000000, $customThresholds));
    }
}
