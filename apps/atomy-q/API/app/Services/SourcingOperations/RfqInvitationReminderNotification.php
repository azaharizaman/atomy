<?php

declare(strict_types=1);

namespace App\Services\SourcingOperations;

use Nexus\Notifier\Services\AbstractNotification;
use Nexus\SourcingOperations\DTOs\RfqInvitationRecord;
use Nexus\SourcingOperations\DTOs\RfqLifecycleRecord;

final readonly class RfqInvitationReminderNotification extends AbstractNotification
{
    public function __construct(
        private RfqLifecycleRecord $rfq,
        private RfqInvitationRecord $invitation,
    ) {
    }

    public function toEmail(): array
    {
        $title = $this->rfq->title ?? $this->rfq->rfqId;

        return [
            'subject' => sprintf('Reminder: RFQ %s is awaiting your response', $title),
            'template' => 'rfq-invitation-reminder',
            'data' => [
                'rfq_id' => $this->rfq->rfqId,
                'rfq_title' => $title,
                'vendor_name' => $this->invitation->vendorName ?? 'Vendor',
                'vendor_email' => $this->invitation->vendorEmail,
                'channel' => $this->invitation->channel ?? 'email',
                'submission_deadline' => $this->rfq->submissionDeadline,
            ],
        ];
    }

    public function toSms(): string
    {
        return '';
    }

    public function toPush(): array
    {
        return [
            'title' => '',
            'body' => '',
        ];
    }

    public function toInApp(): array
    {
        return [
            'title' => '',
            'message' => '',
        ];
    }
}
