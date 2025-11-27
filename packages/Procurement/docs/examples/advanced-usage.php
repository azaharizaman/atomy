<?php

declare(strict_types=1);

/**
 * Advanced Usage Example: Procurement Package
 *
 * Demonstrates:
 * 1. Blanket Purchase Orders
 * 2. Batch Three-Way Matching
 * 3. ML Feature Extraction for Fraud Detection
 * 4. Vendor Quote Comparison
 * 5. Error Handling Patterns
 */

use Nexus\Procurement\Contracts\ProcurementManagerInterface;
use Nexus\Procurement\Services\{MatchingEngine, VendorQuoteManager, PurchaseOrderManager};
use Nexus\Procurement\MachineLearning\{
    VendorFraudDetectionExtractor,
    VendorPricingAnomalyExtractor,
    RequisitionApprovalRiskExtractor
};
use Nexus\Procurement\Exceptions\{
    BudgetExceededException,
    InvalidGoodsReceiptDataException
};
use Psr\Log\LoggerInterface;

// ============================================
// Example 1: Blanket Purchase Orders
// ============================================

/**
 * Blanket POs are used for recurring purchases with a committed value.
 * Releases against blanket POs cannot exceed the total committed value.
 */

/** @var ProcurementManagerInterface $procurement */

// Create blanket PO with committed value
$blanketPo = $procurement->createDirectPO(
    tenantId: 'tenant-001',
    creatorId: 'user-buyer-001',
    data: [
        'number' => 'BPO-2025-001',
        'vendor_id' => 'vendor-office-supplies',
        'po_type' => 'blanket',
        'total_committed_value' => 50000.00, // $50,000 annual commitment
        'valid_from' => '2025-01-01',
        'valid_until' => '2025-12-31',
        'currency' => 'MYR',
        'payment_terms' => 'Net 30',
        'lines' => [], // Blanket POs may have no initial lines
    ]
);

echo "Created Blanket PO: {$blanketPo->getPoNumber()}\n";
echo "Committed Value: {$blanketPo->getTotalAmount()}\n";

// Create release against blanket PO
/** @var PurchaseOrderManager $poManager */
try {
    $release = $poManager->createBlanketRelease(
        blanketPoId: $blanketPo->getId(),
        creatorId: 'user-buyer-001',
        data: [
            'number' => 'BPO-2025-001-R01',
            'lines' => [
                [
                    'item_code' => 'PAPER-A4',
                    'description' => 'A4 Paper - January Order',
                    'quantity' => 100,
                    'unit' => 'box',
                    'unit_price' => 25.00,
                ],
            ],
        ]
    );
    
    echo "Created Release: {$release->getPoNumber()}\n";
    echo "Release Amount: {$release->getTotalAmount()}\n";
    // Remaining commitment: $50,000 - $2,500 = $47,500
    
} catch (BudgetExceededException $e) {
    echo "Error: Release exceeds remaining blanket PO amount\n";
    echo $e->getMessage();
}

// ============================================
// Example 2: Batch Three-Way Matching
// ============================================

/**
 * For large invoices with many lines, use batch matching for performance.
 * Target: <500ms for 100-line invoices (PER-PRO-0341)
 */

/** @var MatchingEngine $matchingEngine */
/** @var PurchaseOrderRepositoryInterface $poRepository */
/** @var GoodsReceiptRepositoryInterface $grnRepository */

// Simulate processing a 50-line invoice
$invoiceLines = [
    ['po_line_reference' => 'PO-2025-001-L001', 'quantity' => 10, 'unit_price' => 24.50, 'line_total' => 245.00],
    ['po_line_reference' => 'PO-2025-001-L002', 'quantity' => 5, 'unit_price' => 11.00, 'line_total' => 55.00],
    // ... more lines
];

$matchSet = [];
foreach ($invoiceLines as $invoiceLine) {
    $poLine = $poRepository->findLineByReference($invoiceLine['po_line_reference']);
    $grnLine = $grnRepository->findLineByReference($invoiceLine['po_line_reference']);
    
    if ($poLine && $grnLine) {
        $matchSet[] = [
            'po_line' => $poLine,
            'grn_line' => $grnLine,
            'invoice_line' => $invoiceLine,
        ];
    }
}

$batchResult = $matchingEngine->performBatchMatch($matchSet);

echo "=== Batch Match Results ===\n";
echo "Total Lines: {$batchResult['total_lines']}\n";
echo "Matched: {$batchResult['matched_lines']}\n";
echo "Discrepancies: {$batchResult['discrepancy_lines']}\n";
echo "Elapsed: {$batchResult['elapsed_ms']}ms\n";

if ($batchResult['overall_matched']) {
    echo "✅ All lines matched - auto-approve invoice\n";
} else {
    echo "⚠️  Discrepancies found - manual review required\n";
    
    foreach ($batchResult['line_results'] as $index => $result) {
        if (!$result['matched']) {
            echo "  Line {$index}: {$result['recommendation']}\n";
        }
    }
}

// ============================================
// Example 3: ML Feature Extraction
// ============================================

