<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Services\Handlers;

use Nexus\DataPrivacy\Contracts\RequestHandlerInterface;
use Nexus\DataPrivacy\Contracts\External\PartyProviderInterface;
use Nexus\DataPrivacy\Contracts\External\AuditLoggerInterface;
use Nexus\DataPrivacy\Contracts\External\StorageInterface;
use Nexus\DataPrivacy\ValueObjects\DataSubjectRequest;
use Nexus\DataPrivacy\Enums\RequestType;
use Nexus\DataPrivacy\Enums\RequestStatus;
use Nexus\DataPrivacy\Exceptions\InvalidRequestException;

/**
 * Handles data access (Subject Access Request) operations.
 */
final readonly class AccessRequestHandler implements RequestHandlerInterface
{
    public function __construct(
        private PartyProviderInterface $partyProvider,
        private StorageInterface $storage,
        private ?AuditLoggerInterface $auditLogger = null
    ) {
    }

    public function supports(RequestType $type): bool
    {
        return $type === RequestType::ACCESS;
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

        // Check if the data subject exists
        if (!$this->partyProvider->partyExists($dataSubjectId)) {
            return [
                'success' => false,
                'error' => 'Data subject not found',
                'data' => null,
            ];
        }

        // Collect all personal data
        $personalData = $this->partyProvider->getPersonalData($dataSubjectId);

        // Export to a portable format
        $exportData = $this->partyProvider->exportPersonalData($dataSubjectId);
        $export = json_encode($exportData, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);

        // Store the export for download
        $exportReference = $this->storage->storeExport(
            $dataSubjectId,
            $export,
            'personal-data-export.json'
        );

        // Generate download URL (valid for 7 days)
        $downloadUrl = $this->storage->getExportUrl($exportReference, 7 * 24 * 60);

        $this->auditLogger?->logDataExported(
            $dataSubjectId,
            'json',
            count($personalData)
        );

        return [
            'success' => true,
            'data' => $personalData,
            'export_reference' => $exportReference,
            'download_url' => $downloadUrl,
            'expires_in_hours' => 168,
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

        return $errors;
    }

    public function getEstimatedProcessingDays(): int
    {
        return 3; // Typically fast to execute
    }
}
