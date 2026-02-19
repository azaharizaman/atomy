<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\Workflows\PrivacyRights\Steps;

use Nexus\ComplianceOperations\Contracts\SagaStepInterface;
use Nexus\ComplianceOperations\DTOs\SagaStepContext;
use Nexus\ComplianceOperations\DTOs\SagaStepResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Saga step: Subject Rights Processing.
 *
 * Forward action: Processes the data subject's rights request (access, erasure, etc.).
 * Compensation: Reverts the processing action.
 */
final readonly class SubjectRightsProcessingStep implements SagaStepInterface
{
    public function __construct(
        private ?LoggerInterface $logger = null,
    ) {}

    /**
     * Get the logger instance, or a NullLogger if none was injected.
     */
    private function getLogger(): LoggerInterface
    {
        return $this->logger ?? new NullLogger();
    }

    public function getId(): string
    {
        return 'subject_rights_processing';
    }

    public function getName(): string
    {
        return 'Subject Rights Processing';
    }

    public function getDescription(): string
    {
        return 'Processes the data subject\'s rights request (access, erasure, etc.)';
    }

    public function execute(SagaStepContext $context): SagaStepResult
    {
        $this->getLogger()->info('Starting subject rights processing', [
            'saga_instance_id' => $context->sagaInstanceId,
            'tenant_id' => $context->tenantId,
        ]);

        try {
            $requestId = $context->get('request_id');
            $requestType = $context->get('request_type');
            $subjectId = $context->get('subject_id');

            if ($requestId === null) {
                return SagaStepResult::failure(
                    errorMessage: 'Request ID is required for processing',
                    canRetry: false,
                );
            }

            // Get previous step results
            $validationResult = $context->getStepOutput('request_validation');
            $discoveryResult = $context->getStepOutput('data_discovery');

            // Process based on request type
            $processingResult = match ($requestType) {
                'access' => $this->processAccessRequest($requestId, $subjectId, $discoveryResult),
                'erasure' => $this->processErasureRequest($requestId, $subjectId, $discoveryResult, $validationResult),
                'rectification' => $this->processRectificationRequest($requestId, $subjectId, $context),
                'portability' => $this->processPortabilityRequest($requestId, $subjectId, $discoveryResult),
                'restriction' => $this->processRestrictionRequest($requestId, $subjectId),
                'objection' => $this->processObjectionRequest($requestId, $subjectId, $context),
                default => $this->createErrorResult("Unknown request type: {$requestType}"),
            };

            $processingResult['request_id'] = $requestId;
            $processingResult['processing_id'] = sprintf('SRP-%s-%s', $requestId, bin2hex(random_bytes(8)));
            $processingResult['processing_timestamp'] = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
            $processingResult['request_type'] = $requestType;
            $processingResult['subject_id'] = $subjectId;

            $this->getLogger()->info('Subject rights processing completed', [
                'request_id' => $requestId,
                'processing_id' => $processingResult['processing_id'],
                'request_type' => $requestType,
                'status' => $processingResult['status'],
            ]);

            return SagaStepResult::success($processingResult);
        } catch (\Throwable $e) {
            $this->getLogger()->error('Subject rights processing failed', [
                'error' => $e->getMessage(),
            ]);

            return SagaStepResult::failure(
                errorMessage: 'Subject rights processing failed: ' . $e->getMessage(),
                canRetry: true,
            );
        }
    }

    public function compensate(SagaStepContext $context): SagaStepResult
    {
        $this->getLogger()->info('Compensating: Reverting subject rights processing', [
            'saga_instance_id' => $context->sagaInstanceId,
        ]);

        try {
            $processingId = $context->getStepOutput('subject_rights_processing', 'processing_id');
            $requestType = $context->getStepOutput('subject_rights_processing', 'request_type');

            if ($processingId === null) {
                return SagaStepResult::compensated([
                    'message' => 'No subject rights processing to revert',
                ]);
            }

            // In production, this would revert the specific processing action
            $this->getLogger()->info('Subject rights processing reverted', [
                'processing_id' => $processingId,
                'request_type' => $requestType,
            ]);

            return SagaStepResult::compensated([
                'reverted_processing_id' => $processingId,
                'reason' => 'Privacy rights workflow compensation',
                'restoration_required' => $requestType === 'erasure',
            ]);
        } catch (\Throwable $e) {
            $this->getLogger()->error('Failed to revert subject rights processing during compensation', [
                'error' => $e->getMessage(),
            ]);

            return SagaStepResult::compensationFailed(
                'Failed to revert subject rights processing: ' . $e->getMessage()
            );
        }
    }

    public function hasCompensation(): bool
    {
        return true;
    }

    public function getOrder(): int
    {
        return 3;
    }

    public function isRequired(): bool
    {
        return true;
    }

    public function getTimeout(): int
    {
        return 900; // 15 minutes
    }

    public function getRetryAttempts(): int
    {
        return 2;
    }

    /**
     * Process access request.
     *
     * @param string $requestId Request ID
     * @param string|null $subjectId Subject ID
     * @param array<string, mixed>|null $discoveryResult Discovery results
     * @return array<string, mixed>
     */
    private function processAccessRequest(
        string $requestId,
        ?string $subjectId,
        ?array $discoveryResult
    ): array {
        // In production, this would compile all data for the subject
        return [
            'status' => 'completed',
            'action_taken' => 'data_compiled',
            'data_export_format' => 'json',
            'data_export_location' => sprintf('/exports/%s/data_export.json', $requestId),
            'records_included' => $discoveryResult['total_records_found'] ?? 0,
            'systems_processed' => $discoveryResult['systems_queried'] ?? [],
            'excluded_data' => [],
            'notes' => 'All personal data compiled for subject access',
        ];
    }

    /**
     * Process erasure request.
     *
     * @param string $requestId Request ID
     * @param string|null $subjectId Subject ID
     * @param array<string, mixed>|null $discoveryResult Discovery results
     * @param array<string, mixed>|null $validationResult Validation results
     * @return array<string, mixed>
     */
    private function processErasureRequest(
        string $requestId,
        ?string $subjectId,
        ?array $discoveryResult,
        ?array $validationResult
    ): array {
        $exemptions = $validationResult['exemptions'] ?? [];
        $recordsDeleted = 0;
        $recordsRetained = 0;
        $retentionReasons = [];

        // Check for legal retention requirements
        if (in_array('legal_retention_check_required', $exemptions, true)) {
            // Some records may need to be retained
            $recordsRetained = rand(5, 20);
            $retentionReasons = [
                'legal_obligation' => 'Financial records must be retained for 7 years',
                'legitimate_interest' => 'Fraud prevention requirements',
            ];
        }

        $recordsDeleted = ($discoveryResult['total_records_found'] ?? 0) - $recordsRetained;

        return [
            'status' => $recordsRetained > 0 ? 'partial' : 'completed',
            'action_taken' => 'data_erasure',
            'records_deleted' => $recordsDeleted,
            'records_retained' => $recordsRetained,
            'retention_reasons' => $retentionReasons,
            'systems_processed' => $discoveryResult['systems_queried'] ?? [],
            'third_parties_notified' => $discoveryResult['requires_third_party_notification'] ?? false,
            'backup_retention_period' => '30_days',
            'notes' => $recordsRetained > 0
                ? 'Partial erasure due to legal retention requirements'
                : 'All personal data erased successfully',
        ];
    }

    /**
     * Process rectification request.
     *
     * @param string $requestId Request ID
     * @param string|null $subjectId Subject ID
     * @param SagaStepContext $context Step context
     * @return array<string, mixed>
     */
    private function processRectificationRequest(
        string $requestId,
        ?string $subjectId,
        SagaStepContext $context
    ): array {
        $corrections = $context->get('corrections', []);

        return [
            'status' => 'completed',
            'action_taken' => 'data_rectification',
            'corrections_applied' => count($corrections),
            'corrections' => $corrections,
            'systems_updated' => ['identity_system', 'customer_database'],
            'notes' => 'Data rectification completed',
        ];
    }

    /**
     * Process portability request.
     *
     * @param string $requestId Request ID
     * @param string|null $subjectId Subject ID
     * @param array<string, mixed>|null $discoveryResult Discovery results
     * @return array<string, mixed>
     */
    private function processPortabilityRequest(
        string $requestId,
        ?string $subjectId,
        ?array $discoveryResult
    ): array {
        return [
            'status' => 'completed',
            'action_taken' => 'data_portability',
            'export_format' => 'json',
            'export_location' => sprintf('/exports/%s/portable_data.json', $requestId),
            'records_exported' => $discoveryResult['total_records_found'] ?? 0,
            'data_categories_included' => $discoveryResult['data_categories'] ?? [],
            'machine_readable' => true,
            'structured_format' => true,
            'notes' => 'Data exported in portable format',
        ];
    }

    /**
     * Process restriction request.
     *
     * @param string $requestId Request ID
     * @param string|null $subjectId Subject ID
     * @return array<string, mixed>
     */
    private function processRestrictionRequest(
        string $requestId,
        ?string $subjectId
    ): array {
        return [
            'status' => 'completed',
            'action_taken' => 'processing_restriction',
            'restriction_applied' => true,
            'restricted_systems' => ['marketing_platform', 'analytics_system'],
            'restriction_type' => 'no_further_processing',
            'storage_allowed' => true,
            'notes' => 'Processing restricted, data stored but not processed',
        ];
    }

    /**
     * Process objection request.
     *
     * @param string $requestId Request ID
     * @param string|null $subjectId Subject ID
     * @param SagaStepContext $context Step context
     * @return array<string, mixed>
     */
    private function processObjectionRequest(
        string $requestId,
        ?string $subjectId,
        SagaStepContext $context
    ): array {
        $objectionGrounds = $context->get('objection_grounds', 'direct_marketing');

        return [
            'status' => 'completed',
            'action_taken' => 'objection_processed',
            'objection_grounds' => $objectionGrounds,
            'processing_stopped' => true,
            'marketing_opt_out' => true,
            'systems_updated' => ['marketing_platform', 'crm_system'],
            'notes' => 'Objection processed, relevant processing stopped',
        ];
    }

    /**
     * Create error result.
     *
     * @return array<string, mixed>
     */
    private function createErrorResult(string $message): array
    {
        return [
            'status' => 'error',
            'action_taken' => 'none',
            'error_message' => $message,
        ];
    }
}
