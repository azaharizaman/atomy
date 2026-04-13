# TODO: ProcurementOperations Tenant-Scoped Contract Corrections

- [x] **Phase 1: Layer 1 Contract Corrections**
    - [x] 1.1 Procurement contracts (GoodsReceiptQueryInterface, GoodsReceiptPersistInterface, GoodsReceiptRepositoryInterface)
    - [x] 1.2 Payable contracts (VendorBillQueryInterface, VendorBillRepositoryInterface)
- [x] **Phase 2: Layer 2 ProcurementOperations Updates**
    - [x] Update imports/type hints in DataProviders and Coordinators
    - [x] Change vendor bill fetch to tenant-scoped query
    - [x] Replace ID-only goods receipt reads with tenant-scoped reads
- [/] **Phase 3: Test and Regression Hardening**
    - [ ] Update tests to assert tenant parameter
    - [ ] Fix/replace impossible mocks
    - [ ] Add regression tests for corrected paths
- [ ] **Phase 4: Documentation and Change Closure**
    - [ ] Update IMPLEMENTATION_SUMMARY.md for ProcurementOperations
    - [ ] Update IMPLEMENTATION_SUMMARY.md for packages/Procurement
    - [ ] Update IMPLEMENTATION_SUMMARY.md for packages/Payable
