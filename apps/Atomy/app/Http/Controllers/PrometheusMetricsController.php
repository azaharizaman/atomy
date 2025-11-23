<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Monitoring\PrometheusTelemetryAdapter;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

/**
 * Prometheus Metrics Controller
 *
 * Exposes /api/metrics/prometheus endpoint for Prometheus scraping.
 * Returns metrics in Prometheus exposition format.
 */
class PrometheusMetricsController extends Controller
{
    public function __construct(
        private readonly PrometheusTelemetryAdapter $adapter
    ) {}

    /**
     * Export metrics in Prometheus text format.
     *
     * @return Response
     */
    public function metrics(): Response
    {
        $metrics = $this->adapter->exportPrometheusMetrics();

        return response($metrics, 200, [
            'Content-Type' => 'text/plain; version=0.0.4; charset=utf-8',
        ]);
    }
}
