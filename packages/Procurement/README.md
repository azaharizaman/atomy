# Nexus\Procurement

Framework-agnostic procurement management package providing comprehensive purchase requisition, purchase order, goods receipt, and 3-way matching capabilities for ERP systems.

## Overview

The `Nexus\Procurement` package provides:

- **Purchase Requisition Management**: Complete requisition workflow from draft to approval to PO conversion
- **Purchase Order Processing**: Create POs from requisitions or directly with budget validation
- **Goods Receipt Notes (GRN)**: Record and validate received goods against purchase orders
- **3-Way Matching Engine**: Validate Invoice-PO-GRN alignment for accounts payable
- **Vendor Quote Management**: RFQ process and quote comparison
- **Approval Workflows**: Multi-level requisition approval with authorization checks

## Key Features

### Enterprise-Grade Procurement
- **Requisition-to-PO Conversion**: Seamless conversion with budget validation
- **Direct PO Creation**: Bypass requisition for urgent purchases
- **Segregation of Duties**: Enforces approval rules (requester cannot approve own requisition)
- **Budget Controls**: PO cannot exceed requisition by >10% without re-approval
- **Multi-Currency Support**: Full currency conversion via `Nexus\Currency`

### 3-Way Matching Integration
The package provides concrete implementations for contracts defined in `Nexus\Payable`:
1. **Purchase Order Repository** - Supplies PO line data for matching
2. **Goods Receipt Repository** - Supplies GRN line data for matching
3. **Matching Engine** - Validates Invoice-PO-GRN alignment with configurable tolerances

### Business Rule Enforcement
- Requisition must have ≥1 line item
- Approved requisitions are immutable
- GRN quantity cannot exceed PO quantity
- PO creator cannot create GRN for same PO (segregation of duties)
- Requester cannot approve own requisition

## Installation

```bash
composer require nexus/procurement
```

## Architecture

### Framework-Agnostic Core
- All business logic in `src/Services/`
- All data structures defined via interfaces in `src/Contracts/`
- Zero Laravel dependencies in package layer
- Persistence via repository interfaces

### Integration Points
- **Nexus\Payable**: Provides PO and GRN data for 3-way matching
- **Nexus\Uom**: Unit of measurement validation
- **Nexus\Currency**: Multi-currency support
- **Nexus\Workflow**: Requisition approval workflows
- **Nexus\AuditLogger**: Comprehensive change tracking
- **Nexus\Intelligence**: AI-powered anomaly detection and predictive analytics

## AI Intelligence Features

The `Nexus\Procurement` package includes **6 production-ready feature extractors** for AI-powered procurement optimization:

### 1. VendorFraudDetectionExtractor (25 features)
**Purpose:** Real-time fraud screening on PO creation and payment requests  
**Key Features:**
- Duplicate vendor pattern detection (name similarity, same bank account, contact info)
- Behavioral anomaly detection (price volatility, RFQ win rate, budget proximity)
- Relationship red flags (requester-vendor frequency, after-hours submissions, split orders)
- Document integrity checks (missing certifications, invoice gaps, metadata anomalies)

**Business Impact:** Prevents fraud, ensures compliance, protects assets

### 2. VendorPricingAnomalyExtractor (22 features)
**Purpose:** Cost optimization through pricing validation  
**Key Features:**
- Historical vendor pricing analysis (avg, std, min, max)
- Market benchmark comparison across all vendors
- Competitive quote ranking and analysis
- Contract pricing compliance validation
- Volume discount verification (expected vs actual)
- Currency volatility and geographic variance

**Business Impact:** Prevents overpayment, identifies kickback schemes, ensures contract compliance

### 3. RequisitionApprovalRiskExtractor (20 features)
**Purpose:** Predict approval delays and prioritize critical requisitions  
**Key Features:**
- Requester historical performance (approval rate, avg duration, rejection reasons)
- Department budget analysis (utilization, velocity, remaining budget)
- Approval chain complexity (levels, approver workload, cross-department flag)
- Urgency scoring (delivery timeline vs approval duration)
- Compliance requirements and technical complexity

**Business Impact:** Reduces procurement cycle time, improves cash flow planning

### 4. BudgetOverrunPredictionExtractor (16 features)
**Purpose:** Prevent budget violations before approval  
**Key Features:**
- Current budget status (allocated, committed, actual, pending)
- Historical burn rate and spending pattern consistency
- Period analysis (days remaining, progress percentage)
- Seasonality factors and emergency purchase frequency
- Budget amendment history tracking

**Business Impact:** Prevents budget violations, enables proactive reallocation

### 5. GRNDiscrepancyPredictionExtractor (18 features)
**Purpose:** Predict goods receipt issues before delivery  
**Key Features:**
- Vendor delivery performance (accuracy, damage rate, defect rate)
- Lead time reliability (variance, promised vs actual)
- Quality metrics (inspection fail rate, packaging adequacy)
- Transit risk calculation (distance, method, fragile goods)
- Seasonal demand spike detection

**Business Impact:** Proactive quality control, better inventory planning, vendor performance improvement

### 6. POConversionEfficiencyExtractor (14 features)
**Purpose:** Predict requisition-to-PO conversion time  
**Key Features:**
- Vendor catalog availability and coverage scoring
- Specification completeness assessment
- Procurement officer capacity and workload tracking
- Multi-vendor and custom product complexity
- Weekend/holiday processing offset

**Business Impact:** Improved cycle time, accurate delivery date prediction, resource optimization

### Integration Example

```php
use Nexus\Procurement\Intelligence\VendorFraudDetectionExtractor;
use Nexus\Intelligence\Services\IntelligenceManager;

// Extract features
$features = $this->fraudExtractor->extract($poTransaction);

// Evaluate with AI
$result = $this->intelligence->evaluate('procurement_fraud_check', $features);

// Act on result
if ($result->isFlagged() && $result->getSeverity() === SeverityLevel::CRITICAL) {
    $this->auditLogger->log($poId, 'fraud_flag', $result->getReason());
    throw new FraudDetectedException($result->getReason());
}
```

## Usage

See `docs/PROCUREMENT_IMPLEMENTATION.md` for complete implementation guide and usage examples.

## 📖 Documentation

### Package Documentation
- [Getting Started Guide](docs/getting-started.md)
- [API Reference](docs/api-reference.md)
- [Integration Guide](docs/integration-guide.md)
- [Examples](docs/examples/)

### Additional Resources
- `IMPLEMENTATION_SUMMARY.md` - Implementation progress
- `REQUIREMENTS.md` - Requirements
- `TEST_SUITE_SUMMARY.md` - Tests
- `VALUATION_MATRIX.md` - Valuation


## License

MIT License - see LICENSE file for details.
