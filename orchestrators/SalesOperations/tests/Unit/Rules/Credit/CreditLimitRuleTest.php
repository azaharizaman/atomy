<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Tests\Unit\Rules\Credit;

use Nexus\SalesOperations\Contracts\CreditManagerInterface;
use Nexus\SalesOperations\Contracts\CustomerProviderInterface;
use Nexus\SalesOperations\DTOs\CreditCheckRequest;
use Nexus\SalesOperations\Rules\Credit\CreditLimitRule;
use Nexus\SalesOperations\Rules\RuleResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(CreditLimitRule::class)]
final class CreditLimitRuleTest extends TestCase
{
    #[Test]
    public function check_passes_when_credit_available(): void
    {
        $creditManager = $this->createMock(CreditManagerInterface::class);
        $creditManager->method('getCreditLimit')->willReturn(10000.0);
        $creditManager->method('getCreditUsed')->willReturn(5000.0);

        $customer = new class {
            public function getCreditLimit(): float { return 10000.0; }
        };

        $customerProvider = $this->createMock(CustomerProviderInterface::class);
        $customerProvider->method('findById')->willReturn($customer);

        $rule = new CreditLimitRule($creditManager, $customerProvider);

        $context = new CreditCheckRequest(
            tenantId: 'tenant-1',
            customerId: 'cust-1',
            orderAmount: 3000.0
        );

        $result = $rule->check($context);

        $this->assertTrue($result->passed());
        $this->assertSame('credit_limit', $result->ruleName);
    }

    #[Test]
    public function check_fails_when_credit_exceeded(): void
    {
        $creditManager = $this->createMock(CreditManagerInterface::class);
        $creditManager->method('getCreditLimit')->willReturn(10000.0);
        $creditManager->method('getCreditUsed')->willReturn(8000.0);

        $customer = new class {
            public function getCreditLimit(): float { return 10000.0; }
        };

        $customerProvider = $this->createMock(CustomerProviderInterface::class);
        $customerProvider->method('findById')->willReturn($customer);

        $rule = new CreditLimitRule($creditManager, $customerProvider);

        $context = new CreditCheckRequest(
            tenantId: 'tenant-1',
            customerId: 'cust-1',
            orderAmount: 5000.0
        );

        $result = $rule->check($context);

        $this->assertTrue($result->failed());
        $this->assertStringContainsString('Credit limit exceeded', $result->message);
    }

    #[Test]
    public function check_fails_for_missing_customer(): void
    {
        $creditManager = $this->createMock(CreditManagerInterface::class);
        $customerProvider = $this->createMock(CustomerProviderInterface::class);
        $customerProvider->method('findById')->willReturn(null);

        $rule = new CreditLimitRule($creditManager, $customerProvider);

        $context = new CreditCheckRequest(
            tenantId: 'tenant-1',
            customerId: 'cust-1',
            orderAmount: 1000.0
        );

        $result = $rule->check($context);

        $this->assertTrue($result->failed());
        $this->assertStringContainsString('not found', $result->message);
    }

    #[Test]
    public function check_fails_for_invalid_context(): void
    {
        $creditManager = $this->createMock(CreditManagerInterface::class);
        $customerProvider = $this->createMock(CustomerProviderInterface::class);

        $rule = new CreditLimitRule($creditManager, $customerProvider);

        $result = $rule->check(new \stdClass());

        $this->assertTrue($result->failed());
        $this->assertStringContainsString('Invalid context', $result->message);
    }
}
