<?php

declare(strict_types=1);

namespace Nexus\Compliance\Contracts;

/**
 * Repository interface for SOD rule persistence.
 */
interface SodRuleRepositoryInterface
{
    /**
     * Find a SOD rule by ID.
     *
     * @param string $id The rule ID
     * @return SodRuleInterface|null
     */
    public function findById(string $id): ?SodRuleInterface;

    /**
     * Get all rules for a tenant and transaction type.
     *
     * @param string $tenantId The tenant identifier
     * @param string $transactionType The transaction type
     * @return array<SodRuleInterface>
     */
    public function getRulesByTransactionType(string $tenantId, string $transactionType): array;

    /**
     * Get all active rules for a tenant.
     *
     * @param string $tenantId The tenant identifier
     * @return array<SodRuleInterface>
     */
    public function getActiveRules(string $tenantId): array;

    /**
     * Get all rules for a tenant (active and inactive).
     *
     * @param string $tenantId The tenant identifier
     * @return array<SodRuleInterface>
     */
    public function getAllRules(string $tenantId): array;

    /**
     * Save a SOD rule.
     *
     * @param SodRuleInterface $rule The rule to save
     * @return void
     */
    public function save(SodRuleInterface $rule): void;

    /**
     * Delete a SOD rule.
     *
     * @param string $id The rule ID
     * @return void
     */
    public function delete(string $id): void;
}
