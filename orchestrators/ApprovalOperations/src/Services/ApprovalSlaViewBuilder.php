<?php

declare(strict_types=1);

namespace Nexus\ApprovalOperations\Services;

use Nexus\ApprovalOperations\Contracts\ApprovalInstanceQueryInterface;
use Nexus\ApprovalOperations\DTOs\ApprovalSlaView;
use Nexus\ApprovalOperations\Exceptions\OperationalApprovalNotFoundException;

final readonly class ApprovalSlaViewBuilder
{
    public function __construct(
        private ApprovalInstanceQueryInterface $instancesQuery,
    ) {
    }

    public function build(string $tenantId, string $instanceId): ApprovalSlaView
    {
        $instance = $this->instancesQuery->findById($tenantId, $instanceId);
        if ($instance === null) {
            throw OperationalApprovalNotFoundException::forInstance($instanceId);
        }

        return new ApprovalSlaView(dueAtIso8601: null, secondsRemaining: null);
    }
}
