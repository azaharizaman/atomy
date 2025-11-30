<?php

declare(strict_types=1);

namespace Nexus\Assets\Services;

use Nexus\Assets\Contracts\AssetInterface;
use Nexus\Assets\Contracts\AssetRepositoryInterface;
use Nexus\Assets\Contracts\AssetVerifierInterface;
use Nexus\Assets\Events\PhysicalAuditFailedEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * Asset Verifier - Physical audit and reconciliation engine (Tier 3).
 *
 * Capabilities:
 * - Physical asset verification workflow
 * - Discrepancy detection (missing, extra, location mismatch)
 * - Audit trail generation
 * - Integration with QR/RFID scanning systems
 *
 * Workflow:
 * 1. Generate audit checklist from database
 * 2. Field auditors scan/verify physical assets
 * 3. System compares scanned vs. expected
 * 4. Flag discrepancies for investigation
 * 5. Generate audit report for management
 *
 * Use Cases:
 * - Annual physical inventory audits
 * - SOX/IFRS compliance verification
 * - Insurance claim documentation
 * - Asset transfer validation
 */
final readonly class AssetVerifier implements AssetVerifierInterface
{
    public function __construct(
        private AssetRepositoryInterface $repository,
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger
    ) {}

    public function initiatePhysicalAudit(array $scope): string
    {
        // Determine audit scope
        $assets = $this->getAuditScope($scope);

        // Generate audit session ID
        $auditId = $this->generateAuditId();

        // Create audit record
        $this->repository->createPhysicalAudit([
            'audit_id' => $auditId,
            'initiated_at' => new \DateTimeImmutable(),
            'scope' => $scope,
            'total_assets' => count($assets),
            'status' => 'IN_PROGRESS',
        ]);

        $this->logger->info('Physical audit initiated', [
            'audit_id' => $auditId,
            'scope' => $scope,
            'asset_count' => count($assets),
        ]);

        return $auditId;
    }

    public function recordPhysicalVerification(
        string $auditId,
        string $assetTag,
        array $verificationData
    ): void {
        // Find asset by tag
        $asset = $this->repository->findByAssetTag($assetTag);

        if (!$asset) {
            // Record as "extra" asset (not in database)
            $this->recordDiscrepancy($auditId, [
                'type' => 'EXTRA_ASSET',
                'asset_tag' => $assetTag,
                'physical_location' => $verificationData['location'] ?? null,
                'notes' => 'Asset found physically but not in database',
            ]);

            return;
        }

        // Validate location match
        $expectedLocation = $asset->getLocation();
        $actualLocation = $verificationData['location'] ?? null;

        $locationMatch = $this->compareLocations($expectedLocation, $actualLocation);

        // Validate condition
        $expectedCondition = $asset->getStatus();
        $actualCondition = $verificationData['condition'] ?? null;

        // Record verification
        $this->repository->recordVerification([
            'audit_id' => $auditId,
            'asset_id' => $asset->getId(),
            'asset_tag' => $assetTag,
            'verified_at' => new \DateTimeImmutable(),
            'location_match' => $locationMatch,
            'expected_location' => $expectedLocation,
            'actual_location' => $actualLocation,
            'condition' => $actualCondition,
            'notes' => $verificationData['notes'] ?? null,
            'verified_by' => $verificationData['verified_by'] ?? null,
        ]);

        // Flag discrepancy if location mismatch
        if (!$locationMatch) {
            $this->recordDiscrepancy($auditId, [
                'type' => 'LOCATION_MISMATCH',
                'asset_id' => $asset->getId(),
                'asset_tag' => $assetTag,
                'expected_location' => $expectedLocation,
                'actual_location' => $actualLocation,
                'notes' => 'Asset found in different location than database record',
            ]);
        }
    }

    public function completePhysicalAudit(string $auditId): array
    {
        $audit = $this->repository->getPhysicalAudit($auditId);
        $verifications = $this->repository->getVerifications($auditId);
        $discrepancies = $this->repository->getDiscrepancies($auditId);

        // Calculate missing assets (in database but not verified)
        $expectedAssets = $this->getAuditScope($audit['scope']);
        $verifiedAssetIds = array_map(fn($v) => $v['asset_id'], $verifications);
        $missingAssets = array_filter(
            $expectedAssets,
            fn($asset) => !in_array($asset->getId(), $verifiedAssetIds)
        );

        // Record missing assets as discrepancies
        foreach ($missingAssets as $asset) {
            $this->recordDiscrepancy($auditId, [
                'type' => 'MISSING_ASSET',
                'asset_id' => $asset->getId(),
                'asset_tag' => $asset->getAssetTag(),
                'expected_location' => $asset->getLocation(),
                'notes' => 'Asset not found during physical audit',
            ]);

            // Dispatch critical event
            $this->eventDispatcher->dispatch(
                new PhysicalAuditFailedEvent(
                    assetId: $asset->getId(),
                    assetTag: $asset->getAssetTag(),
                    auditId: $auditId,
                    expectedLocation: $asset->getLocation(),
                    actualLocation: null,
                    discrepancyType: 'MISSING_ASSET'
                )
            );
        }

        // Update audit status
        $this->repository->updatePhysicalAudit($auditId, [
            'status' => 'COMPLETED',
            'completed_at' => new \DateTimeImmutable(),
            'total_verified' => count($verifications),
            'total_discrepancies' => count($discrepancies) + count($missingAssets),
        ]);

        $result = [
            'audit_id' => $auditId,
            'total_expected' => count($expectedAssets),
            'total_verified' => count($verifications),
            'total_missing' => count($missingAssets),
            'total_extra' => count(array_filter($discrepancies, fn($d) => $d['type'] === 'EXTRA_ASSET')),
            'total_location_mismatch' => count(array_filter($discrepancies, fn($d) => $d['type'] === 'LOCATION_MISMATCH')),
            'accuracy_rate' => $this->calculateAccuracy($verifications, $discrepancies, count($expectedAssets)),
        ];

        $this->logger->info('Physical audit completed', $result);

        return $result;
    }

    /**
     * Get assets within audit scope.
     */
    private function getAuditScope(array $scope): array
    {
        $filters = [];

        if (isset($scope['location_ids'])) {
            $filters['location_ids'] = $scope['location_ids'];
        }

        if (isset($scope['category_ids'])) {
            $filters['category_ids'] = $scope['category_ids'];
        }

        if (isset($scope['asset_ids'])) {
            $filters['ids'] = $scope['asset_ids'];
        }

        return $this->repository->findAll($filters);
    }

    /**
     * Generate unique audit ID.
     */
    private function generateAuditId(): string
    {
        return 'AUDIT-' . date('Ymd') . '-' . bin2hex(random_bytes(4));
    }

    /**
     * Compare expected vs. actual location.
     *
     * Handles both string (Tier 1) and object (Tier 2/3) locations.
     */
    private function compareLocations(mixed $expected, mixed $actual): bool
    {
        if ($expected === null || $actual === null) {
            return false;
        }

        // String comparison for Tier 1
        if (is_string($expected) && is_string($actual)) {
            return strcasecmp(trim($expected), trim($actual)) === 0;
        }

        // Object comparison for Tier 2/3
        if (is_object($expected) && is_object($actual)) {
            return $expected->getId() === $actual->getId();
        }

        return false;
    }

    /**
     * Record discrepancy in audit log.
     */
    private function recordDiscrepancy(string $auditId, array $data): void
    {
        $this->repository->recordDiscrepancy(array_merge([
            'audit_id' => $auditId,
            'detected_at' => new \DateTimeImmutable(),
        ], $data));

        $this->logger->warning('Audit discrepancy detected', [
            'audit_id' => $auditId,
            'type' => $data['type'],
            'asset_tag' => $data['asset_tag'] ?? null,
        ]);
    }

    /**
     * Calculate audit accuracy rate.
     */
    private function calculateAccuracy(array $verifications, array $discrepancies, int $totalExpected): float
    {
        if ($totalExpected === 0) {
            return 100.0;
        }

        $accurate = count(array_filter($verifications, fn($v) => $v['location_match']));
        
        return ($accurate / $totalExpected) * 100.0;
    }
}
