<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\DataProviders;

use Nexus\DataPrivacy\Contracts\ConsentManagerInterface;
use Nexus\DataPrivacy\Contracts\DataSubjectRequestManagerInterface;
use Nexus\DataPrivacy\Contracts\ProcessingActivityManagerInterface;
use Nexus\DataPrivacy\Contracts\RetentionPolicyManagerInterface;
use Nexus\DataPrivacy\Contracts\BreachRecordManagerInterface;
use Nexus\DataPrivacy\ValueObjects\DataSubjectId;
use Nexus\DataPrivacy\Enums\RequestType;
use Nexus\DataPrivacy\Enums\RequestStatus;
use Nexus\DataPrivacy\Enums\ConsentPurpose;
use Nexus\ComplianceOperations\DTOs\Privacy\PrivacyContext;
use Nexus\ComplianceOperations\DTOs\Privacy\PrivacySummaryData;
use Nexus\ComplianceOperations\Exceptions\PrivacyDataException;
use Psr\Log\LoggerInterface;

/**
 * DataProvider for data privacy/GDPR data aggregation.
 *
 * Aggregates privacy data from the DataPrivacy package to provide
 * comprehensive context for compliance workflows including:
 * - Consent management
 * - Data subject requests (DSR)
 * - Processing activities
 * - Retention policies
 * - Breach records
 *
 * Following Advanced Orchestrator Pattern v1.1:
 * DataProviders abstract data fetching from Coordinators.
 */
