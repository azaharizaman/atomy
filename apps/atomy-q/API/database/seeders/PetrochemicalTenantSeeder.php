<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Approval;
use App\Models\Award;
use App\Models\ComparisonRun;
use App\Models\Project;
use App\Models\ProjectAcl;
use App\Models\QuoteSubmission;
use App\Models\Rfq;
use App\Models\RfqLineItem;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorInvitation;
use Database\Factories\ProjectFactory;
use Database\Factories\QuoteSubmissionFactory;
use Database\Factories\UserFactory;
use Database\Factories\VendorFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Single-tenant demo dataset: petrochemical buyer, 10+ projects, 50+ RFQs, 100+ quotes,
 * coherent RFQ/quote/comparison/approval/award flow (quote pipeline + readiness rules).
 */
final class PetrochemicalTenantSeeder extends Seeder
{
    private const DEFAULT_TENANT_ID = '01KKH77M4R0V8QZ1M8NB3XWWWQ';

    private Carbon $now;

    /** @var list<string> */
    private array $userIds = [];

    /** @var list<string> */
    private array $projectIds = [];

    /** @var list<Vendor> */
    private array $vendors = [];

    private string $tenantId = '';

    private string $scoringModelId = '';

    private string $scoringPolicyId = '';

    public function run(): void
    {
        $this->now = now();
        $envTenant = env('ATOMY_SEED_TENANT_ID');
        $this->tenantId = $envTenant !== null && $envTenant !== '' ? (string) $envTenant : self::DEFAULT_TENANT_ID;

        // Check if tenant already seeded
        if (DB::table('rfqs')->where('tenant_id', $this->tenantId)->exists()) {
            $this->command->info('Tenant already seeded, skipping.');
            return;
        }

        $this->seedTenant();
        $this->seedUsers();
        $this->seedProjectsAndAcl();
        $this->seedScoringAndTemplates();
        $this->seedVendors();
        $this->seedRfqsAndQuotes();
    }

    private function seedTenant(): void
    {
        // Check if tenant with this code already exists
        $existing = Tenant::query()->where('code', 'NORDFJORD')->first();
        if ($existing !== null) {
            $this->tenantId = $existing->id;
            $this->command->info('Tenant NORDFJORD already exists, using existing.');
            return;
        }

        // Also check by our default ID
        $existingById = Tenant::query()->find($this->tenantId);
        if ($existingById !== null) {
            $this->tenantId = $existingById->id;
            $this->command->info('Tenant with default ID already exists, using existing.');
            return;
        }

        // Create new tenant
        $tenant = Tenant::query()->create([
            'id' => $this->tenantId,
            'code' => 'NORDFJORD',
            'name' => 'Nordfjord Process Chemicals AS',
            'email' => 'procurement@nordfjord.example.com',
            'status' => 'active',
            'timezone' => 'Europe/Oslo',
            'locale' => 'en',
            'currency' => 'NOK',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
            'max_users' => 100,
            'storage_quota' => 10737418240,
            'storage_used' => 0,
            'rate_limit' => 60,
            'is_readonly' => false,
            'onboarding_progress' => 100,
        ]);

        $this->tenantId = $tenant->id;
    }

    private function seedUsers(): void
    {
        $names = [
            'Ingrid Solberg',
            'Erik Haugen',
            'Marte Nilsen',
            'Jonas Berg',
            'Sofia Lind',
            'Anders Vik',
            'Nora Dahl',
            'Henrik Moen',
        ];

        $this->userIds = UserFactory::new()
            ->count(8)
            ->sequence(fn ($sequence) => [
                'tenant_id' => $this->tenantId,
                'email' => 'user' . ($sequence->index + 1) . '@nordfjord.example.com',
                'name' => $names[$sequence->index],
                'password_hash' => bcrypt('secret'),
                'role' => $sequence->index === 0 ? 'admin' : 'user',
                'status' => 'active',
                'timezone' => 'Europe/Oslo',
                'locale' => 'en',
                'email_verified_at' => $this->now,
                'last_login_at' => $this->now,
            ])
            ->create()
            ->pluck('id')
            ->all();
    }

