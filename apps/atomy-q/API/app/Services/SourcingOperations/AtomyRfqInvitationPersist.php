<?php

declare(strict_types=1);

namespace App\Services\SourcingOperations;

use App\Models\VendorInvitation;
use Nexus\SourcingOperations\Contracts\RfqInvitationPersistPortInterface;
use Nexus\SourcingOperations\DTOs\RemindRfqInvitationCommand;
use Nexus\SourcingOperations\DTOs\RfqInvitationRecord;

final readonly class AtomyRfqInvitationPersist implements RfqInvitationPersistPortInterface
{
    public function markInvitationReminded(RfqInvitationRecord $invitation, RemindRfqInvitationCommand $command): RfqInvitationRecord
    {
        /** @var VendorInvitation $model */
        $model = VendorInvitation::query()
            ->where('tenant_id', $command->tenantId)
            ->where('rfq_id', $command->rfqId)
            ->where('id', $invitation->id)
            ->firstOrFail();

        $model->reminded_at = now();
        $model->save();

        return new RfqInvitationRecord(
            id: (string) $model->id,
            tenantId: (string) $model->tenant_id,
            rfqId: (string) $model->rfq_id,
            vendorEmail: $model->vendor_email,
            vendorName: $model->vendor_name,
            status: (string) $model->status,
        );
    }
}
