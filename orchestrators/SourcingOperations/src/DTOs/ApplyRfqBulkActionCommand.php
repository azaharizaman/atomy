<?php

declare(strict_types=1);

namespace Nexus\SourcingOperations\DTOs;

final readonly class ApplyRfqBulkActionCommand
{
    public string $tenantId;
    public string $action;
    /**
     * @var array<int, string>
     */
    public array $rfqIds;

    /**
     * @param array<int, string> $rfqIds
     */
    public function __construct(
        string $tenantId,
        string $action,
        array $rfqIds,
    ) {
        if (trim($tenantId) === '') {
            throw new \InvalidArgumentException('Tenant id cannot be empty.');
        }

        if (trim($action) === '') {
            throw new \InvalidArgumentException('Action cannot be empty.');
        }

        if ($rfqIds === []) {
            throw new \InvalidArgumentException('RFQ ids cannot be empty.');
        }

        foreach ($rfqIds as $rfqId) {
            if (trim($rfqId) === '') {
                throw new \InvalidArgumentException('RFQ ids cannot contain empty values.');
            }
        }

        $this->tenantId = trim($tenantId);
        $this->action = trim($action);
        $this->rfqIds = array_values(array_map('trim', $rfqIds));
    }
}
