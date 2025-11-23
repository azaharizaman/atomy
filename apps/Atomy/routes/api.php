<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Atomy\Http\Controllers\Api\VendorController;
use Atomy\Http\Controllers\Api\BillController;
use Atomy\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\QuotationController;
use App\Http\Controllers\Api\SalesOrderController;
use App\Http\Controllers\Api\PricingController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\ReceivablePaymentController;
use App\Http\Controllers\Api\CreditLimitController;
use App\Http\Controllers\Api\AgingController;
use App\Http\Controllers\GeoController;
use App\Http\Controllers\RoutingController;
use App\Http\Controllers\PrometheusMetricsController;
use App\Http\Controllers\Api\FieldService\WorkOrderController;
use App\Http\Controllers\Api\FieldService\ServiceContractController;
use App\Http\Controllers\Api\FieldService\TechnicianDispatchController;
use App\Http\Controllers\Api\FieldService\MobileWorkOrderController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Payable API routes
Route::middleware('auth:sanctum')->prefix('payable')->group(function () {
    // Vendors
    Route::prefix('vendors')->group(function () {
        Route::get('/', [VendorController::class, 'index']);
        Route::post('/', [VendorController::class, 'store']);
        Route::get('/{vendorId}', [VendorController::class, 'show']);
        Route::put('/{vendorId}', [VendorController::class, 'update']);
        Route::get('/{vendorId}/bills', [VendorController::class, 'bills']);
        Route::get('/{vendorId}/aging', [VendorController::class, 'aging']);
    });

    // Bills
    Route::prefix('bills')->group(function () {
        Route::get('/{billId}', [BillController::class, 'show']);
        Route::post('/', [BillController::class, 'store']);
        Route::post('/import-csv', [BillController::class, 'importCsv']);
        Route::post('/{billId}/match', [BillController::class, 'match']);
        Route::post('/{billId}/approve', [BillController::class, 'approve']);
        Route::post('/{billId}/post-to-gl', [BillController::class, 'postToGl']);
        Route::post('/{billId}/schedule-payment', [BillController::class, 'schedulePayment']);
    });

    // Payments
    Route::prefix('payments')->group(function () {
        Route::get('/due', [PaymentController::class, 'due']);
        Route::post('/', [PaymentController::class, 'store']);
        Route::post('/{paymentId}/allocate', [PaymentController::class, 'allocate']);
        Route::post('/{paymentId}/void', [PaymentController::class, 'void']);
    });
});

// Sales API routes
Route::middleware('auth:sanctum')->prefix('sales')->group(function () {
    // Quotations
    Route::prefix('quotations')->group(function () {
        Route::get('/', [QuotationController::class, 'index']);
        Route::get('/{id}', [QuotationController::class, 'show']);
        Route::post('/{id}/send', [QuotationController::class, 'send']);
        Route::post('/{id}/accept', [QuotationController::class, 'accept']);
        Route::post('/{id}/reject', [QuotationController::class, 'reject']);
    });

    // Sales Orders
    Route::prefix('orders')->group(function () {
        Route::get('/', [SalesOrderController::class, 'index']);
        Route::get('/{id}', [SalesOrderController::class, 'show']);
        Route::post('/from-quote/{quotationId}', [SalesOrderController::class, 'convertFromQuote']);
        Route::post('/{id}/confirm', [SalesOrderController::class, 'confirm']);
        Route::post('/{id}/cancel', [SalesOrderController::class, 'cancel']);
        Route::post('/{id}/ship', [SalesOrderController::class, 'ship']);
        Route::post('/{id}/generate-invoice', [SalesOrderController::class, 'generateInvoice']);
    });

    // Pricing
    Route::prefix('pricing')->group(function () {
        Route::post('/get-price', [PricingController::class, 'getPrice']);
    });
});

// Receivable API routes
Route::middleware('auth:sanctum')->prefix('receivable')->group(function () {
    // Invoices
    Route::prefix('invoices')->group(function () {
        Route::post('/', [InvoiceController::class, 'create']);
        Route::get('/{id}', [InvoiceController::class, 'show']);
        Route::post('/{id}/post', [InvoiceController::class, 'post']);
        Route::post('/{id}/void', [InvoiceController::class, 'void']);
        Route::post('/{id}/write-off', [InvoiceController::class, 'writeOff']);
        Route::get('/customer/{customerId}', [InvoiceController::class, 'index']);
        Route::get('/overdue', [InvoiceController::class, 'overdue']);
    });

    // Payment Receipts
    Route::prefix('payments')->group(function () {
        Route::post('/', [ReceivablePaymentController::class, 'create']);
        Route::get('/{id}', [ReceivablePaymentController::class, 'show']);
        Route::post('/{id}/allocate', [ReceivablePaymentController::class, 'allocate']);
        Route::post('/{id}/void', [ReceivablePaymentController::class, 'void']);
        Route::get('/customer/{customerId}/unapplied', [ReceivablePaymentController::class, 'unapplied']);
    });

    // Credit Limit
    Route::prefix('credit-limit')->group(function () {
        Route::post('/customer/{customerId}/check', [CreditLimitController::class, 'check']);
        Route::get('/customer/{customerId}/available', [CreditLimitController::class, 'available']);
        Route::get('/customer/{customerId}/exceeded', [CreditLimitController::class, 'exceeded']);
        Route::get('/group/{customerGroupId}/available', [CreditLimitController::class, 'groupAvailable']);
    });

    // Aging & Collections
    Route::prefix('aging')->group(function () {
        Route::get('/customer/{customerId}', [AgingController::class, 'calculate']);
        Route::get('/all', [AgingController::class, 'all']);
        Route::post('/dunning/send', [AgingController::class, 'sendDunning']);
        Route::get('/dunning/levels', [AgingController::class, 'dunningLevels']);
    });
});

