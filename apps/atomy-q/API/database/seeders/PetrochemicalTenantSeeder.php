<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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

    /** @var list<array{id: string, name: string, risky: bool}> */
    private array $vendorPool = [];

    private string $tenantId = '';

    private string $scoringModelId = '';

    private string $scoringPolicyId = '';

    /** Deterministic float in [0,1) from index */
    private static function hash(int $n): float
    {
        $x = sin($n * 9999) * 10000;

        return $x - floor($x);
    }

    public function run(): void
    {
        $this->now = now();
        $envTenant = env('ATOMY_SEED_TENANT_ID');
        $this->tenantId = $envTenant !== null && $envTenant !== '' ? (string) $envTenant : self::DEFAULT_TENANT_ID;

        $this->seedUsers();
        $this->seedProjectsAndAcl();
        $this->seedScoringAndTemplates();
        $this->buildVendorPool();

        /** @var list<array<string, mixed>> $rfqRows */
        $rfqRows = [];
        $rfqCount = 56;
        for ($i = 0; $i < $rfqCount; $i++) {
            $rfqRows[] = $this->insertRfqAndLines($this->buildRfqContext($i));
        }

        foreach ($rfqRows as $idx => $_) {
            $this->insertInvitationsAndQuotes($rfqRows[$idx]);
        }

        foreach ($rfqRows as $ctx) {
            $this->insertComparisonApprovalsAwards($ctx);
        }

        $this->seedDemoExtras($rfqRows);
        $this->seedInfrastructureRows();
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
        for ($i = 0; $i < 8; $i++) {
            $userId = (string) Str::ulid();
            $this->userIds[] = $userId;
            DB::table('users')->insert([
                'id' => $userId,
                'tenant_id' => $this->tenantId,
                'email' => 'user' . ($i + 1) . '@example.com',
                'name' => $names[$i],
                'password_hash' => Hash::make('secret'),
                'role' => $i === 0 ? 'admin' : 'user',
                'status' => 'active',
                'timezone' => 'Europe/Oslo',
                'locale' => 'en',
                'email_verified_at' => $this->now,
                'last_login_at' => $this->now,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
        }
    }

    private function seedProjectsAndAcl(): void
    {
        $projects = [
            ['name' => 'Steam Cracker TAR-2026 (Rafnes)', 'client' => 'NRF-RAFNES', 'status' => 'active', 'budget' => 'fixed_price', 'pct' => 38.0],
            ['name' => 'Catalyst Regeneration Campaign Q2', 'client' => 'CAT-VENDOR-X', 'status' => 'planning', 'budget' => 'time_and_materials', 'pct' => 12.0],
            ['name' => 'Stord Marine Terminal Expansion', 'client' => 'TERM-STORD', 'status' => 'active', 'budget' => 'fixed_price', 'pct' => 55.0],
            ['name' => 'Hydrogen Plant Feed Purification', 'client' => 'H2-NOR', 'status' => 'planning', 'budget' => 'time_and_materials', 'pct' => 8.0],
            ['name' => 'Lab Specialty Solvents — Frame 2024–27', 'client' => 'LAB-NFC', 'status' => 'completed', 'budget' => 'fixed_price', 'pct' => 100.0],
            ['name' => 'Flare Tip Replacement Package', 'client' => 'SAFETY-NFC', 'status' => 'on_hold', 'budget' => 'fixed_price', 'pct' => 22.0],
            ['name' => 'Rail Unloading Arms + VRU', 'client' => 'LOG-RAIL', 'status' => 'active', 'budget' => 'time_and_materials', 'pct' => 44.0],
            ['name' => 'Sulfur Unit Pit Lining (deferred)', 'client' => 'SUL-LEG', 'status' => 'cancelled', 'budget' => 'fixed_price', 'pct' => 0.0],
            ['name' => 'Cooling Water Chemical Program', 'client' => 'UTIL-CW', 'status' => 'active', 'budget' => 'fixed_price', 'pct' => 67.0],
            ['name' => 'DCS Migration Phase 2', 'client' => 'AUTO-DCS', 'status' => 'planning', 'budget' => 'time_and_materials', 'pct' => 15.0],
            ['name' => 'Berth 4 Loading Arm Renewal', 'client' => 'MARINE-JETTY', 'status' => 'active', 'budget' => 'fixed_price', 'pct' => 71.0],
            ['name' => 'Packaged Boiler House Revamp', 'client' => 'UTIL-STM', 'status' => 'completed', 'budget' => 'fixed_price', 'pct' => 100.0],
        ];

        $pm = $this->userIds[1];
        foreach ($projects as $p) {
            $pid = (string) Str::ulid();
            $this->projectIds[] = $pid;
            DB::table('projects')->insert([
                'id' => $pid,
                'tenant_id' => $this->tenantId,
                'name' => $p['name'],
                'client_id' => $p['client'],
                'start_date' => $this->now->copy()->subMonths(14)->toDateString(),
                'end_date' => $this->now->copy()->addMonths(20)->toDateString(),
                'project_manager_id' => $pm,
                'status' => $p['status'],
                'budget_type' => $p['budget'],
                'completion_percentage' => $p['pct'],
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
        }

        foreach ($this->projectIds as $projId) {
            foreach ($this->userIds as $uIdx => $uid) {
                $role = match (true) {
                    $uIdx === 0 => 'owner',
                    $uIdx === 1 => 'admin',
                    $uIdx < 5 => 'editor',
                    default => 'viewer',
                };
                DB::table('project_acl')->insert([
                    'id' => (string) Str::ulid(),
                    'tenant_id' => $this->tenantId,
                    'project_id' => $projId,
                    'user_id' => $uid,
                    'role' => $role,
                    'created_at' => $this->now,
                    'updated_at' => $this->now,
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

        foreach (['Valve & rotating packages', 'Chemicals & catalysts'] as $idx => $name) {
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

    private function buildVendorPool(): void
    {
        $defs = [
            ['FlowServe Nordics AS', false],
            ['Emerson Fisher Norway', false],
            ['Alfa Laval Aalborg', false],
            ['Sulzer Chemtech', false],
            ['John Zink Hamworthy Combustion', false],
            ['Donaldson Process Filtration', false],
            ['Grundfos Process Nordic', false],
            ['Atlas Copco Compressors', false],
            ['Linde Engineering', false],
            ['Air Liquide Advanced Separations', false],
            ['Technip Energies Norge', false],
            ['Wood PLC Norway', false],
            ['Worley Chemetics', false],
            ['Hayward Tyler (IMO) Pumps', false],
            ['SIHI Liquid Ring', false],
            ['Burkert Fluid Control', false],
            ['Swagelok Bergen', false],
            ['Parker Hannifin Scandinavia', false],
            ['Honeywell UOP', false],
            ['BASF Catalysts', false],
            ['Clariant Catalysts', false],
            ['Johnson Matthey NORAM', false],
            ['Baltic Surplus Valves OU', true],
            ['QuickTurn Fabrication LLC', true],
            ['Offshore Chemtrade Services Ltd', true],
            ['Nordic Rebuild Works (NRW)', true],
            ['Discount MRO Express', true],
            ['Shell fictive Contractor Nordic A/S', false],
        ];
        foreach ($defs as [$name, $risky]) {
            $this->vendorPool[] = [
                'id' => (string) Str::ulid(),
                'name' => $name,
                'risky' => $risky,
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRfqContext(int $i): array
    {
        $kind = $this->rfqKind($i);
        $projectId = ($i % 11 === 0) ? null : $this->projectIds[$i % count($this->projectIds)];
        $lineProfile = ['tiny', 'small', 'medium', 'large'][$i % 4];
        $lineCount = $this->lineCountForProfile($i, $lineProfile);
        $owner = $this->userIds[$i % count($this->userIds)];

        $status = match ($kind) {
            'draft' => 'draft',
            'published_intake', 'published_stuck' => 'published',
            'closed_pending' => 'closed',
            'awarded' => 'awarded',
            'cancelled' => 'cancelled',
            default => 'draft',
        };

        $seq = $i + 1;
        $titles = [
            'HP control valves Class 900 — ethylene rundown',
            'Mechanical seal upgrade — cracked gas compressor',
            'Sulfuric acid catalyst reload ( Claus tail gas )',
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
        $title = $titles[$i % count($titles)] . ($seq > count($titles) ? " (lot {$seq})" : '');

        $categories = ['Rotating equipment', 'Instrumentation', 'Valves & piping', 'Electrical & automation', 'Civil/structural', 'Turnaround services', 'Chemicals & consumables'];
        $departments = ['Maintenance', 'Projects', 'Operations', 'HSE', 'Procurement'];

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

        $ev = 25000.0 + ($i * 17500) + self::hash($i + 3) * 420000;
        $savings = 2.5 + self::hash($i + 5) * 16.0;

        return [
            'index' => $i,
            'kind' => $kind,
            'rfq_id' => (string) Str::ulid(),
            'project_id' => $projectId,
            'status' => $status,
            'rfq_number' => sprintf('RFQ-NFC-2026-%04d', $seq),
            'title' => $title,
            'description' => 'Nordfjord Process Chemicals AS — seeded sourcing event (petrochemical maintenance & projects).',
            'category' => $categories[$i % count($categories)],
            'department' => $departments[$i % count($departments)],
            'owner_id' => $owner,
            'estimated_value' => round($ev, 2),
            'savings_percentage' => round($savings, 2),
            'submission_deadline' => $deadline,
            'closing_date' => $closing,
            'line_profile' => $lineProfile,
            'line_count' => $lineCount,
            'scoring_spread' => $kind === 'closed_pending' ? (self::hash($i) > 0.35) : (self::hash($i + 7) > 0.55),
        ];
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

    private function lineCountForProfile(int $i, string $profile): int
    {
        return match ($profile) {
            'tiny' => 1 + ($i % 2),
            'small' => 3 + ($i % 6),
            'medium' => 10 + ($i % 16),
            'large' => 38 + ($i % 42),
            default => 8,
        };
    }

    /**
     * @param array<string, mixed> $ctx
     * @return array<string, mixed>
     */
    private function insertRfqAndLines(array $ctx): array
    {
        $rfqId = (string) $ctx['rfq_id'];
        DB::table('rfqs')->insert([
            'id' => $rfqId,
            'tenant_id' => $this->tenantId,
            'project_id' => $ctx['project_id'],
            'rfq_number' => $ctx['rfq_number'],
            'title' => $ctx['title'],
            'description' => $ctx['description'],
            'category' => $ctx['category'],
            'department' => $ctx['department'],
            'status' => $ctx['status'],
            'owner_id' => $ctx['owner_id'],
            'estimated_value' => $ctx['estimated_value'],
            'savings_percentage' => $ctx['savings_percentage'],
            'submission_deadline' => $ctx['submission_deadline'],
            'closing_date' => $ctx['closing_date'],
            'payment_terms' => ($ctx['index'] % 3 === 0) ? 'Net 45 EOM' : 'Net 30',
            'evaluation_method' => 'weighted',
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        $uoms = ['ea', 'm', 'kg', 'kL', 'MT', 'set', 'lot'];
        $lineIds = [];
        $lineCount = (int) $ctx['line_count'];
        for ($j = 1; $j <= $lineCount; $j++) {
            $lid = (string) Str::ulid();
            $lineIds[] = [
                'id' => $lid,
                'quantity' => (float) (1 + ($j % 17) * ($j % 4 + 1)),
                'uom' => $uoms[$j % count($uoms)],
                'unit_price' => round(120.0 + $j * 37.5 + self::hash($j + (int) $ctx['index']) * 800, 2),
            ];
            DB::table('rfq_line_items')->insert([
                'id' => $lid,
                'tenant_id' => $this->tenantId,
                'rfq_id' => $rfqId,
                'description' => sprintf('Line %d — tag NFC-%04d-%03d', $j, (int) $ctx['index'] + 1, $j),
                'quantity' => $lineIds[$j - 1]['quantity'],
                'uom' => $lineIds[$j - 1]['uom'],
                'unit_price' => $lineIds[$j - 1]['unit_price'],
                'currency' => 'USD',
                'specifications' => 'ASME / PED applicable; material cert 3.1 required.',
                'sort_order' => $j,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
        }
        $ctx['_line_ids'] = $lineIds;

        return $ctx;
    }

    /**
     * @param array<string, mixed> $ctx
     */
    private function insertInvitationsAndQuotes(array &$ctx): void
    {
        $rfqId = (string) $ctx['rfq_id'];
        $kind = (string) $ctx['kind'];
        /** @var list<array{id: string, quantity: float, uom: string, unit_price: float}> $lines */
        $lines = $ctx['_line_ids'] ?? [];
        $i = (int) $ctx['index'];

        $inviteTotal = match ($kind) {
            'draft' => 2 + ($i % 4),
            'cancelled' => 2 + ($i % 3),
            default => 4 + ($i % 5),
        };

        $quoteTarget = match ($kind) {
            'draft' => 0,
            'published_intake' => 1 + ($i % 4),
            'published_stuck' => 3,
            'closed_pending' => 3 + ($i % 2),
            'awarded' => 3 + ($i % 3),
            'cancelled' => $i % 3,
            default => 0,
        };

        $invitationIds = [];
        $invitationMeta = [];
        for ($v = 0; $v < $inviteTotal; $v++) {
            $vend = $this->vendorPool[($v + $i * 7) % count($this->vendorPool)];
            $invId = (string) Str::ulid();
            $invitationIds[] = $invId;
            $hasQuote = $v < $quoteTarget;
            $invitationMeta[$invId] = $vend;
            DB::table('vendor_invitations')->insert([
                'id' => $invId,
                'tenant_id' => $this->tenantId,
                'rfq_id' => $rfqId,
                'vendor_id' => $vend['id'],
                'vendor_email' => strtolower(preg_replace('/[^a-z0-9]+/i', '.', $vend['name'])) . '@vendor.test',
                'vendor_name' => $vend['name'],
                'status' => $hasQuote ? 'accepted' : 'pending',
                'invited_at' => $this->now,
                'responded_at' => $hasQuote ? $this->now : null,
                'channel' => 'email',
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
        }

        $quoteIds = [];
        foreach (array_slice($invitationIds, 0, $quoteTarget) as $qIdx => $invId) {
            $vend = $invitationMeta[$invId];
            $quoteId = (string) Str::ulid();
            $quoteIds[] = $quoteId;
            $st = $this->quoteStatusFor($kind, $qIdx, $i);
            $warnings = match ($st) {
                'needs_review' => 2 + ($qIdx % 3),
                'failed' => 0,
                default => (int) ($vend['risky'] ? 1 : 0),
            };
            $errors = $st === 'failed' ? 2 : 0;
            DB::table('quote_submissions')->insert([
                'id' => $quoteId,
                'tenant_id' => $this->tenantId,
                'rfq_id' => $rfqId,
                'vendor_id' => $vend['id'],
                'vendor_name' => $vend['name'],
                'uploaded_by' => $ctx['owner_id'],
                'status' => $st,
                'file_path' => '/uploads/quotes/' . $rfqId . '-' . $qIdx . '.pdf',
                'file_type' => 'application/pdf',
                'original_filename' => 'Quote_' . preg_replace('/\W+/', '_', $vend['name']) . '.pdf',
                'submitted_at' => $this->now->copy()->subDays($qIdx % 6),
                'confidence' => round(72.0 + self::hash($i + $qIdx) * 27.0, 2),
                'line_items_count' => count($lines),
                'warnings_count' => $warnings,
                'errors_count' => $errors,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);

            if ($st === 'ready' || ($kind === 'published_stuck' && $st === 'needs_review')) {
                $this->seedNormalizationForQuote($quoteId, $lines, $kind, $qIdx, $st === 'needs_review');
            }

            if ($vend['risky'] && $kind !== 'draft') {
                DB::table('risk_items')->insert([
                    'id' => (string) Str::ulid(),
                    'tenant_id' => $this->tenantId,
                    'rfq_id' => $rfqId,
                    'severity' => $vend['risky'] && self::hash($i + $qIdx) > 0.6 ? 'high' : 'medium',
                    'title' => 'Supplier diligence — ' . $vend['name'],
                    'description' => 'Seeded risk: limited local reference installs / financial covenant watchlist (demo).',
                    'source' => 'system',
                    'status' => 'open',
                    'resolved_at' => null,
                    'resolved_by' => null,
                    'created_at' => $this->now,
                    'updated_at' => $this->now,
                ]);
            }
        }

        $ctx['_invitation_ids'] = $invitationIds;
        $ctx['_quote_ids'] = $quoteIds;
        $ctx['_invitation_meta'] = $invitationMeta;
    }

    private function quoteStatusFor(string $kind, int $qIdx, int $i): string
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

    /**
     * @param list<array{id: string, quantity: float, uom: string, unit_price: float}> $lines
     */
    private function seedNormalizationForQuote(string $quoteId, array $lines, string $kind, int $qIdx, bool $withOpenConflict): void
    {
        $lineCount = count($lines);
        $skipLast = $withOpenConflict && $qIdx === 0 && $kind === 'published_stuck';
        $max = $skipLast ? max(0, $lineCount - 1) : $lineCount;

        for ($k = 0; $k < $max; $k++) {
            $line = $lines[$k];
            $nlId = (string) Str::ulid();
            $priceDelta = 1.0 + (self::hash((int) crc32($quoteId) + $k) - 0.5) * 0.08;
            DB::table('normalization_source_lines')->insert([
                'id' => $nlId,
                'tenant_id' => $this->tenantId,
                'quote_submission_id' => $quoteId,
                'rfq_line_item_id' => $line['id'],
                'source_vendor' => 'PDF extract',
                'source_description' => 'Mapped line ' . ($k + 1),
                'source_quantity' => $line['quantity'],
                'source_uom' => $line['uom'],
                'source_unit_price' => round($line['unit_price'] * $priceDelta, 4),
                'raw_data' => json_encode(['page' => 1 + ($k % 4)], JSON_THROW_ON_ERROR),
                'sort_order' => $k + 1,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);

            if ($withOpenConflict && $qIdx === 1 && $k === 0) {
                DB::table('normalization_conflicts')->insert([
                    'id' => (string) Str::ulid(),
                    'tenant_id' => $this->tenantId,
                    'normalization_source_line_id' => $nlId,
                    'conflict_type' => 'uom_mismatch',
                    'resolution' => null,
                    'resolved_at' => null,
                    'resolved_by' => null,
                    'created_at' => $this->now,
                    'updated_at' => $this->now,
                ]);
            }
        }
    }

    /**
     * @param array<string, mixed> $ctx
     */
    private function insertComparisonApprovalsAwards(array $ctx): void
    {
        $kind = (string) $ctx['kind'];
        if (! in_array($kind, ['closed_pending', 'awarded'], true)) {
            return;
        }

        $rfqId = (string) $ctx['rfq_id'];
        $quotes = DB::table('quote_submissions')->where('rfq_id', $rfqId)->where('tenant_id', $this->tenantId)->get();
        $ready = $quotes->filter(static fn ($q) => $q->status === 'ready');
        if ($ready->count() < 2) {
            return;
        }

        $spread = (bool) $ctx['scoring_spread'];
        $matrix = [];
        $scores = [];
        foreach ($ready->values() as $idx => $q) {
            if ($spread) {
                $total = 58 + ($idx * 11) % 35 + self::hash((int) $ctx['index'] + $idx) * 8;
            } else {
                $total = 86 + ($idx % 5) - (int) (self::hash((int) $ctx['index'] + $idx) * 4);
            }
            $matrix[] = ['quote_id' => $q->id, 'vendor' => $q->vendor_name, 'normalized_total' => round($total * 1200, 2)];
            $scores[] = [
                'vendor_id' => $q->vendor_id,
                'vendor_name' => $q->vendor_name,
                'total_score' => round(min(99.5, $total), 2),
                'price_score' => round(min(99.0, $total - 5 + $idx), 2),
                'delivery_score' => round(min(99.0, $total - 2), 2),
                'technical_score' => round(min(99.0, $total + 3 - $idx), 2),
            ];
        }

        $runPreview = (string) Str::ulid();
        DB::table('comparison_runs')->insert([
            'id' => $runPreview,
            'tenant_id' => $this->tenantId,
            'rfq_id' => $rfqId,
            'name' => 'Comparison preview',
            'description' => 'What-if matrix (preview).',
            'idempotency_key' => 'preview-' . $rfqId,
            'is_preview' => true,
            'created_by' => $this->userIds[0],
            'request_payload' => json_encode(['rfq_id' => $rfqId, 'phase' => 'preview'], JSON_THROW_ON_ERROR),
            'matrix_payload' => json_encode(['rows' => $matrix], JSON_THROW_ON_ERROR),
            'scoring_payload' => json_encode(['vendors' => $scores], JSON_THROW_ON_ERROR),
            'approval_payload' => json_encode([], JSON_THROW_ON_ERROR),
            'response_payload' => json_encode(['status' => 'preview'], JSON_THROW_ON_ERROR),
            'readiness_payload' => json_encode(['ready_quotes' => $ready->count()], JSON_THROW_ON_ERROR),
            'status' => 'draft',
            'version' => 1,
            'expires_at' => $this->now->copy()->addDays(3),
            'discarded_at' => null,
            'discarded_by' => null,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        $runFinal = (string) Str::ulid();
        DB::table('comparison_runs')->insert([
            'id' => $runFinal,
            'tenant_id' => $this->tenantId,
            'rfq_id' => $rfqId,
            'name' => 'Final comparison',
            'description' => 'Locked matrix for approval / award.',
            'idempotency_key' => 'final-' . $rfqId,
            'is_preview' => false,
            'created_by' => $this->userIds[0],
            'request_payload' => json_encode(['rfq_id' => $rfqId, 'phase' => 'final'], JSON_THROW_ON_ERROR),
            'matrix_payload' => json_encode(['rows' => $matrix, 'locked' => true], JSON_THROW_ON_ERROR),
            'scoring_payload' => json_encode(['vendors' => $scores, 'ranked' => true], JSON_THROW_ON_ERROR),
            'approval_payload' => json_encode(['policy_id' => $this->scoringPolicyId], JSON_THROW_ON_ERROR),
            'response_payload' => json_encode(['snapshot' => ['rfq_id' => $rfqId, 'quote_ids' => $ready->pluck('id')->all()]], JSON_THROW_ON_ERROR),
            'readiness_payload' => json_encode(['all_ready' => true], JSON_THROW_ON_ERROR),
            'status' => 'final',
            'version' => 1,
            'expires_at' => null,
            'discarded_at' => null,
            'discarded_by' => null,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        $approved = $kind === 'awarded';
        $approvalId = (string) Str::ulid();
        DB::table('approvals')->insert([
            'id' => $approvalId,
            'tenant_id' => $this->tenantId,
            'rfq_id' => $rfqId,
            'comparison_run_id' => $runFinal,
            'type' => 'comparison_approval',
            'status' => $approved ? 'approved' : 'pending',
            'requested_by' => $this->userIds[0],
            'requested_at' => $this->now,
            'amount' => $ctx['estimated_value'],
            'currency' => 'USD',
            'level' => 1,
            'notes' => $approved ? 'Award committee sign-off (seed).' : 'Awaiting delegated approver (seed).',
            'approved_at' => $approved ? $this->now : null,
            'approved_by' => $approved ? $this->userIds[0] : null,
            'snoozed_until' => null,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);
        DB::table('approval_history')->insert([
            'id' => (string) Str::ulid(),
            'tenant_id' => $this->tenantId,
            'approval_id' => $approvalId,
            'action' => $approved ? 'approved' : 'requested',
            'actor_id' => $this->userIds[0],
            'reason' => null,
            'metadata' => json_encode([], JSON_THROW_ON_ERROR),
            'created_at' => $this->now,
        ]);

        if (! $approved) {
            return;
        }

        usort(
            $scores,
            static fn (array $a, array $b): int => $b['total_score'] <=> $a['total_score'],
        );
        $winner = $scores[0];
        $winnerRow = $ready->first(static fn ($q) => (string) $q->vendor_id === (string) $winner['vendor_id'])
            ?? $ready->first();
        if ($winnerRow === null) {
            return;
        }

        $amount = (float) round((float) $ctx['estimated_value'] * (1 - (float) $ctx['savings_percentage'] / 100), 2);
        $awardId = (string) Str::ulid();
        DB::table('awards')->insert([
            'id' => $awardId,
            'tenant_id' => $this->tenantId,
            'rfq_id' => $rfqId,
            'comparison_run_id' => $runFinal,
            'vendor_id' => $winnerRow->vendor_id,
            'status' => 'signed_off',
            'amount' => $amount,
            'currency' => 'USD',
            'split_details' => json_encode([], JSON_THROW_ON_ERROR),
            'protest_id' => null,
            'signoff_at' => $this->now,
            'signed_off_by' => $this->userIds[0],
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);
        DB::table('handoffs')->insert([
            'id' => (string) Str::ulid(),
            'tenant_id' => $this->tenantId,
            'award_id' => $awardId,
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

    /**
     * @param list<array<string, mixed>> $rfqRows
     */
    private function seedDemoExtras(array $rfqRows): void
    {
        $awarded = array_values(array_filter($rfqRows, static fn ($c) => $c['status'] === 'awarded'));
        $demo = array_slice($awarded, 0, min(4, count($awarded)));
        foreach ($demo as $idx => $ctx) {
            $rfqId = (string) $ctx['rfq_id'];
            $runId = DB::table('comparison_runs')->where('rfq_id', $rfqId)->where('tenant_id', $this->tenantId)->where('status', 'final')->value('id');
            if ($runId === null) {
                continue;
            }
            DB::table('scenarios')->insert([
                'id' => (string) Str::ulid(),
                'tenant_id' => $this->tenantId,
                'rfq_id' => $rfqId,
                'comparison_run_id' => $runId,
                'name' => 'Baseline scenario ' . ($idx + 1),
                'description' => 'Seeded what-if scenario.',
                'config' => json_encode(['option' => 'baseline', 'fx' => 'USD'], JSON_THROW_ON_ERROR),
                'status' => 'draft',
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
            DB::table('negotiation_rounds')->insert([
                'id' => (string) Str::ulid(),
                'tenant_id' => $this->tenantId,
                'rfq_id' => $rfqId,
                'round_number' => $idx + 1,
                'status' => 'open',
                'started_at' => $this->now,
                'closed_at' => null,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
        }

        $firstAwarded = $awarded[0] ?? null;
        if ($firstAwarded !== null) {
            $rfqId = (string) $firstAwarded['rfq_id'];
            $runId = DB::table('comparison_runs')->where('rfq_id', $rfqId)->where('tenant_id', $this->tenantId)->where('status', 'final')->value('id');
            $approvalId = DB::table('approvals')->where('rfq_id', $rfqId)->where('tenant_id', $this->tenantId)->value('id');
            if ($runId !== null) {
                $payloadHash = hash('sha256', 'nfc-payload');
                $prevHash = hash('sha256', 'nfc-prev');
                foreach ([1, 2] as $seq) {
                    DB::table('decision_trail_entries')->insert([
                        'id' => (string) Str::ulid(),
                        'tenant_id' => $this->tenantId,
                        'comparison_run_id' => $runId,
                        'rfq_id' => $rfqId,
                        'sequence' => $seq,
                        'event_type' => 'comparison_run',
                        'payload_hash' => $payloadHash,
                        'previous_hash' => $prevHash,
                        'entry_hash' => hash('sha256', "nfc-entry-{$seq}"),
                        'occurred_at' => $this->now,
                        'created_at' => $this->now,
                        'updated_at' => $this->now,
                    ]);
                }
            }
            if ($approvalId !== null) {
                DB::table('evidence_bundles')->insert([
                    'id' => (string) Str::ulid(),
                    'tenant_id' => $this->tenantId,
                    'approval_id' => $approvalId,
                    'type' => 'quote_evidence',
                    'storage_path' => '/evidence/nfc-bundle.zip',
                    'checksum' => hash('sha256', 'nfc-bundle'),
                    'created_by' => $this->userIds[0],
                    'created_at' => $this->now,
                    'updated_at' => $this->now,
                ]);
            }
        }
    }

    private function seedInfrastructureRows(): void
    {
        for ($i = 1; $i <= 2; $i++) {
            $scheduleId = (string) Str::ulid();
            DB::table('report_schedules')->insert([
                'id' => $scheduleId,
                'tenant_id' => $this->tenantId,
                'report_type' => 'spend_summary',
                'frequency' => $i === 1 ? 'daily' : 'weekly',
                'config' => json_encode(['filters' => ['org' => 'NFC']], JSON_THROW_ON_ERROR),
                'last_run_at' => null,
                'next_run_at' => $this->now->copy()->addDays($i),
                'status' => 'active',
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
            DB::table('report_runs')->insert([
                'id' => (string) Str::ulid(),
                'tenant_id' => $this->tenantId,
                'schedule_id' => $scheduleId,
                'report_type' => 'spend_summary',
                'status' => 'completed',
                'started_at' => $this->now->copy()->subHours(3),
                'completed_at' => $this->now->copy()->subHours(2),
                'file_path' => "/reports/nfc-run-{$i}.csv",
                'parameters' => json_encode(['filters' => []], JSON_THROW_ON_ERROR),
                'error_message' => null,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
        }

        for ($i = 1; $i <= 2; $i++) {
            $integrationId = (string) Str::ulid();
            DB::table('integrations')->insert([
                'id' => $integrationId,
                'tenant_id' => $this->tenantId,
                'type' => 'erp',
                'name' => 'SAP S/4HANA NFC ' . $i,
                'config' => json_encode(['endpoint' => 'https://erp.nfc.example'], JSON_THROW_ON_ERROR),
                'status' => 'active',
                'last_sync_at' => null,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
            DB::table('integration_jobs')->insert([
                'id' => (string) Str::ulid(),
                'tenant_id' => $this->tenantId,
                'integration_id' => $integrationId,
                'type' => 'sync',
                'status' => 'pending',
                'payload' => json_encode(['job' => $i], JSON_THROW_ON_ERROR),
                'started_at' => null,
                'completed_at' => null,
                'error_message' => null,
                'retry_count' => 0,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
        }

        foreach ($this->userIds as $index => $userId) {
            DB::table('notifications')->insert([
                'id' => (string) Str::ulid(),
                'tenant_id' => $this->tenantId,
                'user_id' => $userId,
                'title' => 'NFC procurement — ' . ($index + 1),
                'message' => 'Seeded in-app notification.',
                'type' => 'info',
                'read_at' => null,
                'data' => json_encode(['seed' => true], JSON_THROW_ON_ERROR),
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
        }
    }
}
