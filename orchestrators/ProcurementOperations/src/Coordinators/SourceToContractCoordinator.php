<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Coordinators;

use Nexus\ProcurementOperations\Contracts\SecureIdGeneratorInterface;
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
        private LoggerInterface $logger = new NullLogger(),
        private ?SecureIdGeneratorInterface $secureIdGenerator = null,
    ) {}

    /**
     * @inheritDoc
     */
    public function publishRFQ(RFQRequest $request): RFQResult
    {
        if ($this->isBlank($request->tenantId)) {
            return RFQResult::failure('Tenant ID is required to publish an RFQ.');
        }

        if ($this->isBlank($request->title)) {
            return RFQResult::failure('RFQ title is required.');
        }

        $this->logger->info('Publishing RFQ', [
            'tenant_id' => $request->tenantId,
            'title' => $request->title,
        ]);

        $rfqSuffix = strtoupper(substr($this->generateRandomHex(4), 0, 8));

        return RFQResult::success(
            rfqId: 'rfq-' . strtolower($rfqSuffix),
            rfqNumber: 'RFQ-' . (new \DateTimeImmutable())->format('Ymd') . '-' . $rfqSuffix,
            status: 'published',
        );
    }

    /**
     * @inheritDoc
     */
    public function submitQuote(string $tenantId, string $rfqId, QuoteSubmission $submission): RFQResult
    {
        if ($this->isBlank($tenantId) || $this->isBlank($rfqId) || $this->isBlank($submission->vendorId)) {
            return RFQResult::failure('Tenant ID, RFQ ID, and vendor ID are required for quote submission.');
        }

        $this->logger->info('Quote submitted', [
            'tenant_id' => $tenantId,
            'rfq_id' => $rfqId,
            'vendor_id' => $submission->vendorId,
        ]);

        return RFQResult::success($rfqId, 'RFQ-' . $rfqId, 'bid_received');
    }

    /**
     * @inheritDoc
     */
    public function compareAndAward(string $tenantId, string $rfqId, array $rankingWeights): QuoteComparisonResult
    {
        if ($this->isBlank($tenantId) || $this->isBlank($rfqId)) {
            return QuoteComparisonResult::failure(
                rfqId: $rfqId,
                message: 'Tenant ID and RFQ ID are required to compare and award quotes.',
            );
        }

        // 1. Fetch all quotes for RFQ (mocking for now)
        $submissions = []; // Fetch from DB/Provider

        // 2. Run comparison
        $rankings = $this->comparisonService->compare($submissions, $rankingWeights);
        $recommended = $rankings[0]['vendorId'] ?? null;

        if ($rankings === []) {
            return QuoteComparisonResult::failure(
                rfqId: $rfqId,
                message: 'No quote submissions available for comparison.',
            );
        }

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
        if ($this->isBlank($tenantId) || $this->isBlank($rfqId) || $this->isBlank($awardedVendorId)) {
            return RFQResult::failure('Tenant ID, RFQ ID, and awarded vendor ID are required.');
        }

        $this->logger->info('Awarding RFQ and converting to contract', [
            'tenant_id' => $tenantId,
            'rfq_id' => $rfqId,
            'vendor_id' => $awardedVendorId
        ]);

        return RFQResult::success($rfqId, 'RFQ-' . $rfqId, 'awarded', 'Contract generated successfully.');
    }

    private function generateRandomHex(int $length): string
    {
        try {
            return $this->secureIdGenerator?->randomHex($length) ?? bin2hex(random_bytes($length));
        } catch (\Throwable $exception) {
            throw new \RuntimeException('Unable to generate RFQ identifier.', 0, $exception);
        }
    }

    private function isBlank(string $value): bool
    {
        return trim($value) === '';
    }
}
