<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\Workflows\PrivacyRights\Steps;

use Nexus\ComplianceOperations\Contracts\SagaStepInterface;
use Nexus\ComplianceOperations\DTOs\SagaStepContext;
use Nexus\ComplianceOperations\DTOs\SagaStepResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Saga step: Response Generation.
 *
 * Forward action: Generates response to the data subject with results.
 * Compensation: Marks response as void.
 */
final readonly class ResponseGenerationStep implements SagaStepInterface
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
        return 'response_generation';
    }

    public function getName(): string
    {
        return 'Response Generation';
    }

    public function getDescription(): string
    {
        return 'Generates response to the data subject with results';
    }

    public function execute(SagaStepContext $context): SagaStepResult
    {
        $this->getLogger()->info('Starting response generation', [
            'saga_instance_id' => $context->sagaInstanceId,
            'tenant_id' => $context->tenantId,
        ]);

        try {
            $requestId = $context->get('request_id');
            $requestType = $context->get('request_type');
            $subjectEmail = $context->get('subject_email');

            if ($requestId === null) {
                return SagaStepResult::failure(
                    errorMessage: 'Request ID is required for response generation',
                    canRetry: false,
                );
            }

            // Get previous step results
            $validationResult = $context->getStepOutput('request_validation');
            $discoveryResult = $context->getStepOutput('data_discovery');
            $processingResult = $context->getStepOutput('subject_rights_processing');

            // Generate response document
            $responseDocument = $this->generateResponseDocument(
                $requestId,
                $requestType,
                $validationResult,
                $discoveryResult,
                $processingResult
            );

            // Generate compliance certificate
            $complianceCertificate = $this->generateComplianceCertificate(
                $requestId,
                $requestType,
                $processingResult
            );

            $responseResult = [
                'request_id' => $requestId,
                'response_id' => sprintf('RES-%s-%s', $requestId, bin2hex(random_bytes(8))),
                'response_timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                'request_type' => $requestType,
                'subject_email' => $subjectEmail,
                'response_status' => 'completed',
                'response_document' => $responseDocument,
                'compliance_certificate' => $complianceCertificate,
                'delivery_method' => $this->determineDeliveryMethod($requestType),
                'delivery_status' => 'pending',
                'response_deadline_met' => $this->checkDeadlineMet($validationResult),
                'audit_trail' => $this->generateAuditTrail($requestId, $context->userId),
                'retention_period' => '7_years',
                'follow_up_actions' => $this->determineFollowUpActions($processingResult),
            ];

            $this->getLogger()->info('Response generation completed', [
                'request_id' => $requestId,
                'response_id' => $responseResult['response_id'],
                'request_type' => $requestType,
            ]);

            return SagaStepResult::success($responseResult);
        } catch (\Throwable $e) {
            $this->getLogger()->error('Response generation failed', [
                'error' => $e->getMessage(),
            ]);

            return SagaStepResult::failure(
                errorMessage: 'Response generation failed: ' . $e->getMessage(),
                canRetry: true,
            );
        }
    }

    public function compensate(SagaStepContext $context): SagaStepResult
    {
        $this->getLogger()->info('Compensating: Voiding response', [
            'saga_instance_id' => $context->sagaInstanceId,
        ]);

        try {
            $responseId = $context->getStepOutput('response_generation', 'response_id');

            if ($responseId === null) {
                return SagaStepResult::compensated([
                    'message' => 'No response to void',
                ]);
            }

            // In production, this would mark the response as void
            $this->getLogger()->info('Response voided', [
                'response_id' => $responseId,
            ]);

            return SagaStepResult::compensated([
                'voided_response_id' => $responseId,
                'reason' => 'Privacy rights workflow compensation',
            ]);
        } catch (\Throwable $e) {
            $this->getLogger()->error('Failed to void response during compensation', [
                'error' => $e->getMessage(),
            ]);

            return SagaStepResult::compensationFailed(
                'Failed to void response: ' . $e->getMessage()
            );
        }
    }

    public function hasCompensation(): bool
    {
        return true;
    }

    public function getOrder(): int
    {
        return 4;
    }

    public function isRequired(): bool
    {
        return true;
    }

    public function getTimeout(): int
    {
        return 120; // 2 minutes
    }

    public function getRetryAttempts(): int
    {
        return 3;
    }

    /**
     * Generate response document.
     *
     * @param string $requestId Request ID
     * @param string $requestType Request type
     * @param array<string, mixed>|null $validationResult Validation results
     * @param array<string, mixed>|null $discoveryResult Discovery results
     * @param array<string, mixed>|null $processingResult Processing results
     * @return array<string, mixed>
     */
    private function generateResponseDocument(
        string $requestId,
        string $requestType,
        ?array $validationResult,
        ?array $discoveryResult,
        ?array $processingResult
    ): array {
        $template = $this->getResponseTemplate($requestType);

        return [
            'document_id' => sprintf('DOC-%s', bin2hex(random_bytes(8))),
            'template_used' => $template,
            'format' => 'pdf',
            'language' => 'en',
            'sections' => [
                'header' => [
                    'title' => "Response to Data Subject Request - {$requestType}",
                    'request_id' => $requestId,
                    'date' => (new \DateTimeImmutable())->format('Y-m-d'),
                ],
                'summary' => [
                    'request_type' => $requestType,
                    'status' => $processingResult['status'] ?? 'completed',
                    'action_taken' => $processingResult['action_taken'] ?? 'none',
                ],
                'details' => [
                    'legal_basis' => $validationResult['legal_basis'] ?? '',
                    'data_categories' => $discoveryResult['data_categories'] ?? [],
                    'records_processed' => $discoveryResult['total_records_found'] ?? 0,
                ],
                'outcome' => [
                    'action_summary' => $processingResult['notes'] ?? '',
                    'records_affected' => $processingResult['records_deleted'] ?? $processingResult['records_included'] ?? 0,
                ],
                'rights_information' => [
                    'right_to_complain' => true,
                    'supervisory_authority' => 'Data Protection Authority',
                    'complaint_deadline' => '90 days',
                ],
            ],
            'generated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Get response template based on request type.
     */
    private function getResponseTemplate(string $requestType): string
    {
        $templates = [
            'access' => 'dsar_access_response',
            'erasure' => 'dsar_erasure_response',
            'rectification' => 'dsar_rectification_response',
            'portability' => 'dsar_portability_response',
            'restriction' => 'dsar_restriction_response',
            'objection' => 'dsar_objection_response',
        ];

        return $templates[$requestType] ?? 'dsar_generic_response';
    }

    /**
     * Generate compliance certificate.
     *
     * @param string $requestId Request ID
     * @param string $requestType Request type
     * @param array<string, mixed>|null $processingResult Processing results
     * @return array<string, mixed>
     */
    private function generateComplianceCertificate(
        string $requestId,
        string $requestType,
        ?array $processingResult
    ): array {
        return [
            'certificate_id' => sprintf('CERT-%s-%s', $requestId, bin2hex(random_bytes(8))),
            'certificate_timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            'request_id' => $requestId,
            'request_type' => $requestType,
            'compliance_status' => 'compliant',
            'regulations_complied' => ['GDPR'],
            'processing_outcome' => $processingResult['status'] ?? 'completed',
            'verification_hash' => hash('sha256', $requestId . time()),
            'valid_until' => (new \DateTimeImmutable())->modify('+7 years')->format('Y-m-d'),
            'issued_by' => 'Compliance Operations System',
        ];
    }

    /**
     * Determine delivery method based on request type.
     */
    private function determineDeliveryMethod(string $requestType): string
    {
        // Secure delivery for sensitive requests
        if (in_array($requestType, ['access', 'portability'], true)) {
            return 'secure_download';
        }

        return 'email';
    }

    /**
     * Check if deadline was met.
     *
     * @param array<string, mixed>|null $validationResult Validation results
     */
    private function checkDeadlineMet(?array $validationResult): bool
    {
        if ($validationResult === null) {
            return true;
        }

        $deadline = $validationResult['deadline'] ?? null;
        if ($deadline === null) {
            return true;
        }

        return new \DateTimeImmutable() <= new \DateTimeImmutable($deadline);
    }

    /**
     * Generate audit trail.
     *
     * @param string $requestId Request ID
     * @param string $userId User ID
     * @return array<string, mixed>
     */
    private function generateAuditTrail(string $requestId, string $userId): array
    {
        return [
            'audit_id' => sprintf('AUD-%s', bin2hex(random_bytes(16))),
            'request_id' => $requestId,
            'action' => 'dsar_response_generated',
            'performed_by' => $userId,
            'performed_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            'system_version' => '1.0.0',
        ];
    }

    /**
     * Determine follow-up actions.
     *
     * @param array<string, mixed>|null $processingResult Processing results
     * @return array<string>
     */
    private function determineFollowUpActions(?array $processingResult): array
    {
        $actions = [];

        if ($processingResult === null) {
            return $actions;
        }

        if (($processingResult['records_retained'] ?? 0) > 0) {
            $actions[] = 'notify_subject_of_retained_data';
        }

        if (($processingResult['third_parties_notified'] ?? false)) {
            $actions[] = 'verify_third_party_compliance';
        }

        if (($processingResult['backup_retention_period'] ?? null) !== null) {
            $actions[] = 'schedule_backup_purge';
        }

        return $actions;
    }
}
