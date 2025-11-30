<?php

declare(strict_types=1);

namespace Nexus\Assets\Services;

use Nexus\Assets\Contracts\AssetInterface;
use Nexus\Assets\Contracts\AssetManagerInterface;
use Nexus\Assets\Contracts\AssetRepositoryInterface;
use Nexus\Assets\Contracts\DepreciationEngineInterface;
use Nexus\Assets\Enums\AssetStatus;
use Nexus\Assets\Enums\DepreciationMethod;
use Nexus\Assets\Enums\DisposalMethod;
use Nexus\Assets\Events\AssetAcquiredEvent;
use Nexus\Assets\Events\AssetDisposedEvent;
use Nexus\Assets\Events\DepreciationRecordedEvent;
use Nexus\Assets\Exceptions\DisposalNotAllowedException;
use Nexus\Assets\Exceptions\InvalidAssetDataException;
use Nexus\Assets\ValueObjects\AssetTag;
use Nexus\Setting\Services\SettingsManager;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * Asset Manager - Core orchestrator for asset lifecycle management.
 *
 * Progressive Features:
 * - Tier 1 (SB): Basic tracking, Straight-Line depreciation
 * - Tier 2 (MB): Adds warranty tracking, maintenance integration
 * - Tier 3 (LE): Adds GL posting, physical audits, multi-currency
 *
 * Design Pattern: Fluent API with optional chaining.
 */