/**
 * Extract features for AI-powered fraud detection.
 * Integrate with Nexus\Intelligence for evaluation.
 */

// Vendor Fraud Detection
/** @var VendorFraudDetectionExtractor $fraudExtractor */

$poTransaction = [
    'po_id' => 'po-123',
    'vendor_id' => 'vendor-456',
    'creator_id' => 'user-789',
    'amount' => 75000.00,
    'created_at' => new \DateTimeImmutable(),
];

$fraudFeatures = $fraudExtractor->extract($poTransaction);

echo "\n=== Fraud Detection Features (25 total) ===\n";
echo "Duplicate vendor pattern: {$fraudFeatures['duplicate_vendor_score']}\n";
echo "Price volatility: {$fraudFeatures['price_volatility_index']}\n";
echo "RFQ win rate: {$fraudFeatures['rfq_win_rate']}\n";
echo "Budget proximity: {$fraudFeatures['budget_proximity_score']}\n";
echo "After-hours flag: {$fraudFeatures['after_hours_submission']}\n";

// If integrated with Nexus\Intelligence:
// $result = $intelligence->evaluate('procurement_fraud_check', $fraudFeatures);
// if ($result->isFlagged()) { throw new FraudDetectedException(...); }

// Pricing Anomaly Detection
/** @var VendorPricingAnomalyExtractor $pricingExtractor */

$pricingFeatures = $pricingExtractor->extract([
    'vendor_id' => 'vendor-456',
    'item_code' => 'LAPTOP-001',
    'unit_price' => 1500.00,
    'quantity' => 10,
]);

echo "\n=== Pricing Anomaly Features (22 total) ===\n";
echo "Historical avg price: {$pricingFeatures['vendor_avg_price']}\n";
echo "Market benchmark: {$pricingFeatures['market_benchmark_price']}\n";
echo "Price variance: {$pricingFeatures['price_variance_percent']}%\n";
echo "Volume discount expected: {$pricingFeatures['volume_discount_expected']}\n";

// Requisition Approval Risk
/** @var RequisitionApprovalRiskExtractor $approvalExtractor */

$approvalFeatures = $approvalExtractor->extract([
    'requisition_id' => 'req-123',
    'requester_id' => 'user-456',
    'department' => 'IT',
    'amount' => 25000.00,
]);

echo "\n=== Approval Risk Features (20 total) ===\n";
echo "Requester approval rate: {$approvalFeatures['requester_approval_rate']}%\n";
echo "Avg approval duration: {$approvalFeatures['avg_approval_duration_days']} days\n";
echo "Department budget utilization: {$approvalFeatures['dept_budget_utilization']}%\n";
echo "Approval chain complexity: {$approvalFeatures['approval_chain_levels']} levels\n";

// ============================================
// Example 4: Vendor Quote Comparison
// ============================================

/**
 * Compare vendor quotes for a requisition and get recommendations.
 */

/** @var VendorQuoteManager $quoteManager */

// Create quotes from multiple vendors
$quote1 = $quoteManager->createQuote(
    tenantId: 'tenant-001',
    requisitionId: $requisition->getId(),
    data: [
        'quote_number' => 'QUO-V1-001',
        'vendor_id' => 'vendor-supplier-a',
        'valid_until' => '2025-02-01',
        'lines' => [
            ['item_code' => 'LAPTOP-001', 'quantity' => 10, 'unit_price' => 1500.00],
            ['item_code' => 'MOUSE-001', 'quantity' => 10, 'unit_price' => 35.00],
        ],
    ]
);

$quote2 = $quoteManager->createQuote(
    tenantId: 'tenant-001',
    requisitionId: $requisition->getId(),
    data: [
        'quote_number' => 'QUO-V2-001',
        'vendor_id' => 'vendor-supplier-b',
        'valid_until' => '2025-02-01',
        'lines' => [
            ['item_code' => 'LAPTOP-001', 'quantity' => 10, 'unit_price' => 1450.00], // Lower
            ['item_code' => 'MOUSE-001', 'quantity' => 10, 'unit_price' => 40.00],    // Higher
        ],
    ]
);

// Compare quotes
$comparison = $quoteManager->compareQuotes($requisition->getId());

echo "\n=== Quote Comparison ===\n";
echo "Quotes compared: {$comparison['quote_count']}\n";

foreach ($comparison['line_comparisons'] as $itemCode => $lineComp) {
    echo "\nItem: {$itemCode}\n";
    echo "  Lowest price: {$lineComp['lowest_price']} (Vendor: {$lineComp['lowest_vendor']})\n";
    echo "  Highest price: {$lineComp['highest_price']} (Vendor: {$lineComp['highest_vendor']})\n";
    echo "  Price spread: {$lineComp['price_spread_percent']}%\n";
}

echo "\nRecommendation: {$comparison['recommendation']}\n";
echo "Recommended vendor: {$comparison['recommended_vendor_id']}\n";
echo "Total savings vs highest: {$comparison['potential_savings']}\n";

// Accept the winning quote
$acceptedQuote = $quoteManager->acceptQuote(
    quoteId: $quote2->getId(),
    acceptorId: 'user-buyer-001'
);

