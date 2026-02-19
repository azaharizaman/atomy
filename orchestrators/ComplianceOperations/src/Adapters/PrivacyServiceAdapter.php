<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\Adapters;

use Nexus\DataPrivacy\Contracts\ConsentManagerInterface;
use Nexus\DataPrivacy\Contracts\DataSubjectRequestManagerInterface;
use Nexus\DataPrivacy\Contracts\BreachRecordManagerInterface;
use Nexus\DataPrivacy\ValueObjects\DataSubjectId;
use Nexus\DataPrivacy\Enums\ConsentPurpose;
use Nexus\DataPrivacy\Enums\RequestType;
use Nexus\DataPrivacy\ValueObjects\Consent;
use Nexus\DataPrivacy\ValueObjects\DataSubjectRequest;
use Nexus\DataPrivacy\ValueObjects\BreachRecord;
use Nexus\ComplianceOperations\Contracts\PrivacyServiceAdapterInterface;

/**
 * Adapter for DataPrivacy package interface.
 *
 * Adapts the DataPrivacy package to the ComplianceOperations orchestrator's
 * interface requirements. This adapter implements the orchestrator's own contract
 * and delegates to the atomic package's interfaces.
 *
 * Following the Interface Segregation principle from ARCHITECTURE.md:
 * Orchestrators define their own interfaces and adapters implement them using
 * atomic package interfaces.
 */
