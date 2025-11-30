<?php

declare(strict_types=1);

namespace Nexus\Compliance\Contracts;

use Nexus\Compliance\ValueObjects\SeverityLevel;

/**
 * Interface for SOD (Segregation of Duties) rule management.
 */
interface SodManagerInterface
{
    /**
     * Create a new SOD rule.
     *
     * @param string $tenantId The tenant identifier
     * @param string $ruleName The rule name
     * @param string $transactionType The transaction type this rule applies to
     * @param SeverityLevel $severityLevel The severity level
     * @param string $creatorRole The role that can create transactions
     * @param string $approverRole The role that can approve transactions
     * @return string The rule ID
     * @throws \Nexus\Compliance\Exceptions\DuplicateRuleException
     */
    public function createRule(
        string $tenantId,
        string $ruleName,
        string $transactionType,
        SeverityLevel $severityLevel,
        string $creatorRole,
        string $approverRole
    ): string;

    /**
     * Validate a transaction against SOD rules.
     *
     * @param string $tenantId The tenant identifier
     * @param string $transactionType The transaction type
     * @param string $creatorId The user who created the transaction
     * @param string $approverId The user attempting to approve the transaction
     * @return bool True if validation passes
     * @throws \Nexus\Compliance\Exceptions\SodViolationException
     */
    public function validateTransaction(
        string $tenantId,
        string $transactionType,
        string $creatorId,
        string $approverId
    ): bool;

    /**
     * Get all SOD rules for a tenant.
     *
     * @param string $tenantId The tenant identifier
     * @return array<SodRuleInterface>
     */
    public function getRules(string $tenantId): array;

    /**
     * Get all SOD violations for a tenant.
     *
     * @param string $tenantId The tenant identifier
     * @param \DateTimeImmutable|null $from Optional start date
     * @param \DateTimeImmutable|null $to Optional end date
     * @return array<SodViolationInterface>
     */
    public function getViolations(
        string $tenantId,
        ?\DateTimeImmutable $from = null,
        ?\DateTimeImmutable $to = null
    ): array;

    /**
     * Deactivate a SOD rule.
     *
     * @param string $ruleId The rule ID
     * @return void
     * @throws \Nexus\Compliance\Exceptions\RuleNotFoundException
     */
    public function deactivateRule(string $ruleId): void;

    /**
     * Activate a SOD rule.
     *
     * @param string $ruleId The rule ID
     * @return void
     * @throws \Nexus\Compliance\Exceptions\RuleNotFoundException
     */
    public function activateRule(string $ruleId): void;
}
