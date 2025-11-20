<?php

declare(strict_types=1);

namespace Atomy\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Nexus\Payable\Contracts\PayableManagerInterface;
use Nexus\Payable\Exceptions\PayableException;

/**
 * Vendor API controller.
 */
class VendorController extends Controller
{
    public function __construct(
        private readonly PayableManagerInterface $payableManager
    ) {}

    /**
     * List vendors.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id; // Assuming multi-tenant setup
        $filters = $request->only(['status', 'currency', 'search']);

        $vendors = $this->payableManager->listVendors($tenantId, $filters);

        return response()->json([
            'data' => $vendors,
        ]);
    }

    /**
     * Get vendor by ID.
     *
     * @param string $vendorId
     * @return JsonResponse
     */
    public function show(string $vendorId): JsonResponse
    {
        try {
            $vendor = $this->payableManager->getVendor($vendorId);

            return response()->json([
                'data' => $vendor,
            ]);
        } catch (PayableException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Create vendor.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'status' => 'nullable|string|in:active,inactive,blocked',
            'payment_terms' => 'nullable|string',
            'qty_tolerance_percent' => 'nullable|numeric|min:0|max:100',
            'price_tolerance_percent' => 'nullable|numeric|min:0|max:100',
            'tax_id' => 'nullable|string|max:50',
            'bank_details' => 'nullable|array',
            'currency' => 'nullable|string|size:3',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|array',
        ]);

        try {
            $tenantId = $request->user()->tenant_id;
            $vendor = $this->payableManager->createVendor($tenantId, $validated);

            return response()->json([
                'data' => $vendor,
            ], 201);
        } catch (PayableException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Update vendor.
     *
     * @param Request $request
     * @param string $vendorId
     * @return JsonResponse
     */
    public function update(Request $request, string $vendorId): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'sometimes|string|max:50',
            'name' => 'sometimes|string|max:255',
            'status' => 'sometimes|string|in:active,inactive,blocked',
            'payment_terms' => 'sometimes|string',
            'qty_tolerance_percent' => 'sometimes|numeric|min:0|max:100',
            'price_tolerance_percent' => 'sometimes|numeric|min:0|max:100',
            'tax_id' => 'nullable|string|max:50',
            'bank_details' => 'nullable|array',
            'currency' => 'sometimes|string|size:3',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|array',
        ]);

        try {
            $vendor = $this->payableManager->updateVendor($vendorId, $validated);

            return response()->json([
                'data' => $vendor,
            ]);
        } catch (PayableException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Get vendor bills.
     *
     * @param Request $request
     * @param string $vendorId
     * @return JsonResponse
     */
    public function bills(Request $request, string $vendorId): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $bills = $this->payableManager->getVendorBills($tenantId, $vendorId);

        return response()->json([
            'data' => $bills,
        ]);
    }

    /**
     * Get vendor aging report.
     *
     * @param Request $request
     * @param string $vendorId
     * @return JsonResponse
     */
    public function aging(Request $request, string $vendorId): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $asOfDate = $request->input('as_of_date')
            ? new \DateTime($request->input('as_of_date'))
            : new \DateTime();

        $aging = $this->payableManager->getVendorAging($tenantId, $vendorId, $asOfDate);

        return response()->json([
            'data' => $aging,
        ]);
    }
}
