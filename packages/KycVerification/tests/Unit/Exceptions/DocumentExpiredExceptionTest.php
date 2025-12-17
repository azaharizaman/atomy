<?php

declare(strict_types=1);

namespace Nexus\KycVerification\Tests\Unit\Exceptions;

use DateTimeImmutable;
use Nexus\KycVerification\Enums\DocumentType;
use Nexus\KycVerification\Exceptions\DocumentExpiredException;
use Nexus\KycVerification\Exceptions\KycVerificationException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(DocumentExpiredException::class)]
final class DocumentExpiredExceptionTest extends TestCase
{
    #[Test]
    public function it_extends_kyc_verification_exception(): void
    {
        $exception = new DocumentExpiredException(
            documentId: 'DOC-001',
            documentType: DocumentType::PASSPORT,
            expiryDate: new DateTimeImmutable('-1 day')
        );

        $this->assertInstanceOf(KycVerificationException::class, $exception);
    }

    #[Test]
    public function it_stores_document_id(): void
    {
        $exception = new DocumentExpiredException(
            documentId: 'DOC-002',
            documentType: DocumentType::NATIONAL_ID,
            expiryDate: new DateTimeImmutable('-30 days')
        );

        $this->assertSame('DOC-002', $exception->getDocumentId());
    }

    #[Test]
    public function it_stores_document_type(): void
    {
        $exception = new DocumentExpiredException(
            documentId: 'DOC-003',
            documentType: DocumentType::DRIVERS_LICENSE,
            expiryDate: new DateTimeImmutable('-7 days')
        );

        $this->assertSame(DocumentType::DRIVERS_LICENSE, $exception->getDocumentType());
    }

    #[Test]
    public function it_stores_expiry_date(): void
    {
        $expiryDate = new DateTimeImmutable('2024-01-15');

        $exception = new DocumentExpiredException(
            documentId: 'DOC-004',
            documentType: DocumentType::PASSPORT,
            expiryDate: $expiryDate
        );

        $this->assertSame($expiryDate, $exception->getExpiryDate());
    }

    #[Test]
    public function it_formats_message_correctly(): void
    {
        $exception = new DocumentExpiredException(
            documentId: 'DOC-005',
            documentType: DocumentType::PASSPORT,
            expiryDate: new DateTimeImmutable('2024-06-15')
        );

        $message = $exception->getMessage();

        $this->assertStringContainsString('DOC-005', $message);
        $this->assertStringContainsString('passport', $message);
        $this->assertStringContainsString('2024-06-15', $message);
    }

    #[Test]
    public function it_calculates_days_since_expiry(): void
    {
        $expiryDate = new DateTimeImmutable('-10 days');

        $exception = new DocumentExpiredException(
            documentId: 'DOC-006',
            documentType: DocumentType::NATIONAL_ID,
            expiryDate: $expiryDate
        );

        $daysSinceExpiry = $exception->getDaysSinceExpiry();

        $this->assertGreaterThanOrEqual(9, $daysSinceExpiry);
        $this->assertLessThanOrEqual(11, $daysSinceExpiry);
    }
}
