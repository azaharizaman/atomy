<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nexus\Receivable\Contracts\CreditLimitCheckerInterface;

/**
 * Credit Limit API Controller
 */
class CreditLimitController extends Controller
{
    public function __construct(
        private readonly CreditLimitCheckerInterface $creditLimitChecker
    ) {}

    /**
     * Check credit limit for customer
     */
    public function check(Request $request, string $customerId): JsonResponse
    {
        $validated = $request->validate([
            'order_amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|size:3',
        ]);

        try {
            $this->creditLimitChecker->checkCreditLimit(
                $customerId,
                (float) $validated['order_amount'],
                $validated['currency']
            );

            return response()->json([
                'success' => true,
                'message' => 'Credit limit check passed',
                'can_proceed' => true,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'can_proceed' => false,
            ], 400);
        }
    }

    /**
     * Get available credit for customer
     */
    public function available(string $customerId): JsonResponse
    {
        $availableCredit = $this->creditLimitChecker->getAvailableCredit($customerId);

        return response()->json([
            'success' => true,
            'data' => [
                'customer_id' => $customerId,
                'available_credit' => $availableCredit,
                'is_unlimited' => $availableCredit === null,
            ],
        ]);
    }

    /**
     * Check if credit limit is exceeded
     */
    public function exceeded(string $customerId): JsonResponse
    {
        $isExceeded = $this->creditLimitChecker->isCreditLimitExceeded($customerId);

        return response()->json([
            'success' => true,
            'data' => [
                'customer_id' => $customerId,
                'is_exceeded' => $isExceeded,
            ],
        ]);
    }

    /**
     * Get group available credit
     */
    public function groupAvailable(string $customerGroupId): JsonResponse
    {
        $availableCredit = $this->creditLimitChecker->getGroupAvailableCredit($customerGroupId);

        return response()->json([
            'success' => true,
            'data' => [
                'customer_group_id' => $customerGroupId,
                'available_credit' => $availableCredit,
                'is_unlimited' => $availableCredit === null,
            ],
        ]);
    }
}
