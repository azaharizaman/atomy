<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

use Nexus\ProcurementML\ValueObjects\VendorRecommendationResult;
use Nexus\ProcurementOperations\DTOs\VendorRecommendation\VendorRecommendationRequest;

interface VendorRecommendationCoordinatorInterface
{
    public function recommend(VendorRecommendationRequest $request): VendorRecommendationResult;
}
