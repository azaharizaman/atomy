<?php

declare(strict_types=1);

namespace Nexus\ApprovalOperations\Services;

use Nexus\ApprovalOperations\Contracts\ApprovalInstanceQueryInterface;
use Nexus\ApprovalOperations\DTOs\ApprovalSlaView;
use Nexus\ApprovalOperations\Exceptions\OperationalApprovalNotFoundException;

final readonly class ApprovalSlaViewBuilder
{
    private const int DEFAULT_SLA_SECONDS = 172800; // 48 hours

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

        return $this->buildFromInstance($instance);
    }

    public function buildFromInstance(\Nexus\ApprovalOperations\DTOs\ApprovalInstanceReadModel $instance): ApprovalSlaView
    {
        $dueAt = $instance->dueAt;
        if ($dueAt === null && $instance->createdAt !== null) {
            $dueAt = $instance->createdAt->add(new \DateInterval('PT' . self::DEFAULT_SLA_SECONDS . 'S'));
        }

        if ($dueAt === null) {
            return new ApprovalSlaView(dueAtIso8601: null, secondsRemaining: null);
        }

        $now = new \DateTimeImmutable('now', $dueAt->getTimezone());
        $remaining = $dueAt->getTimestamp() - $now->getTimestamp();

        return new ApprovalSlaView(
            dueAtIso8601: $dueAt->format(DATE_ATOM),
            secondsRemaining: max(0, $remaining),
        );
    }
}
