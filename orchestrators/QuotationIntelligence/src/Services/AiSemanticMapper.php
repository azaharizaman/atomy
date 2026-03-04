<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Services;

use Nexus\QuotationIntelligence\Contracts\SemanticMapperInterface;
use Nexus\QuotationIntelligence\Exceptions\SemanticMappingException;
use Nexus\MachineLearning\Contracts\PredictionServiceInterface;
use Psr\Log\LoggerInterface;

/**
 * AI-driven semantic mapper for product taxonomies.
 * 
 * Uses Nexus\MachineLearning to classify descriptions into UNSPSC codes.
 */
final readonly class AiSemanticMapper implements SemanticMapperInterface
{
    private const MODEL_TAG = 'procurement_taxonomy_unspcs_v25';

    public function __construct(
        private PredictionServiceInterface $predictionService,
        private LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function mapToTaxonomy(string $description, string $tenantId): array
    {
        $this->logger->debug('Mapping description to taxonomy', [
            'description' => $description,
            'tenant_id' => $tenantId,
        ]);

        // 1. Prepare input for the ML model
        $input = [
            'text' => $description,
            'tenant_id' => $tenantId,
        ];

        // 2. Call the prediction service (async)
        $jobId = $this->predictionService->predictAsync(self::MODEL_TAG, $input);

        // 3. Polling for result (In a real L2 orchestrator, this should be event-driven)
        $prediction = $this->predictionService->getPrediction($jobId);

        if (!$prediction) {
            throw new SemanticMappingException('Prediction result is not available yet.');
        }

        $taxonomyCode = (string)($prediction->getMetadata()['taxonomy_code'] ?? '');
        $modelVersion = $prediction->getModelVersion();

        if (!$this->validateCode($taxonomyCode, $modelVersion)) {
            throw new SemanticMappingException(
                sprintf('Invalid taxonomy mapping output. code="%s", version="%s"', $taxonomyCode, $modelVersion)
            );
        }

        $result = [
            'code' => $taxonomyCode,
            'confidence' => $prediction->getConfidenceScore(),
            'version' => $modelVersion,
        ];

        $this->logger->info('Taxonomy mapping complete', [
            'description' => $description,
            'code' => $result['code'],
            'confidence' => $result['confidence'],
        ]);

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function validateCode(string $code, string $version): bool
    {
        return (bool)preg_match('/^\d{8}$/', $code);
    }
}