    private function seedProjectsAndAcl(): void
    {
        $projects = [
            ['name' => 'Steam Cracker TAR-2026 (Rafnes)', 'client' => 'NRF-RAFNES', 'status' => 'active', 'budget_type' => 'fixed_price', 'pct' => 38.0],
            ['name' => 'Catalyst Regeneration Campaign Q2', 'client' => 'CAT-VENDOR-X', 'status' => 'planning', 'budget_type' => 'time_and_materials', 'pct' => 12.0],
            ['name' => 'Stord Marine Terminal Expansion', 'client' => 'TERM-STORD', 'status' => 'active', 'budget_type' => 'fixed_price', 'pct' => 55.0],
            ['name' => 'Hydrogen Plant Feed Purification', 'client' => 'H2-NOR', 'status' => 'planning', 'budget_type' => 'time_and_materials', 'pct' => 8.0],
            ['name' => 'Lab Specialty Solvents — Frame 2024–27', 'client' => 'LAB-NFC', 'status' => 'completed', 'budget_type' => 'fixed_price', 'pct' => 100.0],
            ['name' => 'Flare Tip Replacement Package', 'client' => 'SAFETY-NFC', 'status' => 'on_hold', 'budget_type' => 'fixed_price', 'pct' => 22.0],
            ['name' => 'Rail Unloading Arms + VRU', 'client' => 'LOG-RAIL', 'status' => 'active', 'budget_type' => 'time_and_materials', 'pct' => 44.0],
            ['name' => 'Sulfur Unit Pit Lining (deferred)', 'client' => 'SUL-LEG', 'status' => 'cancelled', 'budget_type' => 'fixed_price', 'pct' => 0.0],
            ['name' => 'Cooling Water Chemical Program', 'client' => 'UTIL-CW', 'status' => 'active', 'budget_type' => 'fixed_price', 'pct' => 67.0],
            ['name' => 'DCS Migration Phase 2', 'client' => 'AUTO-DCS', 'status' => 'planning', 'budget_type' => 'time_and_materials', 'pct' => 15.0],
            ['name' => 'Berth 4 Loading Arm Renewal', 'client' => 'MARINE-JETTY', 'status' => 'active', 'budget_type' => 'fixed_price', 'pct' => 71.0],
            ['name' => 'Packaged Boiler House Revamp', 'client' => 'UTIL-STM', 'status' => 'completed', 'budget_type' => 'fixed_price', 'pct' => 100.0],
        ];

        $pm = $this->userIds[1] ?? null;

        $this->projectIds = ProjectFactory::new()
            ->count(count($projects))
            ->sequence(fn ($sequence) => [
                'tenant_id' => $this->tenantId,
                'name' => $projects[$sequence->index]['name'],
                'client_id' => $projects[$sequence->index]['client'],
                'start_date' => $this->now->copy()->subMonths(14)->toDateString(),
                'end_date' => $this->now->copy()->addMonths(20)->toDateString(),
                'project_manager_id' => $pm,
                'status' => $projects[$sequence->index]['status'],
                'budget_type' => $projects[$sequence->index]['budget_type'],
                'completion_percentage' => $projects[$sequence->index]['pct'],
            ])
            ->create()
            ->pluck('id')
            ->all();

        foreach ($this->projectIds as $projectId) {
            foreach ($this->userIds as $uIdx => $uid) {
                $role = match (true) {
                    $uIdx === 0 => 'owner',
                    $uIdx === 1 => 'admin',
                    $uIdx < 5 => 'editor',
                    default => 'viewer',
                };
                ProjectAcl::query()->create([
                    'id' => (string) Str::ulid(),
                    'tenant_id' => $this->tenantId,
                    'project_id' => $projectId,
                    'user_id' => $uid,
                    'role' => $role,
                ]);
            }
        }
    }

