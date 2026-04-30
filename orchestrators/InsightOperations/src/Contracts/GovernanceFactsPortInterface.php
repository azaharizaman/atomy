<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Contracts;

use Nexus\InsightOperations\DTOs\GovernanceFactsDto;

interface GovernanceFactsPortInterface
{
    public function factsForVendor(string $tenantId, string $vendorId): GovernanceFactsDto;
}
