<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

use Nexus\ProcurementOperations\DTOs\RFQRequest;
use Nexus\ProcurementOperations\DTOs\RFQResult;
use Nexus\ProcurementOperations\DTOs\QuoteSubmission;
use Nexus\ProcurementOperations\DTOs\QuoteComparisonResult;

/**
 * Contract for sourcing and RFQ lifecycle coordination.
 */
interface SourceToContractCoordinatorInterface
{
    /**
     * Create and publish an RFQ (Request for Quotation).
     */
    public function publishRFQ(RFQRequest $request): RFQResult;

    /**
     * Record a vendor's quote submission against an RFQ.
     */
    public function submitQuote(
        string $tenantId,
        string $rfqId,
        QuoteSubmission $submission
    ): RFQResult;

    /**
     * Compare all submitted quotes and award to one or more vendors.
     *
     * @param array{price: float, quality: float, delivery: float} $rankingWeights Weights for scoring (total should be 1.0)
     */
    public function compareAndAward(
        string $tenantId,
        string $rfqId,
        array $rankingWeights
    ): QuoteComparisonResult;

    /**
     * Finalize the contract or PO from the awarded RFQ.
     */
    public function convertToContract(
        string $tenantId,
        string $rfqId,
        string $awardedVendorId
    ): RFQResult;
}
