<?php

declare(strict_types=1);

namespace Nexus\KycVerification\Tests\Unit\Exceptions;

use DateTimeImmutable;
use Nexus\KycVerification\Exceptions\KycVerificationException;
use Nexus\KycVerification\Exceptions\ReviewOverdueException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ReviewOverdueException::class)]
final class ReviewOverdueExceptionTest extends TestCase
{
    #[Test]
    public function it_extends_kyc_verification_exception(): void
    {
        $exception = new ReviewOverdueException(
            partyId: 'PARTY-001',
            dueDate: new DateTimeImmutable('-30 days')
        );

        $this->assertInstanceOf(KycVerificationException::class, $exception);
    }

    #[Test]
    public function it_stores_party_id(): void
    {
        $exception = new ReviewOverdueException(
            partyId: 'PARTY-002',
            dueDate: new DateTimeImmutable('-7 days')
        );

        $this->assertSame('PARTY-002', $exception->getPartyId());
    }

    #[Test]
    public function it_stores_due_date(): void
    {
        $dueDate = new DateTimeImmutable('2024-01-15');

        $exception = new ReviewOverdueException(
            partyId: 'PARTY-003',
            dueDate: $dueDate
        );

        $this->assertSame($dueDate, $exception->getDueDate());
    }

    #[Test]
    public function it_formats_message_correctly(): void
    {
        $exception = new ReviewOverdueException(
            partyId: 'PARTY-004',
            dueDate: new DateTimeImmutable('-10 days')
        );

        $message = $exception->getMessage();

        $this->assertStringContainsString('PARTY-004', $message);
        $this->assertStringContainsString('overdue', $message);
    }

    #[Test]
    public function it_calculates_days_overdue(): void
    {
        $exception = new ReviewOverdueException(
            partyId: 'PARTY-005',
            dueDate: new DateTimeImmutable('-15 days')
        );

        $daysOverdue = $exception->getDaysOverdue();

        $this->assertGreaterThanOrEqual(14, $daysOverdue);
        $this->assertLessThanOrEqual(16, $daysOverdue);
    }

    #[Test]
    public function it_checks_if_critically_overdue(): void
    {
        $critical = new ReviewOverdueException(
            partyId: 'PARTY-006',
            dueDate: new DateTimeImmutable('-45 days')
        );

        $notCritical = new ReviewOverdueException(
            partyId: 'PARTY-007',
            dueDate: new DateTimeImmutable('-10 days')
        );

        $this->assertTrue($critical->isCriticallyOverdue());
        $this->assertFalse($notCritical->isCriticallyOverdue());
    }

    #[Test]
    public function it_includes_days_overdue_in_message(): void
    {
        $exception = new ReviewOverdueException(
            partyId: 'PARTY-008',
            dueDate: new DateTimeImmutable('-20 days')
        );

        $message = $exception->getMessage();

        // Should contain the number of days overdue (approximately 20)
        $this->assertMatchesRegularExpression('/\d+ days overdue/', $message);
    }
}