// Geo API routes
Route::middleware('auth:sanctum')->prefix('geo')->group(function () {
    Route::post('/geocode', [GeoController::class, 'geocode']);
    Route::post('/reverse-geocode', [GeoController::class, 'reverseGeocode']);
    Route::post('/distance', [GeoController::class, 'calculateDistance']);
    Route::post('/geofence/check', [GeoController::class, 'checkGeofence']);
    Route::get('/regions', [GeoController::class, 'listRegions']);
    Route::get('/cache-metrics', [GeoController::class, 'getCacheMetrics']);
});

// Routing API routes
Route::middleware('auth:sanctum')->prefix('routing')->group(function () {
    Route::post('/tsp', [RoutingController::class, 'optimizeTsp']);
    Route::post('/vrp', [RoutingController::class, 'optimizeVrp']);
    Route::get('/cache-metrics', [RoutingController::class, 'getCacheMetrics']);
    Route::delete('/cache', [RoutingController::class, 'clearCache']);
});

// Field Service API routes
Route::middleware('auth:sanctum')->prefix('field-service')->group(function () {
    // Work Orders
    Route::prefix('work-orders')->group(function () {
        Route::get('/', [WorkOrderController::class, 'index']);
        Route::get('/{id}', [WorkOrderController::class, 'show']);
        Route::post('/', [WorkOrderController::class, 'store']);
        Route::post('/{id}/assign', [WorkOrderController::class, 'assign']);
        Route::post('/{id}/start', [WorkOrderController::class, 'start']);
        Route::post('/{id}/complete', [WorkOrderController::class, 'complete']);
        Route::post('/{id}/verify', [WorkOrderController::class, 'verify']);
        Route::post('/{id}/cancel', [WorkOrderController::class, 'cancel']);
        Route::get('/sla/status', [WorkOrderController::class, 'slaStatus']);
    });

    // Service Contracts
    Route::prefix('contracts')->group(function () {
        Route::get('/', [ServiceContractController::class, 'index']);
        Route::get('/{id}', [ServiceContractController::class, 'show']);
        Route::post('/', [ServiceContractController::class, 'store']);
        Route::put('/{id}', [ServiceContractController::class, 'update']);
        Route::get('/expiring/soon', [ServiceContractController::class, 'expiring']);
        Route::get('/maintenance/due', [ServiceContractController::class, 'dueForMaintenance']);
    });

    // Technician Dispatch
    Route::prefix('dispatch')->group(function () {
        Route::post('/find-best', [TechnicianDispatchController::class, 'findBest']);
        Route::post('/auto-assign/{workOrderId}', [TechnicianDispatchController::class, 'autoAssign']);
        Route::post('/optimize-route/{technicianId}', [TechnicianDispatchController::class, 'optimizeRoute']);
    });

    // Mobile App Endpoints
    Route::prefix('mobile')->group(function () {
        Route::get('/my-work-orders', [MobileWorkOrderController::class, 'myWorkOrders']);
        Route::post('/{workOrderId}/signature', [MobileWorkOrderController::class, 'captureSignature']);
        Route::post('/{workOrderId}/consume-parts', [MobileWorkOrderController::class, 'consumeParts']);
        Route::post('/sync', [MobileWorkOrderController::class, 'sync']);
        Route::post('/sync/resolve-conflict', [MobileWorkOrderController::class, 'resolveConflict']);
    });
});

// Feature Flags API routes
Route::middleware(['auth:sanctum', 'tenant.identify'])->prefix('feature-flags')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\FeatureFlagController::class, 'index']);
    Route::get('/{name}', [\App\Http\Controllers\Api\FeatureFlagController::class, 'show']);
    Route::post('/', [\App\Http\Controllers\Api\FeatureFlagController::class, 'store']);
    Route::put('/{name}', [\App\Http\Controllers\Api\FeatureFlagController::class, 'update']);
    Route::delete('/{name}', [\App\Http\Controllers\Api\FeatureFlagController::class, 'destroy']);
});

// Metrics endpoint for Prometheus scraping (no auth required for monitoring)
Route::get('/metrics/prometheus', [PrometheusMetricsController::class, 'metrics']);
