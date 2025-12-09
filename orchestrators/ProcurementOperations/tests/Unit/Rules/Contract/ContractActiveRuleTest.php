<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Rules\Contract;

use Nexus\ProcurementOperations\DTOs\ContractSpendContext;
use Nexus\ProcurementOperations\Rules\Contract\ContractActiveRule;
use Nexus\ProcurementOperations\Rules\Contract\ContractRuleResult;
use PHPUnit\Framework\TestCase;

final class ContractActiveRuleTest extends TestCase
{
    private ContractActiveRule $rule;

    protected function setUp(): void
    {
        $this->rule = new ContractActiveRule();
    }

    public function test_passes_for_active_contract(): void
    {
        $context = $this->createContext(status: 'ACTIVE');
        
        $result = $this->rule->check($context);
        
        $this->assertTrue($result->passed());
        $this->assertStringContainsString('active', strtolower($result->getMessage()));
    }

    public function test_passes_for_approved_contract(): void
    {
        $context = $this->createContext(status: 'APPROVED');
        
        $result = $this->rule->check($context);
        
        $this->assertTrue($result->passed());
    }

    public function test_fails_for_pending_contract(): void
    {
        $context = $this->createContext(status: 'PENDING');
        
        $result = $this->rule->check($context);
        
        $this->assertTrue($result->failed());
        $this->assertStringContainsString('not active', $result->getMessage());
    }

    public function test_fails_for_closed_contract(): void
    {
        $context = $this->createContext(status: 'CLOSED');
        
        $result = $this->rule->check($context);
        
        $this->assertTrue($result->failed());
        $this->assertStringContainsString('CLOSED', $result->getMessage());
    }

    public function test_fails_for_expired_contract(): void
    {
        $context = $this->createContext(status: 'EXPIRED');
        
        $result = $this->rule->check($context);
        
        $this->assertTrue($result->failed());
    }

    private function createContext(string $status): ContractSpendContext
    {
        return new ContractSpendContext(
            blanketPoId: 'bpo-123',
            blanketPoNumber: 'BPO-2024-001',
            vendorId: 'vendor-456',
            maxAmountCents: 10000000, // $100,000
            currentSpendCents: 5000000, // $50,000
            pendingAmountCents: 0,
            currency: 'USD',
            effectiveFrom: new \DateTimeImmutable('-30 days'),
            effectiveTo: new \DateTimeImmutable('+60 days'),
            status: $status,
        );
    }
}
