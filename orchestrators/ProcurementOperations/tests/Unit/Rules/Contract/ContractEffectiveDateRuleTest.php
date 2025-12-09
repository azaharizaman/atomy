<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Rules\Contract;

use Nexus\ProcurementOperations\DTOs\ContractSpendContext;
use Nexus\ProcurementOperations\Rules\Contract\ContractEffectiveDateRule;
use PHPUnit\Framework\TestCase;

final class ContractEffectiveDateRuleTest extends TestCase
{
    private ContractEffectiveDateRule $rule;

    protected function setUp(): void
    {
        $this->rule = new ContractEffectiveDateRule();
    }

    public function test_passes_for_date_within_period(): void
    {
        $context = $this->createContext(
            effectiveFrom: new \DateTimeImmutable('-30 days'),
            effectiveTo: new \DateTimeImmutable('+60 days')
        );
        
        $result = $this->rule->check($context, new \DateTimeImmutable('today'));
        
        $this->assertTrue($result->passed());
    }

    public function test_fails_for_date_before_effective_start(): void
    {
        $context = $this->createContext(
            effectiveFrom: new \DateTimeImmutable('+7 days'),
            effectiveTo: new \DateTimeImmutable('+90 days')
        );
        
        $result = $this->rule->check($context, new \DateTimeImmutable('today'));
        
        $this->assertTrue($result->failed());
        $this->assertStringContainsString('before contract effective date', $result->getMessage());
    }

    public function test_fails_for_date_after_expiry(): void
    {
        $context = $this->createContext(
            effectiveFrom: new \DateTimeImmutable('-90 days'),
            effectiveTo: new \DateTimeImmutable('-7 days')
        );
        
        $result = $this->rule->check($context, new \DateTimeImmutable('today'));
        
        $this->assertTrue($result->failed());
        $this->assertStringContainsString('after contract expiry', $result->getMessage());
    }

    public function test_passes_with_warning_when_expiring_soon(): void
    {
        $context = $this->createContext(
            effectiveFrom: new \DateTimeImmutable('-60 days'),
            effectiveTo: new \DateTimeImmutable('+15 days')
        );
        
        $result = $this->rule->check($context, new \DateTimeImmutable('today'));
        
        $this->assertTrue($result->passed());
        $this->assertStringContainsString('expire', strtolower($result->getMessage()));
    }

    public function test_exact_start_date_passes(): void
    {
        $today = new \DateTimeImmutable('today');
        $context = $this->createContext(
            effectiveFrom: $today,
            effectiveTo: new \DateTimeImmutable('+90 days')
        );
        
        $result = $this->rule->check($context, $today);
        
        $this->assertTrue($result->passed());
    }

    public function test_exact_end_date_passes(): void
    {
        $today = new \DateTimeImmutable('today');
        $context = $this->createContext(
            effectiveFrom: new \DateTimeImmutable('-90 days'),
            effectiveTo: $today
        );
        
        $result = $this->rule->check($context, $today);
        
        $this->assertTrue($result->passed());
    }

    private function createContext(
        \DateTimeImmutable $effectiveFrom,
        \DateTimeImmutable $effectiveTo
    ): ContractSpendContext {
        return new ContractSpendContext(
            blanketPoId: 'bpo-123',
            blanketPoNumber: 'BPO-2024-001',
            vendorId: 'vendor-456',
            maxAmountCents: 10000000,
            currentSpendCents: 5000000,
            pendingAmountCents: 0,
            currency: 'USD',
            effectiveFrom: $effectiveFrom,
            effectiveTo: $effectiveTo,
            status: 'ACTIVE',
        );
    }
}
