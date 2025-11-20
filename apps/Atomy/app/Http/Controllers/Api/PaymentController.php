<?php

declare(strict_types=1);

namespace Atomy\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Nexus\Payable\Contracts\PayableManagerInterface;
use Nexus\Payable\Exceptions\PayableException;

/**
 * Payment API controller.
 */
class PaymentController extends Controller
{
    public function __construct(
        private readonly PayableManagerInterface $payableManager
    ) {}

    /**
     * Get payments due.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function due(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $asOfDate = $request->input('as_of_date')
            ? new \DateTime($request->input('as_of_date'))
            : new \DateTime();

        $paymentsDue = $this->payableManager->getPaymentsDue($tenantId, $asOfDate);

        return response()->json([
            'data' => $paymentsDue,
        ]);
    }

    /**
     * Process a payment.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'payment_method' => 'required|string|in:bank_transfer,cheque,credit_card,cash,online',
            'bank_account' => 'required|string',
            'reference' => 'nullable|string',
            'allocations' => 'required|array|min:1',
            'allocations.*.bill_id' => 'required|uuid|exists:vendor_bills,id',
            'allocations.*.amount' => 'required|numeric|min:0',
        ]);

        try {
            $tenantId = $request->user()->tenant_id;
            $payment = $this->payableManager->processPayment($tenantId, $validated);

            return response()->json([
                'data' => $payment,
            ], 201);
        } catch (PayableException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Allocate payment to bills.
     *
     * @param Request $request
     * @param string $paymentId
     * @return JsonResponse
     */
    public function allocate(Request $request, string $paymentId): JsonResponse
    {
        $validated = $request->validate([
            'allocations' => 'required|array|min:1',
            'allocations.*.bill_id' => 'required|uuid|exists:vendor_bills,id',
            'allocations.*.amount' => 'required|numeric|min:0',
        ]);

        try {
            $payment = $this->payableManager->allocatePayment($paymentId, $validated['allocations']);

            return response()->json([
                'data' => $payment,
            ]);
        } catch (PayableException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Void a payment.
     *
     * @param Request $request
     * @param string $paymentId
     * @return JsonResponse
     */
    public function void(Request $request, string $paymentId): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string',
        ]);

        try {
            $this->payableManager->voidPayment($paymentId, $validated['reason']);

            return response()->json([
                'data' => [
                    'message' => 'Payment voided successfully',
                ],
            ]);
        } catch (PayableException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
