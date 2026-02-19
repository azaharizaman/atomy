<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\Coordinators;

use Nexus\ComplianceOperations\Contracts\PrivacyServiceAdapterInterface;
use Nexus\ComplianceOperations\DTOs\SagaContext;
use Nexus\ComplianceOperations\Workflows\PrivacyRights\PrivacyRightsWorkflow;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Coordinates DSAR and privacy rights fulfillment.
 *
 * This coordinator manages the complete Data Subject Access Request (DSAR)
 * fulfillment process, including request validation, data discovery,
 * subject rights processing, and response generation.
 *
 * Following the Advanced Orchestrator Pattern:
 * - Coordinators direct flow, they do not execute business logic
 * - Delegates to workflows for stateful operations
 * - Uses adapters for external service integration
 *
 * @see ARCHITECTURE.md Section 3 for coordinator patterns
 */
final readonly class PrivacyRightsCoordinator
{
    public function __construct(
        private PrivacyRightsWorkflow $workflow,
        private PrivacyServiceAdapterInterface $privacyAdapter,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Submit a data subject access request (DSAR).
     *
     * @param string $tenantId Tenant identifier
     * @param string $userId User submitting the request
     * @param string $requestType Request type (access, erasure, portability, rectification, restriction, objection)
     * @param string $subjectId Data subject identifier
     * @param string|null $subjectEmail Data subject email
     * @param string $jurisdiction Jurisdiction (EU, US, etc.)
     * @param array<string, mixed> $additionalContext Additional context data
     * @return array<string, mixed> Request submission result
     */
    public function submitRequest(
        string $tenantId,
        string $userId,
        string $requestType,
        string $subjectId,
        ?string $subjectEmail = null,
        string $jurisdiction = 'EU',
        array $additionalContext = []
    ): array {
        $this->logger->info('Submitting privacy rights request', [
            'tenant_id' => $tenantId,
            'request_type' => $requestType,
            'subject_id' => $subjectId,
        ]);

        try {
            // Check if there's already a pending request of this type
            if ($this->privacyAdapter->hasPendingRequest($subjectId, $requestType)) {
                return [
                    'success' => false,
                    'subject_id' => $subjectId,
                    'request_type' => $requestType,
                    'message' => 'A pending request of this type already exists for this subject',
                ];
            }

            // Submit the request to get a request ID
            $requestRecord = $this->privacyAdapter->submitRequest(
                dataSubjectId: $subjectId,
                type: $requestType,
                deadlineDays: $this->getDeadlineDays($jurisdiction)
            );

            $requestId = $requestRecord['id'] ?? $requestRecord['request_id'] ?? null;

            if ($requestId === null) {
                throw new \RuntimeException('Failed to create request record');
            }

            // Build workflow context
            $contextData = array_merge([
                'request_id' => $requestId,
                'request_type' => $requestType,
                'subject_id' => $subjectId,
                'subject_email' => $subjectEmail,
                'jurisdiction' => $jurisdiction,
                'submitted_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            ], $additionalContext);

            $sagaContext = new SagaContext(
                tenantId: $tenantId,
                userId: $userId,
                data: $contextData,
            );

            // Execute the privacy rights workflow
            $sagaResult = $this->workflow->execute($sagaContext);

            return [
                'success' => $sagaResult->isSuccessful(),
                'saga_id' => $sagaResult->sagaId,
                'instance_id' => $sagaResult->instanceId,
                'request_id' => $requestId,
                'subject_id' => $subjectId,
                'request_type' => $requestType,
                'status' => $sagaResult->status->value,
                'completed_steps' => $sagaResult->completedSteps,
                'failed_step' => $sagaResult->failedStep,
                'error_message' => $sagaResult->errorMessage,
                'message' => $sagaResult->isSuccessful()
                    ? 'Privacy rights request processed successfully'
                    : 'Privacy rights request processing failed',
                'data' => $sagaResult->data,
            ];
        } catch (\Throwable $e) {
            $this->logger->error('Failed to submit privacy rights request', [
                'subject_id' => $subjectId,
                'request_type' => $requestType,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'subject_id' => $subjectId,
                'request_type' => $requestType,
                'status' => 'failed',
                'message' => 'Failed to submit privacy rights request',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get the current status of a privacy rights request.
     *
     * @param string $instanceId Saga instance identifier
     * @return array<string, mixed> Request status
     */
    public function getRequestStatus(string $instanceId): array
    {
        $this->logger->info('Getting privacy rights request status', ['instance_id' => $instanceId]);

        try {
            $state = $this->workflow->getState($instanceId);

            if ($state === null) {
                return [
                    'success' => false,
                    'message' => 'Privacy rights request not found',
                    'instance_id' => $instanceId,
                ];
            }

            return [
                'success' => true,
                'instance_id' => $instanceId,
                'saga_id' => $state->getSagaId(),
                'tenant_id' => $state->getTenantId(),
                'status' => $state->getStatus()->value,
                'completed_steps' => $state->getCompletedSteps(),
                'compensated_steps' => $state->getCompensatedSteps(),
                'context_data' => $state->getContextData(),
                'step_data' => $state->getStepData(),
                'error_message' => $state->getErrorMessage(),
                'is_terminal' => $state->isTerminal(),
                'created_at' => $state->getCreatedAt()->format(\DateTimeInterface::ATOM),
                'updated_at' => $state->getUpdatedAt()->format(\DateTimeInterface::ATOM),
            ];
        } catch (\Throwable $e) {
            $this->logger->error('Failed to get privacy rights request status', [
                'instance_id' => $instanceId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'instance_id' => $instanceId,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get all requests for a data subject.
     *
     * @param string $subjectId Data subject identifier
     * @return array<string, mixed> Subject's requests
     */
    public function getSubjectRequests(string $subjectId): array
    {
        $this->logger->info('Getting subject requests', ['subject_id' => $subjectId]);

        $requests = $this->privacyAdapter->getRequests($subjectId);

        return [
            'subject_id' => $subjectId,
            'requests' => $requests,
            'total_count' => count($requests),
            'retrieved_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * Manage consent for a data subject.
     *
     * @param string $subjectId Data subject identifier
     * @param string $purpose Consent purpose
     * @param string $action Action (grant, withdraw)
     * @param array<string, mixed> $options Consent options
     * @return array<string, mixed> Consent management result
     */
    public function manageConsent(
        string $subjectId,
        string $purpose,
        string $action,
        array $options = []
    ): array {
        $this->logger->info('Managing consent', [
            'subject_id' => $subjectId,
            'purpose' => $purpose,
            'action' => $action,
        ]);

        try {
            $result = match ($action) {
                'grant' => $this->privacyAdapter->grantConsent($subjectId, $purpose, $options),
                'withdraw' => $this->privacyAdapter->withdrawConsent($subjectId, $purpose),
                default => throw new \InvalidArgumentException("Unknown consent action: {$action}"),
            };

            return [
                'success' => true,
                'subject_id' => $subjectId,
                'purpose' => $purpose,
                'action' => $action,
                'consent_record' => $result,
                'message' => "Consent {$action} successful",
            ];
        } catch (\Throwable $e) {
            $this->logger->error('Failed to manage consent', [
                'subject_id' => $subjectId,
                'purpose' => $purpose,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'subject_id' => $subjectId,
                'purpose' => $purpose,
                'action' => $action,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get all consents for a data subject.
     *
     * @param string $subjectId Data subject identifier
     * @return array<string, mixed> Subject's consents
     */
    public function getSubjectConsents(string $subjectId): array
    {
        $this->logger->info('Getting subject consents', ['subject_id' => $subjectId]);

        $consents = $this->privacyAdapter->getConsents($subjectId);

        return [
            'subject_id' => $subjectId,
            'consents' => $consents,
            'total_count' => count($consents),
            'retrieved_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * Check if a data subject has valid consent for a purpose.
     *
     * @param string $subjectId Data subject identifier
     * @param string $purpose Consent purpose
     * @return array<string, mixed> Consent check result
     */
    public function checkConsent(string $subjectId, string $purpose): array
    {
        $hasConsent = $this->privacyAdapter->hasValidConsent($subjectId, $purpose);

        return [
            'subject_id' => $subjectId,
            'purpose' => $purpose,
            'has_valid_consent' => $hasConsent,
            'checked_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * Get active data breaches.
     *
     * @return array<string, mixed> Active breaches
     */
    public function getActiveBreaches(): array
    {
        $this->logger->info('Getting active data breaches');

        $breaches = $this->privacyAdapter->getActiveBreaches();

        return [
            'breaches' => $breaches,
            'total_count' => count($breaches),
            'retrieved_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * Get deadline days based on jurisdiction.
     *
     * @param string $jurisdiction Jurisdiction code
     * @return int Deadline in days
     */
    private function getDeadlineDays(string $jurisdiction): int
    {
        return match (strtoupper($jurisdiction)) {
            'EU', 'GDPR' => 30,
            'US', 'CCPA' => 45,
            'UK' => 30,
            default => 30,
        };
    }

    /**
     * Get supported request types.
     *
     * @return array<string> Supported request types
     */
    public function getSupportedRequestTypes(): array
    {
        return $this->workflow->getSupportedRequestTypes();
    }
}