final readonly class AssetManager implements AssetManagerInterface
{
    public function __construct(
        private AssetRepositoryInterface $repository,
        private SettingsManager $settings,
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger
    ) {}

    public function createAsset(array $data): AssetInterface
    {
        // Validate required fields
        $this->validateAssetData($data);

        // Detect tier and validate tier-specific features
        $currentTier = $this->detectCurrentTier();
        $this->validateTierFeatures($data, $currentTier);

        // Generate asset tag if not provided (Tier 1 uses simple sequence)
        if (!isset($data['asset_tag'])) {
            $data['asset_tag'] = $this->generateAssetTag($currentTier);
        }

        // Set default status
        $data['status'] = $data['status'] ?? AssetStatus::ACTIVE;

        // Create asset
        $asset = $this->repository->create($data);

        // Dispatch acquisition event
        $this->eventDispatcher->dispatch(
            new AssetAcquiredEvent(
                assetId: $asset->getId(),
                assetTag: $asset->getAssetTag(),
                cost: $asset->getCost(),
                acquisitionDate: $asset->getAcquisitionDate(),
                category: $data['category_id'] ?? null,
                location: $data['location'] ?? null
            )
        );

        $this->logger->info('Asset created', [
            'asset_id' => $asset->getId(),
            'asset_tag' => $asset->getAssetTag(),
            'cost' => $asset->getCost(),
        ]);

        return $asset;
    }

    public function updateAsset(string $id, array $data): AssetInterface
    {
        $asset = $this->repository->findById($id);

        // Prevent updating certain fields after acquisition
        $protectedFields = ['cost', 'acquisition_date', 'depreciation_method'];
        foreach ($protectedFields as $field) {
            if (isset($data[$field]) && $data[$field] !== $asset->{'get' . str_replace('_', '', ucwords($field, '_'))}()) {
                throw InvalidAssetDataException::cannotModifyField($id, $field);
            }
        }

        $updated = $this->repository->update($id, $data);

        $this->logger->info('Asset updated', [
            'asset_id' => $id,
            'fields_updated' => array_keys($data),
        ]);

        return $updated;
    }

    public function disposeAsset(
        string $id,
        DisposalMethod $method,
        \DateTimeImmutable $disposalDate,
        float $proceeds = 0.0,
        ?string $notes = null
    ): array {
        $asset = $this->repository->findById($id);

        // Validate disposal is allowed
        if (!$asset->getStatus()->canTransitionTo(AssetStatus::DISPOSED)) {
            throw DisposalNotAllowedException::invalidStatus($id, $asset->getStatus());
        }

        if ($disposalDate < $asset->getAcquisitionDate()) {
            throw InvalidAssetDataException::disposalBeforeAcquisition($id, $disposalDate, $asset->getAcquisitionDate());
        }

        // Calculate disposal gain/loss
        $bookValue = $asset->getCost() - $asset->getAccumulatedDepreciation();
        $gainLoss = $proceeds - $bookValue;

        // Update asset status
        $this->repository->update($id, [
            'status' => AssetStatus::DISPOSED,
            'disposal_date' => $disposalDate,
            'disposal_method' => $method,
            'disposal_proceeds' => $proceeds,
            'disposal_notes' => $notes,
        ]);

        // Determine if GL posting is needed (Tier 3 only)
        $shouldPostToGL = $this->shouldPostToGL();

        // Dispatch disposal event (GL listener will create JE if Tier 3)
        $this->eventDispatcher->dispatch(
            new AssetDisposedEvent(
                assetId: $id,
                assetTag: $asset->getAssetTag(),
                disposalDate: $disposalDate,
                disposalMethod: $method,
                originalCost: $asset->getCost(),
                accumulatedDepreciation: $asset->getAccumulatedDepreciation(),
                proceeds: $proceeds,
                gainLoss: $gainLoss,
                shouldPostToGL: $shouldPostToGL
            )
        );

        $this->logger->info('Asset disposed', [
            'asset_id' => $id,
            'method' => $method->value,
            'gain_loss' => $gainLoss,
            'gl_posting' => $shouldPostToGL,
        ]);

        return [
            'book_value' => $bookValue,
            'proceeds' => $proceeds,
            'gain_loss' => $gainLoss,
            'requires_gl_posting' => $shouldPostToGL,
        ];
    }

    public function recordDepreciation(
        string $assetId,
        \DateTimeImmutable $periodStart,
        \DateTimeImmutable $periodEnd,
        DepreciationEngineInterface $engine,
        ?float $unitsConsumed = null
    ): float {
        $asset = $this->repository->findById($assetId);

        // Validate asset can be depreciated
        if (!$asset->getStatus()->canDepreciate()) {
            throw InvalidAssetDataException::cannotDepreciateInStatus($assetId, $asset->getStatus());
        }

        // Calculate depreciation based on method
        $depreciationMethod = $asset->getDepreciationMethod();
        
        if ($depreciationMethod === DepreciationMethod::UNITS_OF_PRODUCTION) {
            if ($unitsConsumed === null) {
                throw InvalidAssetDataException::missingUnitsConsumed($assetId);
            }
            $amount = $engine->calculateUnits($asset, $unitsConsumed, $periodStart, $periodEnd);
        } else {
            $amount = $engine->calculateDepreciation($asset, $periodStart, $periodEnd);
        }

        if ($amount <= 0) {
            return 0.0;
        }

        // Update accumulated depreciation
        $oldAccumulated = $asset->getAccumulatedDepreciation();
        $newAccumulated = $oldAccumulated + $amount;

        $this->repository->update($assetId, [
            'accumulated_depreciation' => $newAccumulated,
        ]);

        // Calculate new book value
        $oldBookValue = $asset->getCost() - $oldAccumulated;
        $newBookValue = $asset->getCost() - $newAccumulated;

        // Dispatch depreciation event
        $this->eventDispatcher->dispatch(
            new DepreciationRecordedEvent(
                assetId: $assetId,
                periodStart: $periodStart,
                periodEnd: $periodEnd,
                depreciationAmount: $amount,
                method: $depreciationMethod,
                newAccumulatedDepreciation: $newAccumulated,
                netBookValueChange: $oldBookValue - $newBookValue,
                unitsConsumed: $unitsConsumed
            )
        );

        return $amount;
    }

    public function getAsset(string $id): AssetInterface
    {
        return $this->repository->findById($id);
    }

    public function listAssets(array $filters = []): array
    {
        return $this->repository->findAll($filters);
    }

    /**
     * Detect current tier from Nexus\Setting.
     *
     * Returns: 'basic', 'advanced', or 'enterprise'
     */
    private function detectCurrentTier(): string
    {
        return $this->settings->getString('assets.tier', 'basic');
    }

    /**
     * Validate that requested features are available in current tier.
     */
    private function validateTierFeatures(array $data, string $currentTier): void
    {
        // Tier 2 features
        if (isset($data['warranty_expiry']) || isset($data['maintenance_schedule'])) {
            if ($currentTier === 'basic') {
                throw InvalidAssetDataException::tierFeatureNotAvailable('Warranty tracking', 'advanced');
            }
        }

        // Tier 3 features
        if (isset($data['location_id']) || isset($data['currency_code'])) {
            if ($currentTier !== 'enterprise') {
                throw InvalidAssetDataException::tierFeatureNotAvailable('Advanced location/currency', 'enterprise');
            }
        }

        // Validate depreciation method tier
        if (isset($data['depreciation_method'])) {
            $method = DepreciationMethod::from($data['depreciation_method']);
            $requiredTier = $method->getRequiredTier();

            $tierHierarchy = ['basic' => 1, 'advanced' => 2, 'enterprise' => 3];
            if ($tierHierarchy[$currentTier] < $tierHierarchy[$requiredTier]) {
                throw InvalidAssetDataException::tierFeatureNotAvailable(
                    "Depreciation method: {$method->value}",
                    $requiredTier
                );
            }
        }
    }

    /**
     * Generate asset tag based on tier capabilities.
     */
    private function generateAssetTag(string $tier): string
    {
        if ($tier === 'basic') {
            // Tier 1: Simple incrementing sequence
            return AssetTag::fromSequence($this->repository->getNextSequence())->toString();
        }

        // Tier 2/3: Use Nexus\Sequencing with pattern
        // In real implementation, inject SequencingManager and generate with pattern
        return 'AST-' . str_pad((string) $this->repository->getNextSequence(), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Check if current tier should post to GL automatically.
     */
    private function shouldPostToGL(): bool
    {
        return $this->detectCurrentTier() === 'enterprise';
    }

    /**
     * Validate required asset data fields.
     */
    private function validateAssetData(array $data): void
    {
        $required = ['name', 'cost', 'acquisition_date', 'depreciation_method', 'useful_life_months'];

        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === null) {
                throw InvalidAssetDataException::missingRequiredField($field);
            }
        }

        if ($data['cost'] <= 0) {
            throw InvalidAssetDataException::invalidCost('new', $data['cost']);
        }

        if ($data['useful_life_months'] <= 0) {
            throw InvalidAssetDataException::invalidUsefulLife('new', $data['useful_life_months']);
        }
    }
}
