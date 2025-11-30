<?php

declare(strict_types=1);

namespace Nexus\Compliance\Services;

use Nexus\Compliance\Contracts\ComplianceSchemeInterface;
use Nexus\Compliance\Core\Contracts\RuleEngineInterface;
use Nexus\Compliance\Core\Engine\ConfigurationValidator;
use Nexus\Compliance\Exceptions\ConfigurationValidationException;
use Psr\Log\LoggerInterface;

/**
 * Configuration auditor for compliance schemes.
 * 
 * Audits system configuration against compliance scheme requirements.
 * This is a framework-agnostic service that orchestrates configuration validation.
 */
final class ConfigurationAuditor
{
    public function __construct(
        private readonly RuleEngineInterface $ruleEngine,
        private readonly ConfigurationValidator $validator,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Audit system configuration against a compliance scheme.
     *
     * @param ComplianceSchemeInterface $scheme The compliance scheme to audit against
     * @param array<string, mixed> $systemConfiguration Current system configuration
     * @return array{passed: bool, results: array<string, array<string, mixed>>} Audit results
     */
    public function auditConfiguration(
        ComplianceSchemeInterface $scheme,
        array $systemConfiguration
    ): array {
        $this->logger->info("Starting configuration audit", [
            'scheme_name' => $scheme->getName(),
        ]);

        $results = [
            'features' => [],
            'settings' => [],
            'fields' => [],
            'overall' => [],
        ];

        $allPassed = true;

        // Validate required features
        try {
            $this->validator->validateRequiredFeatures(
                $scheme->getConfiguration()['required_features'] ?? [],
                $systemConfiguration['enabled_features'] ?? []
            );
            $results['features'] = [
                'status' => 'passed',
                'message' => 'All required features are enabled',
            ];
        } catch (ConfigurationValidationException $e) {
            $allPassed = false;
            $results['features'] = [
                'status' => 'failed',
                'message' => $e->getMessage(),
            ];
        }

        // Validate required settings
        try {
            $this->validator->validateRequiredSettings(
                $scheme->getConfiguration()['required_settings'] ?? [],
                $systemConfiguration
            );
            $results['settings'] = [
                'status' => 'passed',
                'message' => 'All required settings are configured',
            ];
        } catch (ConfigurationValidationException $e) {
            $allPassed = false;
            $results['settings'] = [
                'status' => 'failed',
                'message' => $e->getMessage(),
            ];
        }

        // Validate required fields
        try {
            $this->validator->validateRequiredFields(
                $scheme->getConfiguration()['required_fields'] ?? [],
                $systemConfiguration['field_configuration'] ?? []
            );
            $results['fields'] = [
                'status' => 'passed',
                'message' => 'All required fields are configured',
            ];
        } catch (ConfigurationValidationException $e) {
            $allPassed = false;
            $results['fields'] = [
                'status' => 'failed',
                'message' => $e->getMessage(),
            ];
        }

        // Overall scheme configuration validation
        try {
            $this->validator->validateSchemeConfiguration(
                $scheme,
                $systemConfiguration
            );
            $results['overall'] = [
                'status' => 'passed',
                'message' => 'Scheme configuration is valid',
            ];
        } catch (ConfigurationValidationException $e) {
            $allPassed = false;
            $results['overall'] = [
                'status' => 'failed',
                'message' => $e->getMessage(),
            ];
        }

        $this->logger->info("Configuration audit completed", [
            'scheme_name' => $scheme->getName(),
            'passed' => $allPassed,
        ]);

        return [
            'passed' => $allPassed,
            'results' => $results,
        ];
    }

    /**
     * Validate a specific checkpoint against system configuration.
     *
     * @param string $checkpointType The checkpoint type
     * @param array<string, mixed> $validationRules The validation rules
     * @param array<string, mixed> $systemConfiguration Current system configuration
     * @return array{passed: bool, message: string} Validation result
     */
    public function validateCheckpoint(
        string $checkpointType,
        array $validationRules,
        array $systemConfiguration
    ): array {
        $this->logger->info("Validating checkpoint", [
            'checkpoint_type' => $checkpointType,
        ]);

        try {
            // Evaluate all rules in the checkpoint
            foreach ($validationRules as $ruleName => $rule) {
                $result = $this->ruleEngine->evaluate(
                    $rule['operator'] ?? 'equals',
                    $systemConfiguration,
                    $rule['expected_value'] ?? null,
                    $rule['field'] ?? null
                );

                if (!$result) {
                    return [
                        'passed' => false,
                        'message' => "Checkpoint '{$checkpointType}' failed: Rule '{$ruleName}' not satisfied",
                    ];
                }
            }

            return [
                'passed' => true,
                'message' => "Checkpoint '{$checkpointType}' passed all validation rules",
            ];
        } catch (\Exception $e) {
            $this->logger->error("Checkpoint validation error", [
                'checkpoint_type' => $checkpointType,
                'error' => $e->getMessage(),
            ]);

            return [
                'passed' => false,
                'message' => "Checkpoint validation error: {$e->getMessage()}",
            ];
        }
    }

    /**
     * Generate audit report for a compliance scheme.
     *
     * @param ComplianceSchemeInterface $scheme The compliance scheme
     * @param array<string, mixed> $systemConfiguration Current system configuration
     * @param array<string, mixed> $auditResults Audit results from auditConfiguration()
     * @return array<string, mixed> Formatted audit report
     */
    public function generateAuditReport(
        ComplianceSchemeInterface $scheme,
        array $systemConfiguration,
        array $auditResults
    ): array {
        $this->logger->info("Generating audit report", [
            'scheme_name' => $scheme->getName(),
        ]);

        $report = [
            'scheme' => [
                'name' => $scheme->getName(),
                'description' => $scheme->getDescription(),
                'is_active' => $scheme->isActive(),
            ],
            'audit_timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'overall_status' => $auditResults['passed'] ? 'COMPLIANT' : 'NON_COMPLIANT',
            'validation_results' => $auditResults['results'],
            'recommendations' => [],
        ];

        // Generate recommendations for failed checks
        foreach ($auditResults['results'] as $category => $result) {
            if ($result['status'] === 'failed') {
                $report['recommendations'][] = [
                    'category' => $category,
                    'message' => $result['message'],
                    'severity' => 'high',
                ];
            }
        }

        $this->logger->info("Audit report generated", [
            'scheme_name' => $scheme->getName(),
            'status' => $report['overall_status'],
            'recommendation_count' => count($report['recommendations']),
        ]);

        return $report;
    }
}