    private function seedScoringAndTemplates(): void
    {
        $this->scoringModelId = (string) Str::ulid();
        DB::table('scoring_models')->insert([
            'id' => $this->scoringModelId,
            'tenant_id' => $this->tenantId,
            'name' => 'NFC Weighted TCO',
            'description' => 'Price + delivery + technical compliance (petrochemical procurement).',
            'type' => 'weighted',
            'config' => json_encode(['version' => 2, 'criteria' => ['price', 'delivery', 'technical']], JSON_THROW_ON_ERROR),
            'status' => 'active',
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        $this->scoringPolicyId = (string) Str::ulid();
        DB::table('scoring_policies')->insert([
            'id' => $this->scoringPolicyId,
            'tenant_id' => $this->tenantId,
            'scoring_model_id' => $this->scoringModelId,
            'name' => 'Default NFC Policy',
            'description' => 'Seeded policy for demos.',
            'weights' => json_encode(['price' => 0.45, 'delivery' => 0.25, 'technical' => 0.30], JSON_THROW_ON_ERROR),
            'status' => 'active',
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        foreach (['Valve & rotating packages', 'Chemicals & catalysts'] as $name) {
            DB::table('rfq_templates')->insert([
                'id' => (string) Str::ulid(),
                'tenant_id' => $this->tenantId,
                'name' => $name,
                'description' => 'Nordfjord Process Chemicals template.',
                'category' => 'Process',
                'department' => 'Procurement',
                'line_items_schema' => json_encode([['label' => 'Tag / line', 'type' => 'text'], ['label' => 'Qty', 'type' => 'number']], JSON_THROW_ON_ERROR),
                'status' => 'active',
                'created_by' => $this->userIds[0],
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
        }
    }

    private function seedVendors(): void
    {
        $vendorNames = [
            'FlowServe Nordics AS',
            'Emerson Fisher Norway',
            'Alfa Laval Aalborg',
            'Sulzer Chemtech',
            'John Zink Hamworthy Combustion',
            'Donaldson Process Filtration',
            'Grundfos Process Nordic',
            'Atlas Copco Compressors',
            'Linde Engineering',
            'Air Liquide Advanced Separations',
            'Technip Energies Norge',
            'Wood PLC Norway',
            'Worley Chemetics',
            'Hayward Tyler (IMO) Pumps',
            'SIHI Liquid Ring',
            'Burkert Fluid Control',
            'Swagelok Bergen',
            'Parker Hannifin Scandinavia',
            'Honeywell UOP',
            'BASF Catalysts',
            'Clariant Catalysts',
            'Johnson Matthey NORAM',
            'Baltic Surplus Valves OU',
            'QuickTurn Fabrication LLC',
            'Offshore Chemtrade Services Ltd',
            'Nordic Rebuild Works (NRW)',
            'Discount MRO Express',
            'Shell fictive Contractor Nordic A/S',
        ];

        $categories = ['Rotating equipment', 'Instrumentation', 'Valves & piping', 'Electrical & automation', 'Turnaround services', 'Chemicals & consumables'];
        $regions = [['NO', 'SE', 'DK'], ['NO', 'GB', 'NL'], ['NO', 'DE', 'BE'], ['NO', 'MY', 'SG']];
        $allCountries = ['NO', 'SE', 'DK', 'GB', 'NL', 'DE', 'BE', 'US', 'SG', 'MY'];

        $vendorObjects = VendorFactory::new()
            ->count(count($vendorNames))
            ->sequence(fn ($sequence) => [
                'tenant_id' => $this->tenantId,
                'registration_number' => 'NO-' . str_pad((string) (730000 + $sequence->index), 6, '0', STR_PAD_LEFT),
                'tax_id' => 'MVA-' . str_pad((string) (930000 + $sequence->index), 6, '0', STR_PAD_LEFT),
                'legal_name' => $vendorNames[$sequence->index],
                'display_name' => $vendorNames[$sequence->index],
                'country_of_registration' => $allCountries[$sequence->index % count($allCountries)],
                'primary_contact_name' => $this->vendorContactName($sequence->index),
                'primary_contact_email' => strtolower(preg_replace('/[^a-z0-9]+/i', '.', $vendorNames[$sequence->index])) . '@vendor.test',
                'primary_contact_phone' => '+47 ' . (22000000 + $sequence->index * 137),
                'onboarded_at' => $this->now->copy()->subDays(420 - ($sequence->index % 240)),
            ])
            ->create();

        foreach ($vendorObjects as $index => $vendor) {
            $risky = $index >= 22;
            $status = match (true) {
                $risky && $index % 3 === 0 => 'restricted',
                $risky && $index % 3 === 1 => 'under_review',
                $risky => 'suspended',
                default => 'approved',
            };

            $vendor->update(['status' => $status]);

            if ($status === 'approved') {
                $vendor->update([
                    'approved_by_user_id' => $this->userIds[$index % count($this->userIds)],
                    'approved_at' => $this->now->copy()->subDays(180 - ($index % 90)),
                    'approval_note' => 'Seeded approval after petrochemical supplier qualification review.',
                ]);
            }

            $this->vendors[] = $vendor;
        }
    }

    private function vendorContactName(int $index): string
    {
        $names = [
            'Ingrid Solheim',
            'Marius Dahl',
            'Kari Lund',
            'Sofie Berg',
            'Anders Haugen',
            'Liv Johansen',
            'Erik Nilsen',
            'Nora Vik',
        ];

        return $names[$index % count($names)];
    }

    private function seedRfqsAndQuotes(): void
    {
        $titles = [
            'HP control valves Class 900 — ethylene rundown',
            'Mechanical seal upgrade — cracked gas compressor',
            'Sulfuric acid catalyst reload (Claus tail gas)',
            'Demineralized water polisher resin',
            'Thermal oxidizer burner management system',
            'Stainless tank internals — agitator + baffles',
            'Vapor recovery unit compressor package',
            'Corrosion inhibitor injection skid',
            'DCS I/O marshalling cabinets + IS barriers',
            'Loading arm spare hydraulic power unit',
            'Nitrogen generator membrane replacement',
            'Flare pilot / igniter assembly',
            'Heat exchanger bundle — lean amine',
            'Emergency shower & eyewash stations (ATEX)',
            'Chemical metering pumps — cooling tower',
            'HVAC ATEX fan wall — analyzer shelter',
            'Fixed gas detection — LEL / H2S',
            'Firewater pump mechanical seal kit',
            'Insulation & cladding — steam tracing package',
            'Bolt tensioning service — turnaround',
            'Temporary steam boiler rental (12 MW)',
            'Scaffold & containment — sulfur pit',
            'NDT phased-array — welds on HP piping',
            'Relief valve recertification (in-situ)',
            'Laser alignment — train B compressors',
            'Catalyst sampling probes + quills',
            'Sorbent media — mercury guard bed',
            'Oxygen analyzer cells — reformer feed',
            'Submersible sump pumps — containment sump',
            'Gasket kit ANSI 900 — RF joint set',
        ];

        $categories = ['Rotating equipment', 'Instrumentation', 'Valves & piping', 'Electrical & automation', 'Civil/structural', 'Turnaround services', 'Chemicals & consumables'];
        $departments = ['Maintenance', 'Projects', 'Operations', 'HSE', 'Procurement'];

        $rfqCount = 56;
        for ($i = 0; $i < $rfqCount; $i++) {
            $kind = $this->rfqKind($i);
            $projectId = ($i % 11 === 0) ? null : $this->projectIds[$i % count($this->projectIds)];
            $owner = $this->userIds[$i % count($this->userIds)];

            $status = match ($kind) {
                'draft' => 'draft',
                'published_intake', 'published_stuck' => 'published',
                'closed_pending' => 'closed',
                'awarded' => 'awarded',
                'cancelled' => 'cancelled',
                default => 'draft',
            };

            $deadline = match ($status) {
                'draft' => $this->now->copy()->addDays(30),
                'published' => $this->now->copy()->addDays(7 + ($i % 10)),
                'cancelled' => $this->now->copy()->subDays(40),
                default => $this->now->copy()->subDays(5 + ($i % 8)),
            };

            $closing = match ($status) {
                'draft', 'published' => $deadline->copy()->addDays(14),
                'cancelled' => $this->now->copy()->subDays(20),
                default => $this->now->copy()->subDays(1 + ($i % 5)),
            };

            $technicalReviewDueAt = $closing->copy()->subDays(5 + ($i % 3));
            $financialReviewDueAt = $closing->copy()->subDays(2 + ($i % 2));
            $expectedAwardAt = $closing->copy()->addDays(4 + ($i % 6));

            $ev = 25000.0 + ($i * 17500) + (sin($i * 9999) * 10000 - floor(sin($i * 9999) * 10000)) * 420000;
            $savings = 2.5 + (sin(($i + 5) * 9999) * 10000 - floor(sin(($i + 5) * 9999) * 10000)) * 16.0;

            $rfq = Rfq::query()->create([
                'id' => (string) Str::ulid(),
                'tenant_id' => $this->tenantId,
                'project_id' => $projectId,
                'rfq_number' => sprintf('RFQ-NFC-2026-%04d', $i + 1),
                'title' => $titles[$i % count($titles)] . ($i + 1 > count($titles) ? " (lot " . ($i + 1) . ")" : ''),
                'description' => 'Nordfjord Process Chemicals AS — seeded sourcing event (petrochemical maintenance & projects).',
                'category' => $categories[$i % count($categories)],
                'department' => $departments[$i % count($departments)],
                'status' => $status,
                'owner_id' => $owner,
                'estimated_value' => round($ev, 2),
                'savings_percentage' => round($savings, 2),
                'submission_deadline' => $deadline,
                'closing_date' => $closing,
                'technical_review_due_at' => $technicalReviewDueAt,
                'financial_review_due_at' => $financialReviewDueAt,
                'expected_award_at' => $expectedAwardAt,
                'payment_terms' => ($i % 3 === 0) ? 'Net 45 EOM' : 'Net 30',
                'evaluation_method' => 'weighted',
            ]);

            // Seed RFQ line items
            $lineCount = $this->lineCountForProfile($i);
            $uoms = ['ea', 'm', 'kg', 'kL', 'MT', 'set', 'lot'];
            for ($j = 1; $j <= $lineCount; $j++) {
                RfqLineItem::query()->create([
                    'id' => (string) Str::ulid(),
                    'tenant_id' => $this->tenantId,
                    'rfq_id' => $rfq->id,
                    'description' => sprintf('Line %d — tag NFC-%04d-%03d', $j, $i + 1, $j),
                    'quantity' => (float) (1 + ($j % 17) * ($j % 4 + 1)),
                    'uom' => $uoms[$j % count($uoms)],
                    'unit_price' => round(120.0 + $j * 37.5 + (sin(($j + $i) * 9999) * 10000 - floor(sin(($j + $i) * 9999) * 10000)) * 800, 2),
                    'currency' => 'USD',
                    'specifications' => 'ASME / PED applicable; material cert 3.1 required.',
                    'sort_order' => $j,
                ]);
            }

            // Seed vendor invitations and quotes for non-draft RFQs
            if ($status !== 'draft') {
                $this->seedInvitationsAndQuotes($rfq, $i, $kind);
            }
        }
    }

    private function rfqKind(int $i): string
    {
        if ($i < 8) {
            return 'draft';
        }
        if ($i < 18) {
            return 'published_intake';
        }
        if ($i < 24) {
            return 'published_stuck';
        }
        if ($i < 32) {
            return 'closed_pending';
        }
        if ($i < 52) {
            return 'awarded';
        }

        return 'cancelled';
    }

    private function lineCountForProfile(int $i): int
    {
        $profile = ['tiny', 'small', 'medium', 'large'][$i % 4];

        return match ($profile) {
            'tiny' => 1 + ($i % 2),
            'small' => 3 + ($i % 6),
            'medium' => 10 + ($i % 16),
            'large' => 38 + ($i % 42),
            default => 8,
        };
    }

    private function seedInvitationsAndQuotes(Rfq $rfq, int $index, string $kind): void
    {
        $inviteTotal = match ($kind) {
            'cancelled' => 2 + ($index % 3),
            default => 4 + ($index % 5),
        };

        $quoteTarget = match ($kind) {
            'published_intake' => 1 + ($index % 4),
            'published_stuck' => 3,
            'closed_pending' => 3 + ($index % 2),
            'awarded' => 3 + ($index % 3),
            'cancelled' => $index % 3,
            default => 0,
        };

        $approvedVendors = array_filter($this->vendors, fn ($v) => $v->status === 'approved');
        $approvedVendors = array_values($approvedVendors);

        $invitationIds = [];
        for ($v = 0; $v < $inviteTotal; $v++) {
            $vend = $approvedVendors[($v + $index * 7) % count($approvedVendors)] ?? $approvedVendors[0];
            $hasQuote = $v < $quoteTarget;

            $invitation = VendorInvitation::query()->create([
                'id' => (string) Str::ulid(),
                'tenant_id' => $this->tenantId,
                'rfq_id' => $rfq->id,
                'vendor_id' => $vend->id,
                'vendor_email' => $vend->primary_contact_email,
                'vendor_name' => $vend->display_name,
                'status' => $hasQuote ? 'accepted' : 'pending',
                'invited_at' => $this->now,
                'responded_at' => $hasQuote ? $this->now : null,
                'channel' => 'email',
            ]);

            $invitationIds[] = $invitation->id;

            // Seed selected vendor
            if ($vend->status === 'approved') {
                DB::table('requisition_selected_vendors')->insertOrIgnore([
                    'id' => (string) Str::ulid(),
                    'tenant_id' => $this->tenantId,
                    'rfq_id' => $rfq->id,
                    'vendor_id' => $vend->id,
                    'selected_by_user_id' => $rfq->owner_id,
                    'selected_at' => $this->now->copy()->subDays(1 + ($v % 4)),
                    'created_at' => $this->now,
                    'updated_at' => $this->now,
                ]);
            }

            // Seed quote submission
            if ($hasQuote) {
                $st = $this->quoteStatusFor($kind, $v);
                $this->seedQuote($rfq, $vend, $st, $index, $v);
            }
        }

        // Seed comparison, approval, award for closed/awarded RFQs
        if (in_array($kind, ['closed_pending', 'awarded'], true)) {
            $this->seedComparisonApprovalAward($rfq, $kind, $index);
        }
    }

    private function quoteStatusFor(string $kind, int $qIdx): string
    {
        return match ($kind) {
            'published_intake' => match ($qIdx % 4) {
                0 => 'uploaded',
                1 => 'extracting',
                2 => 'extracted',
                default => 'normalizing',
            },
            'published_stuck' => 'needs_review',
            'closed_pending', 'awarded' => 'ready',
            'cancelled' => $qIdx === 0 ? 'normalizing' : 'failed',
            default => 'ready',
        };
    }

    private function seedQuote(Rfq $rfq, Vendor $vendor, string $status, int $rfqIndex, int $quoteIndex): void
    {
        $submittedAt = $this->now->copy()->subDays($quoteIndex % 6);
        $processingStartedAt = null;
        $processingCompletedAt = null;
        $parsedAt = null;
        $errorCode = null;
        $errorMessage = null;
        $retryCount = 0;
        $confidence = null;
        $lineItemsCount = 0;
        $warningsCount = 0;
        $errorsCount = 0;

        if ($status !== 'uploaded') {
            $processingStartedAt = $submittedAt->copy()->addMinutes(2);
        }

        if (in_array($status, ['extracted', 'normalizing', 'ready', 'needs_review', 'failed'], true)) {
            $parsedAt = $processingStartedAt?->copy()->addMinutes(5);
        }

        if (in_array($status, ['ready', 'needs_review', 'failed'], true)) {
            $processingCompletedAt = $parsedAt?->copy()->addMinutes(1);
        }

        if ($status === 'ready') {
            $confidence = round(72.0 + (sin(($rfqIndex + $quoteIndex) * 9999) * 10000 - floor(sin(($rfqIndex + $quoteIndex) * 9999) * 10000)) * 27.0, 2);
            $lineItemsCount = rand(3, 25);
            $warningsCount = rand(0, 2);
        }

        if ($status === 'needs_review') {
            $warningsCount = 2 + ($quoteIndex % 3);
        }

        if ($status === 'failed') {
            $errorCode = 'EXTRACTION_FAILED';
            $errorMessage = 'Unable to parse document structure. Retry exhaustion.';
            $retryCount = 3;
            $errorsCount = 2;
        }

        QuoteSubmission::query()->create([
            'id' => (string) Str::ulid(),
            'tenant_id' => $this->tenantId,
            'rfq_id' => $rfq->id,
            'vendor_id' => $vendor->id,
            'vendor_name' => $vendor->display_name,
            'uploaded_by' => $rfq->owner_id,
            'status' => $status,
            'file_path' => '/uploads/quotes/' . $rfq->id . '-' . $quoteIndex . '.pdf',
            'file_type' => 'application/pdf',
            'original_filename' => 'Quote_' . preg_replace('/\W+/', '_', $vendor->legal_name) . '.pdf',
            'submitted_at' => $submittedAt,
            'confidence' => $confidence,
            'line_items_count' => $lineItemsCount,
            'warnings_count' => $warningsCount,
            'errors_count' => $errorsCount,
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
            'processing_started_at' => $processingStartedAt,
            'processing_completed_at' => $processingCompletedAt,
            'parsed_at' => $parsedAt,
            'retry_count' => $retryCount,
        ]);
    }

    private function seedComparisonApprovalAward(Rfq $rfq, string $kind, int $index): void
    {
        $quotes = QuoteSubmission::query()
            ->where('rfq_id', $rfq->id)
            ->where('tenant_id', $this->tenantId)
            ->where('status', 'ready')
            ->get();

        if ($quotes->count() < 2) {
            return;
        }

        // Create preview comparison run
        $runPreview = ComparisonRun::query()->create([
            'id' => (string) Str::ulid(),
            'tenant_id' => $this->tenantId,
            'rfq_id' => $rfq->id,
            'name' => 'Comparison preview',
            'description' => 'What-if matrix (preview).',
            'idempotency_key' => 'preview-' . $rfq->id,
            'is_preview' => true,
            'created_by' => $this->userIds[0],
            'request_payload' => ['rfq_id' => $rfq->id, 'phase' => 'preview'],
            'matrix_payload' => ['rows' => []],
            'scoring_payload' => ['vendors' => []],
            'approval_payload' => [],
            'response_payload' => ['status' => 'preview'],
            'readiness_payload' => ['ready_quotes' => $quotes->count()],
            'status' => 'draft',
            'version' => 1,
            'expires_at' => $this->now->copy()->addDays(3),
        ]);

        // Create final comparison run
        $scores = [];
        $matrix = [];
        foreach ($quotes->values() as $idx => $q) {
            $total = 58 + ($idx * 11) % 35 + (sin(($index + $idx) * 9999) * 10000 - floor(sin(($index + $idx) * 9999) * 10000)) * 8;
            $total = min(99.5, $total);
            $scores[] = [
                'vendor_id' => $q->vendor_id,
                'vendor_name' => $q->vendor_name,
                'total_score' => round($total, 2),
                'price_score' => round(min(99.0, $total - 5 + $idx), 2),
                'delivery_score' => round(min(99.0, $total - 2), 2),
                'technical_score' => round(min(99.0, $total + 3 - $idx), 2),
            ];
            $matrix[] = ['quote_id' => $q->id, 'vendor' => $q->vendor_name, 'normalized_total' => round($total * 1200, 2)];
        }

        $runFinal = ComparisonRun::query()->create([
            'id' => (string) Str::ulid(),
            'tenant_id' => $this->tenantId,
            'rfq_id' => $rfq->id,
            'name' => 'Final comparison',
            'description' => 'Locked matrix for approval / award.',
            'idempotency_key' => 'final-' . $rfq->id,
            'is_preview' => false,
            'created_by' => $this->userIds[0],
            'request_payload' => ['rfq_id' => $rfq->id, 'phase' => 'final'],
            'matrix_payload' => ['rows' => $matrix, 'locked' => true],
            'scoring_payload' => ['vendors' => $scores, 'ranked' => true],
            'approval_payload' => ['policy_id' => $this->scoringPolicyId],
            'response_payload' => ['snapshot' => ['rfq_id' => $rfq->id, 'quote_ids' => $quotes->pluck('id')->all()]],
            'readiness_payload' => ['all_ready' => true],
            'status' => 'final',
            'version' => 1,
            'expires_at' => null,
        ]);

        // Create approval
        $approved = $kind === 'awarded';
        $approval = Approval::query()->create([
            'id' => (string) Str::ulid(),
            'tenant_id' => $this->tenantId,
            'rfq_id' => $rfq->id,
            'comparison_run_id' => $runFinal->id,
            'type' => 'comparison_approval',
            'status' => $approved ? 'approved' : 'pending',
            'requested_by' => $this->userIds[0],
            'requested_at' => $this->now,
            'amount' => $rfq->estimated_value,
            'currency' => 'USD',
            'level' => 1,
            'notes' => $approved ? 'Award committee sign-off (seed).' : 'Awaiting delegated approver (seed).',
            'approved_at' => $approved ? $this->now : null,
            'approved_by' => $approved ? $this->userIds[0] : null,
        ]);

        // Seed approval history
        DB::table('approval_history')->insert([
            'id' => (string) Str::ulid(),
            'tenant_id' => $this->tenantId,
            'approval_id' => $approval->id,
            'action' => $approved ? 'approved' : 'requested',
            'actor_id' => $this->userIds[0],
            'reason' => null,
            'metadata' => json_encode([], JSON_THROW_ON_ERROR),
            'created_at' => $this->now,
        ]);

        // Create award for awarded RFQs
        if ($approved) {
            usort($scores, static fn (array $a, array $b): int => $b['total_score'] <=> $a['total_score']);
            $winner = $scores[0];
            $winnerQuote = $quotes->first(fn ($q) => (string) $q->vendor_id === (string) $winner['vendor_id']);

            if ($winnerQuote !== null) {
                $amount = (float) round((float) $rfq->estimated_value * (1 - (float) $rfq->savings_percentage / 100), 2);

                $award = Award::query()->create([
                    'id' => (string) Str::ulid(),
                    'tenant_id' => $this->tenantId,
                    'rfq_id' => $rfq->id,
                    'comparison_run_id' => $runFinal->id,
                    'vendor_id' => $winnerQuote->vendor_id,
                    'status' => 'signed_off',
                    'amount' => $amount,
                    'currency' => 'USD',
                    'split_details' => [],
                    'protest_id' => null,
                    'signoff_at' => $this->now,
                    'signed_off_by' => $this->userIds[0],
                ]);

                // Seed handoff
                DB::table('handoffs')->insert([
                    'id' => (string) Str::ulid(),
                    'tenant_id' => $this->tenantId,
                    'award_id' => $award->id,
                    'destination_type' => 'erp',
                    'destination_id' => 'SAP-S4-NFC',
                    'status' => 'pending',
                    'sent_at' => null,
                    'retry_count' => 0,
                    'error_message' => null,
                    'created_at' => $this->now,
                    'updated_at' => $this->now,
                ]);
            }
        }
    }
}