final readonly class PrivacyServiceAdapter implements PrivacyServiceAdapterInterface
{
    public function __construct(
        private ConsentManagerInterface $consentManager,
        private DataSubjectRequestManagerInterface $requestManager,
        private BreachRecordManagerInterface $breachManager,
    ) {}

    /**
     * Check if data subject has valid consent for purpose.
     *
     * @param string $dataSubjectId Data subject identifier
     * @param string $purpose Consent purpose
     */
    public function hasValidConsent(string $dataSubjectId, string $purpose): bool
    {
        $subjectId = $this->createDataSubjectId($dataSubjectId);
        
        try {
            $purposeEnum = ConsentPurpose::from($purpose);
            return $this->consentManager->hasValidConsent($subjectId, $purposeEnum);
        } catch (\ValueError $e) {
            return false;
        }
    }

    /**
     * Grant consent for a data subject.
     *
     * @param string $dataSubjectId Data subject identifier
     * @param string $purpose Consent purpose
     * @param array<string, mixed> $options Consent options
     * @return array<string, mixed> Consent record
     */
    public function grantConsent(string $dataSubjectId, string $purpose, array $options = []): array
    {
        $subjectId = $this->createDataSubjectId($dataSubjectId);
        $purposeEnum = ConsentPurpose::from($purpose);

        $consent = $this->consentManager->grantConsent($subjectId, $purposeEnum, $options);

        return $this->buildConsentData($consent);
    }

    /**
     * Withdraw consent for a data subject.
     *
     * @param string $dataSubjectId Data subject identifier
     * @param string $purpose Consent purpose
     * @return array<string, mixed> Consent record
     */
    public function withdrawConsent(string $dataSubjectId, string $purpose): array
    {
        $subjectId = $this->createDataSubjectId($dataSubjectId);
        $purposeEnum = ConsentPurpose::from($purpose);

        $consent = $this->consentManager->withdrawConsent($subjectId, $purposeEnum);

        return $this->buildConsentData($consent);
    }

    /**
     * Get all consents for a data subject.
     *
     * @param string $dataSubjectId Data subject identifier
     * @return array<int, array<string, mixed>> Consent records
     */
    public function getConsents(string $dataSubjectId): array
    {
        $subjectId = $this->createDataSubjectId($dataSubjectId);
        $consents = $this->consentManager->getConsents($subjectId);

        return array_map(fn($consent) => $this->buildConsentData($consent), $consents);
    }

    /**
     * Submit a data subject request.
     *
     * @param string $dataSubjectId Data subject identifier
     * @param string $type Request type (access, erasure, portability, rectification)
     * @param int $deadlineDays Days until deadline
     * @return array<string, mixed> Request record
     */
    public function submitRequest(
        string $dataSubjectId,
        string $type,
        int $deadlineDays = 30
    ): array {
        $subjectId = $this->createDataSubjectId($dataSubjectId);
        $typeEnum = RequestType::from($type);

        $request = $this->requestManager->submitRequest($subjectId, $typeEnum, $deadlineDays);

        return $this->buildRequestData($request);
    }

    /**
     * Get data subject requests.
     *
     * @param string $dataSubjectId Data subject identifier
     * @return array<int, array<string, mixed>> Request records
     */
    public function getRequests(string $dataSubjectId): array
    {
        $subjectId = $this->createDataSubjectId($dataSubjectId);
        $requests = $this->requestManager->getRequestsByDataSubject($subjectId);

        return array_map(fn($request) => $this->buildRequestData($request), $requests);
    }

    /**
     * Check if data subject has pending request.
     *
     * @param string $dataSubjectId Data subject identifier
     * @param string $type Request type
     */
    public function hasPendingRequest(string $dataSubjectId, string $type): bool
    {
        $subjectId = $this->createDataSubjectId($dataSubjectId);
        
        try {
            $typeEnum = RequestType::from($type);
            return $this->requestManager->hasPendingRequest($subjectId, $typeEnum);
        } catch (\ValueError $e) {
            return false;
        }
    }

    /**
     * Complete a data subject request.
     *
     * @param string $requestId Request identifier
     * @return array<string, mixed> Request record
     */
    public function completeRequest(string $requestId): array
    {
        $request = $this->requestManager->completeRequest($requestId);
        return $this->buildRequestData($request);
    }

    /**
     * Reject a data subject request.
     *
     * @param string $requestId Request identifier
     * @param string $reason Rejection reason
     * @return array<string, mixed> Request record
     */
    public function rejectRequest(string $requestId, string $reason): array
    {
        $request = $this->requestManager->rejectRequest($requestId, $reason);
        return $this->buildRequestData($request);
    }

    /**
     * Get active breach records.
     *
     * @return array<int, array<string, mixed>> Breach records
     */
    public function getActiveBreaches(): array
    {
        $breaches = $this->breachManager->getUnresolvedBreaches();

        return array_map(fn($breach) => $this->buildBreachData($breach), $breaches);
    }

    /**
     * Create DataSubjectId value object.
     */
    private function createDataSubjectId(string $id): DataSubjectId
    {
        return new DataSubjectId($id);
    }

    /**
     * Build consent data array.
     *
     * @return array<string, mixed>
     */
    private function buildConsentData(Consent $consent): array
    {
        return [
            'consentId' => $consent->id,
            'dataSubjectId' => $consent->dataSubjectId->value,
            'purpose' => $consent->purpose->value,
            'status' => $consent->status->value,
            'grantedAt' => $consent->grantedAt->format('Y-m-d H:i:s'),
            'expiresAt' => $consent->expiresAt?->format('Y-m-d'),
            'version' => $consent->version,
            'ipAddress' => $consent->ipAddress,
            'userAgent' => $consent->userAgent,
        ];
    }

    /**
     * Build request data array.
     *
     * @return array<string, mixed>
     */
    private function buildRequestData(DataSubjectRequest $request): array
    {
        return [
            'requestId' => $request->id,
            'dataSubjectId' => $request->dataSubjectId->value,
            'type' => $request->type->value,
            'status' => $request->status->value,
            'submittedAt' => $request->submittedAt->format('Y-m-d H:i:s'),
            'deadline' => $request->deadline->format('Y-m-d'),
            'isOverdue' => $request->isOverdue(),
            'assignedTo' => $request->assignedTo,
            'completedAt' => $request->completedAt?->format('Y-m-d H:i:s'),
            'rejectionReason' => $request->rejectionReason,
        ];
    }

    /**
     * Build breach data array.
     *
     * @return array<string, mixed>
     */
    private function buildBreachData(BreachRecord $breach): array
    {
        return [
            'breachId' => $breach->id,
            'title' => $breach->title,
            'severity' => $breach->severity->value,
            'discoveredAt' => $breach->discoveredAt->format('Y-m-d H:i:s'),
            'occurredAt' => $breach->occurredAt->format('Y-m-d H:i:s'),
            'recordsAffected' => $breach->recordsAffected,
            'regulatoryNotified' => $breach->regulatoryNotified,
            'individualsNotified' => $breach->individualsNotified,
            'isResolved' => $breach->isResolved(),
            'reportedBy' => $breach->reportedBy,
        ];
    }
}
