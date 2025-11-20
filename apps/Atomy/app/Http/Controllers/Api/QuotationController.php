<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nexus\Sales\Services\QuotationManager;
use Nexus\Sales\Exceptions\SalesException;

class QuotationController extends Controller
{
    public function __construct(
        private readonly QuotationManager $quotationManager
    ) {}

    /**
     * List quotations for a customer.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->input('tenant_id');
        $customerId = $request->input('customer_id');

        $quotations = $this->quotationManager->findQuotationsByCustomer($tenantId, $customerId);

        return response()->json([
            'data' => array_map(fn($q) => $q->toArray(), $quotations),
        ]);
    }

    /**
     * Get a specific quotation.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $quotation = $this->quotationManager->findQuotation($id);

            return response()->json([
                'data' => $quotation->toArray(),
            ]);
        } catch (SalesException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Send quotation to customer.
     */
    public function send(string $id): JsonResponse
    {
        try {
            $this->quotationManager->sendQuotation($id);

            return response()->json([
                'message' => 'Quotation sent successfully',
            ]);
        } catch (SalesException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Accept quotation.
     */
    public function accept(string $id): JsonResponse
    {
        try {
            $this->quotationManager->acceptQuotation($id);

            return response()->json([
                'message' => 'Quotation accepted successfully',
            ]);
        } catch (SalesException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Reject quotation.
     */
    public function reject(Request $request, string $id): JsonResponse
    {
        try {
            $reason = $request->input('reason');
            $this->quotationManager->rejectQuotation($id, $reason);

            return response()->json([
                'message' => 'Quotation rejected successfully',
            ]);
        } catch (SalesException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
