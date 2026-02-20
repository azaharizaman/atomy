<?php

declare(strict_types=1);

namespace Nexus\Treasury\Services;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;
use Nexus\Treasury\Contracts\TreasuryPolicyInterface;
use Nexus\Treasury\Contracts\TreasuryPolicyQueryInterface;
use Nexus\Treasury\Contracts\TreasuryPolicyPersistInterface;
use Nexus\Treasury\Entities\TreasuryPolicy;
use Nexus\Treasury\Enums\TreasuryStatus;
use Nexus\Treasury\Exceptions\TreasuryPolicyNotFoundException;
use Nexus\Treasury\ValueObjects\TreasuryPolicyData;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final readonly class TreasuryPolicyService
{
    public function __construct(
        private TreasuryPolicyQueryInterface $query,
        private TreasuryPolicyPersistInterface $persist,
        private LoggerInterface $logger = new NullLogger()
    ) {}

    public function create(string $tenantId, TreasuryPolicyData $data): TreasuryPolicyInterface
    {
        $now = new DateTimeImmutable();
        $effectiveFrom = $data->effectiveFrom ?? $now;

        $policy = new TreasuryPolicy(
            id: $this->generateId(),
            tenantId: $tenantId,
            name: $data->name,
            minimumCashBalance: $data->minimumCashBalance,
            maximumSingleTransaction: $data->maximumSingleTransaction,
            approvalThreshold: $data->approvalThreshold,
            approvalRequired: $data->approvalRequired,
            status: TreasuryStatus::PENDING,
            effectiveFrom: $effectiveFrom,
            effectiveTo: $data->effectiveTo,
            description: $data->description,
            createdAt: $now,
            updatedAt: $now
        );

        $this->persist->save($policy);

        $this->logger->info('Treasury policy created', [
            'policy_id' => $policy->getId(),
            'tenant_id' => $tenantId,
            'name' => $data->name,
        ]);

        return $policy;
    }

    public function activate(string $policyId): TreasuryPolicyInterface
    {
        $policy = $this->query->findOrFail($policyId);

        $activatedPolicy = new TreasuryPolicy(
            id: $policy->getId(),
            tenantId: $policy->getTenantId(),
            name: $policy->getName(),
            minimumCashBalance: $policy->getMinimumCashBalance(),
            maximumSingleTransaction: $policy->getMaximumSingleTransaction(),
            approvalThreshold: $policy->getApprovalThreshold(),
            approvalRequired: $policy->isApprovalRequired(),
            status: TreasuryStatus::ACTIVE,
            effectiveFrom: $policy->getEffectiveFrom(),
            effectiveTo: $policy->getEffectiveTo(),
            description: $policy->getDescription(),
            createdAt: $policy->getCreatedAt(),
            updatedAt: new DateTimeImmutable()
        );

        $this->persist->save($activatedPolicy);

        $this->logger->info('Treasury policy activated', [
            'policy_id' => $policyId,
            'tenant_id' => $policy->getTenantId(),
        ]);

        return $activatedPolicy;
    }

    public function deactivate(string $policyId): TreasuryPolicyInterface
    {
        $policy = $this->query->findOrFail($policyId);

        $deactivatedPolicy = new TreasuryPolicy(
            id: $policy->getId(),
            tenantId: $policy->getTenantId(),
            name: $policy->getName(),
            minimumCashBalance: $policy->getMinimumCashBalance(),
            maximumSingleTransaction: $policy->getMaximumSingleTransaction(),
            approvalThreshold: $policy->getApprovalThreshold(),
            approvalRequired: $policy->isApprovalRequired(),
            status: TreasuryStatus::INACTIVE,
            effectiveFrom: $policy->getEffectiveFrom(),
            effectiveTo: $policy->getEffectiveTo(),
            description: $policy->getDescription(),
            createdAt: $policy->getCreatedAt(),
            updatedAt: new DateTimeImmutable()
        );

        $this->persist->save($deactivatedPolicy);

        $this->logger->info('Treasury policy deactivated', [
            'policy_id' => $policyId,
            'tenant_id' => $policy->getTenantId(),
        ]);

        return $deactivatedPolicy;
    }

    public function update(string $policyId, TreasuryPolicyData $data): TreasuryPolicyInterface
    {
        $existing = $this->query->findOrFail($policyId);

        $updatedPolicy = new TreasuryPolicy(
            id: $existing->getId(),
            tenantId: $existing->getTenantId(),
            name: $data->name,
            minimumCashBalance: $data->minimumCashBalance,
            maximumSingleTransaction: $data->maximumSingleTransaction,
            approvalThreshold: $data->approvalThreshold,
            approvalRequired: $data->approvalRequired,
            status: $existing->getStatus(),
            effectiveFrom: $data->effectiveFrom ?? $existing->getEffectiveFrom(),
            effectiveTo: $data->effectiveTo,
            description: $data->description,
            createdAt: $existing->getCreatedAt(),
            updatedAt: new DateTimeImmutable()
        );

        $this->persist->save($updatedPolicy);

        $this->logger->info('Treasury policy updated', [
            'policy_id' => $policyId,
            'tenant_id' => $existing->getTenantId(),
        ]);

        return $updatedPolicy;
    }

    public function get(string $policyId): TreasuryPolicyInterface
    {
        return $this->query->findOrFail($policyId);
    }

    public function getActive(string $tenantId): ?TreasuryPolicyInterface
    {
        $activePolicies = $this->query->findActiveByTenantId($tenantId);

        foreach ($activePolicies as $policy) {
            if ($policy->isEffective(new DateTimeImmutable())) {
                return $policy;
            }
        }

        return null;
    }

    public function findByTenant(string $tenantId): array
    {
        return $this->query->findByTenantId($tenantId);
    }

    public function delete(string $policyId): void
    {
        $policy = $this->query->find($policyId);

        if ($policy === null) {
            throw TreasuryPolicyNotFoundException::forId($policyId);
        }

        $this->persist->delete($policyId);

        $this->logger->info('Treasury policy deleted', [
            'policy_id' => $policyId,
            'tenant_id' => $policy->getTenantId(),
        ]);
    }

    private function generateId(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40);
        $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80);
        $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
        return 'TRE-POL-' . $uuid;
    }
}
