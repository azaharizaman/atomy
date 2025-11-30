<?php

declare(strict_types=1);

/**
 * Basic Usage Example: Procurement Package
 *
 * Demonstrates:
 * 1. Creating a purchase requisition
 * 2. Submitting for approval
 * 3. Approving a requisition
 * 4. Converting to purchase order
 * 5. Recording goods receipt
 * 6. Three-way matching
 */

use Nexus\Procurement\Contracts\ProcurementManagerInterface;
use Nexus\Procurement\Exceptions\{
    InvalidRequisitionDataException,
    UnauthorizedApprovalException,
    BudgetExceededException
};

// ============================================
// Step 1: Create a Purchase Requisition
// ============================================

// Assume $procurement is injected via DI
/** @var ProcurementManagerInterface $procurement */

$requisition = $procurement->createRequisition(
    tenantId: 'tenant-001',
    requesterId: 'user-requester-123',
    data: [
        'number' => 'REQ-2025-001',
        'description' => 'Office supplies for Q1 2025',
        'department' => 'Administration',
        'lines' => [
            [
                'item_code' => 'PAPER-A4',
                'description' => 'A4 Paper 500 sheets',
                'quantity' => 10,
                'unit' => 'box',
                'estimated_unit_price' => 25.00,
            ],
            [
                'item_code' => 'PEN-BLK',
                'description' => 'Black ballpoint pens (dozen)',
                'quantity' => 5,
                'unit' => 'dozen',
                'estimated_unit_price' => 12.00,
            ],
        ],
    ]
);

echo "Created requisition: {$requisition->getRequisitionNumber()}\n";
echo "Total estimate: {$requisition->getTotalEstimate()}\n";
echo "Status: {$requisition->getStatus()}\n"; // "draft"

// ============================================
// Step 2: Submit for Approval
// ============================================

$requisition = $procurement->submitRequisitionForApproval(
    requisitionId: $requisition->getId()
);

echo "Status after submit: {$requisition->getStatus()}\n"; // "pending_approval"

// ============================================
// Step 3: Approve Requisition
// ============================================

// IMPORTANT: Approver must be different from requester (BUS-PRO-0095)
try {
    $requisition = $procurement->approveRequisition(
        requisitionId: $requisition->getId(),
        approverId: 'user-manager-456' // Must NOT be 'user-requester-123'
    );
    
    echo "Requisition approved by: {$requisition->getApprovedBy()}\n";
    echo "Status: {$requisition->getStatus()}\n"; // "approved"
    
} catch (UnauthorizedApprovalException $e) {
    // This happens if requester tries to approve own requisition
    echo "Error: {$e->getMessage()}\n";
}

// ============================================
// Step 4: Convert to Purchase Order
// ============================================

// Build PO lines with safe array access
$requisitionLines = $requisition->getLines();
$poLines = [];

if (isset($requisitionLines[0])) {
    $poLines[] = [
        'requisition_line_id' => $requisitionLines[0]->getId(),
        'item_code' => 'PAPER-A4',
        'description' => 'A4 Paper 500 sheets',
        'quantity' => 10,
        'unit' => 'box',
        'unit_price' => 24.50, // Negotiated price (within 10% of estimate)
    ];
}

if (isset($requisitionLines[1])) {
    $poLines[] = [
        'requisition_line_id' => $requisitionLines[1]->getId(),
        'item_code' => 'PEN-BLK',
        'description' => 'Black ballpoint pens (dozen)',
        'quantity' => 5,
        'unit' => 'dozen',
        'unit_price' => 11.00, // Negotiated price
    ];
}

try {
    $purchaseOrder = $procurement->convertRequisitionToPO(
        tenantId: 'tenant-001',
        requisitionId: $requisition->getId(),
        creatorId: 'user-buyer-789',
        poData: [
            'number' => 'PO-2025-001',
            'vendor_id' => 'vendor-office-supplies',
            'currency' => 'MYR',
            'payment_terms' => 'Net 30',
            'lines' => $poLines,
        ]
    );
    
    echo "Created PO: {$purchaseOrder->getPoNumber()}\n";
    echo "Total: {$purchaseOrder->getTotalAmount()} {$purchaseOrder->getCurrency()}\n";
    
} catch (BudgetExceededException $e) {
    // PO total exceeds requisition by more than 10%
    echo "Budget error: {$e->getMessage()}\n";
}

