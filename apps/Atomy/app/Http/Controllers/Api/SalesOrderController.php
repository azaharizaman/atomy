<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nexus\Sales\Services\SalesOrderManager;
use Nexus\Sales\Services\QuoteToOrderConverter;
use Nexus\Sales\Exceptions\SalesException;

class SalesOrderController extends Controller
{
    public function __construct(
        private readonly SalesOrderManager $salesOrderManager,
        private readonly QuoteToOrderConverter $quoteToOrderConverter
    ) {}

    /**
     * List sales orders for a customer.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->input('tenant_id');
        $customerId = $request->input('customer_id');

        $orders = $this->salesOrderManager->findOrdersByCustomer($tenantId, $customerId);

        return response()->json([
            'data' => array_map(fn($o) => $o->toArray(), $orders),
        ]);
    }

    /**
     * Get a specific sales order.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $order = $this->salesOrderManager->findOrder($id);

            return response()->json([
                'data' => $order->toArray(),
            ]);
        } catch (SalesException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Convert quotation to order.
     */
    public function convertFromQuote(Request $request, string $quotationId): JsonResponse
    {
        try {
            $orderData = $request->all();
            $order = $this->quoteToOrderConverter->convertToOrder($quotationId, $orderData);

            return response()->json([
                'data' => $order->toArray(),
                'message' => 'Quotation converted to order successfully',
            ], 201);
        } catch (SalesException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Confirm sales order (locks exchange rate, checks credit, reserves stock).
     */
    public function confirm(Request $request, string $id): JsonResponse
    {
        try {
            $confirmedBy = $request->input('confirmed_by');
            $this->salesOrderManager->confirmOrder($id, $confirmedBy);

            return response()->json([
                'message' => 'Sales order confirmed successfully',
            ]);
        } catch (SalesException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Cancel sales order.
     */
    public function cancel(Request $request, string $id): JsonResponse
    {
        try {
            $reason = $request->input('reason');
            $this->salesOrderManager->cancelOrder($id, $reason);

            return response()->json([
                'message' => 'Sales order cancelled successfully',
            ]);
        } catch (SalesException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Mark order as shipped.
     */
    public function ship(Request $request, string $id): JsonResponse
    {
        try {
            $isPartial = $request->input('is_partial', false);
            $this->salesOrderManager->markAsShipped($id, $isPartial);

            return response()->json([
                'message' => 'Sales order marked as shipped successfully',
            ]);
        } catch (SalesException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Generate invoice from order.
     */
    public function generateInvoice(string $id): JsonResponse
    {
        try {
            $invoiceId = $this->salesOrderManager->generateInvoice($id);

            return response()->json([
                'data' => ['invoice_id' => $invoiceId],
                'message' => 'Invoice generated successfully',
            ]);
        } catch (\BadMethodCallException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 501); // Not Implemented
        } catch (SalesException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
