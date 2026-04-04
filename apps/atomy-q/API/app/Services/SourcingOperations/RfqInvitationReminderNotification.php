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
        $title = $this->nonEmptyOrFallback($this->rfq->title, $this->rfq->rfqId);
        $vendorName = $this->nonEmptyOrFallback($this->invitation->vendorName, 'Vendor');
        $channel = $this->nonEmptyOrFallback($this->invitation->channel, 'email');

        return [
            'subject' => sprintf('Reminder: RFQ %s is awaiting your response', $title),
            'template' => 'rfq-invitation-reminder',
            'data' => [
                'rfq_id' => $this->rfq->rfqId,
                'rfq_title' => $title,
                'vendor_name' => $vendorName,
                'channel' => $channel,
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

    private function nonEmptyOrFallback(?string $value, string $fallback): string
    {
        if ($value === null || trim($value) === '') {
            return $fallback;
        }

        return trim($value);
    }
}
