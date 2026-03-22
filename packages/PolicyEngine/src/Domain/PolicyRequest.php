<?php

declare(strict_types=1);

namespace Nexus\PolicyEngine\Domain;

use Nexus\PolicyEngine\ValueObjects\PolicyId;
use Nexus\PolicyEngine\ValueObjects\PolicyVersion;
use Nexus\PolicyEngine\ValueObjects\TenantId;

final readonly class PolicyRequest
{
    /**
     * @param array<string, mixed> $subject
     * @param array<string, mixed> $resource
     * @param array<string, mixed> $context
     */
    public function __construct(
        public TenantId $tenantId,
        public PolicyId $policyId,
        public PolicyVersion $policyVersion,
        public string $action,
        public array $subject = [],
        public array $resource = [],
        public array $context = [],
    ) {
        if (trim($this->action) === '') {
            throw new \InvalidArgumentException('PolicyRequest action cannot be empty.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function evaluationContext(): array
    {
        return array_merge(
            $this->subject,
            $this->resource,
            $this->context,
            ['action' => $this->action]
        );
    }
}
