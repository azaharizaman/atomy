<?php

declare(strict_types=1);

namespace Nexus\KycVerification\Exceptions;

/**
 * Exception thrown when identity verification fails.
 */
class VerificationFailedException extends KycVerificationException
{
    /**
     * @var array<string>
     */
    private array $failureReasons;

    private string $partyId;

    private ?string $verificationId;

    /**
     * @param array<string> $failureReasons
     */
    public function __construct(
        string $partyId,
        array $failureReasons = [],
        ?string $verificationId = null,
        ?\Throwable $previous = null
    ) {
        $this->partyId = $partyId;
        $this->failureReasons = $failureReasons;
        $this->verificationId = $verificationId;

        $message = sprintf(
            'Verification failed for party %s%s: %s',
            $partyId,
            $verificationId !== null ? " (verification: {$verificationId})" : '',
            empty($failureReasons) ? 'Unspecified reason' : implode('; ', $failureReasons)
        );

        parent::__construct($message, 0, $previous);
    }

    public function getPartyId(): string
    {
        return $this->partyId;
    }

    /**
     * @return array<string>
     */
    public function getFailureReasons(): array
    {
        return $this->failureReasons;
    }

    public function getVerificationId(): ?string
    {
        return $this->verificationId;
    }

    /**
     * Create for missing required documents
     * 
     * @param array<string> $missingDocuments
     */
    public static function missingDocuments(string $partyId, array $missingDocuments): self
    {
        $reasons = array_map(
            fn(string $doc) => "Missing required document: {$doc}",
            $missingDocuments
        );

        return new self($partyId, $reasons);
    }

    /**
     * Create for document validation failure
     */
    public static function documentValidationFailed(string $partyId, string $documentType, string $reason): self
    {
        return new self($partyId, [
            sprintf('Document %s validation failed: %s', $documentType, $reason),
        ]);
    }

    /**
     * Create for identity mismatch
     */
    public static function identityMismatch(string $partyId, string $field): self
    {
        return new self($partyId, [
            sprintf('Identity mismatch detected in field: %s', $field),
        ]);
    }
}
