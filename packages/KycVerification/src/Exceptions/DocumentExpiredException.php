<?php

declare(strict_types=1);

namespace Nexus\KycVerification\Exceptions;

use Nexus\KycVerification\Enums\DocumentType;

/**
 * Exception thrown when a required document has expired.
 */
class DocumentExpiredException extends KycVerificationException
{
    private string $documentId;

    private DocumentType $documentType;

    private \DateTimeImmutable $expiryDate;

    public function __construct(
        string $documentId,
        DocumentType $documentType,
        \DateTimeImmutable $expiryDate,
        ?\Throwable $previous = null
    ) {
        $this->documentId = $documentId;
        $this->documentType = $documentType;
        $this->expiryDate = $expiryDate;

        parent::__construct(
            sprintf(
                'Document %s of type %s expired on %s',
                $documentId,
                $documentType->value,
                $expiryDate->format('Y-m-d')
            ),
            0,
            $previous
        );
    }

    public function getDocumentId(): string
    {
        return $this->documentId;
    }

    public function getDocumentType(): DocumentType
    {
        return $this->documentType;
    }

    public function getExpiryDate(): \DateTimeImmutable
    {
        return $this->expiryDate;
    }

    /**
     * Get days since expiry
     */
    public function getDaysSinceExpiry(): int
    {
        $now = new \DateTimeImmutable();
        return (int) $now->diff($this->expiryDate)->days;
    }
}
