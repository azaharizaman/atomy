<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Tests\Unit\ValueObjects;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentGateway\Enums\TransactionStatus;
use Nexus\PaymentGateway\ValueObjects\AuthorizationResult;
use Nexus\PaymentGateway\ValueObjects\GatewayError;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(AuthorizationResult::class)]
final class AuthorizationResultTest extends TestCase
{
    #[Test]
    public function it_creates_successful_result(): void
    {
        $amount = Money::of(100.00, 'USD');
        $expiresAt = new \DateTimeImmutable('+7 days');

        $result = AuthorizationResult::success(
            authorizationId: 'auth_123',
            amount: $amount,
            transactionId: 'txn_456',
            expiresAt: $expiresAt,
            rawResponse: ['raw' => 'data'],
        );

        $this->assertTrue($result->success);
        $this->assertSame('auth_123', $result->authorizationId);
        $this->assertSame('txn_456', $result->transactionId);
        $this->assertSame(TransactionStatus::AUTHORIZED, $result->status);
        $this->assertSame($amount, $result->authorizedAmount);
        $this->assertSame($expiresAt, $result->expiresAt);
        $this->assertNull($result->error);
        $this->assertFalse($result->requires3ds);
        $this->assertSame(['raw' => 'data'], $result->rawResponse);
    }

    #[Test]
    public function it_creates_failed_result(): void
    {
        $error = GatewayError::cardDeclined('insufficient_funds');

        $result = AuthorizationResult::failed(
            error: $error,
            transactionId: 'txn_789',
            rawResponse: ['error' => 'data'],
        );

        $this->assertFalse($result->success);
        $this->assertNull($result->authorizationId);
        $this->assertSame('txn_789', $result->transactionId);
        $this->assertSame(TransactionStatus::FAILED, $result->status);
        $this->assertNull($result->authorizedAmount);
        $this->assertSame($error, $result->error);
    }

    #[Test]
    public function it_creates_3ds_required_result(): void
    {
        $result = AuthorizationResult::requires3dsAuthentication(
            authorizationId: 'auth_3ds',
            threeDsUrl: 'https://bank.com/3ds',
            rawResponse: ['3ds' => 'required'],
        );

        $this->assertFalse($result->success);
        $this->assertSame('auth_3ds', $result->authorizationId);
        $this->assertSame(TransactionStatus::PENDING, $result->status);
        $this->assertTrue($result->requires3ds);
        $this->assertSame('https://bank.com/3ds', $result->threeDsUrl);
    }

    #[Test]
    public function it_detects_expired_authorization(): void
    {
        $expired = new AuthorizationResult(
            success: true,
            authorizationId: 'auth_expired',
            status: TransactionStatus::AUTHORIZED,
            expiresAt: new \DateTimeImmutable('-1 day'),
        );
        $this->assertTrue($expired->isExpired());

        $notExpired = new AuthorizationResult(
            success: true,
            authorizationId: 'auth_valid',
            status: TransactionStatus::AUTHORIZED,
            expiresAt: new \DateTimeImmutable('+7 days'),
        );
        $this->assertFalse($notExpired->isExpired());

        $noExpiry = new AuthorizationResult(
            success: true,
            authorizationId: 'auth_no_expiry',
            status: TransactionStatus::AUTHORIZED,
        );
        $this->assertFalse($noExpiry->isExpired());
    }

    #[Test]
    public function it_determines_if_can_capture(): void
    {
        $canCapture = new AuthorizationResult(
            success: true,
            authorizationId: 'auth_123',
            status: TransactionStatus::AUTHORIZED,
            expiresAt: new \DateTimeImmutable('+7 days'),
        );
        $this->assertTrue($canCapture->canCapture());

        $failed = new AuthorizationResult(
            success: false,
            status: TransactionStatus::FAILED,
        );
        $this->assertFalse($failed->canCapture());

        $expired = new AuthorizationResult(
            success: true,
            authorizationId: 'auth_expired',
            status: TransactionStatus::AUTHORIZED,
            expiresAt: new \DateTimeImmutable('-1 day'),
        );
        $this->assertFalse($expired->canCapture());

        $alreadyCaptured = new AuthorizationResult(
            success: true,
            authorizationId: 'auth_captured',
            status: TransactionStatus::CAPTURED,
        );
        $this->assertFalse($alreadyCaptured->canCapture());
    }
}
