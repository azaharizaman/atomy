<?php

declare(strict_types=1);

namespace Nexus\Assets\Exceptions;

/**
 * Disposal Not Allowed Exception
 *
 * Thrown when asset disposal is not allowed due to business rules.
 */
class DisposalNotAllowedException extends AssetException
{
    public static function assetInUse(string $assetId, string $userId): self
    {
        return new self("Asset {$assetId} cannot be disposed while assigned to user {$userId}");
    }

    public static function maintenanceContractActive(string $assetId): self
    {
        return new self("Asset {$assetId} has active maintenance contract and cannot be disposed");
    }

    public static function invalidStatus(string $assetId, string $status): self
    {
        return new self("Asset {$assetId} cannot be disposed in status: {$status}");
    }
}
