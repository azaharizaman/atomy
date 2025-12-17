<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Services\Handlers;

use Nexus\DataPrivacy\Contracts\RequestHandlerInterface;
use Nexus\DataPrivacy\Contracts\External\PartyProviderInterface;
use Nexus\DataPrivacy\Contracts\External\AuditLoggerInterface;
use Nexus\DataPrivacy\ValueObjects\DataSubjectRequest;
use Nexus\DataPrivacy\Enums\RequestType;
use Nexus\DataPrivacy\Enums\RequestStatus;
use Nexus\DataPrivacy\Exceptions\InvalidRequestException;

/**
 * Handles rectification (correction of inaccurate data) requests.
 */
final readonly class RectificationRequestHandler implements RequestHandlerInterface
{
    public function __construct(
        private PartyProviderInterface $partyProvider,
        private ?AuditLoggerInterface $auditLogger = null
    ) {
    }

    public function supports(RequestType $type): bool
    {
        return $type === RequestType::RECTIFICATION;
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

        // Check if the data subject exists
        if (!$this->partyProvider->partyExists($dataSubjectId)) {
            return [
                'success' => false,
                'error' => 'Data subject not found',
                'fields_updated' => 0,
            ];
        }

        // Get the corrections from metadata
        $corrections = $metadata['corrections'] ?? [];
        if (empty($corrections)) {
            return [
                'success' => false,
                'error' => 'No corrections provided in request',
                'fields_updated' => 0,
            ];
        }

        // Get current data for audit trail
        $currentData = $this->partyProvider->getPersonalData($dataSubjectId);

        // Apply rectifications
        $this->partyProvider->rectifyPersonalData(
            $dataSubjectId,
            $corrections
        );

        $updatedFields = count($corrections);

        $this->auditLogger?->log(
            'data_subject',
            $dataSubjectId,
            'rectified',
            "Personal data rectified: {$updatedFields} fields updated",
            [
                'fields_updated' => $updatedFields,
                'request_id' => $request->id,
            ]
        );

        return [
            'success' => true,
            'fields_updated' => $updatedFields,
            'corrections_applied' => array_keys($corrections),
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
        if (empty($metadata['corrections'])) {
            $errors[] = 'Request must include corrections to apply';
        }

        return $errors;
    }

    public function getEstimatedProcessingDays(): int
    {
        return 5;
    }
}
