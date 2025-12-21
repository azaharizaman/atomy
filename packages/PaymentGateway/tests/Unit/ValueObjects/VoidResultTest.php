<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Tests\Unit\ValueObjects;

use Nexus\PaymentGateway\Enums\TransactionStatus;
use Nexus\PaymentGateway\ValueObjects\GatewayError;
use Nexus\PaymentGateway\ValueObjects\VoidResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(VoidResult::class)]
final class VoidResultTest extends TestCase
{
    #[Test]
    public function it_creates_void_result_with_minimal_parameters(): void
    {
        $result = new VoidResult(
            success: false,
        );

        $this->assertFalse($result->success);
        $this->assertNull($result->voidId);
        $this->assertNull($result->transactionId);
        $this->assertSame(TransactionStatus::PENDING, $result->status);
        $this->assertNull($result->error);
        $this->assertNull($result->voidedAt);
        $this->assertEmpty($result->rawResponse);
    }

    #[Test]
    public function it_creates_successful_void(): void
    {
        $result = VoidResult::success(
            voidId: 'void_123',
            transactionId: 'txn_456',
            rawResponse: ['status' => 'canceled'],
        );

        $this->assertTrue($result->success);
        $this->assertSame('void_123', $result->voidId);
        $this->assertSame('txn_456', $result->transactionId);
        $this->assertSame(TransactionStatus::VOIDED, $result->status);
        $this->assertNull($result->error);
        $this->assertInstanceOf(\DateTimeImmutable::class, $result->voidedAt);
        $this->assertSame(['status' => 'canceled'], $result->rawResponse);
    }

    #[Test]
    public function it_creates_failed_void(): void
    {
        $error = GatewayError::networkError('Connection failed');

        $result = VoidResult::failed(
            error: $error,
            transactionId: 'txn_789',
            rawResponse: ['error' => 'connection_failed'],
        );

        $this->assertFalse($result->success);
        $this->assertNull($result->voidId);
        $this->assertSame('txn_789', $result->transactionId);
        $this->assertSame(TransactionStatus::FAILED, $result->status);
        $this->assertSame($error, $result->error);
        $this->assertNull($result->voidedAt);
        $this->assertSame(['error' => 'connection_failed'], $result->rawResponse);
    }
}
