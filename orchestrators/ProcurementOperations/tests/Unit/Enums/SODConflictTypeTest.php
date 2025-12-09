<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Enums;

use Nexus\ProcurementOperations\Enums\SODConflictType;
use PHPUnit\Framework\TestCase;

final class SODConflictTypeTest extends TestCase
{
    public function test_all_conflict_types_have_valid_values(): void
    {
        $expected = [
            'REQUESTOR_APPROVER',
            'APPROVER_RECEIVER',
            'RECEIVER_PAYER',
            'VENDOR_CREATOR_PAYER',
            'PRICING_APPROVER',
            'JOURNAL_CREATOR_APPROVER',
            'VENDOR_MANAGER_PAYMENT_APPROVER',
            'PO_CREATOR_INVOICE_MATCHER',
        ];

        $actual = array_map(fn(SODConflictType $t) => $t->value, SODConflictType::cases());

        $this->assertSame($expected, $actual);
    }

    public function test_get_conflicting_roles_returns_pair(): void
    {
        $roles = SODConflictType::REQUESTOR_APPROVER->getConflictingRoles();

        $this->assertCount(2, $roles);
        $this->assertSame('procurement.requestor', $roles[0]);
        $this->assertSame('procurement.approver', $roles[1]);
    }

    public function test_get_risk_level_high(): void
    {
        $highRiskTypes = [
            SODConflictType::REQUESTOR_APPROVER,
            SODConflictType::VENDOR_CREATOR_PAYER,
            SODConflictType::RECEIVER_PAYER,
        ];

        foreach ($highRiskTypes as $type) {
            $this->assertSame('HIGH', $type->getRiskLevel(), "Expected HIGH for {$type->value}");
        }
    }

    public function test_get_risk_level_medium(): void
    {
        $mediumRiskTypes = [
            SODConflictType::APPROVER_RECEIVER,
            SODConflictType::JOURNAL_CREATOR_APPROVER,
            SODConflictType::VENDOR_MANAGER_PAYMENT_APPROVER,
        ];

        foreach ($mediumRiskTypes as $type) {
            $this->assertSame('MEDIUM', $type->getRiskLevel(), "Expected MEDIUM for {$type->value}");
        }
    }

    public function test_get_risk_level_low(): void
    {
        $lowRiskTypes = [
            SODConflictType::PRICING_APPROVER,
            SODConflictType::PO_CREATOR_INVOICE_MATCHER,
        ];

        foreach ($lowRiskTypes as $type) {
            $this->assertSame('LOW', $type->getRiskLevel(), "Expected LOW for {$type->value}");
        }
    }

    public function test_get_description(): void
    {
        $description = SODConflictType::REQUESTOR_APPROVER->getDescription();

        $this->assertStringContainsString('create', $description);
        $this->assertStringContainsString('approve', $description);
    }

    public function test_high_risk_conflicts(): void
    {
        $highRisk = SODConflictType::highRiskConflicts();

        $this->assertCount(3, $highRisk);

        foreach ($highRisk as $type) {
            $this->assertSame('HIGH', $type->getRiskLevel());
        }
    }

    public function test_find_conflict_returns_match(): void
    {
        $conflict = SODConflictType::findConflict(
            'procurement.requestor',
            'procurement.approver'
        );

        $this->assertSame(SODConflictType::REQUESTOR_APPROVER, $conflict);
    }

    public function test_find_conflict_returns_match_reversed_order(): void
    {
        $conflict = SODConflictType::findConflict(
            'procurement.approver',
            'procurement.requestor'
        );

        $this->assertSame(SODConflictType::REQUESTOR_APPROVER, $conflict);
    }

    public function test_find_conflict_returns_null_when_not_found(): void
    {
        $conflict = SODConflictType::findConflict(
            'random.role',
            'another.role'
        );

        $this->assertNull($conflict);
    }

    public function test_all_conflict_types_have_descriptions(): void
    {
        foreach (SODConflictType::cases() as $type) {
            $description = $type->getDescription();

            $this->assertNotEmpty($description, "Missing description for {$type->value}");
            $this->assertIsString($description);
        }
    }

    public function test_all_conflict_types_have_conflicting_roles(): void
    {
        foreach (SODConflictType::cases() as $type) {
            $roles = $type->getConflictingRoles();

            $this->assertCount(2, $roles, "Expected 2 roles for {$type->value}");
            $this->assertNotEmpty($roles[0], "First role empty for {$type->value}");
            $this->assertNotEmpty($roles[1], "Second role empty for {$type->value}");
        }
    }
}
