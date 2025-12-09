<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Rules\Contract;

use Nexus\ProcurementOperations\DTOs\ContractSpendContext;
use Nexus\ProcurementOperations\Rules\Contract\ContractSpendLimitRule;
use PHPUnit\Framework\TestCase;

final class ContractSpendLimitRuleTest extends TestCase
{
    private ContractSpendLimitRule $rule;

    protected function setUp(): void
    {
        $this->rule = new ContractSpendLimitRule();
    }

    public function test_passes_when_amount_within_limit(): void
    {
        $context = $this->createContext(
            maxAmountCents: 10000000, // $100,000
            currentSpendCents: 5000000 // $50,000
        );
        
        $result = $this->rule->check($context, 2000000); // $20,000
        
        $this->assertTrue($result->passed());
    }

    public function test_fails_when_amount_exceeds_remaining(): void
    {
        $context = $this->createContext(
            maxAmountCents: 10000000, // $100,000
            currentSpendCents: 9000000 // $90,000
        );
        
        $result = $this->rule->check($context, 2000000); // $20,000
        
        $this->assertTrue($result->failed());
        $this->assertStringContainsString('exceeds', $result->getMessage());
    }

    public function test_fails_when_below_minimum_order(): void
    {
        $context = $this->createContext(
            maxAmountCents: 10000000,
            currentSpendCents: 0,
            minOrderAmountCents: 100000 // $1,000 minimum
        );
        
        $result = $this->rule->check($context, 50000); // $500 - below minimum
        
        $this->assertTrue($result->failed());
        $this->assertStringContainsString('below minimum', $result->getMessage());
    }

    public function test_passes_with_warning_when_approaching_limit(): void
    {
        $context = $this->createContext(
            maxAmountCents: 10000000, // $100,000
            currentSpendCents: 7500000, // $75,000 - 75% used
            warningThresholdPercent: 80
        );
        
        $result = $this->rule->check($context, 1000000); // $10,000 - will bring to 85%
        
        $this->assertTrue($result->passed());
        $this->assertStringContainsString('85%', $result->getMessage());
    }

    public function test_exact_remaining_amount_passes(): void
    {
        $context = $this->createContext(
            maxAmountCents: 10000000,
            currentSpendCents: 5000000
        );
        
        $result = $this->rule->check($context, 5000000); // Exact remaining
        
        $this->assertTrue($result->passed());
    }

    private function createContext(
        int $maxAmountCents,
        int $currentSpendCents,
        ?int $minOrderAmountCents = null,
        int $warningThresholdPercent = 80
    ): ContractSpendContext {
        return new ContractSpendContext(
            blanketPoId: 'bpo-123',
            blanketPoNumber: 'BPO-2024-001',
            vendorId: 'vendor-456',
            maxAmountCents: $maxAmountCents,
            currentSpendCents: $currentSpendCents,
            pendingAmountCents: 0,
            currency: 'USD',
            effectiveFrom: new \DateTimeImmutable('-30 days'),
            effectiveTo: new \DateTimeImmutable('+60 days'),
            status: 'ACTIVE',
            minOrderAmountCents: $minOrderAmountCents,
            warningThresholdPercent: $warningThresholdPercent,
        );
    }
}
