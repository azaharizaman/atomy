<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Contracts;

use Nexus\QuotationIntelligence\DTOs\NormalizedQuoteLine;

/**
 * Scores vendors using weighted MCDA with lifecycle cost consideration.
 */
interface VendorScoringServiceInterface
{
    /**
     * @param string $tenantId
     * @param string $rfqId
     * @param array<int, array{
     *   vendor_id: string,
     *   lines: array<NormalizedQuoteLine>,
     *   risks: array<int, array<string, mixed>>
     * }> $vendorEvaluations
     *
     * @return array{
     *   weights: array{price: float, risk: float, delivery: float, sustainability: float},
     *   ranking: array<int, array{
     *     vendor_id: string,
     *     rank: int,
     *     total_score: float,
     *     dimensions: array{
     *       price_score: float,
     *       risk_score: float,
     *       delivery_score: float,
     *       sustainability_score: float,
     *       lifecycle_cost_total: float
     *     }
     *   }>
     * }
     */
    public function score(string $tenantId, string $rfqId, array $vendorEvaluations): array;
}

