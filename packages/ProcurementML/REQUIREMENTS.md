# Requirements: Procurement-ML

**Total Requirements:** 8

| Package Namespace | Requirements Type | Code | Requirement Statements | Files/Folders | Status | Notes on Status | Date Last Updated |
|---|---|---|---|---|---|---|---|
| `Nexus\ProcurementML` | Architectural Requirement | ARC-PML-0001 | Package MUST be framework-agnostic and act as an adapter. | composer.json | ✅ Complete | No framework dependencies. | 2024-07-29 |
| `Nexus\ProcurementML` | Functional Requirement | FUN-PML-0001 | Provide feature extraction for requisition approval risk. | src/Extractors/RequisitionApprovalRiskExtractor.php | ✅ Complete | - | 2024-07-29 |
| `Nexus\ProcurementML` | Functional Requirement | FUN-PML-0002 | Provide feature extraction for budget overrun prediction. | src/Extractors/BudgetOverrunPredictionExtractor.php | ✅ Complete | - | 2024-07-29 |
| `Nexus\ProcurementML` | Functional Requirement | FUN-PML-0003 | Provide feature extraction for GRN discrepancy prediction. | src/Extractors/GRNDiscrepancyPredictionExtractor.php | ✅ Complete | - | 2024-07-29 |
| `Nexus\ProcurementML` | Functional Requirement | FUN-PML-0004 | Provide feature extraction for PO conversion efficiency. | src/Extractors/POConversionEfficiencyExtractor.php | ✅ Complete | - | 2024-07-29 |
| `Nexus\ProcurementML` | Functional Requirement | FUN-PML-0005 | Provide feature extraction for purchase order quantity anomalies. | src/Extractors/ProcurementPOQtyExtractor.php | ✅ Complete | - | 2024-07-29 |
| `Nexus\ProcurementML` | Functional Requirement | FUN-PML-0006 | Provide feature extraction for vendor fraud detection. | src/Extractors/VendorFraudDetectionExtractor.php | ✅ Complete | - | 2024-07-29 |
| `Nexus\ProcurementML` | Functional Requirement | FUN-PML-0007 | Provide feature extraction for vendor pricing anomalies. | src/Extractors/VendorPricingAnomalyExtractor.php | ✅ Complete | - | 2024-07-29 |
