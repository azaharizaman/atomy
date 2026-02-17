<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs;

/**
 * Result DTO for quote comparison.
 */
final readonly class QuoteComparisonResult
{
    /**
     * @param bool $success Whether comparison succeeded
     * @param string $rfqId Associated RFQ ID
     * @param array<int, array{
     *     vendorId: string,
     *     vendorName: string,
     *     totalAmountCents: int,
     *     score: float,
     *     rank: int
     * }> $rankings Ranked submissions
     * @param string|null $recommendedVendorId Vendor ID of the top-ranked submission
     * @param string|null $message Human-readable result or recommendation
     */
    public function __construct(
        public bool $success,
        public string $rfqId,
        public array $rankings = [],
        public ?string $recommendedVendorId = null,
        public ?string $message = null
    ) {}

    /**
     * Create a successful result.
     */
    public static function success(
        string $rfqId,
        array $rankings,
        ?string $recommendedVendorId = null,
        ?string $message = null
    ): self {
        return new self(
            success: true,
            rfqId: $rfqId,
            rankings: $rankings,
            recommendedVendorId: $recommendedVendorId,
            message: $message
        );
    }
}
