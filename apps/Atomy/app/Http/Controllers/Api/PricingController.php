<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nexus\Sales\Services\PricingEngine;
use Nexus\Uom\ValueObjects\Quantity;
use DateTimeImmutable;

class PricingController extends Controller
{
    public function __construct(
        private readonly PricingEngine $pricingEngine
    ) {}

    /**
     * Get price for a product variant.
     */
    public function getPrice(Request $request): JsonResponse
    {
        try {
            $tenantId = $request->input('tenant_id');
            $productVariantId = $request->input('product_variant_id');
            $quantity = new Quantity(
                (float) $request->input('quantity'),
                $request->input('uom_code')
            );
            $currencyCode = $request->input('currency_code');
            $customerId = $request->input('customer_id');
            $asOf = $request->has('as_of')
                ? new DateTimeImmutable($request->input('as_of'))
                : null;

            $price = $this->pricingEngine->getPrice(
                $tenantId,
                $productVariantId,
                $quantity,
                $currencyCode,
                $customerId,
                $asOf
            );

            return response()->json([
                'data' => [
                    'product_variant_id' => $productVariantId,
                    'quantity' => $quantity->getValue(),
                    'uom_code' => $quantity->getUom(),
                    'currency_code' => $currencyCode,
                    'unit_price' => $price,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
