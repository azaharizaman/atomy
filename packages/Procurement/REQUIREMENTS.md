# Requirements: Procurement

**Package:** `Nexus\Procurement`  
**Last Updated:** 2025-11-26  
**Total Requirements:** 44

---

## Summary

| Category | Count | Implemented | Skeleton | Not Started |
|----------|-------|-------------|----------|-------------|
| Business Requirements | 15 | 13 | 2 | 0 |
| Functional Requirements | 7 | 7 | 0 | 0 |
| Performance Requirements | 5 | 4 | 1 | 0 |
| Reliability Requirements | 4 | 4 | 0 | 0 |
| Security & Compliance | 6 | 2 | 4 | 0 |
| User Stories | 7 | 7 | 0 | 0 |
| **TOTAL** | **44** | **37** | **7** | **0** |

**Coverage:** 84% Implemented, 16% Skeleton (planned)

---

## Business Requirements (15)

| Code | Requirement | Status | Implementation | Updated |
|------|-------------|--------|----------------|---------|
| BUS-PRO-0041 | A requisition MUST have at least one line item | ✅ Implemented | `RequisitionManager::validateRequisitionData()`, `InvalidRequisitionDataException::noLines()` | 2025-11-20 |
| BUS-PRO-0048 | Requisition total estimate MUST equal sum of line item estimates | ✅ Implemented | Calculated in repository during line item creation | 2025-11-20 |
| BUS-PRO-0055 | Approved requisitions cannot be edited (only cancelled) | ✅ Implemented | `InvalidRequisitionStateException::cannotEditApproved()` | 2025-11-20 |
| BUS-PRO-0062 | A purchase order MUST reference an approved requisition OR be explicitly marked as direct PO | ✅ Implemented | `PurchaseOrderManager::createFromRequisition()`, `createBlanketPo()` | 2025-11-20 |
| BUS-PRO-0069 | PO total amount MUST NOT exceed requisition approved amount by more than 10% without re-approval | ✅ Implemented | `PurchaseOrderManager::validatePoAgainstRequisition()` with configurable tolerance | 2025-11-20 |
| BUS-PRO-0076 | GRN quantity cannot exceed PO quantity for any line item | ✅ Implemented | `GoodsReceiptManager::validateGrnQuantitiesAgainstPo()`, `InvalidGoodsReceiptDataException::quantityExceedsPo()` | 2025-11-20 |
| BUS-PRO-0083 | 3-way match tolerance rules are configurable per tenant | ✅ Implemented | `config/procurement.php` (quantity_tolerance_percent, price_tolerance_percent) | 2025-11-20 |
| BUS-PRO-0089 | Payment authorization requires successful 3-way match OR manual override by authorized user | ✅ Implemented | `GoodsReceiptManager::authorizePayment()` | 2025-11-20 |
| BUS-PRO-0095 | Requester cannot approve their own requisition | ✅ Implemented | `RequisitionManager::approveRequisition()`, `UnauthorizedApprovalException::cannotApproveOwnRequisition()` | 2025-11-20 |
| BUS-PRO-0100 | PO creator cannot create GRN for the same PO | ✅ Implemented | `GoodsReceiptManager::createGoodsReceipt()`, `UnauthorizedApprovalException::cannotCreateGrnForOwnPo()` | 2025-11-20 |
| BUS-PRO-0105 | GRN creator cannot authorize payment for the same PO | ✅ Implemented | `GoodsReceiptManager::authorizePayment()`, `UnauthorizedApprovalException::cannotAuthorizePaymentForOwnGrn()` | 2025-11-20 |
| BUS-PRO-0110 | Blanket PO releases cannot exceed blanket PO total committed value | ✅ Implemented | `PurchaseOrderManager::createBlanketRelease()`, `BudgetExceededException::blanketPoReleaseExceedsTotal()` | 2025-11-20 |
| BUS-PRO-0115 | Vendor quote must be submitted before RFQ deadline to be considered valid | ✅ Implemented | `VendorQuote` model tracks `valid_until` date | 2025-11-20 |
| BUS-PRO-0120 | Tax calculation based on vendor jurisdiction and tax codes | 📋 Skeleton | Metadata JSON column for future `Nexus\Tax` integration | 2025-11-20 |
| BUS-PRO-0124 | All procurement amounts must be in tenant's base currency OR converted at transaction date | 📋 Skeleton | Metadata JSON column for `currency_code` | 2025-11-20 |

---

## Functional Requirements (7)

| Code | Requirement | Status | Implementation | Updated |
|------|-------------|--------|----------------|---------|
| FUN-PRO-0235 | Create purchase requisition with line items | ✅ Implemented | `RequisitionManager::createRequisition()` | 2025-11-20 |
| FUN-PRO-0241 | Requisition approval workflow | ✅ Implemented | `submitForApproval()`, `approveRequisition()`, `rejectRequisition()` | 2025-11-20 |
| FUN-PRO-0247 | Convert requisition to purchase order | ✅ Implemented | `PurchaseOrderManager::createFromRequisition()` with budget validation | 2025-11-20 |
| FUN-PRO-0253 | Direct purchase order creation | ✅ Implemented | `PurchaseOrderManager::createBlanketPo()` | 2025-11-20 |
| FUN-PRO-0259 | Goods receipt note (GRN) creation | ✅ Implemented | `GoodsReceiptManager::createGoodsReceipt()` with quantity validation | 2025-11-20 |
| FUN-PRO-0265 | 3-way matching (PO-GRN-Invoice) | ✅ Implemented | `MatchingEngine::performThreeWayMatch()`, `performBatchMatch()` | 2025-11-20 |
| FUN-PRO-0271 | Purchase requisition status tracking | ✅ Implemented | `RequisitionInterface::getStatus()` with 5 statuses | 2025-11-20 |

