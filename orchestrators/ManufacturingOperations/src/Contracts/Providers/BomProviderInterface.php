<?php

declare(strict_types=1);

namespace Nexus\Orchestrators\ManufacturingOperations\Contracts\Providers;

use Nexus\Orchestrators\ManufacturingOperations\DTOs\BomExplosionResult;
use Nexus\Orchestrators\ManufacturingOperations\DTOs\BomLookupRequest;

interface BomProviderInterface
{
    /**
     * Get the Bill of Materials for a specific product.
     * 
     * @throws \Nexus\Orchestrators\ManufacturingOperations\Exceptions\BomNotFoundException
     */
    public function getBom(string $tenantId, BomLookupRequest $request): BomExplosionResult;

    /**
     * Validate if a BOM exists and is active for production.
     */
    public function validateBom(string $tenantId, string $bomId): bool;
}
