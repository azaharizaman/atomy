<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\Workflows\PrivacyRights\Steps;

use Nexus\ComplianceOperations\Contracts\SagaStepInterface;
use Nexus\ComplianceOperations\DTOs\SagaStepContext;
use Nexus\ComplianceOperations\DTOs\SagaStepResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Saga step: Data Discovery.
 *
 * Forward action: Discovers all PII related to the data subject across systems.
 * Compensation: Clears discovery results.
 */
final readonly class DataDiscoveryStep implements SagaStepInterface
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
        return 'data_discovery';
    }

    public function getName(): string
    {
        return 'Data Discovery';
    }

    public function getDescription(): string
    {
        return 'Discovers all PII related to the data subject across systems';
    }

    public function execute(SagaStepContext $context): SagaStepResult
    {
        $this->getLogger()->info('Starting data discovery', [
            'saga_instance_id' => $context->sagaInstanceId,
            'tenant_id' => $context->tenantId,
        ]);

        try {
            $requestId = $context->get('request_id');
            $subjectId = $context->get('subject_id');
            $subjectEmail = $context->get('subject_email');
            $requestType = $context->get('request_type');

            if ($requestId === null) {
                return SagaStepResult::failure(
                    errorMessage: 'Request ID is required for data discovery',
                    canRetry: false,
                );
            }

            // Get validation results
            $validationResult = $context->getStepOutput('request_validation');
            $applicableRegulations = $validationResult['applicable_regulations'] ?? ['GDPR'];

            // Discover data across systems
            $discoveredData = $this->discoverDataAcrossSystems($subjectId, $subjectEmail);

            // Classify discovered data
            $classifiedData = $this->classifyDiscoveredData($discoveredData, $requestType);

            $discoveryResult = [
                'request_id' => $requestId,
                'discovery_id' => sprintf('DIS-%s-%s', $requestId, bin2hex(random_bytes(8))),
                'discovery_timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                'subject_id' => $subjectId,
                'subject_email' => $subjectEmail,
                'systems_queried' => $discoveredData['systems_queried'],
                'total_records_found' => $discoveredData['total_records'],
                'data_categories' => $classifiedData['categories'],
                'data_locations' => $classifiedData['locations'],
                'sensitive_data_found' => $classifiedData['sensitive_data'],
                'third_party_data' => $classifiedData['third_party_data'],
                'data_summary' => $classifiedData['summary'],
                'processing_activities' => $this->identifyProcessingActivities($discoveredData),
                'data_retention_status' => $this->checkDataRetention($discoveredData),
                'requires_third_party_notification' => !empty($classifiedData['third_party_data']),
            ];

            $this->getLogger()->info('Data discovery completed', [
                'request_id' => $requestId,
                'discovery_id' => $discoveryResult['discovery_id'],
                'total_records_found' => $discoveryResult['total_records_found'],
            ]);

            return SagaStepResult::success($discoveryResult);
        } catch (\Throwable $e) {
            $this->getLogger()->error('Data discovery failed', [
                'error' => $e->getMessage(),
            ]);

            return SagaStepResult::failure(
                errorMessage: 'Data discovery failed: ' . $e->getMessage(),
                canRetry: true,
            );
        }
    }

    public function compensate(SagaStepContext $context): SagaStepResult
    {
        $this->getLogger()->info('Compensating: Clearing data discovery results', [
            'saga_instance_id' => $context->sagaInstanceId,
        ]);

        try {
            $discoveryId = $context->getStepOutput('data_discovery', 'discovery_id');

            if ($discoveryId === null) {
                return SagaStepResult::compensated([
                    'message' => 'No data discovery to clear',
                ]);
            }

            // In production, this would clear cached discovery results
            $this->getLogger()->info('Data discovery results cleared', [
                'discovery_id' => $discoveryId,
            ]);

            return SagaStepResult::compensated([
                'cleared_discovery_id' => $discoveryId,
                'reason' => 'Privacy rights workflow compensation',
            ]);
        } catch (\Throwable $e) {
            $this->getLogger()->error('Failed to clear data discovery during compensation', [
                'error' => $e->getMessage(),
            ]);

            return SagaStepResult::compensationFailed(
                'Failed to clear data discovery: ' . $e->getMessage()
            );
        }
    }

    public function hasCompensation(): bool
    {
        return true;
    }

    public function getOrder(): int
    {
        return 2;
    }

    public function isRequired(): bool
    {
        return true;
    }

    public function getTimeout(): int
    {
        return 600; // 10 minutes
    }

    public function getRetryAttempts(): int
    {
        return 3;
    }

    /**
     * Discover data across all systems.
     *
     * @param string|null $subjectId Subject ID
     * @param string|null $subjectEmail Subject email
     * @return array<string, mixed>
     */
    private function discoverDataAcrossSystems(?string $subjectId, ?string $subjectEmail): array
    {
        // In production, this would query actual systems via adapters
        $systemsQueried = [
            'identity_system',
            'customer_database',
            'transaction_system',
            'communication_logs',
            'support_tickets',
            'marketing_platform',
            'analytics_system',
        ];

        $discoveredRecords = [];

        foreach ($systemsQueried as $system) {
            // Simulate finding records
            $recordCount = rand(0, 50);
            $discoveredRecords[$system] = [
                'record_count' => $recordCount,
                'data_types' => $this->getDataTypesForSystem($system),
                'last_updated' => (new \DateTimeImmutable())->modify('-' . rand(1, 30) . ' days')->format('Y-m-d'),
            ];
        }

        $totalRecords = array_sum(array_column($discoveredRecords, 'record_count'));

        return [
            'systems_queried' => $systemsQueried,
            'total_records' => $totalRecords,
            'records_by_system' => $discoveredRecords,
        ];
    }

    /**
     * Get data types for a specific system.
     *
     * @return array<string>
     */
    private function getDataTypesForSystem(string $system): array
    {
        $dataTypes = [
            'identity_system' => ['name', 'email', 'phone', 'address', 'date_of_birth'],
            'customer_database' => ['name', 'email', 'phone', 'address', 'preferences'],
            'transaction_system' => ['transaction_history', 'payment_details', 'billing_address'],
            'communication_logs' => ['email_content', 'sms_logs', 'call_records'],
            'support_tickets' => ['ticket_content', 'attachments', 'communication_history'],
            'marketing_platform' => ['email', 'preferences', 'engagement_data'],
            'analytics_system' => ['usage_data', 'behavior_data', 'device_info'],
        ];

        return $dataTypes[$system] ?? [];
    }

    /**
     * Classify discovered data.
     *
     * @param array<string, mixed> $discoveredData Discovered data
     * @param string $requestType Request type
     * @return array<string, mixed>
     */
    private function classifyDiscoveredData(array $discoveredData, string $requestType): array
    {
        $categories = [];
        $locations = [];
        $sensitiveData = [];
        $thirdPartyData = [];
        $summary = [
            'total_data_types' => 0,
            'sensitive_categories' => 0,
            'third_party_sharing' => 0,
        ];

        $sensitiveCategories = [
            'health_data',
            'financial_data',
            'biometric_data',
            'racial_ethnic_origin',
            'political_opinions',
            'religious_beliefs',
            'sexual_orientation',
        ];

        foreach ($discoveredData['records_by_system'] ?? [] as $system => $data) {
            $locations[] = $system;

            foreach ($data['data_types'] ?? [] as $dataType) {
                if (!in_array($dataType, $categories, true)) {
                    $categories[] = $dataType;
                    $summary['total_data_types']++;

                    if (in_array($dataType, $sensitiveCategories, true)) {
                        $sensitiveData[] = $dataType;
                        $summary['sensitive_categories']++;
                    }
                }
            }
        }

        // Check for third-party data sharing
        $thirdPartySystems = ['marketing_platform', 'analytics_system'];
        foreach ($thirdPartySystems as $system) {
            if (isset($discoveredData['records_by_system'][$system])) {
                $thirdPartyData[] = $system;
                $summary['third_party_sharing']++;
            }
        }

        return [
            'categories' => $categories,
            'locations' => $locations,
            'sensitive_data' => $sensitiveData,
            'third_party_data' => $thirdPartyData,
            'summary' => $summary,
        ];
    }

    /**
     * Identify processing activities.
     *
     * @param array<string, mixed> $discoveredData Discovered data
     * @return array<string, mixed>
     */
    private function identifyProcessingActivities(array $discoveredData): array
    {
        return [
            'collection' => true,
            'storage' => true,
            'processing' => true,
            'sharing' => !empty($discoveredData['records_by_system']['marketing_platform']),
            'retention' => true,
        ];
    }

    /**
     * Check data retention status.
     *
     * @param array<string, mixed> $discoveredData Discovered data
     * @return array<string, mixed>
     */
    private function checkDataRetention(array $discoveredData): array
    {
        $status = [];

        foreach ($discoveredData['records_by_system'] ?? [] as $system => $data) {
            $status[$system] = [
                'retention_policy' => '7_years',
                'eligible_for_deletion' => rand(0, 10) > 7,
                'legal_hold' => false,
            ];
        }

        return $status;
    }
}
