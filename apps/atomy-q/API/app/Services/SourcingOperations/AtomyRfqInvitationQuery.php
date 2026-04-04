<?php

declare(strict_types=1);

namespace App\Services\SourcingOperations;

use App\Models\VendorInvitation;
use Nexus\SourcingOperations\Contracts\RfqInvitationQueryPortInterface;
use Nexus\SourcingOperations\DTOs\RfqInvitationRecord;

final readonly class AtomyRfqInvitationQuery implements RfqInvitationQueryPortInterface
{
    public function findInvitationByTenantAndId(string $tenantId, string $rfqId, string $invitationId): ?RfqInvitationRecord
    {
        $invitation = VendorInvitation::query()
            ->where('tenant_id', $tenantId)
            ->where('rfq_id', $rfqId)
            ->where('id', $invitationId)
            ->first();

        if ($invitation === null) {
            return null;
        }

        return new RfqInvitationRecord(
            id: (string) $invitation->id,
            tenantId: (string) $invitation->tenant_id,
            rfqId: (string) $invitation->rfq_id,
            vendorEmail: $invitation->vendor_email,
            vendorName: $invitation->vendor_name,
            status: (string) $invitation->status,
            channel: $invitation->channel,
        );
    }
}
