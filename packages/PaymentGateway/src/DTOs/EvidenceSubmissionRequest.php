<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\DTOs;

/**
 * Request to submit evidence for a chargeback/dispute.
 */
final readonly class EvidenceSubmissionRequest
{
    /**
     * @param string $disputeId The ID of the dispute/chargeback
     * @param string|null $textEvidence Textual evidence/explanation
     * @param array<string> $fileIds IDs of uploaded files (from Storage) to submit as evidence
     * @param array<string, mixed> $metadata Additional metadata
     */
    public function __construct(
        public string $disputeId,
        public ?string $textEvidence = null,
        public array $fileIds = [],
        public array $metadata = [],
    ) {}
}
