<?php

declare(strict_types=1);

namespace Nexus\KycVerification\Tests\Unit\Exceptions;

use Nexus\KycVerification\Exceptions\KycVerificationException;
use Nexus\KycVerification\Exceptions\VerificationFailedException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(VerificationFailedException::class)]
final class VerificationFailedExceptionTest extends TestCase
{
    #[Test]
    public function it_extends_kyc_verification_exception(): void
    {
        $exception = new VerificationFailedException(
            partyId: 'PARTY-001',
            failureReasons: ['Identity verification failed']
        );

        $this->assertInstanceOf(KycVerificationException::class, $exception);
    }

    #[Test]
    public function it_stores_party_id(): void
    {
        $exception = new VerificationFailedException(
            partyId: 'PARTY-002',
            failureReasons: ['Test reason']
        );

        $this->assertSame('PARTY-002', $exception->getPartyId());
    }

    #[Test]
    public function it_stores_failure_reasons(): void
    {
        $exception = new VerificationFailedException(
            partyId: 'PARTY-003',
            failureReasons: ['Document mismatch detected', 'Address verification failed']
        );

        $this->assertSame(['Document mismatch detected', 'Address verification failed'], $exception->getFailureReasons());
    }

    #[Test]
    public function it_formats_message_correctly(): void
    {
        $exception = new VerificationFailedException(
            partyId: 'PARTY-004',
            failureReasons: ['Failed sanctions screening']
        );

        $this->assertStringContainsString('PARTY-004', $exception->getMessage());
        $this->assertStringContainsString('Failed sanctions screening', $exception->getMessage());
    }

    #[Test]
    public function it_handles_empty_failure_reasons(): void
    {
        $exception = new VerificationFailedException(
            partyId: 'PARTY-005',
            failureReasons: []
        );

        $this->assertSame([], $exception->getFailureReasons());
        $this->assertStringContainsString('Unspecified reason', $exception->getMessage());
    }

    #[Test]
    public function it_stores_verification_id(): void
    {
        $exception = new VerificationFailedException(
            partyId: 'PARTY-006',
            failureReasons: ['Test'],
            verificationId: 'VER-001'
        );

        $this->assertSame('VER-001', $exception->getVerificationId());
    }

    #[Test]
    public function it_returns_null_verification_id_when_not_provided(): void
    {
        $exception = new VerificationFailedException(
            partyId: 'PARTY-007',
            failureReasons: ['Test']
        );

        $this->assertNull($exception->getVerificationId());
    }

    #[Test]
    public function it_includes_verification_id_in_message(): void
    {
        $exception = new VerificationFailedException(
            partyId: 'PARTY-008',
            failureReasons: ['Test'],
            verificationId: 'VER-002'
        );

        $this->assertStringContainsString('VER-002', $exception->getMessage());
    }
}