final readonly class PrivacyDataProvider
{
    public function __construct(
        private ConsentManagerInterface $consentManager,
        private DataSubjectRequestManagerInterface $requestManager,
        private ProcessingActivityManagerInterface $activityManager,
        private RetentionPolicyManagerInterface $retentionManager,
        private BreachRecordManagerInterface $breachManager,
        private LoggerInterface $logger,
    ) {}

    /**
     * Get comprehensive privacy context for a data subject.
     *
     * @param string $tenantId Tenant context
     * @param string $dataSubjectId Data subject identifier
     * @throws PrivacyDataException If data cannot be retrieved
     */
    public function getPrivacyContext(string $tenantId, string $dataSubjectId): PrivacyContext
    {
        $this->logger->info('Fetching privacy context', [
            'tenant_id' => $tenantId,
            'data_subject_id' => $dataSubjectId,
        ]);

        // Use DataSubjectId::fromString() to create value object
        $subjectId = DataSubjectId::fromString($dataSubjectId);

        // Get consents
        $consents = $this->consentManager->getValidConsents($subjectId);

        // Get data subject requests
        $requests = $this->requestManager->getRequestsByDataSubject($subjectId);

        // Check for pending requests
        $hasPendingRequests = $this->requestManager->hasPendingRequest($subjectId, RequestType::ACCESS)
            || $this->requestManager->hasPendingRequest($subjectId, RequestType::ERASURE);

        // Use ProcessingActivityManager::getActivitiesByDataSubject() to get activities
        try {
            $processingActivities = $this->activityManager->getActivitiesByDataSubject($subjectId);
        } catch (\Throwable $e) {
            $this->logger->warning('Failed to fetch processing activities', [
                'data_subject_id' => $dataSubjectId,
                'error' => $e->getMessage(),
            ]);
            $processingActivities = [];
        }

        return new PrivacyContext(
            tenantId: $tenantId,
            dataSubjectId: $dataSubjectId,
            consents: $this->buildConsentsData($consents),
            requests: $this->buildRequestsData($requests),
            hasPendingRequests: $hasPendingRequests,
            processingActivities: $this->buildActivitiesData($processingActivities),
            consentStatus: $this->buildConsentStatus($consents),
            fetchedAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Get privacy summary for dashboard display.
     *
     * @param string $tenantId Tenant context
     */
    public function getPrivacySummary(string $tenantId): PrivacySummaryData
    {
        $this->logger->info('Fetching privacy summary', [
            'tenant_id' => $tenantId,
        ]);

        $requestMetrics = $this->requestManager->getRequestMetrics();
        
        // Note: BreachRecordManagerInterface doesn't have getActiveBreaches method
        // Use getUnresolvedBreaches instead
        $unresolvedBreaches = $this->breachManager->getUnresolvedBreaches();
        
        // Note: processExpiredConsents returns int (count), not array
        $expiredConsentsCount = $this->consentManager->processExpiredConsents();

        return new PrivacySummaryData(
            tenantId: $tenantId,
            totalRequests: $requestMetrics['total'] ?? 0,
            pendingRequests: $requestMetrics['by_status']['pending'] ?? 0,
            overdueRequests: $requestMetrics['overdue'] ?? 0,
            activeBreaches: count($unresolvedBreaches),
            expiringConsents: $expiredConsentsCount,
            averageCompletionDays: $requestMetrics['average_completion_days'] ?? 0.0,
            generatedAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Get overdue data subject requests.
     *
     * @param string $tenantId Tenant context
     * @return array<int, array<string, mixed>>
     */
    public function getOverdueRequests(string $tenantId): array
    {
        $this->logger->info('Fetching overdue DSR requests', [
            'tenant_id' => $tenantId,
        ]);

        $requests = $this->requestManager->getOverdueRequests();
        return $this->buildRequestsData($requests);
    }

    /**
     * Get requests approaching deadline.
     *
     * @param string $tenantId Tenant context
     * @param int $withinDays Days threshold
     * @return array<int, array<string, mixed>>
     */
    public function getRequestsApproachingDeadline(string $tenantId, int $withinDays = 5): array
    {
        $this->logger->info('Fetching DSR requests approaching deadline', [
            'tenant_id' => $tenantId,
            'within_days' => $withinDays,
        ]);

        $requests = $this->requestManager->getRequestsApproachingDeadline($withinDays);
        return $this->buildRequestsData($requests);
    }

    /**
     * Check if data subject has valid consent for purpose.
     *
     * @param string $dataSubjectId Data subject identifier
     * @param string $purpose Consent purpose
     */
    public function hasValidConsent(string $dataSubjectId, string $purpose): bool
    {
        // Use DataSubjectId::fromString() to create value object
        $subjectId = DataSubjectId::fromString($dataSubjectId);
        
        try {
            $purposeEnum = ConsentPurpose::from($purpose);
            return $this->consentManager->hasValidConsent($subjectId, $purposeEnum);
        } catch (\ValueError $e) {
            return false;
        }
    }

    /**
     * Get active breach records.
     *
     * @param string $tenantId Tenant context
     * @return array<int, array<string, mixed>>
     */
    public function getActiveBreaches(string $tenantId): array
    {
        $this->logger->info('Fetching active breach records', [
            'tenant_id' => $tenantId,
        ]);

        // Use getUnresolvedBreaches instead of getActiveBreaches
        $breaches = $this->breachManager->getUnresolvedBreaches();
        return array_map(fn($breach) => [
            'breachId' => $breach->id, // Note: property is id, not breachId
            'name' => $breach->title, // Note: property is title, not name/description
            'severity' => $breach->severity->value,
            'status' => $breach->resolvedAt === null ? 'unresolved' : 'resolved',
            'discoveredAt' => $breach->discoveredAt->format('Y-m-d H:i:s'),
            'affectedSubjects' => $breach->recordsAffected,
            'authorityNotified' => $breach->regulatoryNotified,
            'subjectsNotified' => $breach->individualsNotified,
        ], $breaches);
    }

    /**
     * Get retention policy for data category.
     *
     * @param string $tenantId Tenant context
     * @param string $category Data category
     * @return array<string, mixed>|null
     */
    public function getRetentionPolicy(string $tenantId, string $category): ?array
    {
        $this->logger->info('Fetching retention policy', [
            'tenant_id' => $tenantId,
            'category' => $category,
        ]);

        // Use RetentionPolicyManager::getPolicyByCategory() to get policy
        try {
            $policy = $this->retentionManager->getPolicyByCategory($category);
            
            if ($policy === null) {
                return null;
            }
            
            return [
                'policyId' => $policy->id,
                'name' => $policy->name,
                'category' => $policy->category->value,
                'retentionMonths' => $policy->retentionMonths,
                'isEffective' => $policy->isEffective(),
                'legalBasis' => $policy->legalBasis,
                'requiresSecureDeletion' => $policy->requiresSecureDeletion,
            ];
        } catch (\Throwable $e) {
            $this->logger->error('Failed to fetch retention policy', [
                'tenant_id' => $tenantId,
                'category' => $category,
                'error' => $e->getMessage(),
            ]);

            throw new PrivacyDataException('Failed to fetch retention policy: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Build consents data array.
     *
     * @param array $consents
     * @return array<int, array<string, mixed>>
     */
    private function buildConsentsData(array $consents): array
    {
        return array_map(fn($consent) => [
            'consentId' => $consent->consentId,
            'purpose' => $consent->purpose->value,
            'status' => $consent->status->value,
            'grantedAt' => $consent->grantedAt->format('Y-m-d H:i:s'),
            'expiresAt' => $consent->expiresAt?->format('Y-m-d'),
            'source' => $consent->source,
            'version' => $consent->version,
        ], $consents);
    }

    /**
     * Build requests data array.
     *
     * @param array $requests
     * @return array<int, array<string, mixed>>
     */
    private function buildRequestsData(array $requests): array
    {
        return array_map(fn($request) => [
            'requestId' => $request->requestId,
            'type' => $request->type->value,
            'status' => $request->status->value,
            'submittedAt' => $request->submittedAt->format('Y-m-d H:i:s'),
            'deadline' => $request->deadline->format('Y-m-d'),
            'isOverdue' => $request->isOverdue(),
            'assignedTo' => $request->assignedTo,
        ], $requests);
    }

    /**
     * Build processing activities data array.
     *
     * @param array $activities
     * @return array<int, array<string, mixed>>
     */
    private function buildActivitiesData(array $activities): array
    {
        return array_map(fn($activity) => [
            'activityId' => $activity->activityId,
            'name' => $activity->name,
            'purpose' => $activity->purpose,
            'lawfulBasis' => $activity->lawfulBasis->value,
            'dataCategories' => $activity->dataCategories,
            'isActive' => $activity->isActive(),
        ], $activities);
    }

    /**
     * Build consent status summary.
     *
     * @param array $consents
     * @return array<string, bool>
     */
    private function buildConsentStatus(array $consents): array
    {
        $status = [];
        foreach ($consents as $consent) {
            $status[$consent->purpose->value] = $consent->status->value === 'granted';
        }
        return $status;
    }
}
