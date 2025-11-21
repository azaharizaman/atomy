# Requirements: Procurement

Total Requirements: 88

| Package Namespace | Requirements Type | Code | Requirement Statements | Files/Folders | Status | Notes on Status | Date Last Updated |
|-------------------|-------------------|------|------------------------|---------------|--------|-----------------|-------------------|
| `Nexus\Procurement` | Business Requirements | BUS-PRO-0041 | A requisition MUST have at least one line item | ✅ Implemented | Enforced in RequisitionManager::validateRequisitionData() and InvalidRequisitionDataException::noLines() | 2025-11-20 |  |
| `Nexus\Procurement` | Business Requirements | BUS-PRO-0048 | Requisition total estimate MUST equal sum of line item estimates | ✅ Implemented | Calculated in DbRequisitionRepository::create() during line item creation | 2025-11-20 |  |
| `Nexus\Procurement` | Business Requirements | BUS-PRO-0055 | Approved requisitions cannot be edited (only cancelled) | ✅ Implemented | Enforced in InvalidRequisitionStateException::cannotEditApproved() | 2025-11-20 |  |
| `Nexus\Procurement` | Business Requirements | BUS-PRO-0062 | A purchase order MUST reference an approved requisition OR be explicitly marked as direct PO | ✅ Implemented | Supported via PurchaseOrderManager::createFromRequisition() and createBlanketPo() | 2025-11-20 |  |
| `Nexus\Procurement` | Business Requirements | BUS-PRO-0069 | PO total amount MUST NOT exceed requisition approved amount by more than 10% without re-approval | ✅ Implemented | Enforced in PurchaseOrderManager::validatePoAgainstRequisition() with configurable tolerance | 2025-11-20 |  |
| `Nexus\Procurement` | Business Requirements | BUS-PRO-0076 | GRN quantity cannot exceed PO quantity for any line item | ✅ Implemented | Enforced in GoodsReceiptManager::validateGrnQuantitiesAgainstPo() via InvalidGoodsReceiptDataException::quantityExceedsPo() | 2025-11-20 |  |
| `Nexus\Procurement` | Business Requirements | BUS-PRO-0083 | 3-way match tolerance rules are configurable per tenant | ✅ Implemented | Configured in config/procurement.php (quantity_tolerance_percent, price_tolerance_percent) | 2025-11-20 |  |
| `Nexus\Procurement` | Business Requirements | BUS-PRO-0089 | Payment authorization requires successful 3-way match OR manual override by authorized user | ✅ Implemented | GoodsReceiptManager::authorizePayment() provides authorization mechanism | 2025-11-20 |  |
| `Nexus\Procurement` | Business Requirements | BUS-PRO-0095 | Requester cannot approve their own requisition | ✅ Implemented | Enforced in RequisitionManager::approveRequisition() via UnauthorizedApprovalException::cannotApproveOwnRequisition() | 2025-11-20 |  |
| `Nexus\Procurement` | Business Requirements | BUS-PRO-0100 | PO creator cannot create GRN for the same PO | ✅ Implemented | Enforced in GoodsReceiptManager::createGoodsReceipt() via UnauthorizedApprovalException::cannotCreateGrnForOwnPo() | 2025-11-20 |  |
| `Nexus\Procurement` | Business Requirements | BUS-PRO-0105 | GRN creator cannot authorize payment for the same PO | ✅ Implemented | Enforced in GoodsReceiptManager::authorizePayment() via UnauthorizedApprovalException::cannotAuthorizePaymentForOwnGrn() | 2025-11-20 |  |
| `Nexus\Procurement` | Business Requirements | BUS-PRO-0110 | Blanket PO releases cannot exceed blanket PO total committed value | ✅ Implemented | Enforced in PurchaseOrderManager::createBlanketRelease() via BudgetExceededException::blanketPoReleaseExceedsTotal() | 2025-11-20 |  |
| `Nexus\Procurement` | Business Requirements | BUS-PRO-0115 | Vendor quote must be submitted before RFQ deadline to be considered valid | ✅ Implemented | VendorQuote model tracks valid_until date for validation | 2025-11-20 |  |
| `Nexus\Procurement` | Business Requirements | BUS-PRO-0120 | Tax calculation based on vendor jurisdiction and tax codes from nexus-tax-management | ✅ Skeleton | Supported via metadata JSON column for future integration | 2025-11-20 |  |
| `Nexus\Procurement` | Business Requirements | BUS-PRO-0124 | All procurement amounts must be in tenant's base currency OR converted at transaction date exchange rate | ✅ Skeleton | Supported via metadata JSON column for currency_code | 2025-11-20 |  |
| `Nexus\Procurement` | Functional Requirement | FUN-PRO-0235 | Create purchase requisition with line items | ✅ Implemented | RequisitionManager::createRequisition() with line item support | 2025-11-20 |  |
| `Nexus\Procurement` | Functional Requirement | FUN-PRO-0241 | Requisition approval workflow | ✅ Implemented | RequisitionManager::submitForApproval(), approveRequisition(), rejectRequisition() | 2025-11-20 |  |
| `Nexus\Procurement` | Functional Requirement | FUN-PRO-0247 | Convert requisition to purchase order | ✅ Implemented | PurchaseOrderManager::createFromRequisition() with budget validation | 2025-11-20 |  |
| `Nexus\Procurement` | Functional Requirement | FUN-PRO-0253 | Direct purchase order creation | ✅ Implemented | PurchaseOrderManager::createBlanketPo() for direct POs | 2025-11-20 |  |
| `Nexus\Procurement` | Functional Requirement | FUN-PRO-0259 | Goods receipt note (GRN) creation | ✅ Implemented | GoodsReceiptManager::createGoodsReceipt() with quantity validation | 2025-11-20 |  |
| `Nexus\Procurement` | Functional Requirement | FUN-PRO-0265 | 3-way matching (PO-GRN-Invoice) | ✅ Implemented | MatchingEngine::performThreeWayMatch() and performBatchMatch() | 2025-11-20 |  |
| `Nexus\Procurement` | Functional Requirement | FUN-PRO-0271 | Purchase requisition status tracking | ✅ Implemented | RequisitionInterface::getStatus() with 5 statuses | 2025-11-20 |  |
| `Nexus\Procurement` | Performance Requirement | PER-PRO-0327 | Requisition creation and save | ✅ Implemented | Optimized with batch inserts in DbRequisitionRepository::create() | 2025-11-20 |  |
| `Nexus\Procurement` | Performance Requirement | PER-PRO-0334 | PO generation from requisition | ✅ Implemented | Optimized with eager loading and indexed queries | 2025-11-20 |  |
| `Nexus\Procurement` | Performance Requirement | PER-PRO-0341 | 3-way match processing | ✅ Implemented | MatchingEngine target: <500ms for 100 lines with batch processing | 2025-11-20 |  |
| `Nexus\Procurement` | Performance Requirement | PER-PRO-0347 | Vendor quote comparison loading | ✅ Implemented | VendorQuoteManager::compareQuotes() with single query retrieval | 2025-11-20 |  |
| `Nexus\Procurement` | Performance Requirement | PER-PRO-0353 | Procurement analytics dashboard | ✅ Skeleton | Repository methods support filters for future analytics queries | 2025-11-20 |  |
| `Nexus\Procurement` | Reliability Requirement | REL-PRO-0389 | All financial transactions MUST be ACID-compliant | ✅ Implemented | Using Laravel database transactions with Eloquent ORM | 2025-11-20 |  |
| `Nexus\Procurement` | Reliability Requirement | REL-PRO-0395 | 3-way match MUST prevent payment authorization if discrepancies exceed tolerance | ✅ Implemented | MatchingEngine returns match result with recommendation | 2025-11-20 |  |
| `Nexus\Procurement` | Reliability Requirement | REL-PRO-0401 | Approval workflows MUST be resumable after system failure | ✅ Implemented | Status-based state machine persisted in database | 2025-11-20 |  |
| `Nexus\Procurement` | Reliability Requirement | REL-PRO-0407 | Concurrency control for PO approval | ✅ Implemented | Eloquent optimistic locking via updated_at timestamp | 2025-11-20 |  |
| `Nexus\Procurement` | Security and Compliance Requirement | SEC-PRO-0441 | Tenant data isolation | ✅ Implemented | All queries filtered by tenant_id with indexed columns | 2025-11-20 |  |
| `Nexus\Procurement` | Security and Compliance Requirement | SEC-PRO-0447 | Role-based access control | ✅ Skeleton | Application layer (Atomy) enforces RBAC via middleware | 2025-11-20 |  |
| `Nexus\Procurement` | Security and Compliance Requirement | SEC-PRO-0453 | Vendor data encryption | ✅ Skeleton | Database-level encryption via Laravel encrypted casting | 2025-11-20 |  |
| `Nexus\Procurement` | Security and Compliance Requirement | SEC-PRO-0459 | Audit trail completeness | ✅ Skeleton | Timestamps and soft deletes, integrates with Nexus\AuditLogger | 2025-11-20 |  |
| `Nexus\Procurement` | Security and Compliance Requirement | SEC-PRO-0465 | Separation of duties | ✅ Implemented | 3-person rule enforced in multiple managers | 2025-11-20 |  |
| `Nexus\Procurement` | Security and Compliance Requirement | SEC-PRO-0470 | Document access control | ✅ Skeleton | Application layer enforces access control via policies | 2025-11-20 |  |
| `Nexus\Procurement` | User Story | USE-PRO-0508 | As a requester, I want to create a purchase requisition for items I need, specifying quantity, description, and estimated cost | ✅ Implemented | ProcurementManager::createRequisition() with full line item support | 2025-11-20 |  |
| `Nexus\Procurement` | User Story | USE-PRO-0515 | As a department manager, I want to approve or reject requisitions from my team members with comments | ✅ Implemented | ProcurementManager::approveRequisition() and rejectRequisition() | 2025-11-20 |  |
| `Nexus\Procurement` | User Story | USE-PRO-0522 | As a procurement officer, I want to convert an approved requisition into a purchase order, selecting a vendor and negotiating final price | ✅ Implemented | ProcurementManager::convertRequisitionToPo() with vendor selection | 2025-11-20 |  |
| `Nexus\Procurement` | User Story | USE-PRO-0529 | As a procurement officer, I want to create purchase orders directly (without requisition) for regular/recurring purchases | ✅ Implemented | ProcurementManager::createPurchaseOrder() creates blanket POs | 2025-11-20 |  |
| `Nexus\Procurement` | User Story | USE-PRO-0536 | As warehouse staff, I want to record goods receipt against a PO, noting actual quantity received and any discrepancies | ✅ Implemented | ProcurementManager::createGoodsReceipt() with line-level details | 2025-11-20 |  |
| `Nexus\Procurement` | User Story | USE-PRO-0542 | As AP clerk, I want to match a vendor invoice against the PO and GRN (3-way match) before authorizing payment | ✅ Implemented | ProcurementManager::performThreeWayMatch() returns match result | 2025-11-20 |  |
| `Nexus\Procurement` | User Story | USE-PRO-0548 | As a requester, I want to view the status of my requisitions (pending, approved, converted to PO, delivered) | ✅ Implemented | ProcurementManager::getRequisition() and getRequisitionsForTenant() | 2025-11-20 |  |
| `Nexus\Procurement` | Business Requirements | BUS-PRO-0041 | A requisition MUST have at least one line item |  |  |  |  |
| `Nexus\Procurement` | Business Requirements | BUS-PRO-0048 | Requisition total estimate MUST equal sum of line item estimates |  |  |  |  |
| `Nexus\Procurement` | Business Requirements | BUS-PRO-0055 | Approved requisitions cannot be edited (only cancelled) |  |  |  |  |
| `Nexus\Procurement` | Business Requirements | BUS-PRO-0062 | A purchase order MUST reference an approved requisition OR be explicitly marked as direct PO |  |  |  |  |
| `Nexus\Procurement` | Business Requirements | BUS-PRO-0069 | PO total amount MUST NOT exceed requisition approved amount by more than 10% without re-approval |  |  |  |  |
| `Nexus\Procurement` | Business Requirements | BUS-PRO-0076 | GRN quantity cannot exceed PO quantity for any line item |  |  |  |  |
| `Nexus\Procurement` | Business Requirements | BUS-PRO-0083 | 3-way match tolerance rules are configurable per tenant |  |  |  |  |
| `Nexus\Procurement` | Business Requirements | BUS-PRO-0089 | Payment authorization requires successful 3-way match OR manual override by authorized user |  |  |  |  |
| `Nexus\Procurement` | Business Requirements | BUS-PRO-0095 | Requester cannot approve their own requisition |  |  |  |  |
| `Nexus\Procurement` | Business Requirements | BUS-PRO-0100 | PO creator cannot create GRN for the same PO |  |  |  |  |
| `Nexus\Procurement` | Business Requirements | BUS-PRO-0105 | GRN creator cannot authorize payment for the same PO |  |  |  |  |
| `Nexus\Procurement` | Business Requirements | BUS-PRO-0110 | Blanket PO releases cannot exceed blanket PO total committed value |  |  |  |  |
| `Nexus\Procurement` | Business Requirements | BUS-PRO-0115 | Vendor quote must be submitted before RFQ deadline to be considered valid |  |  |  |  |
| `Nexus\Procurement` | Business Requirements | BUS-PRO-0120 | Tax calculation based on vendor jurisdiction and tax codes from nexus-tax-management |  |  |  |  |
| `Nexus\Procurement` | Business Requirements | BUS-PRO-0124 | All procurement amounts must be in tenant's base currency OR converted at transaction date exchange rate |  |  |  |  |
| `Nexus\Procurement` | Functional Requirement | FUN-PRO-0235 | Create purchase requisition with line items |  |  |  |  |
| `Nexus\Procurement` | Functional Requirement | FUN-PRO-0241 | Requisition approval workflow |  |  |  |  |
| `Nexus\Procurement` | Functional Requirement | FUN-PRO-0247 | Convert requisition to purchase order |  |  |  |  |
| `Nexus\Procurement` | Functional Requirement | FUN-PRO-0253 | Direct purchase order creation |  |  |  |  |
| `Nexus\Procurement` | Functional Requirement | FUN-PRO-0259 | Goods receipt note (GRN) creation |  |  |  |  |
| `Nexus\Procurement` | Functional Requirement | FUN-PRO-0265 | 3-way matching (PO-GRN-Invoice) |  |  |  |  |
| `Nexus\Procurement` | Functional Requirement | FUN-PRO-0271 | Purchase requisition status tracking |  |  |  |  |
| `Nexus\Procurement` | Performance Requirement | PER-PRO-0327 | Requisition creation and save |  |  |  |  |
| `Nexus\Procurement` | Performance Requirement | PER-PRO-0334 | PO generation from requisition |  |  |  |  |
| `Nexus\Procurement` | Performance Requirement | PER-PRO-0341 | 3-way match processing |  |  |  |  |
| `Nexus\Procurement` | Performance Requirement | PER-PRO-0347 | Vendor quote comparison loading |  |  |  |  |
| `Nexus\Procurement` | Performance Requirement | PER-PRO-0353 | Procurement analytics dashboard |  |  |  |  |
| `Nexus\Procurement` | Reliability Requirement | REL-PRO-0389 | All financial transactions MUST be ACID-compliant |  |  |  |  |
| `Nexus\Procurement` | Reliability Requirement | REL-PRO-0395 | 3-way match MUST prevent payment authorization if discrepancies exceed tolerance |  |  |  |  |
| `Nexus\Procurement` | Reliability Requirement | REL-PRO-0401 | Approval workflows MUST be resumable after system failure |  |  |  |  |
| `Nexus\Procurement` | Reliability Requirement | REL-PRO-0407 | Concurrency control for PO approval |  |  |  |  |
| `Nexus\Procurement` | Security and Compliance Requirement | SEC-PRO-0441 | Tenant data isolation |  |  |  |  |
| `Nexus\Procurement` | Security and Compliance Requirement | SEC-PRO-0447 | Role-based access control |  |  |  |  |
| `Nexus\Procurement` | Security and Compliance Requirement | SEC-PRO-0453 | Vendor data encryption |  |  |  |  |
| `Nexus\Procurement` | Security and Compliance Requirement | SEC-PRO-0459 | Audit trail completeness |  |  |  |  |
| `Nexus\Procurement` | Security and Compliance Requirement | SEC-PRO-0465 | Separation of duties |  |  |  |  |
| `Nexus\Procurement` | Security and Compliance Requirement | SEC-PRO-0470 | Document access control |  |  |  |  |
| `Nexus\Procurement` | User Story | USE-PRO-0508 | As a requester, I want to create a purchase requisition for items I need, specifying quantity, description, and estimated cost |  |  |  |  |
| `Nexus\Procurement` | User Story | USE-PRO-0515 | As a department manager, I want to approve or reject requisitions from my team members with comments |  |  |  |  |
| `Nexus\Procurement` | User Story | USE-PRO-0522 | As a procurement officer, I want to convert an approved requisition into a purchase order, selecting a vendor and negotiating final price |  |  |  |  |
| `Nexus\Procurement` | User Story | USE-PRO-0529 | As a procurement officer, I want to create purchase orders directly (without requisition) for regular/recurring purchases |  |  |  |  |
| `Nexus\Procurement` | User Story | USE-PRO-0536 | As warehouse staff, I want to record goods receipt against a PO, noting actual quantity received and any discrepancies |  |  |  |  |
| `Nexus\Procurement` | User Story | USE-PRO-0542 | As AP clerk, I want to match a vendor invoice against the PO and GRN (3-way match) before authorizing payment |  |  |  |  |
| `Nexus\Procurement` | User Story | USE-PRO-0548 | As a requester, I want to view the status of my requisitions (pending, approved, converted to PO, delivered) |  |  |  |  |
