<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Enums;

use Nexus\ProcurementOperations\Enums\P2PStep;
use Nexus\ProcurementOperations\Enums\SOXControlPoint;
use Nexus\ProcurementOperations\Enums\SOXControlType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SOXControlPoint::class)]
final class SOXControlPointTest extends TestCase
{
    #[Test]
    public function all_control_points_have_valid_values(): void
    {
        $controlPoints = SOXControlPoint::cases();

        $this->assertNotEmpty($controlPoints);

        foreach ($controlPoints as $controlPoint) {
            $this->assertNotEmpty($controlPoint->value);
            $this->assertNotEmpty($controlPoint->getDescription());
        }
    }

    #[Test]
    #[DataProvider('controlPointP2PStepProvider')]
    public function getP2PStep_returns_correct_step(
        SOXControlPoint $controlPoint,
        P2PStep $expectedStep,
    ): void {
        $this->assertEquals($expectedStep, $controlPoint->getP2PStep());
    }

    /**
     * @return iterable<array{SOXControlPoint, P2PStep}>
     */
    public static function controlPointP2PStepProvider(): iterable
    {
        yield 'REQ_BUDGET_CHECK -> REQUISITION' => [
            SOXControlPoint::REQ_BUDGET_CHECK,
            P2PStep::REQUISITION,
        ];
        yield 'REQ_APPROVAL_AUTHORITY -> REQUISITION' => [
            SOXControlPoint::REQ_APPROVAL_AUTHORITY,
            P2PStep::REQUISITION,
        ];
        yield 'PO_VENDOR_COMPLIANCE -> PO_CREATION' => [
            SOXControlPoint::PO_VENDOR_COMPLIANCE,
            P2PStep::PO_CREATION,
        ];
        yield 'PO_PRICE_VARIANCE -> PO_CREATION' => [
            SOXControlPoint::PO_PRICE_VARIANCE,
            P2PStep::PO_CREATION,
        ];
        yield 'GR_QUANTITY_TOLERANCE -> GOODS_RECEIPT' => [
            SOXControlPoint::GR_QUANTITY_TOLERANCE,
            P2PStep::GOODS_RECEIPT,
        ];
        yield 'INV_THREE_WAY_MATCH -> INVOICE_MATCH' => [
            SOXControlPoint::INV_THREE_WAY_MATCH,
            P2PStep::INVOICE_MATCH,
        ];
        yield 'PAY_DUAL_APPROVAL -> PAYMENT' => [
            SOXControlPoint::PAY_DUAL_APPROVAL,
            P2PStep::PAYMENT,
        ];
    }

    #[Test]
    #[DataProvider('controlPointRiskLevelProvider')]
    public function getRiskLevel_returns_expected_level(
        SOXControlPoint $controlPoint,
        string $expectedRiskLevel,
    ): void {
        $riskLevel = $controlPoint->getRiskLevel();

        $this->assertEquals($expectedRiskLevel, $riskLevel);
    }

    /**
     * @return iterable<array{SOXControlPoint, string}>
     */
    public static function controlPointRiskLevelProvider(): iterable
    {
        // High risk controls
        yield 'PAY_DUAL_APPROVAL is high risk' => [
            SOXControlPoint::PAY_DUAL_APPROVAL,
            'high',
        ];
        yield 'PAY_BANK_VALIDATION is high risk' => [
            SOXControlPoint::PAY_BANK_VALIDATION,
            'high',
        ];
        yield 'INV_DUPLICATE_CHECK is high risk' => [
            SOXControlPoint::INV_DUPLICATE_CHECK,
            'high',
        ];

        // Medium risk controls
        yield 'REQ_BUDGET_CHECK is medium risk' => [
            SOXControlPoint::REQ_BUDGET_CHECK,
            'medium',
        ];
        yield 'PO_VENDOR_COMPLIANCE is medium risk' => [
            SOXControlPoint::PO_VENDOR_COMPLIANCE,
            'medium',
        ];

        // Low risk controls
        yield 'REQ_REQUESTER_VALIDATION is low risk' => [
            SOXControlPoint::REQ_REQUESTER_VALIDATION,
            'low',
        ];
    }

    #[Test]
    public function getControlType_returns_valid_type(): void
    {
        foreach (SOXControlPoint::cases() as $controlPoint) {
            $type = $controlPoint->getControlType();

            $this->assertInstanceOf(SOXControlType::class, $type);
        }
    }

    #[Test]
    public function getControlsForStep_returns_correct_controls(): void
    {
        $requisitionControls = SOXControlPoint::getControlsForStep(P2PStep::REQUISITION);

        $this->assertNotEmpty($requisitionControls);

        foreach ($requisitionControls as $control) {
            $this->assertEquals(P2PStep::REQUISITION, $control->getP2PStep());
        }
    }

    #[Test]
    public function getHighRiskControls_returns_only_high_risk(): void
    {
        $highRiskControls = SOXControlPoint::getHighRiskControls();

        $this->assertNotEmpty($highRiskControls);

        foreach ($highRiskControls as $control) {
            $this->assertEquals('high', $control->getRiskLevel());
        }
    }

    #[Test]
    public function isPreventive_returns_true_for_preventive_controls(): void
    {
        // Budget check should be preventive
        $this->assertTrue(SOXControlPoint::REQ_BUDGET_CHECK->isPreventive());

        // SOD check should be preventive
        $this->assertTrue(SOXControlPoint::REQ_SOD_CHECK->isPreventive());
    }

    #[Test]
    public function isDetective_returns_true_for_detective_controls(): void
    {
        // Duplicate check should be detective
        $this->assertTrue(SOXControlPoint::INV_DUPLICATE_CHECK->isDetective());

        // Three-way match should be detective
        $this->assertTrue(SOXControlPoint::INV_THREE_WAY_MATCH->isDetective());
    }

    #[Test]
    public function all_p2p_steps_have_controls(): void
    {
        foreach (P2PStep::cases() as $step) {
            $controls = SOXControlPoint::getControlsForStep($step);

            $this->assertNotEmpty(
                $controls,
                "P2P Step {$step->value} should have at least one SOX control point",
            );
        }
    }

    #[Test]
    public function getTimeout_returns_reasonable_value(): void
    {
        foreach (SOXControlPoint::cases() as $controlPoint) {
            $timeout = $controlPoint->getDefaultTimeout();

            $this->assertGreaterThan(0, $timeout);
            $this->assertLessThanOrEqual(5000, $timeout); // Max 5 seconds
        }
    }
}
