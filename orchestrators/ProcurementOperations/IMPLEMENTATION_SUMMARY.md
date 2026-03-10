# ProcurementOperations - Implementation Summary

## 2026-03-10 Hardening Update

### Scope
- Hardened `src/Services/AccrualCalculationService.php` to remove synthetic success IDs and enforce fail-fast behavior for accounting posting paths.
- Fixed exception named-argument mismatch in `src/Listeners/ReverseAccrualOnInvoiceMatched.php`.
- Added focused unit tests in `tests/Unit/Services/AccrualCalculationServiceTest.php`.

### Changes Applied
1. **Removed synthetic return IDs (`PENDING-*`)**
   - Replaced placeholder returns with `AccrualException::postingFailed(...)` when posting infrastructure is unavailable.
   - Affected methods:
     - `postGoodsReceiptAccrual(...)`
     - `reverseAccrualOnMatch(...)`
     - `postPayableLiability(...)`
     - `postPaymentEntry(...)`

2. **Fail-fast validation hardening**
   - Added amount guard (`<= 0`) for payable and payment posting methods.
   - Added discount guards in `postPaymentEntry(...)`:
     - discount cannot be negative
     - discount cannot exceed amount
   - Added reversal input guard requiring at least one goods receipt ID.

3. **Contract alignment**
   - Aligned `AccrualCalculationService::postPaymentEntry(...)` signature with `AccrualServiceInterface` (includes `discountCents` parameter in expected order).
   - Added discount-aware journal line generation:
     - debit AP for full amount
     - credit bank for net cash amount
     - credit discount account when discount is applied

4. **Listener robustness**
   - Corrected named argument in `ReverseAccrualOnInvoiceMatched` from `message:` to `reason:` for `AccrualException::postingFailed(...)`.

### Risk/Impact Notes
- Hardening prioritizes **data integrity over silent continuation** in financial posting paths.
- Existing callers that relied on synthetic IDs now receive explicit exceptions and can trigger retry/escalation paths.
- This change is intentionally strict to avoid hidden accounting drift.
