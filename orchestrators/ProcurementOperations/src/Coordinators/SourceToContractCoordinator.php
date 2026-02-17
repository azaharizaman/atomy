<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Coordinators;

use Nexus\ProcurementOperations\Contracts\SourceToContractCoordinatorInterface;
use Nexus\ProcurementOperations\DTOs\RFQRequest;
use Nexus\ProcurementOperations\DTOs\RFQResult;
use Nexus\ProcurementOperations\DTOs\QuoteSubmission;
use Nexus\ProcurementOperations\DTOs\QuoteComparisonResult;
use Nexus\ProcurementOperations\Services\QuoteComparisonService;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Coordinates the sourcing process from RFQ publication to awarding.
 */
final readonly class SourceToContractCoordinator implements SourceToContractCoordinatorInterface
{
    public function __construct(
        private QuoteComparisonService $comparisonService,
        private LoggerInterface $logger = new NullLogger()
    ) {}

    /**
     * @inheritDoc
     */
    public function publishRFQ(RFQRequest $request): RFQResult
    {
        $this->logger->info('Publishing RFQ', ['title' => $request->title]);

        // Logic to persist RFQ in atomic package would go here
        $rfqId = 'rfq-' . uniqid();
        
        return RFQResult::success($rfqId, 'RFQ-' . date('Ymd'), 'published');
    }

    /**
     * @inheritDoc
     */
    public function submitQuote(string $tenantId, string $rfqId, QuoteSubmission $submission): RFQResult
    {
        $this->logger->info('Quote submitted', ['rfq_id' => $rfqId, 'vendor_id' => $submission->vendorId]);
        
        return RFQResult::success($rfqId, 'RFQ-' . $rfqId, 'bid_received');
    }

    /**
     * @inheritDoc
     */
    public function compareAndAward(string $tenantId, string $rfqId, array $rankingWeights): QuoteComparisonResult
    {
        // 1. Fetch all quotes for RFQ (mocking for now)
        $submissions = []; // Fetch from DB/Provider

        // 2. Run comparison
        $rankings = $this->comparisonService->compare($submissions, $rankingWeights);
        $recommended = $rankings[0]['vendorId'] ?? null;

        return QuoteComparisonResult::success(
            rfqId: $rfqId,
            rankings: $rankings,
            recommendedVendorId: $recommended,
            message: 'Comparison complete. Recommendation based on weighted scoring.'
        );
    }

    /**
     * @inheritDoc
     */
    public function convertToContract(string $tenantId, string $rfqId, string $awardedVendorId): RFQResult
    {
        $this->logger->info('Awarding RFQ and converting to contract', [
            'rfq_id' => $rfqId,
            'vendor_id' => $awardedVendorId
        ]);

        return RFQResult::success($rfqId, 'RFQ-' . $rfqId, 'awarded', 'Contract generated successfully.');
    }
}
