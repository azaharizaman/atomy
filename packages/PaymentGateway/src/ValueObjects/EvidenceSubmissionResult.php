<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\ValueObjects;

/**
 * Result of an evidence submission.
 */
final readonly class EvidenceSubmissionResult
{
    public function __construct(
        public bool $success,
        public string $submissionId,
        public string $status,
        public \DateTimeImmutable $submittedAt,
        public ?string $errorMessage = null,
    ) {}

    public static function success(string $submissionId, string $status): self
    {
        return new self(
            success: true,
            submissionId: $submissionId,
            status: $status,
            submittedAt: new \DateTimeImmutable(),
        );
    }

    public static function failure(string $errorMessage): self
    {
        return new self(
            success: false,
            submissionId: '',
            status: 'failed',
            submittedAt: new \DateTimeImmutable(),
            errorMessage: $errorMessage,
        );
    }
}
