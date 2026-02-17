<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs;

/**
 * Request DTO for creating an RFQ.
 */
final readonly class RFQRequest
{
    /**
     * @param string $tenantId Tenant context
     * @param string $title RFQ Title
     * @param array<int, array{
     *     productId: string,
     *     description: string,
     *     quantity: float,
     *     uom: string
     * }> $items Requested items
     * @param array<int, string> $invitedVendorIds Vendors invited to bid
     * @param \DateTimeImmutable|null $deadline Bidding deadline
     * @param string|null $justification Business justification
     * @param array<string, mixed> $metadata Additional metadata
     */
    public function __construct(
        public string $tenantId,
        public string $title,
        public array $items,
        public array $invitedVendorIds = [],
        public ?\DateTimeImmutable $deadline = null,
        public ?string $justification = null,
        public array $metadata = []
    ) {}
}
