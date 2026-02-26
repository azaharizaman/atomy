<?php

declare(strict_types=1);

namespace Nexus\DataExchangeOperations\Services;

use Nexus\DataExchangeOperations\Contracts\DataOffboardingCoordinatorInterface;
use Nexus\DataExchangeOperations\Contracts\DataOnboardingCoordinatorInterface;
use Nexus\Export\Contracts\ExportGeneratorInterface;
use Nexus\Import\Contracts\ImportProcessorInterface;
use Nexus\Notifier\Contracts\NotificationManagerInterface;
use Nexus\Storage\Contracts\StorageDriverInterface;
use Psr\Log\LoggerInterface;

/**
 * Class DataExchangeCoordinator
 *
 * Orchestrates data movement in and out of the Nexus system.
 */
final readonly class DataExchangeCoordinator implements DataOnboardingCoordinatorInterface, DataOffboardingCoordinatorInterface
{
    public function __construct(
        private ImportProcessorInterface $importProcessor,
        private ExportGeneratorInterface $exportGenerator,
        private StorageDriverInterface $storageDriver,
        private NotificationManagerInterface $notificationManager,
        private LoggerInterface $logger
    ) {}

    /**
     * @inheritDoc
     */
    public function onboard(string $sourcePath, string $tenantId, array $options = []): string
    {
        $this->logger->info("Initiating data onboarding for tenant: {$tenantId}", [
            'source' => $sourcePath,
            'options' => $options,
        ]);

        try {
            // Logic to process import via Import package
            $result = $this->importProcessor->process($sourcePath, $tenantId, $options);
            
            // Post-import cleanup if configured
            if ($options['cleanup'] ?? true) {
                $this->storageDriver->delete($sourcePath);
            }

            return $result->getTaskId();
        } catch (\Throwable $e) {
            $this->logger->error("Data onboarding failed for tenant: {$tenantId}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * @inheritDoc
     */
    public function getTaskStatus(string $taskId): array
    {
        // Integration with Import repository or task tracker
        return [
            'id' => $taskId,
            'status' => 'completed', // Placeholder
        ];
    }

    /**
     * @inheritDoc
     */
    public function offboard(array $query, string $format, string $destination, array $recipients = []): string
    {
        $this->logger->info("Initiating data offboarding", [
            'format' => $format,
            'destination' => $destination,
        ]);

        try {
            // Generate export
            $exportResult = $this->exportGenerator->generate($query, $format);
            $filePath = $exportResult->getFilePathOrFail();

            // Move to final storage (e.g., S3)
            $finalPath = "exports/" . basename($filePath);
            $content = file_get_contents($filePath);
            
            if ($content === false) {
                throw new \RuntimeException("Failed to read generated export file at: {$filePath}");
            }

            $this->storageDriver->put($finalPath, $content);

            // Notify recipients
            foreach ($recipients as $recipient) {
                // Simplified notification logic
                // $this->notificationManager->send($recipient, new DataExportReadyNotification($finalPath));
            }

            return $finalPath;
        } catch (\Throwable $e) {
            $this->logger->error("Data offboarding failed", [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
