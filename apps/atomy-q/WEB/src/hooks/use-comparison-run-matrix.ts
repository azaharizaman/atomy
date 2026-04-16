'use client';

import { useQuery } from '@tanstack/react-query';
import { fetchLiveOrFail } from '@/lib/api-live';
import { isObject, toText, unwrapResponse } from '@/hooks/normalize-utils';

export interface ComparisonRunMatrixOffer {
  vendorId: string;
  rfqLineId: string;
  taxonomyCode: string;
  normalizedUnitPrice: number;
  normalizedQuantity: number;
  aiConfidence: number;
}

export interface ComparisonRunMatrixCluster {
  clusterKey: string;
  basis: string;
  offers: ComparisonRunMatrixOffer[];
  statistics: {
    minNormalizedUnitPrice: number;
    maxNormalizedUnitPrice: number;
    avgNormalizedUnitPrice: number;
  };
  recommendation: {
    recommendedVendorId: string;
    reason: string;
  };
}

export interface ComparisonRunMatrix {
  id: string;
  clusters: ComparisonRunMatrixCluster[];
}

function toRequiredNumber(value: unknown, fieldName: string): number {
  if (value === null || value === undefined || value === '') {
    throw new Error(`Comparison matrix payload is missing ${fieldName}.`);
  }

  const numberValue = Number(value);
  if (!Number.isFinite(numberValue)) {
    throw new Error(`Comparison matrix field ${fieldName} must be numeric.`);
  }

  return numberValue;
}

function normalizeOffer(payload: unknown, index: number): ComparisonRunMatrixOffer {
  if (!isObject(payload)) {
    throw new Error(`Comparison matrix offer at index ${index} must be an object.`);
  }

  const vendorId = toText(payload.vendor_id ?? payload.vendorId);
  const rfqLineId = toText(payload.rfq_line_id ?? payload.rfqLineId);
  const taxonomyCode = toText(payload.taxonomy_code ?? payload.taxonomyCode);
  if (vendorId === null || rfqLineId === null || taxonomyCode === null) {
    throw new Error(`Comparison matrix offer at index ${index} is missing vendor_id, rfq_line_id, or taxonomy_code.`);
  }

  const normalizedUnitPrice = toRequiredNumber(payload.normalized_unit_price ?? payload.normalizedUnitPrice, 'normalized_unit_price');
  const normalizedQuantity = toRequiredNumber(payload.normalized_quantity ?? payload.normalizedQuantity, 'normalized_quantity');
  const aiConfidence = toRequiredNumber(payload.ai_confidence ?? payload.aiConfidence, 'ai_confidence');

  return {
    vendorId,
    rfqLineId,
    taxonomyCode,
    normalizedUnitPrice,
    normalizedQuantity,
    aiConfidence,
  };
}

function normalizeCluster(payload: unknown, index: number): ComparisonRunMatrixCluster {
  if (!isObject(payload)) {
    throw new Error(`Comparison matrix cluster at index ${index} must be an object.`);
  }

  const clusterKey = toText(payload.cluster_key ?? payload.clusterKey);
  const basis = toText(payload.basis);
  if (clusterKey === null || basis === null) {
    throw new Error(`Comparison matrix cluster at index ${index} is missing cluster_key or basis.`);
  }

  const offersRaw = Array.isArray(payload.offers) ? payload.offers : [];
  const offers = offersRaw.map((item, offerIndex) => normalizeOffer(item, offerIndex));

  if (!isObject(payload.statistics)) {
    throw new Error(`Comparison matrix cluster at index ${index} is missing statistics.`);
  }
  const minNormalizedUnitPrice = toRequiredNumber(
    payload.statistics.min_normalized_unit_price ?? payload.statistics.minNormalizedUnitPrice,
    'statistics.min_normalized_unit_price',
  );
  const maxNormalizedUnitPrice = toRequiredNumber(
    payload.statistics.max_normalized_unit_price ?? payload.statistics.maxNormalizedUnitPrice,
    'statistics.max_normalized_unit_price',
  );
  const avgNormalizedUnitPrice = toRequiredNumber(
    payload.statistics.avg_normalized_unit_price ?? payload.statistics.avgNormalizedUnitPrice,
    'statistics.avg_normalized_unit_price',
  );

  if (!isObject(payload.recommendation)) {
    throw new Error(`Comparison matrix cluster at index ${index} is missing recommendation.`);
  }
  const recommendedVendorId = toText(
    payload.recommendation.recommended_vendor_id ?? payload.recommendation.recommendedVendorId,
  );
  const reason = toText(payload.recommendation.reason);
  if (recommendedVendorId === null || reason === null) {
    throw new Error(`Comparison matrix cluster at index ${index} is missing recommendation fields.`);
  }

  return {
    clusterKey,
    basis,
    offers,
    statistics: {
      minNormalizedUnitPrice,
      maxNormalizedUnitPrice,
      avgNormalizedUnitPrice,
    },
    recommendation: {
      recommendedVendorId,
      reason,
    },
  };
}

function normalizeComparisonRunMatrix(payload: unknown): ComparisonRunMatrix {
  const raw = unwrapResponse(payload);
  if (!isObject(raw)) {
    throw new Error('Comparison matrix payload must be an object.');
  }

  const id = toText(raw.id ?? raw.run_id ?? raw.runId);
  if (id === null) {
    throw new Error('Comparison matrix payload is missing id.');
  }

  const matrix = isObject(raw.matrix) ? raw.matrix : raw;
  if (!Array.isArray(matrix.clusters)) {
    throw new Error('Comparison matrix payload is missing clusters.');
  }
  const clustersRaw = matrix.clusters;

  return {
    id,
    clusters: clustersRaw.map((item, index) => normalizeCluster(item, index)),
  };
}

export function useComparisonRunMatrix(runId: string, options?: { rfqId?: string }) {
  const useMocks = process.env.NEXT_PUBLIC_USE_MOCKS === 'true';

  return useQuery({
    queryKey: ['comparison-run-matrix', options?.rfqId ?? runId, runId],
    queryFn: async (): Promise<ComparisonRunMatrix> => {
      if (useMocks) {
        const { getSeedComparisonRunsByRfqId } = await import('@/data/seed');
        const seedRun = options?.rfqId
          ? getSeedComparisonRunsByRfqId(options.rfqId).find((item) => item.id === runId || item.runId === runId)
          : null;
        return {
          id: seedRun?.id ?? runId,
          clusters: [],
        };
      }

      const data = await fetchLiveOrFail<{ data: ComparisonRunMatrix }>(`/comparison-runs/${encodeURIComponent(runId)}/matrix`);

      if (data === undefined) {
        throw new Error(`Comparison matrix "${runId}" is unavailable from the live API.`);
      }

      return normalizeComparisonRunMatrix(data);
    },
    enabled: Boolean(runId),
  });
}

export { normalizeComparisonRunMatrix };
