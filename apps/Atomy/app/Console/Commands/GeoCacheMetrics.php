<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Nexus\Geo\Contracts\GeoRepositoryInterface;

final class GeoCacheMetrics extends Command
{
    protected $signature = 'geo:cache-metrics {--tenant=default-tenant : Tenant ID}';
    
    protected $description = 'Display geocoding cache performance metrics';

    public function __construct(
        private readonly GeoRepositoryInterface $geoRepository
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $tenantId = $this->option('tenant');

        $this->info("Geocoding Cache Metrics for Tenant: {$tenantId}");
        $this->newLine();

        $metrics = $this->geoRepository->getCacheMetrics($tenantId);

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Entries', number_format($metrics['total_entries'])],
                ['Active Entries', number_format($metrics['active_entries'])],
                ['Expired Entries', number_format($metrics['expired_entries'])],
                ['Hit Rate Estimate', $metrics['hit_rate_estimate'] . '%'],
            ]
        );

        if (!empty($metrics['provider_breakdown'])) {
            $this->newLine();
            $this->info('Provider Breakdown:');
            
            $providerRows = [];
            foreach ($metrics['provider_breakdown'] as $provider => $count) {
                $providerRows[] = [$provider, number_format($count)];
            }
            
            $this->table(['Provider', 'Count'], $providerRows);
        }

        // Recommendations
        $this->newLine();
        if ($metrics['hit_rate_estimate'] < 80) {
            $this->warn("⚠️  Cache hit rate is below target (80%). Consider increasing cache TTL.");
        } else {
            $this->info("✓ Cache hit rate is healthy (target: >80%)");
        }

        if ($metrics['expired_entries'] > 100) {
            $this->warn("⚠️  High number of expired entries. Run: php artisan geo:cache-prune");
        }

        return Command::SUCCESS;
    }
}
