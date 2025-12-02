<?php

declare(strict_types=1);

namespace Nexus\FinancialStatements\Contracts;

/**
 * Contract for statement layout templates.
 */
interface StatementTemplateInterface
{
    /**
     * Get the template identifier.
     */
    public function getId(): string;

    /**
     * Get the template name.
     */
    public function getName(): string;

    /**
     * Get the section definitions for this template.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getSectionDefinitions(): array;

    /**
     * Get the compliance framework this template supports.
     */
    public function getComplianceFramework(): string;
}
