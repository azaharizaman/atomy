<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Nexus\SourcingOperations\DTOs\DuplicateRfqCommand;
use Nexus\SourcingOperations\DTOs\RfqInvitationRecord;
use Nexus\SourcingOperations\DTOs\RfqLifecycleRecord;
use Nexus\SourcingOperations\Exceptions\DuplicateRfqNumberException;
use App\Models\Rfq;
use App\Services\SourcingOperations\AtomyRfqLifecyclePersist;
use App\Services\SourcingOperations\AtomySourcingTransactionManager;
use App\Services\SourcingOperations\RfqInvitationReminderNotification;
use App\Services\SourcingOperations\RfqInvitationReminderRecipient;
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
        $this->app->make(DatabaseManager::class)->reconnect('sqlite');

        Schema::dropIfExists('rfqs');
        Schema::create('rfqs', static function ($table): void {
            $table->string('id')->primary();
            $table->string('tenant_id')->index();
            $table->string('rfq_number');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->string('department')->nullable();
            $table->string('status')->default('draft');
            $table->string('owner_id');
            $table->string('project_id')->nullable();
            $table->decimal('estimated_value', 15, 2)->nullable();
            $table->decimal('savings_percentage', 5, 2)->nullable();
            $table->timestamp('submission_deadline')->nullable();
            $table->timestamp('closing_date')->nullable();
            $table->timestamp('expected_award_at')->nullable();
            $table->timestamp('technical_review_due_at')->nullable();
            $table->timestamp('financial_review_due_at')->nullable();
            $table->string('payment_terms')->nullable();
            $table->string('evaluation_method')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'rfq_number']);
        });
    }

    public function testTransactionManagerAcceptsInvokableObjectCallables(): void
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

    public function testReminderNotificationUsesNonEmptyFallbacksAndOmitsVendorEmail(): void
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

        $this->assertArrayHasKey('template', $email);
        $this->assertNotEmpty($email['template']);
        $this->assertSame('Reminder: RFQ rfq-1 is awaiting your response', $email['subject']);
        $this->assertSame('rfq-1', $email['data']['rfq_title']);
        $this->assertSame('Vendor', $email['data']['vendor_name']);
        $this->assertSame('email', $email['data']['channel']);
        $this->assertArrayNotHasKey('vendor_email', $email);
        $this->assertArrayNotHasKey('vendor_email', $email['data']);
    }

    public function testReminderRecipientUsesRuntimePreferencesAndUniqueIdentifier(): void
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

    public function testCreateDuplicateUsesHighestNumericSuffixInsteadOfLexicographicOrder(): void
    {
        $tenantId = (string) Str::ulid();
        $source = $this->createRfq($tenantId, 'RFQ-' . date('Y') . '-0001', 'Source RFQ');
        $this->createRfq($tenantId, 'RFQ-' . date('Y') . '-9', 'Older nine');
        $this->createRfq($tenantId, 'RFQ-' . date('Y') . '-10', 'Older ten');

        $persist = new AtomyRfqLifecyclePersist();
        $duplicated = $persist->createDuplicate(
            new RfqLifecycleRecord(
                tenantId: $tenantId,
                rfqId: (string) $source->id,
                status: 'published',
                title: (string) $source->title,
            ),
            new DuplicateRfqCommand(
                tenantId: $tenantId,
                sourceRfqId: (string) $source->id,
            ),
            [],
        );

        $duplicateModel = Rfq::query()->findOrFail($duplicated->rfqId);

        $this->assertSame('RFQ-' . date('Y') . '-0011', $duplicateModel->rfq_number);
    }

    public function testCreateDuplicateWrapsPersistentDuplicateNumberFailuresInDomainException(): void
    {
        $tenantId = (string) Str::ulid();
        $source = $this->createRfq($tenantId, 'RFQ-' . date('Y') . '-0001', 'Trigger duplicate');

        DB::statement(sprintf(
            "CREATE TRIGGER rfqs_force_duplicate BEFORE INSERT ON rfqs
            WHEN NEW.title = '%s'
            BEGIN
                SELECT RAISE(ABORT, 'UNIQUE constraint failed: rfqs.tenant_id, rfqs.rfq_number');
            END;",
            str_replace("'", "''", (string) $source->title),
        ));

        $persist = new AtomyRfqLifecyclePersist();

        $this->expectException(DuplicateRfqNumberException::class);

        $persist->createDuplicate(
            new RfqLifecycleRecord(
                tenantId: $tenantId,
                rfqId: (string) $source->id,
                status: 'published',
                title: (string) $source->title,
            ),
            new DuplicateRfqCommand(
                tenantId: $tenantId,
                sourceRfqId: (string) $source->id,
            ),
            [],
        );
    }

    private function createRfq(string $tenantId, string $rfqNumber, string $title): Rfq
    {
        /** @var Rfq $rfq */
        $rfq = Rfq::query()->create([
            'id' => (string) Str::ulid(),
            'tenant_id' => $tenantId,
            'rfq_number' => $rfqNumber,
            'title' => $title,
            'status' => 'published',
            'owner_id' => (string) Str::ulid(),
        ]);

        return $rfq;
    }
}
