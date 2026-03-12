<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with ample mock data.
     */
    public function run(): void
    {
        $now = now();
        $seedTenant = env('ATOMY_SEED_TENANT_ID');
        $tenantId = $seedTenant !== null && $seedTenant !== '' ? (string) $seedTenant : (string) Str::ulid();

        $userIds = [];
        for ($i = 1; $i <= 5; $i++) {
            $userId = (string) Str::ulid();
            $userIds[] = $userId;

            DB::table('users')->insert([
                'id' => $userId,
                'tenant_id' => $tenantId,
                'email' => "user{$i}@example.com",
                'name' => "User {$i}",
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

        $rfqIds = [];
        $lineItemsByRfq = [];
        for ($i = 1; $i <= 3; $i++) {
            $rfqId = (string) Str::ulid();
            $rfqIds[] = $rfqId;

            DB::table('rfqs')->insert([
                'id' => $rfqId,
                'tenant_id' => $tenantId,
                'rfq_number' => sprintf('RFQ-2026-%04d', $i),
                'title' => "RFQ {$i}",
                'description' => "Seeded RFQ {$i} for coverage tests.",
                'category' => 'IT',
                'department' => 'Procurement',
                'status' => 'draft',
                'owner_id' => $userIds[0],
                'estimated_value' => 10000 * $i,
                'savings_percentage' => 5.25,
                'submission_deadline' => $now->copy()->addDays(7),
                'closing_date' => $now->copy()->addDays(14),
                'payment_terms' => 'Net 30',
                'evaluation_method' => 'weighted',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $lineItemsByRfq[$rfqId] = [];
            for ($j = 1; $j <= 2; $j++) {
                $lineItemId = (string) Str::ulid();
                $lineItemsByRfq[$rfqId][] = $lineItemId;

                DB::table('rfq_line_items')->insert([
                    'id' => $lineItemId,
                    'tenant_id' => $tenantId,
                    'rfq_id' => $rfqId,
                    'description' => "Line item {$j} for RFQ {$i}",
                    'quantity' => 10 * $j,
                    'uom' => 'ea',
                    'unit_price' => 125.50 * $j,
                    'currency' => 'USD',
                    'specifications' => "Spec {$j} for RFQ {$i}",
                    'sort_order' => $j,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $templateIds = [];
        for ($i = 1; $i <= 2; $i++) {
            $templateId = (string) Str::ulid();
            $templateIds[] = $templateId;

            DB::table('rfq_templates')->insert([
                'id' => $templateId,
                'tenant_id' => $tenantId,
                'name' => "Standard Template {$i}",
                'description' => 'Template for recurring sourcing events.',
                'category' => 'IT',
                'department' => 'Procurement',
                'line_items_schema' => json_encode([
                    ['label' => 'Description', 'type' => 'text'],
                    ['label' => 'Quantity', 'type' => 'number'],
                ], JSON_THROW_ON_ERROR),
                'status' => 'active',
                'created_by' => $userIds[0],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $vendorIds = [];
        $invitationIds = [];
        foreach ($rfqIds as $rfqIndex => $rfqId) {
            for ($v = 1; $v <= 2; $v++) {
                $vendorId = (string) Str::ulid();
                $vendorIds[] = $vendorId;
                $invitationId = (string) Str::ulid();
                $invitationIds[] = $invitationId;

                DB::table('vendor_invitations')->insert([
                    'id' => $invitationId,
                    'tenant_id' => $tenantId,
                    'rfq_id' => $rfqId,
                    'vendor_id' => $vendorId,
                    'vendor_email' => "vendor{$rfqIndex}{$v}@example.com",
                    'vendor_name' => "Vendor {$rfqIndex}-{$v}",
                    'status' => 'pending',
                    'invited_at' => $now,
                    'responded_at' => null,
                    'channel' => 'email',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $quoteSubmissionIds = [];
        foreach (array_slice($vendorIds, 0, 3) as $index => $vendorId) {
            $quoteSubmissionId = (string) Str::ulid();
            $quoteSubmissionIds[] = $quoteSubmissionId;

            DB::table('quote_submissions')->insert([
                'id' => $quoteSubmissionId,
                'tenant_id' => $tenantId,
                'rfq_id' => $rfqIds[0],
                'vendor_id' => $vendorId,
                'vendor_name' => "Vendor {$index}",
                'status' => 'processed',
                'file_path' => "/uploads/quote-{$index}.pdf",
                'file_type' => 'application/pdf',
                'submitted_at' => $now->copy()->subDays(1),
                'confidence' => 95.5,
                'line_items_count' => 2,
                'warnings_count' => 0,
                'errors_count' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $normalizationSourceLineIds = [];
        foreach ($quoteSubmissionIds as $index => $quoteSubmissionId) {
            foreach ($lineItemsByRfq[$rfqIds[0]] as $lineIndex => $lineItemId) {
                $sourceLineId = (string) Str::ulid();
                $normalizationSourceLineIds[] = $sourceLineId;

                DB::table('normalization_source_lines')->insert([
                    'id' => $sourceLineId,
                    'tenant_id' => $tenantId,
                    'quote_submission_id' => $quoteSubmissionId,
                    'rfq_line_item_id' => $lineItemId,
                    'source_vendor' => "Vendor {$index}",
                    'source_description' => "Source line {$lineIndex} for vendor {$index}",
                    'source_quantity' => 5 + $lineIndex,
                    'source_uom' => 'ea',
                    'source_unit_price' => 100.00 + ($lineIndex * 10),
                    'raw_data' => json_encode(['raw' => 'payload'], JSON_THROW_ON_ERROR),
                    'sort_order' => $lineIndex + 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $conflictId = (string) Str::ulid();
        DB::table('normalization_conflicts')->insert([
            'id' => $conflictId,
            'tenant_id' => $tenantId,
            'normalization_source_line_id' => $normalizationSourceLineIds[0],
            'conflict_type' => 'uom_mismatch',
            'resolution' => null,
            'resolved_at' => null,
            'resolved_by' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $scoringModelIds = [];
        for ($i = 1; $i <= 2; $i++) {
            $scoringModelId = (string) Str::ulid();
            $scoringModelIds[] = $scoringModelId;

            DB::table('scoring_models')->insert([
                'id' => $scoringModelId,
                'tenant_id' => $tenantId,
                'name' => "Weighted Model {$i}",
                'description' => 'Seeded scoring model.',
                'type' => 'weighted',
                'config' => json_encode(['version' => $i], JSON_THROW_ON_ERROR),
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $scoringPolicyIds = [];
        for ($i = 1; $i <= 2; $i++) {
            $policyId = (string) Str::ulid();
            $scoringPolicyIds[] = $policyId;

            DB::table('scoring_policies')->insert([
                'id' => $policyId,
                'tenant_id' => $tenantId,
                'scoring_model_id' => $scoringModelIds[0],
                'name' => "Policy {$i}",
                'description' => 'Seeded scoring policy.',
                'weights' => json_encode(['price' => 0.5, 'quality' => 0.5], JSON_THROW_ON_ERROR),
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $comparisonRunIds = [];
        foreach ($rfqIds as $index => $rfqId) {
            $comparisonRunId = (string) Str::ulid();
            $comparisonRunIds[] = $comparisonRunId;

            DB::table('comparison_runs')->insert([
                'id' => $comparisonRunId,
                'tenant_id' => $tenantId,
                'rfq_id' => $rfqId,
                'name' => "Comparison Run {$index}",
                'description' => 'Seeded run for coverage.',
                'idempotency_key' => "run-{$index}",
                'is_preview' => $index === 0,
                'created_by' => $userIds[0],
                'request_payload' => json_encode(['rfq_id' => $rfqId], JSON_THROW_ON_ERROR),
                'matrix_payload' => json_encode(['matrix' => []], JSON_THROW_ON_ERROR),
                'scoring_payload' => json_encode(['scores' => []], JSON_THROW_ON_ERROR),
                'approval_payload' => json_encode(['approvals' => []], JSON_THROW_ON_ERROR),
                'response_payload' => json_encode(['status' => 'ok'], JSON_THROW_ON_ERROR),
                'readiness_payload' => json_encode(['ready' => true], JSON_THROW_ON_ERROR),
                'status' => 'draft',
                'version' => 1,
                'expires_at' => $now->copy()->addDays(2),
                'discarded_at' => null,
                'discarded_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $scenarioIds = [];
        foreach ($rfqIds as $index => $rfqId) {
            $scenarioId = (string) Str::ulid();
            $scenarioIds[] = $scenarioId;

            DB::table('scenarios')->insert([
                'id' => $scenarioId,
                'tenant_id' => $tenantId,
                'rfq_id' => $rfqId,
                'comparison_run_id' => $comparisonRunIds[$index] ?? null,
                'name' => "Scenario {$index}",
                'description' => 'Seeded scenario',
                'config' => json_encode(['option' => 'baseline'], JSON_THROW_ON_ERROR),
                'status' => 'draft',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $approvalIds = [];
        foreach ($rfqIds as $index => $rfqId) {
            $approvalId = (string) Str::ulid();
            $approvalIds[] = $approvalId;

            DB::table('approvals')->insert([
                'id' => $approvalId,
                'tenant_id' => $tenantId,
                'rfq_id' => $rfqId,
                'comparison_run_id' => $comparisonRunIds[$index] ?? null,
                'type' => 'quote_approval',
                'status' => 'pending',
                'requested_by' => $userIds[0],
                'requested_at' => $now,
                'amount' => 5000 + ($index * 1000),
                'currency' => 'USD',
                'level' => 1,
                'notes' => 'Seeded approval request.',
                'approved_at' => null,
                'approved_by' => null,
                'snoozed_until' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('approval_history')->insert([
                'id' => (string) Str::ulid(),
                'tenant_id' => $tenantId,
                'approval_id' => $approvalId,
                'action' => 'requested',
                'actor_id' => $userIds[0],
                'reason' => null,
                'metadata' => json_encode(['note' => 'Seeded history'], JSON_THROW_ON_ERROR),
                'created_at' => $now,
            ]);
        }

        foreach ($rfqIds as $index => $rfqId) {
            DB::table('negotiation_rounds')->insert([
                'id' => (string) Str::ulid(),
                'tenant_id' => $tenantId,
                'rfq_id' => $rfqId,
                'round_number' => $index + 1,
                'status' => 'open',
                'started_at' => $now,
                'closed_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $awardIds = [];
        foreach ($rfqIds as $index => $rfqId) {
            $awardId = (string) Str::ulid();
            $awardIds[] = $awardId;

            DB::table('awards')->insert([
                'id' => $awardId,
                'tenant_id' => $tenantId,
                'rfq_id' => $rfqId,
                'comparison_run_id' => $comparisonRunIds[$index] ?? null,
                'vendor_id' => $vendorIds[$index] ?? (string) Str::ulid(),
                'status' => 'pending',
                'amount' => 2000 + ($index * 750),
                'currency' => 'USD',
                'split_details' => json_encode(['items' => []], JSON_THROW_ON_ERROR),
                'protest_id' => null,
                'signoff_at' => null,
                'signed_off_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('handoffs')->insert([
                'id' => (string) Str::ulid(),
                'tenant_id' => $tenantId,
                'award_id' => $awardId,
                'destination_type' => 'erp',
                'destination_id' => "ERP-{$index}",
                'status' => 'pending',
                'sent_at' => null,
                'retry_count' => 0,
                'error_message' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $payloadHash = hash('sha256', 'payload');
        $previousHash = hash('sha256', 'previous');
        foreach ([1, 2] as $sequence) {
            DB::table('decision_trail_entries')->insert([
                'id' => (string) Str::ulid(),
                'tenant_id' => $tenantId,
                'comparison_run_id' => $comparisonRunIds[0],
                'rfq_id' => $rfqIds[0],
                'sequence' => $sequence,
                'event_type' => 'comparison_run',
                'payload_hash' => $payloadHash,
                'previous_hash' => $previousHash,
                'entry_hash' => hash('sha256', "entry-{$sequence}"),
                'occurred_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('evidence_bundles')->insert([
            'id' => (string) Str::ulid(),
            'tenant_id' => $tenantId,
            'approval_id' => $approvalIds[0],
            'type' => 'quote_evidence',
            'storage_path' => '/evidence/bundle-1.zip',
            'checksum' => hash('sha256', 'bundle-1'),
            'created_by' => $userIds[0],
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $reportScheduleIds = [];
        for ($i = 1; $i <= 2; $i++) {
            $scheduleId = (string) Str::ulid();
            $reportScheduleIds[] = $scheduleId;

            DB::table('report_schedules')->insert([
                'id' => $scheduleId,
                'tenant_id' => $tenantId,
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
                'tenant_id' => $tenantId,
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

        $integrationIds = [];
        for ($i = 1; $i <= 2; $i++) {
            $integrationId = (string) Str::ulid();
            $integrationIds[] = $integrationId;

            DB::table('integrations')->insert([
                'id' => $integrationId,
                'tenant_id' => $tenantId,
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
                'tenant_id' => $tenantId,
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

        foreach ($userIds as $index => $userId) {
            DB::table('notifications')->insert([
                'id' => (string) Str::ulid(),
                'tenant_id' => $tenantId,
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

        foreach ($rfqIds as $index => $rfqId) {
            DB::table('risk_items')->insert([
                'id' => (string) Str::ulid(),
                'tenant_id' => $tenantId,
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
    }
}
