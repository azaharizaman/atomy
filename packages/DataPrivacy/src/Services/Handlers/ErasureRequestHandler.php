<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Services\Handlers;

use Nexus\DataPrivacy\Contracts\RequestHandlerInterface;
use Nexus\DataPrivacy\Contracts\ConsentManagerInterface;
use Nexus\DataPrivacy\Contracts\External\PartyProviderInterface;
use Nexus\DataPrivacy\Contracts\External\AuditLoggerInterface;
use Nexus\DataPrivacy\ValueObjects\DataSubjectRequest;
use Nexus\DataPrivacy\Enums\RequestType;
use Nexus\DataPrivacy\Enums\RequestStatus;
use Nexus\DataPrivacy\Exceptions\InvalidRequestException;

/**
 * Handles erasure (Right to be Forgotten) requests.
 */
final readonly class ErasureRequestHandler implements RequestHandlerInterface
{
    public function __construct(
        private PartyProviderInterface $partyProvider,
        private ConsentManagerInterface $consentManager,
        private ?AuditLoggerInterface $auditLogger = null
    ) {
    }

    public function supports(RequestType $type): bool
    {
        return $type === RequestType::ERASURE;
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
                'deleted_records' => 0,
            ];
        }

        // Validate we can proceed with erasure
        $validationErrors = $this->validate($request);
        if (!empty($validationErrors)) {
            return [
                'success' => false,
                'errors' => $validationErrors,
                'deleted_records' => 0,
            ];
        }

        // Withdraw all consents
        $withdrawnConsents = $this->consentManager->withdrawAllConsents(
            $request->dataSubjectId
        );

        // Delete personal data from all sources
        $this->partyProvider->deletePersonalData($dataSubjectId);

        $this->auditLogger?->logDataDeleted(
            $dataSubjectId,
            'erasure_request'
        );

        return [
            'success' => true,
            'withdrawn_consents' => $withdrawnConsents,
            'data_categories_deleted' => $this->getDeletedCategories($dataSubjectId),
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

        // Check for legal retention requirements
        $retentionBlocks = $this->checkRetentionRequirements($dataSubjectId);
        if (!empty($retentionBlocks)) {
            $errors[] = 'Some data cannot be deleted due to legal retention requirements: '
                . implode(', ', $retentionBlocks);
        }

        return $errors;
    }

    public function getEstimatedProcessingDays(): int
    {
        return 14; // More complex, may involve multiple systems
    }

    /**
     * Check if any data is under legal retention hold.
     */
    private function checkRetentionRequirements(string $dataSubjectId): array
    {
        // This would check against retention policies
        // For now, return empty (no blocks)
        return [];
    }

    /**
     * Get the categories of data that were deleted.
     */
    private function getDeletedCategories(string $dataSubjectId): array
    {
        // This would return the specific data categories that were deleted
        return ['contact_info', 'profile', 'preferences'];
    }
}
