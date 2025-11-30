<?php

declare(strict_types=1);

namespace Nexus\Accounting\Contracts;

use Nexus\Accounting\Core\ValueObjects\ReportingPeriod;
use Nexus\Accounting\Core\ValueObjects\StatementSection;
use Nexus\Accounting\Core\Enums\StatementType;

/**
 * Base contract for all financial statements.
 *
 * Defines the common structure and behavior for Balance Sheet,
 * Income Statement, Cash Flow Statement, etc.
 */
interface FinancialStatementInterface
{
    /**
     * Get the statement type.
     */
    public function getType(): StatementType;

    /**
     * Get the reporting period.
     */
    public function getReportingPeriod(): ReportingPeriod;

    /**
     * Get the entity ID this statement belongs to.
     */
    public function getEntityId(): string;

    /**
     * Get all sections of the statement.
     *
     * @return StatementSection[]
     */
    public function getSections(): array;

    /**
     * Get a specific section by name.
     */
    public function getSection(string $name): ?StatementSection;

    /**
     * Get the grand total of the statement.
     */
    public function getGrandTotal(): float;

    /**
     * Get statement metadata (generated date, user, version, etc.).
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
