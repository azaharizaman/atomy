<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

use Nexus\ProcurementOperations\DTOs\VendorRecommendation\VendorRecommendationRequest;
use Nexus\ProcurementOperations\DTOs\VendorRecommendation\VendorRecommendationResult;

interface VendorScorerInterface
{
    public function score(VendorRecommendationRequest $request): VendorRecommendationResult;
}
