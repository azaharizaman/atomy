<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\Contracts;

/**
 * Interface for privacy service adapter.
 *
 * This interface defines the contract for the orchestrator's privacy
 * needs. Adapters implement this interface using the DataPrivacy package.
 *
 * Following Interface Segregation from ARCHITECTURE.md:
 * Orchestrators define their own interfaces, not depending on atomic package interfaces.
 */
interface PrivacyServiceAdapterInterface
{
    /**
     * Check if data subject has valid consent for purpose.
     *
     * @param string $dataSubjectId Data subject identifier
     * @param string $purpose Consent purpose
     */
    public function hasValidConsent(string $dataSubjectId, string $purpose): bool;

    /**
     * Grant consent for a data subject.
     *
     * @param string $dataSubjectId Data subject identifier
     * @param string $purpose Consent purpose
     * @param array<string, mixed> $options Consent options
     * @return array<string, mixed> Consent record
     */
    public function grantConsent(string $dataSubjectId, string $purpose, array $options = []): array;

    /**
     * Withdraw consent for a data subject.
     *
     * @param string $dataSubjectId Data subject identifier
     * @param string $purpose Consent purpose
     * @return array<string, mixed> Consent record
     */
    public function withdrawConsent(string $dataSubjectId, string $purpose): array;

    /**
     * Get all consents for a data subject.
     *
     * @param string $dataSubjectId Data subject identifier
     * @return array<int, array<string, mixed>> Consent records
     */
    public function getConsents(string $dataSubjectId): array;

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
    ): array;

    /**
     * Get data subject requests.
     *
     * @param string $dataSubjectId Data subject identifier
     * @return array<int, array<string, mixed>> Request records
     */
    public function getRequests(string $dataSubjectId): array;

    /**
     * Check if data subject has pending request.
     *
     * @param string $dataSubjectId Data subject identifier
     * @param string $type Request type
     */
    public function hasPendingRequest(string $dataSubjectId, string $type): bool;

    /**
     * Complete a data subject request.
     *
     * @param string $requestId Request identifier
     * @return array<string, mixed> Request record
     */
    public function completeRequest(string $requestId): array;

    /**
     * Reject a data subject request.
     *
     * @param string $requestId Request identifier
     * @param string $reason Rejection reason
     * @return array<string, mixed> Request record
     */
    public function rejectRequest(string $requestId, string $reason): array;

    /**
     * Get active breach records.
     *
     * @return array<int, array<string, mixed>> Breach records
     */
    public function getActiveBreaches(): array;
}
