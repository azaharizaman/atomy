<?php

declare(strict_types=1);

namespace Nexus\FinancialStatements\Services;

use Nexus\FinancialStatements\Contracts\ComplianceTemplateInterface;
use Nexus\FinancialStatements\Contracts\FinancialStatementInterface;
use Nexus\FinancialStatements\Enums\ComplianceFramework;

/**
 * Pure logic for checking compliance requirements.
 */
final readonly class ComplianceChecker
{
    /**
     * @param array<ComplianceTemplateInterface> $templates
     */
    public function __construct(
        private array $templates = [],
    ) {}

    /**
     * Check statement compliance against a framework.
     *
     * @param FinancialStatementInterface $statement
     * @param ComplianceFramework $framework
     * @return array<string, mixed>
     */
    public function checkCompliance(
        FinancialStatementInterface $statement,
        ComplianceFramework $framework
    ): array {
        $template = $this->getTemplateForFramework($framework);
        
        if ($template === null) {
            return [
                'compliant' => false,
                'errors' => ['No template found for framework: ' . $framework->value],
            ];
        }

        return $template->validateCompliance($statement);
    }

    /**
     * Get required sections for a compliance framework.
     *
     * @param ComplianceFramework $framework
     * @return array<string>
     */
    public function getRequiredSections(ComplianceFramework $framework): array
    {
        $template = $this->getTemplateForFramework($framework);
        
        return $template?->getRequiredSections() ?? [];
    }

    /**
     * Get required disclosures for a compliance framework.
     *
     * @param ComplianceFramework $framework
     * @return array<string>
     */
    public function getRequiredDisclosures(ComplianceFramework $framework): array
    {
        $template = $this->getTemplateForFramework($framework);
        
        return $template?->getRequiredDisclosures() ?? [];
    }

    private function getTemplateForFramework(ComplianceFramework $framework): ?ComplianceTemplateInterface
    {
        foreach ($this->templates as $template) {
            if ($template->getFramework() === $framework) {
                return $template;
            }
        }

        return null;
    }
}
