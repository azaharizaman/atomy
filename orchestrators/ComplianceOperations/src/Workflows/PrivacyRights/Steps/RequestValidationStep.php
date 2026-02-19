<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\Workflows\PrivacyRights\Steps;

use Nexus\ComplianceOperations\Contracts\SagaStepInterface;
use Nexus\ComplianceOperations\DTOs\SagaStepContext;
use Nexus\ComplianceOperations\DTOs\SagaStepResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Saga step: Request Validation.
 *
 * Forward action: Validates DSAR (Data Subject Access Request) request.
 * Compensation: Marks request as cancelled.
 */
final readonly class RequestValidationStep implements SagaStepInterface
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
        return 'request_validation';
    }

    public function getName(): string
    {
        return 'Request Validation';
    }

    public function getDescription(): string
    {
        return 'Validates DSAR (Data Subject Access Request) request';
    }

    public function execute(SagaStepContext $context): SagaStepResult
    {
        $this->getLogger()->info('Starting request validation', [
            'saga_instance_id' => $context->sagaInstanceId,
            'tenant_id' => $context->tenantId,
        ]);

        try {
            $requestId = $context->get('request_id');
            $requestType = $context->get('request_type');
            $subjectId = $context->get('subject_id');
            $subjectEmail = $context->get('subject_email');

            if ($requestId === null) {
                return SagaStepResult::failure(
                    errorMessage: 'Request ID is required for validation',
                    canRetry: false,
                );
            }

            if ($requestType === null) {
                return SagaStepResult::failure(
                    errorMessage: 'Request type is required for validation',
                    canRetry: false,
                );
            }

            // Validate request type
            $validRequestTypes = [
                'access', // Right to access
                'rectification', // Right to rectification
                'erasure', // Right to be forgotten
                'restriction', // Right to restriction
                'portability', // Right to data portability
                'objection', // Right to object
            ];

            if (!in_array($requestType, $validRequestTypes, true)) {
                return SagaStepResult::failure(
                    errorMessage: "Invalid request type: {$requestType}",
                    canRetry: false,
                );
            }

            // Validate subject identity
            $identityValidation = $this->validateSubjectIdentity($subjectId, $subjectEmail);

            if (!$identityValidation['valid']) {
                return SagaStepResult::failure(
                    errorMessage: 'Subject identity could not be verified: ' . $identityValidation['reason'],
                    canRetry: false,
                );
            }

            // Check request deadline (typically 30 days under GDPR)
            $deadline = (new \DateTimeImmutable())->modify('+30 days');

            $validationResult = [
                'request_id' => $requestId,
                'validation_id' => sprintf('VAL-%s-%s', $requestId, bin2hex(random_bytes(8))),
                'validation_timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                'request_type' => $requestType,
                'subject_id' => $subjectId,
                'subject_email' => $subjectEmail,
                'validation_status' => 'valid',
                'identity_verified' => true,
                'identity_verification_method' => $identityValidation['method'],
                'deadline' => $deadline->format('Y-m-d'),
                'legal_basis' => $this->determineLegalBasis($requestType),
                'applicable_regulations' => $this->getApplicableRegulations($context->get('jurisdiction', 'EU')),
                'exemptions' => $this->checkExemptions($requestType, $subjectId),
                'requires_legal_review' => $requestType === 'erasure',
            ];

            $this->getLogger()->info('Request validation completed', [
                'request_id' => $requestId,
                'validation_id' => $validationResult['validation_id'],
                'request_type' => $requestType,
            ]);

            return SagaStepResult::success($validationResult);
        } catch (\Throwable $e) {
            $this->getLogger()->error('Request validation failed', [
                'error' => $e->getMessage(),
            ]);

            return SagaStepResult::failure(
                errorMessage: 'Request validation failed: ' . $e->getMessage(),
                canRetry: true,
            );
        }
    }

    public function compensate(SagaStepContext $context): SagaStepResult
    {
        $this->getLogger()->info('Compensating: Cancelling request validation', [
            'saga_instance_id' => $context->sagaInstanceId,
        ]);

        try {
            $validationId = $context->getStepOutput('request_validation', 'validation_id');

            if ($validationId === null) {
                return SagaStepResult::compensated([
                    'message' => 'No request validation to cancel',
                ]);
            }

            // In production, this would mark the request as cancelled
            $this->getLogger()->info('Request validation cancelled', [
                'validation_id' => $validationId,
            ]);

            return SagaStepResult::compensated([
                'cancelled_validation_id' => $validationId,
                'reason' => 'Privacy rights workflow compensation',
            ]);
        } catch (\Throwable $e) {
            $this->getLogger()->error('Failed to cancel request validation during compensation', [
                'error' => $e->getMessage(),
            ]);

            return SagaStepResult::compensationFailed(
                'Failed to cancel request validation: ' . $e->getMessage()
            );
        }
    }

    public function hasCompensation(): bool
    {
        return true;
    }

    public function getOrder(): int
    {
        return 1;
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
     * Validate subject identity.
     *
     * @param string|null $subjectId Subject ID
     * @param string|null $subjectEmail Subject email
     * @return array<string, mixed>
     */
    private function validateSubjectIdentity(?string $subjectId, ?string $subjectEmail): array
    {
        // In production, this would perform actual identity verification
        if ($subjectId === null && $subjectEmail === null) {
            return [
                'valid' => false,
                'reason' => 'No subject identifier provided',
                'method' => null,
            ];
        }

        return [
            'valid' => true,
            'reason' => null,
            'method' => $subjectId !== null ? 'id_verification' : 'email_verification',
        ];
    }

    /**
     * Determine legal basis for the request.
     */
    private function determineLegalBasis(string $requestType): string
    {
        $legalBases = [
            'access' => 'GDPR Article 15 - Right of access',
            'rectification' => 'GDPR Article 16 - Right to rectification',
            'erasure' => 'GDPR Article 17 - Right to erasure',
            'restriction' => 'GDPR Article 18 - Right to restriction',
            'portability' => 'GDPR Article 20 - Right to data portability',
            'objection' => 'GDPR Article 21 - Right to object',
        ];

        return $legalBases[$requestType] ?? 'Unknown legal basis';
    }

    /**
     * Get applicable regulations based on jurisdiction.
     *
     * @return array<string>
     */
    private function getApplicableRegulations(string $jurisdiction): array
    {
        $regulations = [
            'EU' => ['GDPR'],
            'US' => ['CCPA', 'CPRA'],
            'UK' => ['UK GDPR', 'Data Protection Act 2018'],
            'BR' => ['LGPD'],
            'CA' => ['PIPEDA'],
        ];

        return $regulations[$jurisdiction] ?? ['GDPR'];
    }

    /**
     * Check for exemptions.
     *
     * @return array<string>
     */
    private function checkExemptions(string $requestType, ?string $subjectId): array
    {
        // In production, this would check actual exemption conditions
        $exemptions = [];

        if ($requestType === 'erasure') {
            // Check for legal retention requirements
            $exemptions[] = 'legal_retention_check_required';
        }

        return $exemptions;
    }
}