---

## Performance Requirements (5)

| Code | Requirement | Target | Status | Implementation | Updated |
|------|-------------|--------|--------|----------------|---------|
| PER-PRO-0327 | Requisition creation and save | <200ms | ✅ Implemented | Batch inserts in repository | 2025-11-20 |
| PER-PRO-0334 | PO generation from requisition | <300ms | ✅ Implemented | Eager loading and indexed queries | 2025-11-20 |
| PER-PRO-0341 | 3-way match processing for 100 lines | <500ms | ✅ Implemented | `MatchingEngine::performBatchMatch()` | 2025-11-20 |
| PER-PRO-0347 | Vendor quote comparison loading | <200ms | ✅ Implemented | `VendorQuoteManager::compareQuotes()` with single query | 2025-11-20 |
| PER-PRO-0353 | Procurement analytics dashboard | N/A | 📋 Skeleton | Repository filters for future analytics | 2025-11-20 |

---

## Reliability Requirements (4)

| Code | Requirement | Status | Implementation | Updated |
|------|-------------|--------|----------------|---------|
| REL-PRO-0389 | All financial transactions MUST be ACID-compliant | ✅ Implemented | Database transactions | 2025-11-20 |
| REL-PRO-0395 | 3-way match MUST prevent payment authorization if discrepancies exceed tolerance | ✅ Implemented | `MatchingEngine` returns match result with recommendation | 2025-11-20 |
| REL-PRO-0401 | Approval workflows MUST be resumable after system failure | ✅ Implemented | Status-based state machine persisted in database | 2025-11-20 |
| REL-PRO-0407 | Concurrency control for PO approval | ✅ Implemented | Optimistic locking via `updated_at` timestamp | 2025-11-20 |

---

## Security and Compliance Requirements (6)

| Code | Requirement | Status | Implementation | Updated |
|------|-------------|--------|----------------|---------|
| SEC-PRO-0441 | Tenant data isolation | ✅ Implemented | All queries filtered by `tenant_id` with indexed columns | 2025-11-20 |
| SEC-PRO-0447 | Role-based access control | 📋 Skeleton | Application layer enforces RBAC via middleware | 2025-11-20 |
| SEC-PRO-0453 | Vendor data encryption | 📋 Skeleton | Database-level encryption via Laravel encrypted casting | 2025-11-20 |
| SEC-PRO-0459 | Audit trail completeness | 📋 Skeleton | Timestamps, soft deletes, `Nexus\AuditLogger` integration | 2025-11-20 |
| SEC-PRO-0465 | Separation of duties (3-person rule) | ✅ Implemented | Enforced in `RequisitionManager`, `GoodsReceiptManager` | 2025-11-20 |
| SEC-PRO-0470 | Document access control | 📋 Skeleton | Application layer enforces via policies | 2025-11-20 |

---

## User Stories (7)

| Code | User Story | Status | Implementation | Updated |
|------|------------|--------|----------------|---------|
| USE-PRO-0508 | As a requester, I want to create a purchase requisition for items I need, specifying quantity, description, and estimated cost | ✅ Implemented | `ProcurementManager::createRequisition()` | 2025-11-20 |
| USE-PRO-0515 | As a department manager, I want to approve or reject requisitions from my team members with comments | ✅ Implemented | `approveRequisition()`, `rejectRequisition()` | 2025-11-20 |
| USE-PRO-0522 | As a procurement officer, I want to convert an approved requisition into a purchase order, selecting a vendor and negotiating final price | ✅ Implemented | `convertRequisitionToPO()` with vendor selection | 2025-11-20 |
| USE-PRO-0529 | As a procurement officer, I want to create purchase orders directly (without requisition) for regular/recurring purchases | ✅ Implemented | `createDirectPO()` creates blanket POs | 2025-11-20 |
| USE-PRO-0536 | As warehouse staff, I want to record goods receipt against a PO, noting actual quantity received and any discrepancies | ✅ Implemented | `recordGoodsReceipt()` with line-level details | 2025-11-20 |
| USE-PRO-0542 | As AP clerk, I want to match a vendor invoice against the PO and GRN (3-way match) before authorizing payment | ✅ Implemented | `performThreeWayMatch()` returns match result | 2025-11-20 |
| USE-PRO-0548 | As a requester, I want to view the status of my requisitions (pending, approved, converted to PO, delivered) | ✅ Implemented | `getRequisition()`, status tracking | 2025-11-20 |

---

## Legend

| Symbol | Meaning |
|--------|---------|
| ✅ Implemented | Fully implemented and tested |
| 📋 Skeleton | Interface defined, awaiting implementation |
| ⏳ In Progress | Currently being implemented |
| ❌ Not Started | Not yet started |

---

**Maintained By:** Nexus Architecture Team  
**Last Review:** 2025-11-26
