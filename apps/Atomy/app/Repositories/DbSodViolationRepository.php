<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\SodViolation;
use Nexus\Compliance\Contracts\SodViolationInterface;
use Nexus\Compliance\Contracts\SodViolationRepositoryInterface;

/**
 * Database implementation of SodViolationRepositoryInterface.
 */
final class DbSodViolationRepository implements SodViolationRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function findById(string $id): ?SodViolationInterface
    {
        return SodViolation::find($id);
    }

    /**
     * {@inheritDoc}
     */
    public function getViolationsByRule(string $ruleId): array
    {
        return SodViolation::where('rule_id', $ruleId)
            ->get()
            ->all();
    }

    /**
     * {@inheritDoc}
     */
    public function getUnresolvedViolations(string $tenantId): array
    {
        return SodViolation::where('tenant_id', $tenantId)
            ->where('is_resolved', false)
            ->get()
            ->all();
    }

    /**
     * {@inheritDoc}
     */
    public function getViolationsByTransaction(string $transactionId): array
    {
        return SodViolation::where('transaction_id', $transactionId)
            ->get()
            ->all();
    }

    /**
     * {@inheritDoc}
     */
    public function save(SodViolationInterface $violation): void
    {
        if (!$violation instanceof SodViolation) {
            throw new \InvalidArgumentException('Violation must be an instance of SodViolation model');
        }

        $violation->save();
    }

    /**
     * {@inheritDoc}
     */
    public function create(
        string $tenantId,
        string $ruleId,
        string $transactionId,
        string $transactionType,
        string $creatorId,
        string $approverId,
        ?string $violationDetails = null
    ): SodViolationInterface {
        return SodViolation::create([
            'tenant_id' => $tenantId,
            'rule_id' => $ruleId,
            'transaction_id' => $transactionId,
            'transaction_type' => $transactionType,
            'creator_id' => $creatorId,
            'approver_id' => $approverId,
            'violation_details' => $violationDetails,
            'violated_at' => new \DateTimeImmutable(),
            'is_resolved' => false,
        ]);
    }
}
