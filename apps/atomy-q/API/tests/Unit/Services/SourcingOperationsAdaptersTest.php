<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\SourcingOperations\AtomySourcingTransactionManager;
use App\Services\SourcingOperations\RfqInvitationReminderNotification;
use App\Services\SourcingOperations\RfqInvitationReminderRecipient;
use Illuminate\Database\DatabaseManager;
use Nexus\SourcingOperations\DTOs\RfqInvitationRecord;
use Nexus\SourcingOperations\DTOs\RfqLifecycleRecord;
use Tests\TestCase;

final class SourcingOperationsAdaptersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        $this->app->make(DatabaseManager::class)->purge('sqlite');
    }

    public function test_transaction_manager_accepts_invokable_object_callables(): void
    {
        $manager = new AtomySourcingTransactionManager();
        $callback = new class
        {
            public function __invoke(): string
            {
                return 'done';
            }
        };

        $this->assertSame('done', $manager->transaction($callback));
    }

    public function test_reminder_notification_uses_non_empty_fallbacks_and_omits_vendor_email(): void
    {
        $notification = new RfqInvitationReminderNotification(
            new RfqLifecycleRecord(
                tenantId: 'tenant-1',
                rfqId: 'rfq-1',
                status: 'published',
                title: '   ',
                submissionDeadline: '2026-04-30T10:00:00+00:00',
            ),
            new RfqInvitationRecord(
                id: 'inv-1',
                tenantId: 'tenant-1',
                rfqId: 'rfq-1',
                vendorEmail: '   ',
                vendorName: ' ',
                status: 'pending',
                channel: ' ',
            ),
        );

        $email = $notification->toEmail();

        $this->assertSame('Reminder: RFQ rfq-1 is awaiting your response', $email['subject']);
        $this->assertSame('rfq-1', $email['data']['rfq_title']);
        $this->assertSame('Vendor', $email['data']['vendor_name']);
        $this->assertSame('email', $email['data']['channel']);
        $this->assertArrayNotHasKey('vendor_email', $email['data']);
    }

    public function test_reminder_recipient_uses_runtime_preferences_and_unique_identifier(): void
    {
        config()->set('app.timezone', 'Asia/Kuala_Lumpur');
        $this->app->setLocale('ms');

        $recipient = new RfqInvitationReminderRecipient(
            new RfqInvitationRecord(
                id: 'inv-2',
                tenantId: 'tenant-1',
                rfqId: 'rfq-1',
                vendorEmail: ' ',
                vendorName: 'Vendor Co',
                status: 'pending',
                channel: 'email',
            ),
        );

        $this->assertNull($recipient->getNotificationEmail());
        $this->assertSame('ms', $recipient->getNotificationLocale());
        $this->assertSame('Asia/Kuala_Lumpur', $recipient->getNotificationTimezone());
        $this->assertSame('inv-2:Vendor Co', $recipient->getNotificationIdentifier());
    }
}
