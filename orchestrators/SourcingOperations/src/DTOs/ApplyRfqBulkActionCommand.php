<?php

declare(strict_types=1);

namespace Nexus\SourcingOperations\DTOs;

use Nexus\SourcingOperations\Exceptions\CommandValidationException;

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
            throw new CommandValidationException('Tenant id cannot be empty.');
        }

        if (trim($action) === '') {
            throw new CommandValidationException('Action cannot be empty.');
        }

        if ($rfqIds === []) {
            throw new CommandValidationException('RFQ ids cannot be empty.');
        }

        foreach ($rfqIds as $rfqId) {
            if (!is_string($rfqId)) {
                throw new CommandValidationException('RFQ ids must be strings.');
            }
            if (trim($rfqId) === '') {
                throw new CommandValidationException('RFQ ids cannot contain empty values.');
            }
        }

        $this->tenantId = trim($tenantId);
        $this->action = trim($action);
        $this->rfqIds = array_values(array_map('trim', $rfqIds));
    }
}