// ============================================
// Example 5: Comprehensive Error Handling
// ============================================

/**
 * Handle all possible procurement exceptions gracefully.
 */

use Nexus\Procurement\Exceptions\{
    ProcurementException,
    RequisitionNotFoundException,
    PurchaseOrderNotFoundException,
    GoodsReceiptNotFoundException,
    InvalidRequisitionDataException,
    InvalidRequisitionStateException,
    InvalidPurchaseOrderDataException,
    UnauthorizedApprovalException
};

function processProcurementWorkflow(
    ProcurementManagerInterface $procurement,
    string $tenantId,
    string $requisitionId,
    string $userId
): array {
    $errors = [];
    $result = ['success' => false];
    
    try {
        // Step 1: Get requisition
        $requisition = $procurement->getRequisition($requisitionId);
        
        // Step 2: Approve if pending
        if ($requisition->getStatus() === 'pending_approval') {
            $requisition = $procurement->approveRequisition($requisitionId, $userId);
        }
        
        // Step 3: Convert to PO
        $po = $procurement->convertRequisitionToPO(
            tenantId: $tenantId,
            requisitionId: $requisitionId,
            creatorId: $userId,
            poData: [/* ... */]
        );
        
        $result['success'] = true;
        $result['po_id'] = $po->getId();
        
    } catch (RequisitionNotFoundException $e) {
        $errors[] = ['code' => 'REQ_NOT_FOUND', 'message' => 'Requisition not found'];
        
    } catch (InvalidRequisitionStateException $e) {
        $errors[] = ['code' => 'INVALID_STATE', 'message' => $e->getMessage()];
        
    } catch (UnauthorizedApprovalException $e) {
        $errors[] = ['code' => 'UNAUTHORIZED', 'message' => $e->getMessage()];
        
    } catch (BudgetExceededException $e) {
        $errors[] = ['code' => 'BUDGET_EXCEEDED', 'message' => $e->getMessage()];
        
    } catch (ProcurementException $e) {
        // Catch-all for any other procurement errors
        $errors[] = ['code' => 'PROCUREMENT_ERROR', 'message' => $e->getMessage()];
        
    } catch (\Exception $e) {
        // Unexpected errors
        $errors[] = ['code' => 'SYSTEM_ERROR', 'message' => 'An unexpected error occurred'];
        // Log the actual error for debugging
        // $logger->error($e->getMessage(), ['exception' => $e]);
    }
    
    $result['errors'] = $errors;
    return $result;
}

// ============================================
// Example 6: Partial Goods Receipt
// ============================================

/**
 * Handle partial deliveries where not all quantities are received.
 */

// PO with 100 units ordered
$po = $procurement->getPurchaseOrder('po-123');
$poLine = $po->getLines()[0];
echo "Ordered: {$poLine->getQuantity()}\n"; // 100

// First delivery: 60 units
$grn1 = $procurement->recordGoodsReceipt(
    tenantId: 'tenant-001',
    poId: $po->getId(),
    receiverId: 'user-warehouse-001',
    receiptData: [
        'number' => 'GRN-2025-001',
        'received_date' => '2025-01-15',
        'lines' => [
            ['po_line_reference' => $poLine->getLineReference(), 'quantity' => 60, 'unit' => 'unit'],
        ],
    ]
);

echo "First delivery: 60 units\n";
echo "PO Status: {$po->getStatus()}\n"; // "partially_received"

// Second delivery: 40 units
$grn2 = $procurement->recordGoodsReceipt(
    tenantId: 'tenant-001',
    poId: $po->getId(),
    receiverId: 'user-warehouse-001',
    receiptData: [
        'number' => 'GRN-2025-002',
        'received_date' => '2025-01-20',
        'lines' => [
            ['po_line_reference' => $poLine->getLineReference(), 'quantity' => 40, 'unit' => 'unit'],
        ],
    ]
);

echo "Second delivery: 40 units\n";
$po = $procurement->getPurchaseOrder($po->getId());
echo "PO Status: {$po->getStatus()}\n"; // "fully_received"

// Attempting to receive more than ordered fails
try {
    $grn3 = $procurement->recordGoodsReceipt(
        tenantId: 'tenant-001',
        poId: $po->getId(),
        receiverId: 'user-warehouse-001',
        receiptData: [
            'number' => 'GRN-2025-003',
            'lines' => [
                ['po_line_reference' => $poLine->getLineReference(), 'quantity' => 10, 'unit' => 'unit'],
            ],
        ]
    );
} catch (InvalidGoodsReceiptDataException $e) {
    echo "Error: {$e->getMessage()}\n";
    // "GRN quantity (110) exceeds PO quantity (100)"
}

// ============================================
// Summary
// ============================================

echo "\n=== Advanced Features Demonstrated ===\n";
echo "1. Blanket POs with release tracking\n";
echo "2. Batch 3-way matching for performance\n";
echo "3. ML feature extraction for fraud detection\n";
echo "4. Vendor quote comparison and selection\n";
echo "5. Comprehensive exception handling\n";
echo "6. Partial goods receipt handling\n";
