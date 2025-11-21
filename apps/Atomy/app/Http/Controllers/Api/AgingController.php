<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nexus\Receivable\Contracts\AgingCalculatorInterface;
use Nexus\Receivable\Contracts\DunningManagerInterface;

/**
 * Aging and Collections API Controller
 */
class AgingController extends Controller
{
    public function __construct(
        private readonly AgingCalculatorInterface $agingCalculator,
        private readonly DunningManagerInterface $dunningManager
    ) {}

    /**
     * Calculate aging for customer
     */
    public function calculate(Request $request, string $customerId): JsonResponse
    {
        $validated = $request->validate([
            'as_of_date' => 'nullable|date',
        ]);

        $asOfDate = isset($validated['as_of_date'])
            ? new \DateTimeImmutable($validated['as_of_date'])
            : new \DateTimeImmutable();

        $aging = $this->agingCalculator->calculateAging($customerId, $asOfDate);

        return response()->json([
            'success' => true,
            'data' => [
                'customer_id' => $customerId,
                'as_of_date' => $asOfDate->format('Y-m-d'),
                'aging' => $aging,
                'total_outstanding' => array_sum($aging),
            ],
        ]);
    }

    /**
     * Calculate aging for all customers
     */
    public function all(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => 'required|string',
            'as_of_date' => 'nullable|date',
        ]);

        $asOfDate = isset($validated['as_of_date'])
            ? new \DateTimeImmutable($validated['as_of_date'])
            : new \DateTimeImmutable();

        $aging = $this->agingCalculator->calculateAgingForAllCustomers(
            $validated['tenant_id'],
            $asOfDate
        );

        return response()->json([
            'success' => true,
            'data' => $aging,
            'total_customers' => count($aging),
        ]);
    }

    /**
     * Send dunning notices
     */
    public function sendDunning(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => 'required|string',
            'min_days_past_due' => 'integer|min:0',
        ]);

        try {
            $sentCount = $this->dunningManager->sendDunningNotices(
                $validated['tenant_id'],
                $validated['min_days_past_due'] ?? 0
            );

            return response()->json([
                'success' => true,
                'message' => "Sent {$sentCount} dunning notices",
                'notices_sent' => $sentCount,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get dunning level counts
     */
    public function dunningLevels(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => 'required|string',
        ]);

        $levelCounts = $this->dunningManager->getInvoiceCountByDunningLevel(
            $validated['tenant_id']
        );

        return response()->json([
            'success' => true,
            'data' => $levelCounts,
        ]);
    }
}
