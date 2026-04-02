<?php

declare(strict_types=1);

namespace App\Services\SourcingOperations;

use Nexus\SourcingOperations\Contracts\RfqInvitationReminderPortInterface;
use Nexus\SourcingOperations\DTOs\RemindRfqInvitationCommand;
use Nexus\SourcingOperations\DTOs\RfqInvitationRecord;
use Nexus\SourcingOperations\DTOs\RfqLifecycleRecord;
use Psr\Log\LoggerInterface;

final readonly class AtomyRfqInvitationReminder implements RfqInvitationReminderPortInterface
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function sendReminder(RfqLifecycleRecord $rfq, RfqInvitationRecord $invitation, RemindRfqInvitationCommand $command): void
    {
        $this->logger->info('RFQ invitation reminder requested.', [
            'tenant_id' => $command->tenantId,
            'rfq_id' => $rfq->rfqId,
            'invitation_id' => $invitation->id,
            'requested_by' => $command->requestedByPrincipalId,
        ]);
    }
}
