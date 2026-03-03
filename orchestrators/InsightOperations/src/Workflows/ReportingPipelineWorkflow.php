<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Workflows;

use Nexus\InsightOperations\Contracts\ForecastPortInterface;
use Nexus\InsightOperations\Contracts\InsightNotificationPortInterface;
use Nexus\InsightOperations\Contracts\InsightStoragePortInterface;
use Nexus\InsightOperations\Contracts\ReportDataQueryPortInterface;
use Nexus\InsightOperations\Contracts\ReportExportPortInterface;
use Nexus\InsightOperations\DataProviders\PipelineContextDataProvider;
use Nexus\InsightOperations\DTOs\ReportingPipelineRequest;
use Nexus\InsightOperations\DTOs\ReportingPipelineResult;
use Nexus\InsightOperations\Rules\ReportingPipelineRule;

final readonly class ReportingPipelineWorkflow
{
    public function __construct(
        private ReportingPipelineRule $rule,
        private ReportDataQueryPortInterface $queryPort,
        private ForecastPortInterface $forecastPort,
        private ReportExportPortInterface $exportPort,
        private InsightStoragePortInterface $storagePort,
        private InsightNotificationPortInterface $notificationPort,
        private PipelineContextDataProvider $contextProvider,
    ) {}

    public function run(ReportingPipelineRequest $request): ReportingPipelineResult
    {
        $this->rule->assert($request);

        $historical = $this->queryPort->query($request->reportTemplateId, $request->parameters);
        $forecastData = null;
        $metadata = ['forecast_status' => 'not_requested'];

        if (filter_var($request->parameters['include_forecast'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $modelId = (string) ($request->parameters['forecast_model_id'] ?? $request->reportTemplateId);
            $forecastResult = $this->forecastPort->forecast(
                $modelId,
                ['historical' => $historical, 'parameters' => $request->parameters],
                (int) ($request->parameters['forecast_max_attempts'] ?? 10),
                (int) ($request->parameters['forecast_poll_interval_ms'] ?? 100)
            );

            $forecastData = $forecastResult['data'];
            $metadata = [
                'forecast_status' => $forecastResult['status'],
                'forecast_error' => $forecastResult['error'],
                'forecast_confidence' => $forecastResult['confidence'],
                'forecast_model_version' => $forecastResult['model_version'],
            ];
        }

        $reportData = $this->contextProvider->build($request, $historical, $forecastData);
        $exported = $this->exportPort->export($reportData, (string) ($request->deliveryOptions['format'] ?? 'pdf'));

        $storagePath = sprintf(
            'reports/%s/%s/%s',
            gmdate('Y/m/d'),
            preg_replace('/[^A-Za-z0-9_\-]/', '_', $request->reportTemplateId) ?: 'report',
            basename($exported['file_path'])
        );

        $stream = fopen($exported['file_path'], 'rb');
        if ($stream === false) {
            throw new \RuntimeException(sprintf('Unable to open report output: %s', $exported['file_path']));
        }

        try {
            $this->storagePort->put($storagePath, $stream);
        } finally {
            fclose($stream);
            $exportedPath = $exported['file_path'] ?? null;
            if (is_string($exportedPath) && is_file($exportedPath)) {
                unlink($exportedPath);
            }
        }

        $rawRecipients = $request->deliveryOptions['recipients'] ?? [];
        $recipients = [];
        if (is_array($rawRecipients)) {
            foreach ($rawRecipients as $recipient) {
                if (!is_string($recipient)) {
                    continue;
                }

                $recipient = trim($recipient);
                if ($recipient === '') {
                    continue;
                }

                $recipients[] = $recipient;
            }
        }

        if ($recipients !== []) {
            $this->notificationPort->notify(array_values($recipients), 'report_pipeline_ready', [
                'pipeline_id' => $request->pipelineId,
                'report_template_id' => $request->reportTemplateId,
                'storage_path' => $storagePath,
            ]);
        }

        return new ReportingPipelineResult(
            pipelineId: $request->pipelineId,
            storagePath: $storagePath,
            reportData: $reportData,
            metadata: array_merge($metadata, $exported['metadata']),
        );
    }
}
