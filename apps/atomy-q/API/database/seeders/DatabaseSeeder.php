<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class DatabaseSeeder extends Seeder
{
    private const RFQ_COUNT_TENANT_A = 105;
    private const RFQ_COUNT_TENANT_B = 30;

    /** Deterministic float 0..1 from index */
    private static function hash(int $n): float
    {
        $x = sin($n * 9999) * 10000;
        return $x - floor($x);
    }

    /**
     * RFQ status lifecycle: draft -> published -> closed -> awarded.
     * Distribution: ~12% draft, ~25% published, ~25% closed, ~30% awarded, ~8% cancelled.
     */
    private static function rfqStatusForIndex(int $i): string
    {
        $h = self::hash($i);
        if ($h < 0.12) {
            return 'draft';
        }
        if ($h < 0.37) {
            return 'published';
        }
        if ($h < 0.62) {
            return 'closed';
        }
        if ($h < 0.92) {
            return 'awarded';
        }
        return 'cancelled';
    }

    /**
     * Seed the application with coherent lifecycle data: 100+ RFQs, multiple vendors and quotes per RFQ,
     * comparison runs only when there are enough accepted quotes, approvals aligned with run status,
     * awards only for awarded RFQs.
     */
    public function run(): void
    {
        $now = now();
        $seedTenant = env('ATOMY_SEED_TENANT_ID');
        $tenantA = $seedTenant !== null && $seedTenant !== '' ? (string) $seedTenant : '01KKH77M4R0V8QZ1M8NB3XWWWQ';
        $tenantB = (string) Str::ulid();
        $tenantIds = [$tenantA, $tenantB];

        /** @var array<string, list<string>> $usersByTenant */
        $usersByTenant = [];
        foreach ($tenantIds as $tIndex => $tid) {
            $usersByTenant[$tid] = [];
            $count = $tid === $tenantA ? 8 : 3;
            for ($i = 1; $i <= $count; $i++) {
                $userId = (string) Str::ulid();
                $usersByTenant[$tid][] = $userId;
                DB::table('users')->insert([
                    'id' => $userId,
                    'tenant_id' => $tid,
                    'email' => $tid === $tenantA ? "user{$i}@example.com" : "user{$i}-b@example.com",
                    'name' => $tid === $tenantA ? ["Alex Kumar", "Sarah Chen", "Marcus Webb", "Priya Nair", "James Okonkwo", "Elena Vasquez", "David Park", "Rachel Green"][$i - 1] : "User B{$i}",
                    'password_hash' => Hash::make('secret'),
                    'role' => $i === 1 ? 'admin' : 'user',
                    'status' => 'active',
                    'timezone' => 'UTC',
                    'locale' => 'en',
                    'email_verified_at' => $now,
                    'last_login_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
        $userIdsA = $usersByTenant[$tenantA];

        $categories = ['IT Hardware', 'Facilities', 'Software', 'Security', 'Marketing', 'Transport', 'IT Services', 'Professional Services', 'Office Supplies', 'Cloud Services'];
        $departments = ['Procurement', 'Finance', 'IT', 'Operations', 'Facilities', 'HR'];
        $titles = ['Server Infrastructure Refresh', 'Office Furniture Q2', 'Cloud Software Licenses', 'Network Security Audit', 'Marketing Print Materials', 'Fleet Vehicle Leasing', 'Catering Services Contract', 'IT Support Annual', 'Data Center Hardware', 'Endpoint Security Suite', 'ERP Module Upgrade', 'Consulting Services Retainer', 'Backup and DR Solution', 'Unified Communications', 'Warehouse Equipment', 'Facility Maintenance', 'Training and Development', 'Legal Services Panel', 'Travel Management', 'Temporary Staffing'];
        $vendorNames = ['Dell Technologies', 'HP Enterprise', 'Lenovo', 'Cisco Systems', 'IBM', 'Fujitsu', 'Supermicro', 'Juniper Networks', 'Microsoft', 'Oracle', 'SAP', 'Amazon Web Services', 'Google Cloud', 'VMware', 'Accenture', 'Deloitte', 'Capgemini', 'Office Depot', 'Staples', 'CDW', 'Insight', 'SHI', 'Carahsoft', 'Tech Data', 'Ingram Micro'];

        /** @var array<string, array{rfq_id: string, status: string, vendors_count: int, quotes_count: int, owner_id: string, estimated_value: float, savings_percentage: float}> $rfqMeta */
        $rfqMeta = [];
        $rfqIds = [];
        $lineItemsByRfq = [];

        foreach ([$tenantA => self::RFQ_COUNT_TENANT_A, $tenantB => self::RFQ_COUNT_TENANT_B] as $tenantId => $perTenantCount) {
            $userIds = $usersByTenant[$tenantId];
            for ($seq = 1; $seq <= $perTenantCount; $seq++) {
                $rfqId = (string) Str::ulid();
                $rfqIds[] = $rfqId;
                $idx = count($rfqIds);
                $status = self::rfqStatusForIndex($idx);

                $vendorsCount = match ($status) {
                    'draft' => (int) floor(self::hash($idx + 10) * 3),
                    'cancelled' => (int) floor(self::hash($idx + 10) * 4),
                    default => 3 + (int) floor(self::hash($idx + 11) * 6),
                };

                $quotesCount = match ($status) {
                    'draft', 'cancelled' => 0,
                    'published' => min($vendorsCount, (int) floor(self::hash($idx + 12) * 4)),
                    default => min($vendorsCount, 3 + (int) floor(self::hash($idx + 13) * 6)),
                };

                $ownerId = $userIds[array_key_first($userIds)];
                $estimatedValue = 15000 + ($idx * 8000) + (int) (self::hash($idx + 1) * 50000);
                $savingsPct = 3.0 + (float) ((int) (self::hash($idx + 2) * 15));

                $rfqMeta[$rfqId] = [
                    'rfq_id' => $rfqId,
                    'status' => $status,
                    'vendors_count' => $vendorsCount,
                    'quotes_count' => $quotesCount,
                    'owner_id' => $ownerId,
                    'estimated_value' => $estimatedValue,
                    'savings_percentage' => $savingsPct,
                ];

                DB::table('rfqs')->insert([
                    'id' => $rfqId,
                    'tenant_id' => $tenantId,
                    'rfq_number' => sprintf('RFQ-2026-%04d', $seq + ($tenantId === $tenantB ? 1000 : 0)),
                    'title' => $titles[$idx % count($titles)] . ($idx > 20 ? " #{$idx}" : ''),
                    'description' => "Seeded RFQ for lifecycle coverage.",
                    'category' => $categories[$idx % count($categories)],
                    'department' => $departments[$idx % count($departments)],
                    'status' => $status,
                    'owner_id' => $ownerId,
                    'estimated_value' => $estimatedValue,
                    'savings_percentage' => $savingsPct,
                    'submission_deadline' => $now->copy()->addDays(5 + ($idx % 60)),
                    'closing_date' => $now->copy()->addDays(14 + ($idx % 45)),
                    'payment_terms' => 'Net 30',
                    'evaluation_method' => 'weighted',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $lineCount = 5 + (int) (self::hash($idx + 20) * 15);
                $lineItemsByRfq[$rfqId] = [];
                for ($j = 1; $j <= $lineCount; $j++) {
                    $lineItemId = (string) Str::ulid();
                    $lineItemsByRfq[$rfqId][] = $lineItemId;
                    DB::table('rfq_line_items')->insert([
                        'id' => $lineItemId,
                        'tenant_id' => $tenantId,
                        'rfq_id' => $rfqId,
                        'description' => "Line item {$j}",
                        'quantity' => (float) (random_int(1, 100) * ($j % 3 + 1)),
                        'uom' => ['ea', 'kg', 'm', 'box'][$j % 4],
                        'unit_price' => (float) (50 + random_int(0, 500) + $j * 2.5),
                        'currency' => 'USD',
                        'specifications' => "Spec {$j}",
                        'sort_order' => $j,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }

        $templateIds = [];
        for ($i = 1; $i <= 2; $i++) {
            $templateId = (string) Str::ulid();
            $templateIds[] = $templateId;
            DB::table('rfq_templates')->insert([
                'id' => $templateId,
                'tenant_id' => $tenantA,
                'name' => "Standard Template {$i}",
                'description' => 'Template for recurring sourcing events.',
                'category' => 'IT',
                'department' => 'Procurement',
                'line_items_schema' => json_encode([['label' => 'Description', 'type' => 'text'], ['label' => 'Quantity', 'type' => 'number']], JSON_THROW_ON_ERROR),
                'status' => 'active',
                'created_by' => $userIdsA[0],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $scoringModelId = (string) Str::ulid();
        DB::table('scoring_models')->insert([
            'id' => $scoringModelId,
            'tenant_id' => $tenantA,
            'name' => 'Weighted Model',
            'description' => 'Seeded scoring model.',
            'type' => 'weighted',
            'config' => json_encode(['version' => 2], JSON_THROW_ON_ERROR),
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $scoringPolicyId = (string) Str::ulid();
        DB::table('scoring_policies')->insert([
            'id' => $scoringPolicyId,
            'tenant_id' => $tenantA,
            'scoring_model_id' => $scoringModelId,
            'name' => 'Default Policy',
            'description' => 'Seeded policy.',
            'weights' => json_encode(['price' => 0.5, 'quality' => 0.5], JSON_THROW_ON_ERROR),
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        /** @var array<string, list<string>> $vendorInvitationIdsByRfq rfq_id -> [invitation_id, ...] */
        $vendorInvitationIdsByRfq = [];
        /** @var array<string, string> $invitationIdToVendorId invitation_id -> vendor_id (ulid) */
        $invitationIdToVendorId = [];
        /** @var array<string, string> $invitationIdToVendorName */
        $invitationIdToVendorName = [];

        foreach ($rfqMeta as $rfqId => $meta) {
            $tenantId = null;
            foreach ([$tenantA, $tenantB] as $tid) {
                if (DB::table('rfqs')->where('id', $rfqId)->where('tenant_id', $tid)->exists()) {
                    $tenantId = $tid;
                    break;
                }
            }
            if ($tenantId === null) {
                continue;
            }
            $n = $meta['vendors_count'];
            $vendorInvitationIdsByRfq[$rfqId] = [];
            for ($v = 0; $v < $n; $v++) {
                $vendorId = (string) Str::ulid();
                $invitationId = (string) Str::ulid();
                $vendorInvitationIdsByRfq[$rfqId][] = $invitationId;
                $invitationIdToVendorId[$invitationId] = $vendorId;
                $name = $vendorNames[($v + (int) hexdec(substr($rfqId, -6))) % count($vendorNames)];
                $invitationIdToVendorName[$invitationId] = $name;
                DB::table('vendor_invitations')->insert([
                    'id' => $invitationId,
                    'tenant_id' => $tenantId,
                    'rfq_id' => $rfqId,
                    'vendor_id' => $vendorId,
                    'vendor_email' => strtolower(str_replace(' ', '.', $name)) . '@vendor.com',
                    'vendor_name' => $name,
                    'status' => $v < $meta['quotes_count'] ? 'accepted' : 'pending',
                    'invited_at' => $now,
                    'responded_at' => $v < $meta['quotes_count'] ? $now : null,
                    'channel' => 'email',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        /** @var array<string, list<string>> $quoteIdsByRfq */
        $quoteIdsByRfq = [];
        foreach ($rfqMeta as $rfqId => $meta) {
            if ($meta['quotes_count'] === 0) {
                $quoteIdsByRfq[$rfqId] = [];
                continue;
            }
            $tenantId = DB::table('rfqs')->where('id', $rfqId)->value('tenant_id');
            $invIds = $vendorInvitationIdsByRfq[$rfqId] ?? [];
            $quoteIdsByRfq[$rfqId] = [];
            $status = $meta['status'];
            foreach (array_slice($invIds, 0, $meta['quotes_count']) as $qIndex => $invId) {
                $quoteId = (string) Str::ulid();
                $quoteIdsByRfq[$rfqId][] = $quoteId;
                $vendorId = $invitationIdToVendorId[$invId] ?? (string) Str::ulid();
                $vendorName = $invitationIdToVendorName[$invId] ?? 'Vendor';
                $quoteStatus = match ($status) {
                    'published' => ['processing', 'parsed', 'accepted'][(int) (self::hash((int) (hexdec(substr($rfqId, -6))) + $qIndex) * 3)],
                    'closed', 'awarded' => self::hash((int) (hexdec(substr($rfqId, -6))) + $qIndex + 1) > 0.08 ? 'accepted' : 'rejected',
                    default => 'accepted',
                };
                DB::table('quote_submissions')->insert([
                    'id' => $quoteId,
                    'tenant_id' => $tenantId,
                    'rfq_id' => $rfqId,
                    'vendor_id' => $vendorId,
                    'vendor_name' => $vendorName,
                    'status' => $quoteStatus,
                    'file_path' => "/uploads/{$vendorName}_Quote.pdf",
                    'file_type' => 'application/pdf',
                    'submitted_at' => $now->copy()->subDays($qIndex % 5),
                    'confidence' => 80.0 + (self::hash($qIndex + 1) * 20),
                    'line_items_count' => count($lineItemsByRfq[$rfqId] ?? []),
                    'warnings_count' => 0,
                    'errors_count' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        /** @var array<string, list<string>> $comparisonRunIdsByRfq */
        $comparisonRunIdsByRfq = [];
        foreach ($rfqMeta as $rfqId => $meta) {
            $tenantId = DB::table('rfqs')->where('id', $rfqId)->value('tenant_id');
            $acceptedCount = 0;
            foreach ($quoteIdsByRfq[$rfqId] ?? [] as $qid) {
                if (DB::table('quote_submissions')->where('id', $qid)->value('status') === 'accepted') {
                    $acceptedCount++;
                }
            }
            $status = $meta['status'];
            if (($status === 'closed' || $status === 'awarded' || $status === 'published') && $acceptedCount >= 2) {
                $runCount = $status === 'awarded' ? 2 : ($status === 'closed' ? 1 + (int) (self::hash(hexdec(substr($rfqId, -6))) * 2) : 1);
                $comparisonRunIdsByRfq[$rfqId] = [];
                for ($r = 0; $r < $runCount; $r++) {
                    $runId = (string) Str::ulid();
                    $comparisonRunIdsByRfq[$rfqId][] = $runId;
                    $isLast = $r === $runCount - 1;
                    $runStatus = $status === 'awarded' && $isLast ? 'locked' : ($status === 'closed' && $isLast ? (self::hash($r + 1) > 0.5 ? 'stale' : 'generated') : 'generated');
                    DB::table('comparison_runs')->insert([
                        'id' => $runId,
                        'tenant_id' => $tenantId,
                        'rfq_id' => $rfqId,
                        'name' => "Run " . ($r + 1),
                        'description' => 'Seeded comparison run.',
                        'idempotency_key' => "run-{$rfqId}-{$r}",
                        'is_preview' => !$isLast,
                        'created_by' => $userIdsA[0],
                        'request_payload' => json_encode(['rfq_id' => $rfqId], JSON_THROW_ON_ERROR),
                        'matrix_payload' => json_encode(['matrix' => []], JSON_THROW_ON_ERROR),
                        'scoring_payload' => json_encode(['scores' => []], JSON_THROW_ON_ERROR),
                        'approval_payload' => json_encode([], JSON_THROW_ON_ERROR),
                        'response_payload' => json_encode(['status' => 'ok'], JSON_THROW_ON_ERROR),
                        'readiness_payload' => json_encode(['ready' => true], JSON_THROW_ON_ERROR),
                        'status' => $runStatus,
                        'version' => 1,
                        'expires_at' => $now->copy()->addDays(2),
                        'discarded_at' => null,
                        'discarded_by' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            } else {
                $comparisonRunIdsByRfq[$rfqId] = [];
            }
        }

        foreach ($rfqMeta as $rfqId => $meta) {
            $tenantId = DB::table('rfqs')->where('id', $rfqId)->value('tenant_id');
            $runIds = $comparisonRunIdsByRfq[$rfqId] ?? [];
            if (count($runIds) === 0) {
                continue;
            }
            $lastRunId = $runIds[array_key_last($runIds)];
            $approvalId = (string) Str::ulid();
            $status = $meta['status'];
            $approvalApproved = $status === 'awarded';
            DB::table('approvals')->insert([
                'id' => $approvalId,
                'tenant_id' => $tenantId,
                'rfq_id' => $rfqId,
                'comparison_run_id' => $lastRunId,
                'type' => 'comparison_approval',
                'status' => $approvalApproved ? 'approved' : 'pending',
                'requested_by' => $userIdsA[0],
                'requested_at' => $now,
                'amount' => $meta['estimated_value'],
                'currency' => 'USD',
                'level' => 1,
                'notes' => 'Seeded approval.',
                'approved_at' => $approvalApproved ? $now : null,
                'approved_by' => $approvalApproved ? $userIdsA[0] : null,
                'snoozed_until' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('approval_history')->insert([
                'id' => (string) Str::ulid(),
                'tenant_id' => $tenantId,
                'approval_id' => $approvalId,
                'action' => $approvalApproved ? 'approved' : 'requested',
                'actor_id' => $userIdsA[0],
                'reason' => null,
                'metadata' => json_encode([], JSON_THROW_ON_ERROR),
                'created_at' => $now,
            ]);
        }

        foreach ($rfqMeta as $rfqId => $meta) {
            if ($meta['status'] !== 'awarded') {
                continue;
            }
            $tenantId = DB::table('rfqs')->where('id', $rfqId)->value('tenant_id');
            $runIds = $comparisonRunIdsByRfq[$rfqId] ?? [];
            $lastRunId = $runIds[array_key_last($runIds)] ?? null;
            $acceptedQuotes = [];
            foreach ($quoteIdsByRfq[$rfqId] ?? [] as $qid) {
                $row = DB::table('quote_submissions')->where('id', $qid)->first();
                if ($row && $row->status === 'accepted') {
                    $acceptedQuotes[] = $row;
                }
            }
            if (count($acceptedQuotes) === 0) {
                continue;
            }
            $winner = $acceptedQuotes[(int) (self::hash(hexdec(substr($rfqId, -6)) + 30) * count($acceptedQuotes))];
            $awardId = (string) Str::ulid();
            $amount = (float) round($meta['estimated_value'] * (1 - $meta['savings_percentage'] / 100));
            DB::table('awards')->insert([
                'id' => $awardId,
                'tenant_id' => $tenantId,
                'rfq_id' => $rfqId,
                'comparison_run_id' => $lastRunId,
                'vendor_id' => $winner->vendor_id,
                'status' => 'signed_off',
                'amount' => $amount,
                'currency' => 'USD',
                'split_details' => json_encode([], JSON_THROW_ON_ERROR),
                'protest_id' => null,
                'signoff_at' => $now,
                'signed_off_by' => $userIdsA[0],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('handoffs')->insert([
                'id' => (string) Str::ulid(),
                'tenant_id' => $tenantId,
                'award_id' => $awardId,
                'destination_type' => 'erp',
                'destination_id' => 'ERP-1',
                'status' => 'pending',
                'sent_at' => null,
                'retry_count' => 0,
                'error_message' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $demoRfqIds = array_slice(array_keys(array_filter($rfqMeta, fn($m) => $m['status'] === 'awarded')), 0, 3);
        if (count($demoRfqIds) === 0) {
            $demoRfqIds = array_slice(array_keys($rfqMeta), 0, 3);
        }

        foreach ($demoRfqIds as $index => $rfqId) {
            $tenantId = DB::table('rfqs')->where('id', $rfqId)->value('tenant_id');
            $quoteIds = $quoteIdsByRfq[$rfqId] ?? [];
            $firstQuoteId = $quoteIds[0] ?? null;
            if ($firstQuoteId === null) {
                continue;
            }
            foreach (array_slice($lineItemsByRfq[$rfqId] ?? [], 0, 3) as $lineIndex => $lineItemId) {
                DB::table('normalization_source_lines')->insert([
                    'id' => (string) Str::ulid(),
                    'tenant_id' => $tenantId,
                    'quote_submission_id' => $firstQuoteId,
                    'rfq_line_item_id' => $lineItemId,
                    'source_vendor' => 'Vendor',
                    'source_description' => "Source line {$lineIndex}",
                    'source_quantity' => 5 + $lineIndex,
                    'source_uom' => 'ea',
                    'source_unit_price' => 100.0 + ($lineIndex * 10),
                    'raw_data' => json_encode(['raw' => 'payload'], JSON_THROW_ON_ERROR),
                    'sort_order' => $lineIndex + 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $firstRunId = null;
        foreach ($comparisonRunIdsByRfq[$demoRfqIds[0] ?? ''] ?? [] as $rid) {
            $firstRunId = $rid;
            break;
        }
        if ($firstRunId !== null) {
            $payloadHash = hash('sha256', 'payload');
            $prevHash = hash('sha256', 'previous');
            foreach ([1, 2] as $seq) {
                DB::table('decision_trail_entries')->insert([
                    'id' => (string) Str::ulid(),
                    'tenant_id' => $tenantA,
                    'comparison_run_id' => $firstRunId,
                    'rfq_id' => $demoRfqIds[0],
                    'sequence' => $seq,
                    'event_type' => 'comparison_run',
                    'payload_hash' => $payloadHash,
                    'previous_hash' => $prevHash,
                    'entry_hash' => hash('sha256', "entry-{$seq}"),
                    'occurred_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $firstApprovalId = DB::table('approvals')->where('rfq_id', $demoRfqIds[0] ?? '')->value('id');
        if ($firstApprovalId !== null) {
            DB::table('evidence_bundles')->insert([
                'id' => (string) Str::ulid(),
                'tenant_id' => $tenantA,
                'approval_id' => $firstApprovalId,
                'type' => 'quote_evidence',
                'storage_path' => '/evidence/bundle-1.zip',
                'checksum' => hash('sha256', 'bundle-1'),
                'created_by' => $userIdsA[0],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach ($demoRfqIds as $index => $rfqId) {
            DB::table('scenarios')->insert([
                'id' => (string) Str::ulid(),
                'tenant_id' => $tenantA,
                'rfq_id' => $rfqId,
                'comparison_run_id' => ($comparisonRunIdsByRfq[$rfqId] ?? [])[0] ?? null,
                'name' => "Scenario {$index}",
                'description' => 'Seeded scenario',
                'config' => json_encode(['option' => 'baseline'], JSON_THROW_ON_ERROR),
                'status' => 'draft',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('negotiation_rounds')->insert([
                'id' => (string) Str::ulid(),
                'tenant_id' => $tenantA,
                'rfq_id' => $rfqId,
                'round_number' => $index + 1,
                'status' => 'open',
                'started_at' => $now,
                'closed_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('risk_items')->insert([
                'id' => (string) Str::ulid(),
                'tenant_id' => $tenantA,
                'rfq_id' => $rfqId,
                'severity' => $index === 0 ? 'high' : 'medium',
                'title' => "Risk Item {$index}",
                'description' => 'Seeded risk entry.',
                'source' => 'system',
                'status' => 'open',
                'resolved_at' => null,
                'resolved_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        for ($i = 1; $i <= 2; $i++) {
            $scheduleId = (string) Str::ulid();
            DB::table('report_schedules')->insert([
                'id' => $scheduleId,
                'tenant_id' => $tenantA,
                'report_type' => 'spend_summary',
                'frequency' => $i === 1 ? 'daily' : 'weekly',
                'config' => json_encode(['filters' => []], JSON_THROW_ON_ERROR),
                'last_run_at' => null,
                'next_run_at' => $now->copy()->addDays($i),
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('report_runs')->insert([
                'id' => (string) Str::ulid(),
                'tenant_id' => $tenantA,
                'schedule_id' => $scheduleId,
                'report_type' => 'spend_summary',
                'status' => 'completed',
                'started_at' => $now->copy()->subHours(2),
                'completed_at' => $now->copy()->subHours(1),
                'file_path' => "/reports/run-{$i}.csv",
                'parameters' => json_encode(['filters' => []], JSON_THROW_ON_ERROR),
                'error_message' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        for ($i = 1; $i <= 2; $i++) {
            $integrationId = (string) Str::ulid();
            DB::table('integrations')->insert([
                'id' => $integrationId,
                'tenant_id' => $tenantA,
                'type' => 'erp',
                'name' => "ERP Integration {$i}",
                'config' => json_encode(['endpoint' => 'https://example.com'], JSON_THROW_ON_ERROR),
                'status' => 'active',
                'last_sync_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('integration_jobs')->insert([
                'id' => (string) Str::ulid(),
                'tenant_id' => $tenantA,
                'integration_id' => $integrationId,
                'type' => 'sync',
                'status' => 'pending',
                'payload' => json_encode(['job' => $i], JSON_THROW_ON_ERROR),
                'started_at' => null,
                'completed_at' => null,
                'error_message' => null,
                'retry_count' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach ($userIdsA as $index => $userId) {
            DB::table('notifications')->insert([
                'id' => (string) Str::ulid(),
                'tenant_id' => $tenantA,
                'user_id' => $userId,
                'title' => "Notification {$index}",
                'message' => 'Seeded notification.',
                'type' => 'info',
                'read_at' => null,
                'data' => json_encode(['meta' => true], JSON_THROW_ON_ERROR),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
