<?php

declare(strict_types=1);

namespace Nexus\Laravel\Tenant\Jobs;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;

final class TenantPurgeJob extends Command
{
    protected $signature = 'tenant:purge-queued';

    protected $description = 'Permanently delete tenants whose retention period has expired';

    private const RFQ_RELATED_TABLES = [
        'approval_history',
        'evidence_bundles',
        'comparison_runs',
        'negotiation_rounds',
        'normalization_conflicts',
        'quote_submissions',
        'approvals',
        'awards',
        'handoffs',
        'risk_items',
        'report_runs',
        'report_schedules',
        'integration_jobs',
        'scoring_policies',
        'scoring_models',
        'scenarios',
        'rfq_line_items',
        'vendor_invitations',
    ];

    private const TENANT_SCOPED_TABLES = [
        'projects',
        'tasks',
        'rfqs',
        'users',
        'project_acl',
        'notifications',
        'integrations',
    ];

    public function handle(LoggerInterface $logger): int
    {
        $now = Carbon::now('UTC');
        $cutoff = $now->format('Y-m-d H:i:s');

        $tenantsToPurge = DB::table('tenants')
            ->where('status', 'queued_deletion')
            ->where('retention_hold_until', '<', $cutoff)
            ->get();

        if ($tenantsToPurge->isEmpty()) {
            $logger->info('No tenants to purge');
            $this->info('No tenants to purge.');
            return self::SUCCESS;
        }

        $purgedCount = 0;

        foreach ($tenantsToPurge as $tenant) {
            $logger->info('Purging tenant', ['tenant_id' => $tenant->id]);

            $this->cancelPendingInvitations($tenant->id, $logger);

            $this->hardDeleteTenantData($tenant->id, $logger);

            DB::table('tenants')->where('id', $tenant->id)->delete();

            $logger->info('Tenant purged', ['tenant_id' => $tenant->id]);
            $purgedCount++;
        }

        $this->info("Purged {$purgedCount} tenant(s).");
        $logger->info('Tenant purge completed', ['purged_count' => $purgedCount]);

        return self::SUCCESS;
    }

    private function cancelPendingInvitations(string $tenantId, LoggerInterface $logger): void
    {
        $this->cancelTenantInvitations($tenantId, $logger);
        $this->cancelVendorInvitations($tenantId, $logger);
    }

    private function cancelTenantInvitations(string $tenantId, LoggerInterface $logger): void
    {
        try {
            $count = DB::table('invitations')
                ->where('tenant_id', $tenantId)
                ->where('status', 'pending')
                ->update(['status' => 'cancelled']);

            if ($count > 0) {
                $logger->info('Cancelled pending invitations', ['tenant_id' => $tenantId, 'count' => $count]);
                $this->info("Cancelled {$count} pending invitation(s) for tenant {$tenantId}.");
            }
        } catch (\Throwable $e) {
            $logger->warning('Failed to cancel invitations', ['tenant_id' => $tenantId, 'error' => $e->getMessage()]);
        }
    }

    private function cancelVendorInvitations(string $tenantId, LoggerInterface $logger): void
    {
        try {
            $count = DB::table('vendor_invitations')
                ->where('tenant_id', $tenantId)
                ->where('status', 'pending')
                ->update(['status' => 'cancelled']);

            if ($count > 0) {
                $logger->info('Cancelled pending vendor invitations', ['tenant_id' => $tenantId, 'count' => $count]);
            }
        } catch (\Throwable $e) {
            $logger->warning('Failed to cancel vendor invitations', ['tenant_id' => $tenantId, 'error' => $e->getMessage()]);
        }
    }

    private function hardDeleteTenantData(string $tenantId, LoggerInterface $logger): void
    {
        $rfqIds = DB::table('rfqs')
            ->where('tenant_id', $tenantId)
            ->pluck('id')
            ->toArray();

        foreach (self::RFQ_RELATED_TABLES as $table) {
            $this->deleteByTenantOrRfq($table, $tenantId, $rfqIds, $logger);
        }

        foreach (self::TENANT_SCOPED_TABLES as $table) {
            $this->deleteByTenantId($table, $tenantId, $logger);
        }

        $this->deleteByTenantId('invitations', $tenantId, $logger);
    }

    private function deleteByTenantOrRfq(string $table, string $tenantId, array $rfqIds, LoggerInterface $logger): void
    {
        try {
            if ($this->tableExists($table)) {
                $count = 0;

                if (!empty($rfqIds)) {
                    $count = DB::table($table)->whereIn('rfq_id', $rfqIds)->delete();
                }

                if ($count > 0) {
                    $logger->debug("Deleted rfq-related records", ['table' => $table, 'tenant_id' => $tenantId, 'count' => $count]);
                }
            }
        } catch (\Throwable $e) {
            $logger->warning('Failed to delete rfq-related records', [
                'table' => $table,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function deleteByTenantId(string $table, string $tenantId, LoggerInterface $logger): void
    {
        try {
            if ($this->tableExists($table)) {
                $count = DB::table($table)->where('tenant_id', $tenantId)->delete();
                if ($count > 0) {
                    $logger->debug("Deleted tenant-scoped records", ['table' => $table, 'tenant_id' => $tenantId, 'count' => $count]);
                }
            }
        } catch (\Throwable $e) {
            $logger->warning('Failed to delete tenant-scoped records', [
                'table' => $table,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function tableExists(string $table): bool
    {
        return DB::getSchemaBuilder()->hasTable($table);
    }
}
