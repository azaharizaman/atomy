<?php

declare(strict_types=1);

namespace Nexus\Accounting\Domain\Contracts;

use Nexus\Accounting\Domain\ValueObjects\ReportingPeriod;
use Nexus\Accounting\Domain\ValueObjects\StatementSection;
use Nexus\Accounting\Domain\Enums\StatementType;

/**
 * Base interface for financial statements.
 *
 * This interface defines the common structure and behavior for all financial
 * statements including Balance Sheets, Income Statements, and Cash Flow Statements.
 */
interface FinancialStatementInterface
{
    /**
     * Get the type of statement.
     */
    public function getType(): StatementType;

    /**
     * Get the reporting period for this statement.
     */
    public function getReportingPeriod(): ReportingPeriod;

    /**
     * Get the entity ID this statement belongs to.
     */
    public function getEntityId(): string;

    /**
     * Get all sections in the statement.
     *
     * @return StatementSection[]
     */
    public function getSections(): array;

    /**
     * Get a specific section by name/code.
     */
    public function getSection(string $name): ?StatementSection;

    /**
     * Get the grand total of the statement.
     */
    public function getGrandTotal(): float;

    /**
     * Get statement metadata.
     *
     * @return array<string, mixed>
     */
    public function getMetadata(): array;

    /**
     * Convert statement to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;

    /**
     * Check if the statement is locked (finalized).
     */
    public function isLocked(): bool;
}
