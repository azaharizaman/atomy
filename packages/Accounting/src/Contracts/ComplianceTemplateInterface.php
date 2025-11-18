<?php

declare(strict_types=1);

namespace Nexus\Accounting\Contracts;

use Nexus\Accounting\Core\ValueObjects\ComplianceStandard;

/**
 * GAAP/IFRS compliance template contract.
 *
 * Applies compliance-specific formatting and validation rules.
 */
interface ComplianceTemplateInterface
{
    /**
     * Get the compliance standard this template represents.
     */
    public function getStandard(): ComplianceStandard;

    /**
     * Apply compliance formatting to a statement.
     *
     * @param array<string, mixed> $options
     * @return array<string, mixed> Formatted statement data
     */
    public function applyFormatting(
        FinancialStatementInterface $statement,
        array $options = []
    ): array;

    /**
     * Validate statement against compliance rules.
     *
     * @return array<string, mixed> Validation results with violations
     */
    public function validateCompliance(FinancialStatementInterface $statement): array;

    /**
     * Get required disclosures for this standard.
     *
     * @return array<string, string> Disclosure requirements
     */
    public function getRequiredDisclosures(): array;

    /**
     * Get account mapping for this standard.
     *
     * @return array<string, string> Account code mappings
     */
    public function getAccountMapping(): array;

    /**
     * Check if a specific feature is required by this standard.
     */
    public function requiresFeature(string $featureName): bool;
}
