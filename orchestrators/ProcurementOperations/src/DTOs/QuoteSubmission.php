<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs;

/**
 * DTO for a vendor quote submission.
 */
final readonly class QuoteSubmission
{
    /**
     * @param string $vendorId Vendor ID
     * @param array<int, array{
     *     productId: string,
     *     unitPriceCents: int,
     *     leadTimeDays: int,
     *     notes?: string
     * }> $items Quoted items and terms
     * @param string|null $currency Quoted currency
     * @param \DateTimeImmutable|null $validUntil Validity of the quote
     * @param string|null $notes Additional terms or comments
     */
    public function __construct(
        public string $vendorId,
        public array $items,
        public ?string $currency = 'USD',
        public ?\DateTimeImmutable $validUntil = null,
        public ?string $notes = null
    ) {}
}
