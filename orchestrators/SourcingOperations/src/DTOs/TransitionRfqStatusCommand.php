<?php

declare(strict_types=1);

namespace Nexus\SourcingOperations\DTOs;

final readonly class TransitionRfqStatusCommand
{
    public string $tenantId;
    public string $rfqId;
    public string $status;

    public function __construct(
        string $tenantId,
        string $rfqId,
        string $status,
    ) {
        if (trim($tenantId) === '') {
            throw new \InvalidArgumentException('Tenant id cannot be empty.');
        }

        if (trim($rfqId) === '') {
            throw new \InvalidArgumentException('RFQ id cannot be empty.');
        }

        if (trim($status) === '') {
            throw new \InvalidArgumentException('Status cannot be empty.');
        }

        $this->tenantId = trim($tenantId);
        $this->rfqId = trim($rfqId);
        $this->status = trim($status);
    }
}
