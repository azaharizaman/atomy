<?php

declare(strict_types=1);

/**
 * Advanced Usage Example: Procurement-ML
 *
 * This example demonstrates chaining multiple feature extractors to build a
 * composite feature set for a more complex prediction, such as requisition approval risk.
 */

use Nexus\ProcurementML\Extractors\RequisitionApprovalRiskExtractor;
use Nexus\ProcurementML\Contracts\ApprovalAnalyticsRepositoryInterface;
use Nexus\ProcurementML\Contracts\HistoricalDataRepositoryInterface;

// ============================================
// Step 1: Mock Application Components
// ============================================

$requisition = new class {
    public function getRequestorId(): string { return 'USER-456'; }
    public function getTotalAmount(): float { return 15000.00; }
    public function getDepartmentId(): string { return 'DEPT-FIN'; }
};

$approvalRepo = new class implements ApprovalAnalyticsRepositoryInterface {
    public function getRequestorApprovalHistory(string $requestorId): array {
        return ['approved' => 5, 'rejected' => 1];
    }
    public function getDepartmentApprovalRate(string $departmentId): float {
        return 0.95; // 95% approval rate
    }
};

$historicalRepo = new class implements HistoricalDataRepositoryInterface {
    public function getAverageAmountForEntityType(string $entityType): float {
        if ($entityType === 'requisition') {
            return 8000.00;
        }
        return 0.0;
    }
};

// ============================================
// Step 2: Instantiate the Extractor
// ============================================

// This extractor depends on two different analytics repositories
$riskExtractor = new RequisitionApprovalRiskExtractor($approvalRepo, $historicalRepo);

// ============================================
// Step 3: Extract Features
// ============================================

$features = $riskExtractor->extract($requisition);

echo "Extracted Features for Requisition Approval Risk:\n";
print_r($features);

// ============================================
// Step 4: Use with a Prediction Service (Mocked)
// ============================================

// In a real scenario, these features would be sent to a prediction service
// from the nexus/machine-learning package.

$predictionService = new class {
    public function predict(array $features): object {
        $score = 0;
        if ($features['requestor_rejection_ratio'] > 0.1) $score += 0.3;
        if ($features['amount_deviation_from_avg'] > 1.5) $score += 0.5;
        if ($features['dept_approval_rate'] < 0.9) $score += 0.2;

        return (object) [
            'risk_score' => $score,
            'prediction' => $score > 0.4 ? 'HIGH_RISK' : 'LOW_RISK',
        ];
    }
};

$prediction = $predictionService->predict($features);

echo "\nPrediction Result:\n";
echo "Risk Score: " . $prediction->risk_score . "\n";
echo "Prediction: " . $prediction->prediction . "\n";


// Expected output:
// Extracted Features for Requisition Approval Risk:
// Array
// (
//     [requestor_rejection_ratio] => 0.16666666666666666
//     [amount_deviation_from_avg] => 1.875
//     [dept_approval_rate] => 0.95
// )
//
// Prediction Result:
// Risk Score: 0.8
// Prediction: HIGH_RISK
