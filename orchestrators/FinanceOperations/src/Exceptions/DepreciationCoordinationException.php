<?php
declare(strict_types=1);

namespace Nexus\FinanceOperations\Exceptions;

use Throwable;

/**
 * Exception for depreciation coordination errors.
 * 
 * Thrown when depreciation operations fail, including:
 * - Depreciation run failures
 * - Schedule generation errors
 * - Asset revaluation issues
 * 
 * @since 1.0.0
 */
final class DepreciationCoordinationException extends CoordinationException
{
    /**
     * Create exception for depreciation run failure.
     */
    public static function runFailed(
        string $tenantId,
        string $periodId,
        string $reason,
        ?Throwable $previous = null
    ): self {
        return new self(
            sprintf('Depreciation run failed for period %s: %s', $periodId, $reason),
            'DepreciationCoordinator',
            ['tenant_id' => $tenantId, 'period_id' => $periodId],
            $previous
        );
    }

    /**
     * Create exception for asset not found.
     */
    public static function assetNotFound(
        string $tenantId,
        string $assetId
    ): self {
        return new self(
            sprintf('Asset %s not found', $assetId),
            'DepreciationCoordinator',
            ['tenant_id' => $tenantId, 'asset_id' => $assetId]
        );
    }

    /**
     * Create exception for invalid depreciation method.
     */
    public static function invalidDepreciationMethod(
        string $tenantId,
        string $assetId,
        string $method
    ): self {
        return new self(
            sprintf('Invalid depreciation method "%s" for asset %s', $method, $assetId),
            'DepreciationCoordinator',
            ['tenant_id' => $tenantId, 'asset_id' => $assetId, 'method' => $method]
        );
    }

    /**
     * Create exception for fully depreciated asset.
     */
    public static function assetFullyDepreciated(
        string $tenantId,
        string $assetId
    ): self {
        return new self(
            sprintf('Asset %s is fully depreciated', $assetId),
            'DepreciationCoordinator',
            ['tenant_id' => $tenantId, 'asset_id' => $assetId]
        );
    }

    /**
     * Create exception for revaluation failure.
     */
    public static function revaluationFailed(
        string $tenantId,
        string $assetId,
        string $reason,
        ?Throwable $previous = null
    ): self {
        return new self(
            sprintf('Asset revaluation failed for asset %s: %s', $assetId, $reason),
            'DepreciationCoordinator',
            ['tenant_id' => $tenantId, 'asset_id' => $assetId],
            $previous
        );
    }

    /**
     * Create exception for schedule generation failure.
     */
    public static function scheduleGenerationFailed(
        string $tenantId,
        string $assetId,
        string $reason
    ): self {
        return new self(
            sprintf('Depreciation schedule generation failed for asset %s: %s', $assetId, $reason),
            'DepreciationCoordinator',
            ['tenant_id' => $tenantId, 'asset_id' => $assetId]
        );
    }

    /**
     * Create exception for period already processed.
     */
    public static function periodAlreadyProcessed(
        string $tenantId,
        string $periodId
    ): self {
        return new self(
            sprintf('Depreciation for period %s has already been processed', $periodId),
            'DepreciationCoordinator',
            ['tenant_id' => $tenantId, 'period_id' => $periodId]
        );
    }
}
