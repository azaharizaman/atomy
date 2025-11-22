<?php

declare(strict_types=1);

namespace Nexus\Intelligence\Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Intelligence settings seeder
 * 
 * Seeds configurable thresholds and settings for Intelligence package.
 * These settings enable runtime configuration without code deployment.
 */
final class IntelligenceSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds
     */
    public function run(): void
    {
        $settings = [
            // Global Intelligence settings
            'intelligence.tracking.enabled' => true,
            'intelligence.schema.deprecation_months' => 6,
            
            // Payable duplicate detection thresholds
            'intelligence.payable.duplicate_detection.critical_zscore' => 3.0,
            'intelligence.payable.duplicate_detection.high_zscore' => 2.0,
            'intelligence.payable.duplicate_detection.medium_zscore' => 1.5,
            
            // Receivable payment prediction thresholds
            'intelligence.receivable.payment_prediction.critical_zscore' => 2.5,
            'intelligence.receivable.payment_prediction.high_zscore' => 2.0,
            'intelligence.receivable.payment_prediction.medium_zscore' => 1.5,
            
            // Inventory demand forecast thresholds
            'intelligence.inventory.demand_forecast.critical_zscore' => 3.0,
            'intelligence.inventory.demand_forecast.high_zscore' => 2.0,
            'intelligence.inventory.demand_forecast.medium_zscore' => 1.5,
            
            // EventStream polling settings
            'intelligence.eventstream.polling.normal_interval_seconds' => 300,
            'intelligence.eventstream.polling.closing_interval_seconds' => 60,
            'intelligence.eventstream.polling.quiet_interval_seconds' => 600,
            'intelligence.eventstream.polling.high_frequency_threshold_hours' => 72,
            
            // Performance settings
            'intelligence.materialized_view.refresh_interval_minutes' => 15,
            'intelligence.materialized_view.full_refresh_hour' => 1,
            'intelligence.dirty_records.purge_after_hours' => 24,
            'intelligence.dirty_records.alert_threshold' => 100000,
            
            // Cost optimization
            'intelligence.cost.track_extraction_metrics' => true,
            'intelligence.cost.alert_slow_extractor_ms' => 500,
        ];
        
        // Note: This seeder is a template. In the actual Atomy application,
        // these settings would be seeded via Nexus\Setting service.
        // The settings shown here document the required configuration keys.
        
        $this->command->info('Intelligence settings template generated.');
        $this->command->warn('Settings must be implemented in Atomy via Nexus\Setting service.');
    }
}
