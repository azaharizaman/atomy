<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Rules;

use Nexus\ProcurementOperations\DTOs\Audit\RetentionPolicyData;
use Nexus\ProcurementOperations\Enums\RetentionCategory;
use Nexus\ProcurementOperations\Rules\RetentionPolicyRule;
use Nexus\ProcurementOperations\Rules\RetentionPolicyRuleResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(RetentionPolicyRule::class)]
#[CoversClass(RetentionPolicyRuleResult::class)]
final class RetentionPolicyRuleTest extends TestCase
{
    private RetentionPolicyRule $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new RetentionPolicyRule();
    }

    #[Test]
    public function checkRetentionPeriod_withinPeriod_returnsPass(): void
    {
        $policy = new RetentionPolicyData(
            category: RetentionCategory::SOX_FINANCIAL_DATA,
            retentionYears: 7,
            disposalMethod: 'secure_shred',
            regulatoryBasis: 'SOX Section 802',
            requiresApproval: true,
        );

        // Document created 3 years ago (within 7-year retention)
        $documentDate = new \DateTimeImmutable('-3 years');

        $result = $this->rule->checkRetentionPeriod($policy, $documentDate);

        $this->assertTrue($result->passed);
        $this->assertFalse($result->isWarning);
        $this->assertStringContainsString('within retention period', strtolower($result->message));
    }

    #[Test]
    public function checkRetentionPeriod_expiredPeriod_returnsFail(): void
    {
        $policy = new RetentionPolicyData(
            category: RetentionCategory::VENDOR_CONTRACTS,
            retentionYears: 3,
            disposalMethod: 'archive',
            regulatoryBasis: 'Contract Law',
            requiresApproval: true,
        );

        // Document created 5 years ago (beyond 3-year retention)
        $documentDate = new \DateTimeImmutable('-5 years');

        $result = $this->rule->checkRetentionPeriod($policy, $documentDate);

        $this->assertFalse($result->passed);
        $this->assertEquals('RETENTION_EXPIRED', $result->reason);
    }

    #[Test]
    public function checkRetentionPeriod_nearExpiration_returnsWarning(): void
    {
        $policy = new RetentionPolicyData(
            category: RetentionCategory::PURCHASE_ORDERS,
            retentionYears: 5,
            disposalMethod: 'archive',
            regulatoryBasis: 'Business Records',
            requiresApproval: false,
        );

        // Document created 4 years and 10 months ago (within 90-day warning window)
        $documentDate = new \DateTimeImmutable('-4 years -10 months');

        $result = $this->rule->checkRetentionPeriod($policy, $documentDate);

        $this->assertTrue($result->passed);
        $this->assertTrue($result->isWarning);
        $this->assertStringContainsString('approaching expiration', strtolower($result->message));
    }

    #[Test]
    public function checkDisposalEligibility_withinRetention_returnsFail(): void
    {
        $policy = new RetentionPolicyData(
            category: RetentionCategory::TAX_DOCUMENTATION,
            retentionYears: 7,
            disposalMethod: 'secure_shred',
            regulatoryBasis: 'IRS Requirements',
            requiresApproval: true,
        );

        $documentDate = new \DateTimeImmutable('-2 years');

        $result = $this->rule->checkDisposalEligibility($policy, $documentDate);

        $this->assertFalse($result->passed);
        $this->assertEquals('NOT_ELIGIBLE_FOR_DISPOSAL', $result->reason);
        $this->assertNotNull($result->remainingDays);
        $this->assertGreaterThan(0, $result->remainingDays);
    }

    #[Test]
    public function checkDisposalEligibility_expiredRetention_returnsPass(): void
    {
        $policy = new RetentionPolicyData(
            category: RetentionCategory::VENDOR_CORRESPONDENCE,
            retentionYears: 2,
            disposalMethod: 'standard',
            regulatoryBasis: 'Business Practice',
            requiresApproval: false,
        );

        $documentDate = new \DateTimeImmutable('-3 years');

        $result = $this->rule->checkDisposalEligibility($policy, $documentDate);

        $this->assertTrue($result->passed);
        $this->assertStringContainsString('eligible for disposal', strtolower($result->message));
    }

    #[Test]
    public function checkDisposalEligibility_requiresApproval_includesInMessage(): void
    {
        $policy = new RetentionPolicyData(
            category: RetentionCategory::SOX_FINANCIAL_DATA,
            retentionYears: 7,
            disposalMethod: 'secure_shred',
            regulatoryBasis: 'SOX Section 802',
            requiresApproval: true,
        );

        $documentDate = new \DateTimeImmutable('-8 years');

        $result = $this->rule->checkDisposalEligibility($policy, $documentDate);

        $this->assertTrue($result->passed);
        $this->assertTrue($result->requiresApproval);
    }

    #[Test]
    public function checkRegulatoryCompliance_validCategory_returnsPass(): void
    {
        $policy = new RetentionPolicyData(
            category: RetentionCategory::SOX_FINANCIAL_DATA,
            retentionYears: 7,
            disposalMethod: 'secure_shred',
            regulatoryBasis: 'SOX Section 802',
            requiresApproval: true,
        );

        $result = $this->rule->checkRegulatoryCompliance($policy);

        $this->assertTrue($result->passed);
    }

    #[Test]
    public function checkRegulatoryCompliance_insufficientRetention_returnsFail(): void
    {
        // Create policy with retention shorter than regulatory requirement
        $policy = new RetentionPolicyData(
            category: RetentionCategory::SOX_FINANCIAL_DATA, // Requires 7 years
            retentionYears: 3, // Too short
            disposalMethod: 'secure_shred',
            regulatoryBasis: 'SOX Section 802',
            requiresApproval: true,
        );

        $result = $this->rule->checkRegulatoryCompliance($policy);

        $this->assertFalse($result->passed);
        $this->assertEquals('INSUFFICIENT_RETENTION', $result->reason);
    }

    #[Test]
    public function checkRegulatoryCompliance_invalidDisposalMethod_returnsFail(): void
    {
        // SOX financial data requires secure_shred, not standard
        $policy = new RetentionPolicyData(
            category: RetentionCategory::SOX_FINANCIAL_DATA,
            retentionYears: 7,
            disposalMethod: 'standard', // Should be secure_shred
            regulatoryBasis: 'SOX Section 802',
            requiresApproval: true,
        );

        $result = $this->rule->checkRegulatoryCompliance($policy);

        $this->assertFalse($result->passed);
        $this->assertEquals('INVALID_DISPOSAL_METHOD', $result->reason);
    }

    #[Test]
    #[DataProvider('retentionCategoriesProvider')]
    public function checkRegulatoryCompliance_allCategories_respectsMinimumYears(
        RetentionCategory $category,
        int $expectedMinYears,
    ): void {
        $policy = new RetentionPolicyData(
            category: $category,
            retentionYears: $expectedMinYears,
            disposalMethod: $category->getDisposalMethod(),
            regulatoryBasis: $category->getRegulatoryBasis(),
            requiresApproval: true,
        );

        $result = $this->rule->checkRegulatoryCompliance($policy);

        $this->assertTrue($result->passed, "Category {$category->value} should pass with {$expectedMinYears} years retention");
    }

    /**
     * @return array<string, array{RetentionCategory, int}>
     */
    public static function retentionCategoriesProvider(): array
    {
        return [
            'SOX_FINANCIAL_DATA' => [RetentionCategory::SOX_FINANCIAL_DATA, 7],
            'VENDOR_CONTRACTS' => [RetentionCategory::VENDOR_CONTRACTS, 7],
            'PURCHASE_ORDERS' => [RetentionCategory::PURCHASE_ORDERS, 7],
            'TAX_DOCUMENTATION' => [RetentionCategory::TAX_DOCUMENTATION, 7],
            'AUDIT_WORKPAPERS' => [RetentionCategory::AUDIT_WORKPAPERS, 7],
            'LEGAL_LITIGATION_RECORDS' => [RetentionCategory::LEGAL_LITIGATION_RECORDS, 10],
            'VENDOR_CORRESPONDENCE' => [RetentionCategory::VENDOR_CORRESPONDENCE, 3],
            'APPROVAL_RECORDS' => [RetentionCategory::APPROVAL_RECORDS, 7],
            'GOODS_RECEIPT_NOTES' => [RetentionCategory::GOODS_RECEIPT_NOTES, 7],
            'PAYMENT_RECORDS' => [RetentionCategory::PAYMENT_RECORDS, 7],
        ];
    }

    #[Test]
    public function resultToArray_containsAllRelevantFields(): void
    {
        $policy = new RetentionPolicyData(
            category: RetentionCategory::PURCHASE_ORDERS,
            retentionYears: 5,
            disposalMethod: 'archive',
            regulatoryBasis: 'Business Records',
            requiresApproval: false,
        );

        $documentDate = new \DateTimeImmutable('-6 years');

        $result = $this->rule->checkDisposalEligibility($policy, $documentDate);
        $array = $result->toArray();

        $this->assertArrayHasKey('passed', $array);
        $this->assertArrayHasKey('message', $array);
        $this->assertArrayHasKey('category', $array);
    }
}
