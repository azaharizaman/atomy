<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Services\Handlers;

use Nexus\DataPrivacy\Contracts\RequestHandlerInterface;
use Nexus\DataPrivacy\Contracts\External\PartyProviderInterface;
use Nexus\DataPrivacy\Contracts\External\StorageInterface;
use Nexus\DataPrivacy\Contracts\External\AuditLoggerInterface;
use Nexus\DataPrivacy\ValueObjects\DataSubjectRequest;
use Nexus\DataPrivacy\Enums\RequestType;
use Nexus\DataPrivacy\Enums\RequestStatus;
use Nexus\DataPrivacy\Exceptions\InvalidRequestException;

/**
 * Handles data portability requests.
 */
final readonly class PortabilityRequestHandler implements RequestHandlerInterface
{
    private const SUPPORTED_FORMATS = ['json', 'csv', 'xml'];

    public function __construct(
        private PartyProviderInterface $partyProvider,
        private StorageInterface $storage,
        private ?AuditLoggerInterface $auditLogger = null
    ) {
    }

    public function supports(RequestType $type): bool
    {
        return $type === RequestType::PORTABILITY;
    }

    public function execute(DataSubjectRequest $request): array
    {
        if (!$this->supports($request->type)) {
            throw new InvalidRequestException(
                "Handler does not support request type: {$request->type->value}"
            );
        }

        if ($request->status === RequestStatus::COMPLETED) {
            throw new InvalidRequestException('Request is already completed');
        }

        $dataSubjectId = $request->dataSubjectId->value;
        $metadata = $request->metadata;
        $format = $metadata['format'] ?? 'json';

        // Check if the data subject exists
        if (!$this->partyProvider->partyExists($dataSubjectId)) {
            return [
                'success' => false,
                'error' => 'Data subject not found',
                'download_url' => null,
            ];
        }

        // Validate format
        if (!in_array($format, self::SUPPORTED_FORMATS, true)) {
            return [
                'success' => false,
                'error' => "Unsupported format: {$format}. Supported: " . implode(', ', self::SUPPORTED_FORMATS),
                'download_url' => null,
            ];
        }

        // Export data in the requested format
        $exportData = $this->partyProvider->exportPersonalData($dataSubjectId);
        
        // Convert to requested format
        $exportContent = match ($format) {
            'json' => json_encode($exportData, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT),
            'csv' => $this->convertToCsv($exportData),
            'xml' => $this->convertToXml($exportData),
            default => json_encode($exportData, JSON_THROW_ON_ERROR),
        };

        // Store the export
        $filename = "personal-data-export.{$format}";
        $exportReference = $this->storage->storeExport($dataSubjectId, $exportContent, $filename);

        // Generate download URL (valid for 7 days)
        $downloadUrl = $this->storage->getExportUrl($exportReference, 7 * 24 * 60);

        $this->auditLogger?->logDataExported(
            $dataSubjectId,
            $format,
            strlen($exportContent)
        );

        return [
            'success' => true,
            'format' => $format,
            'export_reference' => $exportReference,
            'download_url' => $downloadUrl,
            'expires_in_hours' => 168,
            'file_size_bytes' => strlen($exportContent),
        ];
    }

    public function validate(DataSubjectRequest $request): array
    {
        $errors = [];

        if (!$this->supports($request->type)) {
            $errors[] = "Unsupported request type: {$request->type->value}";
        }

        $dataSubjectId = $request->dataSubjectId->value;

        if (!$this->partyProvider->partyExists($dataSubjectId)) {
            $errors[] = 'Data subject not found in the system';
        }

        $metadata = $request->metadata;
        $format = $metadata['format'] ?? 'json';

        if (!in_array($format, self::SUPPORTED_FORMATS, true)) {
            $errors[] = "Unsupported format: {$format}. Supported: " . implode(', ', self::SUPPORTED_FORMATS);
        }

        return $errors;
    }

    public function getEstimatedProcessingDays(): int
    {
        return 5;
    }

    public static function getSupportedFormats(): array
    {
        return self::SUPPORTED_FORMATS;
    }

    /**
     * Convert export data to CSV format.
     *
     * @param array<string, mixed> $data
     */
    private function convertToCsv(array $data): string
    {
        if (empty($data)) {
            return '';
        }

        $output = fopen('php://temp', 'r+');
        if ($output === false) {
            return json_encode($data, JSON_THROW_ON_ERROR);
        }

        // Write header
        fputcsv($output, array_keys($data));

        // Write values
        fputcsv($output, array_values($data));

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv !== false ? $csv : '';
    }

    /**
     * Convert export data to XML format.
     *
     * @param array<string, mixed> $data
     */
    private function convertToXml(array $data): string
    {
        $xml = new \SimpleXMLElement('<personalData/>');

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $child = $xml->addChild((string) $key);
                foreach ($value as $subKey => $subValue) {
                    $child->addChild((string) $subKey, htmlspecialchars((string) $subValue));
                }
            } else {
                $xml->addChild((string) $key, htmlspecialchars((string) $value));
            }
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $xmlContent = $xml->asXML();
        
        if ($xmlContent === false) {
            return '';
        }
        
        $dom->loadXML($xmlContent);
        $result = $dom->saveXML();
        
        return $result !== false ? $result : '';
    }
}
