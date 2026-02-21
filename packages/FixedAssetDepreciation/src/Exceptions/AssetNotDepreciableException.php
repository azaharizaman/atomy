<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Exceptions;

/**
 * Asset Not Depreciable Exception
 *
 * Thrown when attempting to depreciate an asset that is not eligible
 * for depreciation (e.g., land, fully depreciated, disposed).
 *
 * @package Nexus\FixedAssetDepreciation\Exceptions
 */
class AssetNotDepreciableException extends DepreciationException
{
    protected string $errorCode = 'ASSET_NOT_DEPRECIABLE';

    public function __construct(
        public readonly string $assetId,
        string $reason,
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            sprintf('Asset "%s" is not depreciable: %s', $assetId, $reason),
            0,
            $previous
        );
    }

    public static function landAsset(string $assetId): self
    {
        return new self($assetId, 'Land assets are not depreciable');
    }

    public static function fullyDepreciated(string $assetId, float $netBookValue, float $salvageValue): self
    {
        return new self(
            $assetId,
            sprintf(
                'Asset is fully depreciated (NBV: %s, Salvage: %s)',
                number_format($netBookValue, 2),
                number_format($salvageValue, 2)
            )
        );
    }

    public static function disposed(string $assetId): self
    {
        return new self($assetId, 'Asset has been disposed and cannot be depreciated');
    }

    public static function inactive(string $assetId, string $status): self
    {
        return new self($assetId, sprintf('Asset is not active (status: %s)', $status));
    }

    public static function notFound(string $assetId): self
    {
        return new self($assetId, 'Asset not found');
    }

    public static function zeroCost(string $assetId): self
    {
        return new self($assetId, 'Asset has zero cost and cannot be depreciated');
    }
}
