<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

use Nexus\ProcurementOperations\DTOs\VendorRecommendation\VendorRecommendationRequest;
use Nexus\ProcurementOperations\DTOs\VendorRecommendation\VendorRecommendationResult;

interface VendorRecommendationCoordinatorInterface
{
    public function recommend(VendorRecommendationRequest $request): VendorRecommendationResult;
}
