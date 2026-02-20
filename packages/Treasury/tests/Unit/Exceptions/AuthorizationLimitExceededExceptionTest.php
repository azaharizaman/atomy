<?php

declare(strict_types=1);

namespace Nexus\Treasury\Tests\Unit\Exceptions;

use Nexus\Common\ValueObjects\Money;
use Nexus\Treasury\Exceptions\AuthorizationLimitExceededException;
use PHPUnit\Framework\TestCase;

final class AuthorizationLimitExceededExceptionTest extends TestCase
{
    public function test_for_amount_creates_exception(): void
    {
        $amount = Money::of(100000, 'USD');
        $limit = Money::of(50000, 'USD');

        $exception = AuthorizationLimitExceededException::forAmount($amount, $limit, 'user-001');

        $this->assertStringContainsString('user-001', $exception->getMessage());
        $this->assertStringContainsString('100000.00', $exception->getMessage());
        $this->assertStringContainsString('50000.00', $exception->getMessage());
    }

    public function test_for_transaction_creates_exception(): void
    {
        $amount = Money::of(75000, 'USD');
        $limit = Money::of(50000, 'USD');

        $exception = AuthorizationLimitExceededException::forTransaction('payment', $amount, $limit);

        $this->assertStringContainsString('payment', $exception->getMessage());
    }

    public function test_for_role_creates_exception(): void
    {
        $amount = Money::of(200000, 'USD');
        $limit = Money::of(100000, 'USD');

        $exception = AuthorizationLimitExceededException::forRole('manager', $amount, $limit);

        $this->assertStringContainsString('manager', $exception->getMessage());
    }
}
