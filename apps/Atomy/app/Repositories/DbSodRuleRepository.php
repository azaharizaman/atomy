<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\SodRule;
use Nexus\Compliance\Contracts\SodRuleInterface;
use Nexus\Compliance\Contracts\SodRuleRepositoryInterface;
use Nexus\Compliance\Exceptions\RuleNotFoundException;
use Nexus\Compliance\ValueObjects\SeverityLevel;

/**
 * Database implementation of SodRuleRepositoryInterface.
 */
final class DbSodRuleRepository implements SodRuleRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function findById(string $id): ?SodRuleInterface
    {
        return SodRule::find($id);
    }

    /**
     * {@inheritDoc}
     */
    public function findByTransactionType(string $tenantId, string $transactionType): array
    {
        return SodRule::where('tenant_id', $tenantId)
            ->where('transaction_type', $transactionType)
            ->where('is_active', true)
            ->get()
            ->all();
    }

    /**
     * {@inheritDoc}
     */
    public function getActiveRules(string $tenantId): array
    {
        return SodRule::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get()
            ->all();
    }

    /**
     * {@inheritDoc}
     */
    public function getAllRules(string $tenantId): array
    {
        return SodRule::where('tenant_id', $tenantId)
            ->get()
            ->all();
    }

    /**
     * {@inheritDoc}
     */
    public function save(SodRuleInterface $rule): void
    {
        if (!$rule instanceof SodRule) {
            throw new \InvalidArgumentException('Rule must be an instance of SodRule model');
        }

        $rule->save();
    }

    /**
     * {@inheritDoc}
     */
    public function create(
        string $tenantId,
        string $ruleName,
        string $transactionType,
        SeverityLevel $severityLevel,
        ?string $creatorRole = null,
        ?string $approverRole = null,
        array $constraints = []
    ): SodRuleInterface {
        return SodRule::create([
            'tenant_id' => $tenantId,
            'rule_name' => $ruleName,
            'transaction_type' => $transactionType,
            'severity_level' => $severityLevel->value,
            'creator_role' => $creatorRole,
            'approver_role' => $approverRole,
            'constraints' => $constraints,
            'is_active' => true,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $id): void
    {
        $rule = SodRule::find($id);
        
        if ($rule === null) {
            throw new RuleNotFoundException($id);
        }

        $rule->delete();
    }
}
