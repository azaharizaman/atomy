<?php

declare(strict_types=1);

namespace Nexus\ManufacturingOperations\Contracts\Providers;

use Nexus\ManufacturingOperations\DTOs\BomExplosionResult;
use Nexus\ManufacturingOperations\DTOs\BomLookupRequest;

interface BomProviderInterface
{
    /**
     * Get the Bill of Materials for a specific product.
     * 
     * @throws \Nexus\Manufacturing\Exceptions\BomNotFoundException
     */
    public function getBom(string $tenantId, BomLookupRequest $request): BomExplosionResult;

    /**
     * Validate if a BOM exists and is active for production.
     */
    public function validateBom(string $tenantId, string $bomId): bool;
}
