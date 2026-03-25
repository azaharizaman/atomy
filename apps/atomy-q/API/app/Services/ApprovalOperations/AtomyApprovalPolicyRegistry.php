<?php

declare(strict_types=1);

namespace App\Services\ApprovalOperations;

use App\Models\PolicyDefinitionRecord;
use Nexus\PolicyEngine\Contracts\PolicyDefinitionDecoderInterface;
use Nexus\PolicyEngine\Contracts\PolicyRegistryInterface;
use Nexus\PolicyEngine\Domain\PolicyDefinition;
use Nexus\PolicyEngine\Exceptions\PolicyNotFound;
use Nexus\PolicyEngine\ValueObjects\PolicyId;
use Nexus\PolicyEngine\ValueObjects\PolicyVersion;
use Nexus\PolicyEngine\ValueObjects\TenantId;

final readonly class AtomyApprovalPolicyRegistry implements PolicyRegistryInterface
{
    public function __construct(
        private PolicyDefinitionDecoderInterface $decoder,
    ) {
    }

    public function get(PolicyId $id, PolicyVersion $version, TenantId $tenantId): PolicyDefinition
    {
        $row = PolicyDefinitionRecord::query()
            ->whereRaw('LOWER(tenant_id) = ?', [\mb_strtolower($tenantId->value)])
            ->where('policy_id', $id->value)
            ->where('policy_version', $version->value)
            ->first();

        if ($row === null) {
            throw PolicyNotFound::for($id, $version);
        }

        return $this->decoder->decode((string) $row->payload);
    }
}
