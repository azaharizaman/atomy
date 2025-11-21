<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Nexus\Routing\Contracts\RouteCacheInterface;

final class RoutingCacheMetrics extends Command
{
    protected $signature = 'routing:cache-metrics {--tenant=default-tenant : Tenant ID}';
    
    protected $description = 'Display route optimization cache performance metrics';

    public function handle(): int
    {
        // Resolve from container to avoid constructor injection issues during service discovery
        $routeCache = $this->laravel->make(RouteCacheInterface::class);
        
        $tenantId = $this->option('tenant');

        $this->info("Route Cache Metrics for Tenant: {$tenantId}");
        $this->newLine();

        $metrics = $routeCache->getCacheMetrics($tenantId);

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Entries', number_format($metrics['total_entries'])],
                ['Active Entries', number_format($metrics['active_entries'])],
                ['Total Size (MB)', $metrics['total_size_mb']],
                ['Compressed Size (MB)', $metrics['compressed_size_mb']],
                ['Compression Ratio', $metrics['compression_ratio_percent'] . '%'],
                ['Avg Route Size (KB)', $metrics['avg_route_size_kb']],
                ['Avg Compressed (KB)', $metrics['avg_compressed_kb']],
            ]
        );

        // Storage efficiency
        $this->newLine();
        if ($metrics['compression_ratio_percent'] > 70) {
            $this->info("✓ Excellent compression ratio ({$metrics['compression_ratio_percent']}%)");
        } elseif ($metrics['compression_ratio_percent'] > 50) {
            $this->info("✓ Good compression ratio ({$metrics['compression_ratio_percent']}%)");
        } else {
            $this->warn("⚠️  Low compression ratio ({$metrics['compression_ratio_percent']}%)");
        }

        // Size warnings
        $maxSizeMb = config('routing.max_cache_size_mb', 100);
        if ($metrics['compressed_size_mb'] > $maxSizeMb) {
            $this->warn("⚠️  Cache size exceeds limit ({$maxSizeMb} MB). Run: php artisan routing:cache-prune");
        }

        return Command::SUCCESS;
    }
}
