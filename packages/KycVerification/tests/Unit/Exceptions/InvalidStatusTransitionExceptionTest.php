<?php

declare(strict_types=1);

namespace Nexus\KycVerification\Tests\Unit\Exceptions;

use Nexus\KycVerification\Enums\VerificationStatus;
use Nexus\KycVerification\Exceptions\InvalidStatusTransitionException;
use Nexus\KycVerification\Exceptions\KycVerificationException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(InvalidStatusTransitionException::class)]
final class InvalidStatusTransitionExceptionTest extends TestCase
{
    #[Test]
    public function it_extends_kyc_verification_exception(): void
    {
        $exception = new InvalidStatusTransitionException(
            verificationId: 'VER-001',
            fromStatus: VerificationStatus::PENDING,
            toStatus: VerificationStatus::EXPIRED
        );

        $this->assertInstanceOf(KycVerificationException::class, $exception);
    }

    #[Test]
    public function it_stores_verification_id(): void
    {
        $exception = new InvalidStatusTransitionException(
            verificationId: 'VER-002',
            fromStatus: VerificationStatus::VERIFIED,
            toStatus: VerificationStatus::PENDING
        );

        $this->assertSame('VER-002', $exception->getVerificationId());
    }

    #[Test]
    public function it_stores_from_status(): void
    {
        $exception = new InvalidStatusTransitionException(
            verificationId: 'VER-003',
            fromStatus: VerificationStatus::REJECTED,
            toStatus: VerificationStatus::VERIFIED
        );

        $this->assertSame(VerificationStatus::REJECTED, $exception->getFromStatus());
    }

    #[Test]
    public function it_stores_to_status(): void
    {
        $exception = new InvalidStatusTransitionException(
            verificationId: 'VER-004',
            fromStatus: VerificationStatus::PENDING,
            toStatus: VerificationStatus::EXPIRED
        );

        $this->assertSame(VerificationStatus::EXPIRED, $exception->getToStatus());
    }

    #[Test]
    public function it_formats_message_correctly(): void
    {
        $exception = new InvalidStatusTransitionException(
            verificationId: 'VER-005',
            fromStatus: VerificationStatus::PENDING,
            toStatus: VerificationStatus::EXPIRED
        );

        $message = $exception->getMessage();

        $this->assertStringContainsString('VER-005', $message);
        $this->assertStringContainsString('pending', $message);
        $this->assertStringContainsString('expired', $message);
    }

    #[Test]
    public function it_includes_allowed_transitions_in_message(): void
    {
        $exception = new InvalidStatusTransitionException(
            verificationId: 'VER-006',
            fromStatus: VerificationStatus::PENDING,
            toStatus: VerificationStatus::EXPIRED
        );

        $message = $exception->getMessage();

        // PENDING can transition to documents_requested, in_progress, etc.
        $this->assertStringContainsString('Allowed transitions:', $message);
    }
}
