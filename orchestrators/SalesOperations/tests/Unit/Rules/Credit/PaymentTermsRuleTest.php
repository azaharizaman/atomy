<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Tests\Unit\Rules\Credit;

use Nexus\SalesOperations\Contracts\CustomerProviderInterface;
use Nexus\SalesOperations\DTOs\CreditCheckRequest;
use Nexus\SalesOperations\Rules\Credit\PaymentTermsRule;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PaymentTermsRule::class)]
final class PaymentTermsRuleTest extends TestCase
{
    #[Test]
    public function check_passes_for_valid_terms(): void
    {
        $customer = new class {
            public function getPaymentTerms(): string { return 'NET_30'; }
        };

        $customerProvider = $this->createMock(CustomerProviderInterface::class);
        $customerProvider->method('findById')->willReturn($customer);

        $rule = new PaymentTermsRule($customerProvider);

        $context = new CreditCheckRequest(
            tenantId: 'tenant-1',
            customerId: 'cust-1',
            orderAmount: 1000.0
        );

        $result = $rule->check($context);

        $this->assertTrue($result->passed());
        $this->assertSame('NET_30', $result->context['terms']);
    }

    #[Test]
    public function check_passes_for_cod_with_upfront_flag(): void
    {
        $customer = new class {
            public function getPaymentTerms(): string { return 'COD'; }
        };

        $customerProvider = $this->createMock(CustomerProviderInterface::class);
        $customerProvider->method('findById')->willReturn($customer);

        $rule = new PaymentTermsRule($customerProvider);

        $context = new CreditCheckRequest(
            tenantId: 'tenant-1',
            customerId: 'cust-1',
            orderAmount: 1000.0
        );

        $result = $rule->check($context);

        $this->assertTrue($result->passed());
        $this->assertTrue($result->context['requires_upfront_payment']);
    }

    #[Test]
    public function check_fails_for_invalid_terms(): void
    {
        $customer = new class {
            public function getPaymentTerms(): string { return 'INVALID'; }
        };

        $customerProvider = $this->createMock(CustomerProviderInterface::class);
        $customerProvider->method('findById')->willReturn($customer);

        $rule = new PaymentTermsRule($customerProvider);

        $context = new CreditCheckRequest(
            tenantId: 'tenant-1',
            customerId: 'cust-1',
            orderAmount: 1000.0
        );

        $result = $rule->check($context);

        $this->assertTrue($result->failed());
        $this->assertStringContainsString('Invalid payment terms', $result->message);
    }

    #[Test]
    public function check_fails_for_missing_customer(): void
    {
        $customerProvider = $this->createMock(CustomerProviderInterface::class);
        $customerProvider->method('findById')->willReturn(null);

        $rule = new PaymentTermsRule($customerProvider);

        $context = new CreditCheckRequest(
            tenantId: 'tenant-1',
            customerId: 'cust-1',
            orderAmount: 1000.0
        );

        $result = $rule->check($context);

        $this->assertTrue($result->failed());
    }
}
