<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Receivable\FifoAllocationStrategy;
use App\Services\Receivable\ManualAllocationStrategy;
use App\Services\Receivable\ProportionalAllocationStrategy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nexus\Receivable\Contracts\ReceivableManagerInterface;
use Nexus\Receivable\Contracts\CustomerInvoiceRepositoryInterface;

/**
 * Invoice API Controller
 */
class InvoiceController extends Controller
{
    public function __construct(
        private readonly ReceivableManagerInterface $receivableManager,
        private readonly CustomerInvoiceRepositoryInterface $invoiceRepository
    ) {}

    /**
     * Create invoice from sales order
     */
    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sales_order_id' => 'required|string',
        ]);

        try {
            $invoice = $this->receivableManager->createInvoiceFromOrder(
                $validated['sales_order_id']
            );

            return response()->json([
                'success' => true,
                'data' => $invoice->toArray(),
                'message' => 'Invoice created successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Post invoice to GL
     */
    public function post(string $id): JsonResponse
    {
        try {
            $this->receivableManager->postInvoiceToGL($id);

            return response()->json([
                'success' => true,
                'message' => 'Invoice posted to general ledger successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get invoice by ID
     */
    public function show(string $id): JsonResponse
    {
        try {
            $invoice = $this->receivableManager->getById($id);

            return response()->json([
                'success' => true,
                'data' => $invoice->toArray(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found',
            ], 404);
        }
    }

    /**
     * Get invoices for customer
     */
    public function index(Request $request, string $customerId): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => 'required|string',
        ]);

        $invoices = $this->invoiceRepository->getByCustomer(
            $validated['tenant_id'],
            $customerId
        );

        return response()->json([
            'success' => true,
            'data' => array_map(fn($invoice) => $invoice->toArray(), $invoices),
            'total' => count($invoices),
        ]);
    }

    /**
     * Get overdue invoices
     */
    public function overdue(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => 'required|string',
            'min_days_past_due' => 'integer|min:0',
        ]);

        $invoices = $this->invoiceRepository->getOverdueInvoices(
            $validated['tenant_id'],
            new \DateTimeImmutable(),
            $validated['min_days_past_due'] ?? 0
        );

        return response()->json([
            'success' => true,
            'data' => array_map(fn($invoice) => $invoice->toArray(), $invoices),
            'total' => count($invoices),
        ]);
    }

    /**
     * Void invoice
     */
    public function void(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        try {
            $this->receivableManager->voidInvoice($id, $validated['reason']);

            return response()->json([
                'success' => true,
                'message' => 'Invoice voided successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Write off invoice
     */
    public function writeOff(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        try {
            $this->receivableManager->writeOffInvoice($id, $validated['reason']);

            return response()->json([
                'success' => true,
                'message' => 'Invoice written off successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
