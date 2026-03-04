<?php

declare(strict_types=1);

namespace Nexus\Laravel\InsightOperations\Adapters;

use Nexus\InsightOperations\Contracts\ForecastPortInterface;
use Nexus\MachineLearning\Contracts\PredictionServiceInterface;
use Nexus\MachineLearning\Exceptions\ModelNotFoundException;
use Nexus\MachineLearning\Exceptions\QuotaExceededException;
use Psr\Log\LoggerInterface;

final readonly class ForecastPortAdapter implements ForecastPortInterface
{
    public function __construct(
        private PredictionServiceInterface $predictionService,
        private LoggerInterface $logger,
    ) {}

    public function forecast(string $modelId, array $context, int $maxAttempts, int $pollIntervalMs): array
    {
        try {
            $jobId = $this->predictionService->predictAsync($modelId, $context);

            $status = 'pending';
            for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
                $status = $this->predictionService->getStatus($jobId);
                if ($status === 'completed' || $status === 'failed') {
                    break;
                }

                usleep(max($pollIntervalMs, 1) * 1000);
            }

            if ($status !== 'completed') {
                return [
                    'status' => $status === 'failed' ? 'failed' : 'timeout',
                    'data' => null,
                    'confidence' => null,
                    'model_version' => null,
                    'error' => $status === 'failed' ? 'prediction_failed' : 'prediction_timeout',
                ];
            }

            $prediction = $this->predictionService->getPrediction($jobId);
            if ($prediction === null) {
                return [
                    'status' => 'failed',
                    'data' => null,
                    'confidence' => null,
                    'model_version' => null,
                    'error' => 'prediction_missing',
                ];
            }

            return [
                'status' => 'success',
                'data' => [
                    'value' => $prediction->getValue(),
                    'feature_importance' => $prediction->getFeatureImportance(),
                    'metadata' => $prediction->getMetadata(),
                ],
                'confidence' => $prediction->getCalibratedConfidence(),
                'model_version' => $prediction->getModelVersion(),
                'error' => null,
            ];
        } catch (ModelNotFoundException) {
            return [
                'status' => 'failed',
                'data' => null,
                'confidence' => null,
                'model_version' => null,
                'error' => 'model_not_found',
            ];
        } catch (QuotaExceededException) {
            return [
                'status' => 'failed',
                'data' => null,
                'confidence' => null,
                'model_version' => null,
                'error' => 'quota_exceeded',
            ];
        } catch (\Throwable $e) {
            $this->logger->error('Forecast prediction failed.', [
                'model_id' => $modelId,
                'exception' => $e::class,
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ]);

            return [
                'status' => 'failed',
                'data' => null,
                'confidence' => null,
                'model_version' => null,
                'error' => 'prediction_unavailable',
            ];
        }
    }
}
