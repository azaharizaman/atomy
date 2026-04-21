<?php

declare(strict_types=1);

namespace Nexus\Adapter\Laravel\Vendor\Tests\Feature;

use DateTimeImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Nexus\Adapter\Laravel\Vendor\Exceptions\UnreadableVendorRecordException;
use Nexus\Adapter\Laravel\Vendor\Models\EloquentVendor;
use Nexus\Adapter\Laravel\Vendor\Repositories\EloquentVendorRepository;
use Nexus\Adapter\Laravel\Vendor\Tests\TestCase;
use Nexus\Vendor\Contracts\VendorInterface;
use Nexus\Vendor\Contracts\VendorPersistInterface;
use Nexus\Vendor\Contracts\VendorQueryInterface;
use Nexus\Vendor\Enums\VendorStatus;
use Nexus\Vendor\Exceptions\InvalidVendorStatusTransition;
use Nexus\Vendor\ValueObjects\RegistrationNumber;
use Nexus\Vendor\ValueObjects\VendorApprovalRecord;
use Nexus\Vendor\ValueObjects\VendorDisplayName;
use Nexus\Vendor\ValueObjects\VendorId;
use Nexus\Vendor\ValueObjects\VendorLegalName;

final class VendorRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function testSaveAndFetchVendorByTenantAndId(): void
    {
        $tenantId = (string) Str::ulid();

        $repo = $this->repository();
        $saved = $repo->save($tenantId, $this->makeVendor());

        $found = $repo->findByTenantAndId($tenantId, (string) $saved->getId());

        $this->assertNotNull($found);
        $this->assertSame($saved->getId()->getValue(), $found->getId()->getValue());
        $this->assertSame($saved->getLegalName()->getValue(), $found->getLegalName()->getValue());
        $this->assertSame($saved->getDisplayName()->getValue(), $found->getDisplayName()->getValue());
        $this->assertSame($saved->getRegistrationNumber()->getValue(), $found->getRegistrationNumber()->getValue());
        $this->assertSame($saved->getCountryOfRegistration(), $found->getCountryOfRegistration());
        $this->assertSame($saved->getStatus(), $found->getStatus());
    }

    public function testSearchFiltersVendorsByTenant(): void
    {
        $tenantA = (string) Str::ulid();
        $tenantB = (string) Str::ulid();

        $repo = $this->repository();

        $repo->save($tenantA, $this->makeVendor(displayName: 'Alpha Sdn Bhd'));
        $repo->save($tenantA, $this->makeVendor(displayName: 'Bravo Trading Sdn Bhd'));
        $repo->save($tenantB, $this->makeVendor(displayName: 'Charlie Enterprise Sdn Bhd'));

        $found = $repo->search($tenantA);

        $this->assertCount(2, $found);
        foreach ($found as $vendor) {
            $this->assertSame(
                strtolower($tenantA),
                $this->app['db']->table('vendors')->where('id', (string) $vendor->getId())->value('tenant_id'),
            );
        }
    }

    public function testCrossTenantLookupReturnsNull(): void
    {
        $tenantA = (string) Str::ulid();
        $tenantB = (string) Str::ulid();

        $repo = $this->repository();
        $saved = $repo->save($tenantA, $this->makeVendor());

        $this->assertNull($repo->findByTenantAndId($tenantB, (string) $saved->getId()));
        $this->assertNotNull($repo->findByTenantAndId($tenantA, (string) $saved->getId()));
    }

    public function testRepositoryReadsApiCreatedLowercaseTenantRows(): void
    {
        $tenantId = (string) Str::ulid();
        $vendorId = (string) Str::ulid();

        DB::table('vendors')->insert([
            'id' => $vendorId,
            'tenant_id' => strtolower($tenantId),
            'name' => 'API Created Holdings Sdn Bhd',
            'trading_name' => 'API Created',
            'registration_number' => '202601234501',
            'tax_id' => null,
            'country_code' => 'MY',
            'email' => 'api-created@example.com',
            'phone' => null,
            'status' => 'draft',
            'onboarded_at' => null,
            'metadata' => null,
            'legal_name' => 'API Created Holdings Sdn Bhd',
            'display_name' => 'API Created',
            'country_of_registration' => 'MY',
            'primary_contact_name' => 'API Created',
            'primary_contact_email' => 'api-created@example.com',
            'primary_contact_phone' => '',
            'approved_by_user_id' => '',
            'approved_at' => null,
            'approval_note' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $repo = $this->repository();

        $found = $repo->findByTenantAndId($tenantId, $vendorId);

        $this->assertNotNull($found);
        $this->assertSame($vendorId, (string) $found->getId());
    }

    public function testStatusUpdatePersistsApprovalMetadata(): void
    {
        $tenantId = (string) Str::ulid();
        $repo = $this->repository();
        $saved = $repo->save($tenantId, $this->makeVendor(status: VendorStatus::UnderReview));

        $approvalRecord = new VendorApprovalRecord(
            'user-123',
            new DateTimeImmutable('2026-04-21T08:30:00+00:00'),
            'Approved after compliance review',
        );

        $updated = $repo->updateStatus($tenantId, (string) $saved->getId(), VendorStatus::Approved, $approvalRecord);

        $this->assertSame(VendorStatus::Approved, $updated->getStatus());
        $this->assertNotNull($updated->getApprovalRecord());
        $this->assertSame('user-123', $updated->getApprovalRecord()->getApprovedByUserId());
        $this->assertSame('2026-04-21T08:30:00+00:00', $updated->getApprovalRecord()->getApprovedAt()->format(DATE_ATOM));
        $this->assertSame('Approved after compliance review', $updated->getApprovalRecord()->getApprovalNote());

        $retained = $repo->updateStatus($tenantId, (string) $saved->getId(), VendorStatus::Suspended, null);

        $this->assertSame(VendorStatus::Suspended, $retained->getStatus());
        $this->assertNotNull($retained->getApprovalRecord());
        $this->assertSame('user-123', $retained->getApprovalRecord()->getApprovedByUserId());
        $this->assertSame('2026-04-21T08:30:00+00:00', $retained->getApprovalRecord()->getApprovedAt()->format(DATE_ATOM));
        $this->assertSame('Approved after compliance review', $retained->getApprovalRecord()->getApprovalNote());
    }

    public function testStatusUpdateRejectsInvalidTransition(): void
    {
        $tenantId = (string) Str::ulid();
        $repo = $this->repository();
        $saved = $repo->save($tenantId, $this->makeVendor(status: VendorStatus::Draft));

        $approvalRecord = new VendorApprovalRecord(
            'user-123',
            new DateTimeImmutable('2026-04-21T08:30:00+00:00'),
            'Approved after compliance review',
        );

        $this->expectException(InvalidVendorStatusTransition::class);
        $this->expectExceptionMessage('Cannot transition vendor status from Draft to Approved.');

        $repo->updateStatus($tenantId, (string) $saved->getId(), VendorStatus::Approved, $approvalRecord);
    }

    public function testFindThrowsForPartiallyPopulatedApprovalMetadata(): void
    {
        Schema::dropIfExists('vendors');
        $this->createLegacyVendorsTable();
        $this->loadVendorMigration()->up();

        $tenantId = (string) Str::ulid();
        $vendorId = (string) Str::ulid();

        DB::table('vendors')->insert([
            'id' => $vendorId,
            'tenant_id' => $tenantId,
            'name' => 'Broken Approval Vendor',
            'trading_name' => 'Broken Approval',
            'registration_number' => '202601234500',
            'tax_id' => null,
            'country_code' => 'MY',
            'email' => 'broken@example.com',
            'phone' => '+60111111111',
            'status' => 'approved',
            'onboarded_at' => null,
            'metadata' => null,
            'legal_name' => 'Broken Approval Vendor',
            'display_name' => 'Broken Approval',
            'country_of_registration' => 'MY',
            'primary_contact_name' => 'Broken Approval',
            'primary_contact_email' => 'broken@example.com',
            'primary_contact_phone' => '+60111111111',
            'approved_by_user_id' => 'user-123',
            'approved_at' => null,
            'approval_note' => 'Approval metadata is incomplete',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $repo = $this->repository();

        $this->expectException(UnreadableVendorRecordException::class);
        $repo->findByTenantAndId($tenantId, $vendorId);
    }

    public function testSaveAcceptsPlainVendorInterfaceImplementations(): void
    {
        $tenantId = (string) Str::ulid();
        $repo = $this->repository();
        $vendor = $this->makePlainVendor();

        $saved = $repo->save($tenantId, $vendor);

        $this->assertSame($vendor->getId()->getValue(), $saved->getId()->getValue());
        $this->assertSame('Plain Vendor Holdings', $saved->getLegalName()->getValue());
        $this->assertSame('Plain Vendor', $saved->getDisplayName()->getValue());
        $this->assertSame(VendorStatus::Draft, $saved->getStatus());
    }

    public function testSavePreservesExistingApprovalMetadataWhenNotReplaced(): void
    {
        $tenantId = (string) Str::ulid();
        $repo = $this->repository();
        $vendorId = (string) Str::ulid();

        $saved = $repo->save($tenantId, $this->makePlainVendor(
            vendorId: $vendorId,
            approvalRecord: new VendorApprovalRecord(
                'user-123',
                new DateTimeImmutable('2026-04-21T08:30:00+00:00'),
                'Approved after compliance review',
            ),
        ));

        $updated = $repo->save($tenantId, $this->makePlainVendor(
            vendorId: $vendorId,
            legalName: 'Plain Vendor Holdings Updated',
            displayName: 'Plain Vendor Updated',
        ));

        $this->assertSame('user-123', $saved->getApprovalRecord()->getApprovedByUserId());
        $this->assertNotNull($updated->getApprovalRecord());
        $this->assertSame('user-123', $updated->getApprovalRecord()->getApprovedByUserId());
        $this->assertSame('2026-04-21T08:30:00+00:00', $updated->getApprovalRecord()->getApprovedAt()->format(DATE_ATOM));
        $this->assertSame('Approved after compliance review', $updated->getApprovalRecord()->getApprovalNote());
        $this->assertSame('Plain Vendor Holdings Updated', $updated->getLegalName()->getValue());
    }

    public function testVendorMigrationIsIdempotentAndBackfillsLegacyRows(): void
    {
        Schema::dropIfExists('vendors');
        $this->createLegacyVendorsTable();

        DB::table('vendors')->insert([
            'id' => (string) Str::ulid(),
            'tenant_id' => (string) Str::ulid(),
            'name' => 'Legacy Holdings Sdn Bhd',
            'trading_name' => 'Legacy Trading',
            'registration_number' => null,
            'tax_id' => 'TAX-LEGACY-001',
            'country_code' => 'SG',
            'email' => 'legacy@example.com',
            'phone' => '+6512345678',
            'status' => 'active',
            'onboarded_at' => null,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('vendors')->insert([
            'id' => (string) Str::ulid(),
            'tenant_id' => (string) Str::ulid(),
            'name' => 'Inactive Holdings Sdn Bhd',
            'trading_name' => null,
            'registration_number' => '201901234568',
            'tax_id' => null,
            'country_code' => 'MY',
            'email' => 'inactive@example.com',
            'phone' => null,
            'status' => 'inactive',
            'onboarded_at' => null,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = $this->loadVendorMigration();
        $migration->up();
        $migration->up();

        $rows = DB::table('vendors')->orderBy('name')->get();

        $this->assertCount(2, $rows);

        $legacyRow = $rows->firstWhere('name', 'Legacy Holdings Sdn Bhd');
        $inactiveRow = $rows->firstWhere('name', 'Inactive Holdings Sdn Bhd');

        $this->assertNotNull($legacyRow);
        $this->assertSame('Legacy Holdings Sdn Bhd', $legacyRow->legal_name);
        $this->assertSame('Legacy Trading', $legacyRow->display_name);
        $this->assertSame('SG', $legacyRow->country_of_registration);
        $this->assertSame('TAX-LEGACY-001', $legacyRow->registration_number);
        $this->assertSame('Legacy Holdings Sdn Bhd', $legacyRow->primary_contact_name);
        $this->assertSame('legacy@example.com', $legacyRow->primary_contact_email);
        $this->assertSame('+6512345678', $legacyRow->primary_contact_phone);
        $this->assertSame('approved', $legacyRow->status);

        $this->assertNotNull($inactiveRow);
        $this->assertSame('suspended', $inactiveRow->status);
        $this->assertTrue(Schema::hasIndex('vendors', ['tenant_id', 'status']));
        $this->assertTrue(Schema::hasIndex('vendors', ['tenant_id', 'display_name']));
    }

    public function testVendorCreateFromScratchUsesRequiredCoreColumns(): void
    {
        Schema::dropIfExists('vendors');

        $this->loadVendorMigration()->up();

        $columns = $this->vendorTableColumns();

        $this->assertSame(1, $columns['legal_name']->notnull);
        $this->assertSame(1, $columns['display_name']->notnull);
        $this->assertSame(1, $columns['registration_number']->notnull);
        $this->assertSame(1, $columns['country_of_registration']->notnull);
        $this->assertSame(1, $columns['primary_contact_name']->notnull);
        $this->assertSame(1, $columns['primary_contact_email']->notnull);
        $this->assertSame(1, $columns['approved_by_user_id']->notnull);
        $this->assertSame(0, $columns['approved_at']->notnull);
        $this->assertSame(0, $columns['approval_note']->notnull);
    }

    public function testFindThrowsForUnreadableRow(): void
    {
        Schema::dropIfExists('vendors');
        $this->createLegacyVendorsTable();

        $this->loadVendorMigration()->up();

        $tenantId = (string) Str::ulid();
        $vendorId = (string) Str::ulid();

        DB::table('vendors')->insert([
            'id' => $vendorId,
            'tenant_id' => $tenantId,
            'name' => '',
            'trading_name' => '',
            'registration_number' => '',
            'tax_id' => null,
            'country_code' => 'MY',
            'email' => '',
            'phone' => null,
            'status' => 'active',
            'onboarded_at' => null,
            'metadata' => null,
            'legal_name' => '',
            'display_name' => '',
            'country_of_registration' => 'MY',
            'primary_contact_name' => '',
            'primary_contact_email' => '',
            'primary_contact_phone' => '',
            'approved_by_user_id' => '',
            'approved_at' => null,
            'approval_note' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $repo = $this->repository();

        $this->expectException(UnreadableVendorRecordException::class);
        $repo->findByTenantAndId($tenantId, $vendorId);
    }

    private function makeVendor(
        string $displayName = 'Acme Trading Sdn Bhd',
        ?VendorStatus $status = null,
    ): EloquentVendor {
        $vendor = new EloquentVendor();
        $vendor->id = (string) Str::ulid();
        $vendor->legal_name = 'Acme Holdings Sdn Bhd';
        $vendor->display_name = $displayName;
        $vendor->registration_number = '201901234567';
        $vendor->country_of_registration = 'MY';
        $vendor->status = ($status ?? VendorStatus::Draft)->value;
        $vendor->primary_contact_name = 'Amina Zain';
        $vendor->primary_contact_email = 'amina@example.com';
        $vendor->primary_contact_phone = '+60123456789';

        return $vendor;
    }

    private function repository(): VendorQueryInterface&VendorPersistInterface
    {
        $repository = $this->app->make(EloquentVendorRepository::class);
        self::assertInstanceOf(VendorQueryInterface::class, $repository);
        self::assertInstanceOf(VendorPersistInterface::class, $repository);

        return $repository;
    }

    private function makePlainVendor(
        ?string $vendorId = null,
        string $legalName = 'Plain Vendor Holdings',
        string $displayName = 'Plain Vendor',
        ?VendorApprovalRecord $approvalRecord = null,
    ): VendorInterface
    {
        $id = new VendorId($vendorId ?? (string) Str::ulid());

        return new class($id, $legalName, $displayName, $approvalRecord) implements VendorInterface {
            public function __construct(
                private readonly VendorId $id,
                private readonly string $legalName,
                private readonly string $displayName,
                private readonly ?VendorApprovalRecord $approvalRecord,
            ) {
            }

            public function getId(): VendorId
            {
                return $this->id;
            }

            public function getLegalName(): VendorLegalName
            {
                return new VendorLegalName($this->legalName);
            }

            public function getDisplayName(): VendorDisplayName
            {
                return new VendorDisplayName($this->displayName);
            }

            public function getRegistrationNumber(): RegistrationNumber
            {
                return new RegistrationNumber('202601234567');
            }

            public function getCountryOfRegistration(): string
            {
                return 'MY';
            }

            public function getPrimaryContactName(): string
            {
                return 'Amina Zain';
            }

            public function getPrimaryContactEmail(): string
            {
                return 'amina@example.com';
            }

            public function getPrimaryContactPhone(): ?string
            {
                return '+60123456789';
            }

            public function getStatus(): VendorStatus
            {
                return VendorStatus::Draft;
            }

            public function getApprovalRecord(): ?VendorApprovalRecord
            {
                return $this->approvalRecord;
            }
        };
    }

    private function loadVendorMigration(): object
    {
        return require __DIR__ . '/../../database/migrations/2026_04_21_000001_create_vendors_table.php';
    }

    private function createLegacyVendorsTable(): void
    {
        Schema::create('vendors', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->string('name');
            $table->string('trading_name')->nullable();
            $table->string('registration_number')->nullable();
            $table->string('tax_id')->nullable();
            $table->string('country_code')->default('MY');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('onboarded_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'status']);
        });
    }

    /**
     * @return array<string, object{notnull:int}>
     */
    private function vendorTableColumns(): array
    {
        $columns = [];

        foreach (DB::select('PRAGMA table_info(vendors)') as $column) {
            $columns[$column->name] = $column;
        }

        return $columns;
    }
}
