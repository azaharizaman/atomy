<?php

declare(strict_types=1);

namespace Atomy\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Nexus\Payable\Contracts\PayableManagerInterface;
use Nexus\Payable\Exceptions\PayableException;

/**
 * Bill API controller.
 */
class BillController extends Controller
{
    public function __construct(
        private readonly PayableManagerInterface $payableManager
    ) {}

    /**
     * Get bill by ID.
     *
     * @param string $billId
     * @return JsonResponse
     */
    public function show(string $billId): JsonResponse
    {
        try {
            $bill = $this->payableManager->getBill($billId);

            return response()->json([
                'data' => $bill,
            ]);
        } catch (PayableException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Submit a new bill.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vendor_id' => 'required|uuid|exists:vendors,id',
            'bill_number' => 'required|string|max:100',
            'bill_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:bill_date',
            'currency' => 'nullable|string|size:3',
            'tax_amount' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'lines' => 'required|array|min:1',
            'lines.*.description' => 'required|string',
            'lines.*.quantity' => 'required|numeric|min:0',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.gl_account' => 'required|string',
            'lines.*.tax_code' => 'nullable|string',
            'lines.*.po_line_reference' => 'nullable|string',
            'lines.*.grn_line_reference' => 'nullable|string',
        ]);

        try {
            $tenantId = $request->user()->tenant_id;
            $bill = $this->payableManager->submitBill($tenantId, $validated);

            return response()->json([
                'data' => $bill,
            ], 201);
        } catch (PayableException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Match bill (3-way matching).
     *
     * @param string $billId
     * @return JsonResponse
     */
    public function match(string $billId): JsonResponse
    {
        try {
            $result = $this->payableManager->matchBill($billId);

            return response()->json([
                'data' => [
                    'status' => $result->getStatus(),
                    'matched' => $result->isMatched(),
                    'within_tolerance' => $result->isWithinTolerance(),
                    'variances' => $result->getVariances(),
                    'line_results' => $result->getLineResults(),
                ],
            ]);
        } catch (PayableException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Approve bill.
     *
     * @param Request $request
     * @param string $billId
     * @return JsonResponse
     */
    public function approve(Request $request, string $billId): JsonResponse
    {
        try {
            $approvedBy = $request->user()->name ?? $request->user()->id;
            $bill = $this->payableManager->approveBill($billId, $approvedBy);

            return response()->json([
                'data' => $bill,
            ]);
        } catch (PayableException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Post bill to GL.
     *
     * @param string $billId
     * @return JsonResponse
     */
    public function postToGl(string $billId): JsonResponse
    {
        try {
            $journalId = $this->payableManager->postBillToGL($billId);

            return response()->json([
                'data' => [
                    'gl_journal_id' => $journalId,
                    'message' => 'Bill posted to general ledger successfully',
                ],
            ]);
        } catch (PayableException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Schedule payment for bill.
     *
     * @param string $billId
     * @return JsonResponse
     */
    public function schedulePayment(string $billId): JsonResponse
    {
        try {
            $scheduleId = $this->payableManager->schedulePayment($billId);

            return response()->json([
                'data' => [
                    'schedule_id' => $scheduleId,
                    'message' => 'Payment scheduled successfully',
                ],
            ]);
        } catch (PayableException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Import bills from CSV.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function importCsv(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt',
        ]);

        try {
            $file = $request->file('file');
            $tenantId = $request->user()->tenant_id;
            
            // Parse CSV
            $rows = array_map('str_getcsv', file($file->getPathname()));
            $header = array_shift($rows);
            
            $imported = 0;
            $errors = [];

            foreach ($rows as $idx => $row) {
                $data = array_combine($header, $row);
                
                try {
                    // Parse bill data (simplified, would need proper mapping)
                    $billData = [
                        'vendor_id' => $data['vendor_id'],
                        'bill_number' => $data['bill_number'],
                        'bill_date' => $data['bill_date'],
                        'due_date' => $data['due_date'],
                        'currency' => $data['currency'] ?? 'MYR',
                        'description' => $data['description'] ?? '',
                        'lines' => json_decode($data['lines'], true), // Expecting JSON array
                    ];

                    $this->payableManager->submitBill($tenantId, $billData);
                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Row " . ($idx + 2) . ": " . $e->getMessage();
                }
            }

            return response()->json([
                'data' => [
                    'imported' => $imported,
                    'total' => count($rows),
                    'errors' => $errors,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
