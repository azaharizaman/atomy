<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Nexus\Connector\Contracts\IntegrationLoggerInterface;

/*
|--------------------------------------------------------------------------
| Connector API Routes
|--------------------------------------------------------------------------
|
| Routes for managing integration logs, monitoring connector health,
| and viewing metrics.
|
*/

// Integration Logs
Route::prefix('connector')->group(function () {
    
    // List integration logs with filtering
    Route::get('logs', function (Request $request, IntegrationLoggerInterface $logger) {
        $filters = $request->only(['service', 'status', 'tenant_id', 'date_from', 'date_to']);
        $limit = min((int) $request->get('limit', 100), 500);
        $offset = (int) $request->get('offset', 0);

        $logs = $logger->getLogs($filters, $limit, $offset);

        return response()->json([
            'data' => $logs,
            'meta' => [
                'limit' => $limit,
                'offset' => $offset,
                'count' => count($logs),
            ],
        ]);
    });

    // Get metrics for a specific service
    Route::get('metrics/{service}', function (string $service, Request $request, IntegrationLoggerInterface $logger) {
        $from = new \DateTimeImmutable($request->get('from', '-7 days'));
        $to = new \DateTimeImmutable($request->get('to', 'now'));

        $metrics = $logger->getMetrics($service, $from, $to);

        return response()->json([
            'service' => $service,
            'period' => [
                'from' => $from->format('Y-m-d H:i:s'),
                'to' => $to->format('Y-m-d H:i:s'),
            ],
            'metrics' => $metrics,
        ]);
    });

    // Health check endpoint
    Route::get('health', function () {
        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'services' => [
                'database' => 'connected',
                'connector' => 'operational',
            ],
        ]);
    });

    // Service status overview
    Route::get('status', function (IntegrationLoggerInterface $logger) {
        $services = ['mailchimp', 'sendgrid', 'twilio']; // Could be dynamic
        $from = new \DateTimeImmutable('-24 hours');
        $to = new \DateTimeImmutable('now');

        $statuses = [];

        foreach ($services as $service) {
            try {
                $metrics = $logger->getMetrics($service, $from, $to);
                $statuses[$service] = [
                    'status' => $metrics['success_rate'] >= 95 ? 'healthy' : 'degraded',
                    'success_rate' => $metrics['success_rate'],
                    'avg_duration_ms' => $metrics['avg_duration_ms'],
                ];
            } catch (\Throwable $e) {
                $statuses[$service] = [
                    'status' => 'unknown',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'services' => $statuses,
            'timestamp' => now()->toIso8601String(),
        ]);
    });
});
