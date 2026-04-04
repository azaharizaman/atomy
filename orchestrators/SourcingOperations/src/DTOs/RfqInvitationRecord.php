<?php

declare(strict_types=1);

namespace Nexus\SourcingOperations\DTOs;

final readonly class RfqInvitationRecord
{
    public string $id;
    public string $tenantId;
    public string $rfqId;
    public ?string $vendorEmail;
    public ?string $vendorName;
    public string $status;
    public ?string $channel;

    public function __construct(
        string $id,
        string $tenantId,
        string $rfqId,
        ?string $vendorEmail,
        ?string $vendorName,
        string $status,
        ?string $channel = null,
    ) {
        if (trim($id) === '') {
            throw new \InvalidArgumentException('Invitation id cannot be empty.');
        }

        if (trim($tenantId) === '') {
            throw new \InvalidArgumentException('Tenant id cannot be empty.');
        }

        if (trim($rfqId) === '') {
            throw new \InvalidArgumentException('RFQ id cannot be empty.');
        }

        if (trim($status) === '') {
            throw new \InvalidArgumentException('Status cannot be empty.');
        }

        $this->id = trim($id);
        $this->tenantId = trim($tenantId);
        $this->rfqId = trim($rfqId);
        $this->vendorEmail = $vendorEmail !== null ? trim($vendorEmail) : null;
        $this->vendorName = $vendorName !== null ? trim($vendorName) : null;
        $this->status = trim($status);
        $this->channel = $channel !== null ? trim($channel) : null;
    }
}
