<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Tests\Unit\Rules\Credit;

use Nexus\SalesOperations\Contracts\CreditManagerInterface;
use Nexus\SalesOperations\DTOs\CreditCheckRequest;
use Nexus\SalesOperations\Rules\Credit\CreditHoldRule;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(CreditHoldRule::class)]
final class CreditHoldRuleTest extends TestCase
{
    #[Test]
    public function check_passes_when_not_on_hold(): void
    {
        $creditManager = $this->createMock(CreditManagerInterface::class);
        $creditManager->method('isOnCreditHold')->willReturn(false);

        $rule = new CreditHoldRule($creditManager);

        $context = new CreditCheckRequest(
            tenantId: 'tenant-1',
            customerId: 'cust-1',
            orderAmount: 1000.0
        );

        $result = $rule->check($context);

        $this->assertTrue($result->passed());
    }

    #[Test]
    public function check_fails_when_on_hold(): void
    {
        $creditManager = $this->createMock(CreditManagerInterface::class);
        $creditManager->method('isOnCreditHold')->willReturn(true);
        $creditManager->method('getCreditHoldReason')->willReturn('Overdue payments');

        $rule = new CreditHoldRule($creditManager);

        $context = new CreditCheckRequest(
            tenantId: 'tenant-1',
            customerId: 'cust-1',
            orderAmount: 1000.0
        );

        $result = $rule->check($context);

        $this->assertTrue($result->failed());
        $this->assertStringContainsString('credit hold', $result->message);
        $this->assertStringContainsString('Overdue payments', $result->message);
    }

    #[Test]
    public function get_name_returns_credit_hold(): void
    {
        $creditManager = $this->createMock(CreditManagerInterface::class);
        $rule = new CreditHoldRule($creditManager);

        $this->assertSame('credit_hold', $rule->getName());
    }
}
