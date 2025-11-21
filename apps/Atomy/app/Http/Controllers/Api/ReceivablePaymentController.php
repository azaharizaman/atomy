<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Receivable\FifoAllocationStrategy;
use App\Services\Receivable\ManualAllocationStrategy;
use App\Services\Receivable\ProportionalAllocationStrategy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nexus\Receivable\Contracts\PaymentProcessorInterface;
use Nexus\Receivable\Contracts\PaymentReceiptRepositoryInterface;

/**
 * Receivable Payment Receipt API Controller
 */
class ReceivablePaymentController extends Controller
{
    public function __construct(
        private readonly PaymentProcessorInterface $paymentProcessor,
        private readonly PaymentReceiptRepositoryInterface $receiptRepository
    ) {}

    /**
     * Create payment receipt
     */
    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => 'required|string',
            'customer_id' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|size:3',
            'receipt_date' => 'required|date',
            'payment_method' => 'required|string',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $receipt = $this->paymentProcessor->createPaymentReceipt(
                $validated['tenant_id'],
                $validated['customer_id'],
                (float) $validated['amount'],
                $validated['currency'],
                new \DateTimeImmutable($validated['receipt_date']),
                $validated['payment_method'],
                $validated['reference_number'] ?? null,
                $validated['notes'] ?? null
            );

            return response()->json([
                'success' => true,
                'data' => $receipt->toArray(),
                'message' => 'Payment receipt created successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Allocate payment to invoices
     */
    public function allocate(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'strategy' => 'required|string|in:fifo,proportional,manual',
            'manual_allocations' => 'required_if:strategy,manual|array',
            'manual_allocations.*' => 'numeric|min:0',
        ]);

        try {
            $strategy = match ($validated['strategy']) {
                'fifo' => new FifoAllocationStrategy(),
                'proportional' => new ProportionalAllocationStrategy(),
                'manual' => new ManualAllocationStrategy($validated['manual_allocations'] ?? []),
            };

            $this->paymentProcessor->allocatePayment($id, $strategy);

            return response()->json([
                'success' => true,
                'message' => 'Payment allocated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Void payment receipt
     */
    public function void(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        try {
            $this->paymentProcessor->voidPaymentReceipt($id, $validated['reason']);

            return response()->json([
                'success' => true,
                'message' => 'Payment receipt voided successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get payment receipt by ID
     */
    public function show(string $id): JsonResponse
    {
        try {
            $receipt = $this->receiptRepository->getById($id);

            return response()->json([
                'success' => true,
                'data' => $receipt->toArray(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment receipt not found',
            ], 404);
        }
    }

    /**
     * Get unapplied payment receipts for customer
     */
    public function unapplied(string $customerId): JsonResponse
    {
        $receipts = $this->receiptRepository->getUnappliedReceipts($customerId);

        return response()->json([
            'success' => true,
            'data' => array_map(fn($receipt) => $receipt->toArray(), $receipts),
            'total' => count($receipts),
        ]);
    }
}
