<?php

declare(strict_types=1);

namespace Nexus\FinancialStatements\Contracts;

use Nexus\FinancialStatements\Enums\ComplianceFramework;

/**
 * Contract for compliance-specific statement templates.
 */
interface ComplianceTemplateInterface
{
    /**
     * Get the compliance framework.
     */
    public function getFramework(): ComplianceFramework;

    /**
     * Get required sections for this compliance framework.
     *
     * @return array<string>
     */
    public function getRequiredSections(): array;

    /**
     * Get required disclosures for this compliance framework.
     *
     * @return array<string>
     */
    public function getRequiredDisclosures(): array;

    /**
     * Validate statement against compliance requirements.
     *
     * @param FinancialStatementInterface $statement
     * @return array<string, mixed> Compliance validation result
     */
    public function validateCompliance(FinancialStatementInterface $statement): array;
}