// ============================================
// Step 5: Release PO to Vendor
// ============================================

$purchaseOrder = $procurement->releasePO(
    poId: $purchaseOrder->getId(),
    releasedBy: 'user-buyer-789'
);

echo "PO released at: {$purchaseOrder->getReleasedAt()->format('Y-m-d H:i:s')}\n";
echo "Status: {$purchaseOrder->getStatus()}\n"; // "released"

// ============================================
// Step 6: Record Goods Receipt
// ============================================

// IMPORTANT: Receiver must be different from PO creator (BUS-PRO-0100)
$goodsReceipt = $procurement->recordGoodsReceipt(
    tenantId: 'tenant-001',
    poId: $purchaseOrder->getId(),
    receiverId: 'user-warehouse-001', // Must NOT be 'user-buyer-789'
    receiptData: [
        'number' => 'GRN-2025-001',
        'received_date' => '2025-01-15',
        'warehouse_location' => 'Warehouse A - Shelf B3',
        'lines' => [
            [
                'po_line_reference' => 'PO-2025-001-L001',
                'quantity' => 10, // Full quantity received
                'unit' => 'box',
            ],
            [
                'po_line_reference' => 'PO-2025-001-L002',
                'quantity' => 5, // Full quantity received
                'unit' => 'dozen',
            ],
        ],
    ]
);

echo "Created GRN: {$goodsReceipt->getGrnNumber()}\n";
echo "Status: {$goodsReceipt->getStatus()}\n"; // "confirmed"

// ============================================
// Step 7: Three-Way Matching
// ============================================

// When processing supplier invoice (typically called by Nexus\Payable)
$poLine = $purchaseOrder->getLines()[0];
$grnLine = $goodsReceipt->getLines()[0];

$matchResult = $procurement->performThreeWayMatch(
    poLine: $poLine,
    grnLine: $grnLine,
    invoiceLineData: [
        'quantity' => 10,
        'unit_price' => 24.50,
        'line_total' => 245.00,
    ]
);

if ($matchResult['matched']) {
    echo "✅ Match successful: {$matchResult['recommendation']}\n";
    // Output: "APPROVE: All values match within tolerance."
} else {
    echo "⚠️  Discrepancies found: {$matchResult['recommendation']}\n";
    foreach ($matchResult['discrepancies'] as $field => $discrepancy) {
        echo "  - {$field}: PO={$discrepancy['po_value']}, GRN={$discrepancy['grn_value']}, Invoice={$discrepancy['invoice_value']}, Variance={$discrepancy['variance_percent']}%\n";
    }
}

// ============================================
// Step 8: Authorize Payment
// ============================================

// IMPORTANT: Authorizer must be different from GRN creator (BUS-PRO-0105)
$goodsReceipt = $procurement->authorizeGrnPayment(
    grnId: $goodsReceipt->getId(),
    authorizerId: 'user-ap-clerk-002' // Must NOT be 'user-warehouse-001'
);

echo "Payment authorized by: {$goodsReceipt->getPaymentAuthorizerId()}\n";
echo "Status: {$goodsReceipt->getStatus()}\n"; // "payment_authorized"

// ============================================
// Summary: Segregation of Duties
// ============================================

echo "\n=== Segregation of Duties Summary ===\n";
echo "Requester:          user-requester-123\n";
echo "Approver:           user-manager-456     (≠ Requester)\n";
echo "PO Creator:         user-buyer-789\n";
echo "Goods Receiver:     user-warehouse-001   (≠ PO Creator)\n";
echo "Payment Authorizer: user-ap-clerk-002    (≠ GRN Creator)\n";
echo "Total people: 5 (3-person minimum enforced)\n";
