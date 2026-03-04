<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Services;

use Nexus\QuotationIntelligence\Contracts\QuoteComparisonMatrixServiceInterface;
use Nexus\QuotationIntelligence\DTOs\NormalizedQuoteLine;

/**
 * Builds comparison clusters across vendor submissions for the same RFQ.
 */
final readonly class QuoteComparisonMatrixService implements QuoteComparisonMatrixServiceInterface
{
    /**
     * @inheritDoc
     */
    public function buildMatrix(string $tenantId, string $rfqId, array $vendorLineSets): array
    {
        $clusters = [];

        foreach ($vendorLineSets as $vendorSet) {
            $vendorId = (string)($vendorSet['vendor_id'] ?? '');
            $lines = $vendorSet['lines'] ?? [];

            foreach ($lines as $line) {
                if (!$line instanceof NormalizedQuoteLine) {
                    continue;
                }

                $clusterKey = $this->resolveClusterKey($line);
                if (!isset($clusters[$clusterKey])) {
                    $clusters[$clusterKey] = [
                        'cluster_key' => $clusterKey,
                        'basis' => $line->rfqLineId !== '' ? 'rfq_line_id' : 'taxonomy_code',
                        'offers' => [],
                    ];
                }

                $clusters[$clusterKey]['offers'][] = [
                    'vendor_id' => $vendorId,
                    'rfq_line_id' => $line->rfqLineId,
                    'taxonomy_code' => $line->taxonomyCode,
                    'normalized_unit_price' => $line->normalizedUnitPrice,
                    'normalized_quantity' => $line->normalizedQuantity,
                    'ai_confidence' => $line->aiConfidence,
                ];
            }
        }

        $materializedClusters = [];
        foreach ($clusters as $cluster) {
            $offers = $cluster['offers'];
            $prices = array_map(
                static fn(array $offer): float => (float)$offer['normalized_unit_price'],
                $offers
            );
            $priceCount = count($prices);

            $minPrice = $priceCount > 0 ? min($prices) : 0.0;
            $maxPrice = $priceCount > 0 ? max($prices) : 0.0;
            $avgPrice = $priceCount > 0 ? array_sum($prices) / $priceCount : 0.0;

            $recommendedVendorId = '';
            foreach ($offers as $offer) {
                if ((float)$offer['normalized_unit_price'] === $minPrice) {
                    $recommendedVendorId = (string)$offer['vendor_id'];
                    break;
                }
            }

            $materializedClusters[] = [
                'cluster_key' => $cluster['cluster_key'],
                'basis' => $cluster['basis'],
                'offers' => $offers,
                'statistics' => [
                    'min_normalized_unit_price' => $minPrice,
                    'max_normalized_unit_price' => $maxPrice,
                    'avg_normalized_unit_price' => $avgPrice,
                ],
                'recommendation' => [
                    'recommended_vendor_id' => $recommendedVendorId,
                    'reason' => 'lowest_normalized_unit_price',
                ],
            ];
        }

        return [
            'tenant_id' => $tenantId,
            'rfq_id' => $rfqId,
            'clusters' => $materializedClusters,
        ];
    }

    private function resolveClusterKey(NormalizedQuoteLine $line): string
    {
        if ($line->rfqLineId !== '') {
            return 'rfq:' . $line->rfqLineId;
        }

        return 'tax:' . $line->taxonomyCode;
    }
}

