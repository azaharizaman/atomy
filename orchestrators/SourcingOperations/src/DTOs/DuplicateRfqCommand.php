<?php

declare(strict_types=1);

namespace Nexus\SourcingOperations\DTOs;

use Nexus\Sourcing\ValueObjects\RfqDuplicationOptions;

final readonly class DuplicateRfqCommand
{
    public string $tenantId;
    public string $sourceRfqId;
    public RfqDuplicationOptions $options;

    public function __construct(
        string $tenantId,
        string $sourceRfqId,
        ?RfqDuplicationOptions $options = null,
    ) {
        if (trim($tenantId) === '') {
            throw new \InvalidArgumentException('Tenant id cannot be empty.');
        }

        if (trim($sourceRfqId) === '') {
            throw new \InvalidArgumentException('Source RFQ id cannot be empty.');
        }

        $this->tenantId = trim($tenantId);
        $this->sourceRfqId = trim($sourceRfqId);
        $this->options = $options ?? new RfqDuplicationOptions();
    }
}
