# Test Suite Summary: Statutory

**Package:** `Nexus\Statutory`  
**Last Test Run:** Not yet executed  
**Status:** ⏳ Tests Planned (Implementation Pending)

---

## Test Coverage Metrics

### Overall Coverage
- **Line Coverage:** 0% (Target: 90%+)
- **Function Coverage:** 0% (Target: 95%+)
- **Class Coverage:** 0% (Target: 100%)
- **Complexity Coverage:** 0% (Target: 85%+)

**Note:** Tests are planned but not yet implemented. Package architecture and interfaces validated through integration with Nexus\Finance and Nexus\Payroll.

### Detailed Coverage by Component (Planned)
| Component | Lines Covered | Functions Covered | Target Coverage |
|-----------|---------------|-------------------|-----------------|
| StatutoryReportManager | 0/~350 | 0/~15 | 95% |
| SchemaValidator | 0/~220 | 0/~8 | 90% |
| ReportGenerator | 0/~180 | 0/~6 | 90% |
| FormatConverter | 0/~200 | 0/~10 | 92% |
| FinanceDataExtractor | 0/~150 | 0/~7 | 90% |
| DefaultAccountingAdapter | 0/~180 | 0/~8 | 88% |
| DefaultPayrollStatutoryAdapter | 0/~80 | 0/~5 | 85% |
| Enums (FilingFrequency, ReportFormat) | 0/~50 | 0/~4 | 100% |
| Exceptions | 0/~120 | 0/~12 | 100% |

---

## Test Inventory

### Total Tests Planned: 55

### 1. Interface Tests (5 tests)

#### TaxonomyReportGeneratorInterfaceTest.php
- `test_interface_defines_generate_method()`
- `test_interface_defines_metadata_accessor()`

#### PayrollStatutoryInterfaceTest.php
- `test_interface_defines_calculation_methods()`

#### ReportMetadataInterfaceTest.php
- `test_interface_defines_all_metadata_methods()`

#### StatutoryReportInterfaceTest.php
- `test_interface_defines_entity_properties()`

---

### 2. Service Tests (10 tests)

#### StatutoryReportManagerTest.php
- `test_generate_report_with_valid_taxonomy_adapter()`
- `test_generate_report_throws_exception_for_invalid_type()`
- `test_validate_report_schema_success()`
- `test_validate_report_schema_failure()`
- `test_manage_taxonomy_mapping_create()`
- `test_manage_taxonomy_mapping_update()`
- `test_manage_taxonomy_mapping_delete()`
- `test_version_history_tracking()`
- `test_multi_tenant_isolation()`
- `test_event_dispatching_on_report_generation()`

---

### 3. Core Engine Tests (16 tests)

#### SchemaValidatorTest.php
- `test_validate_xbrl_schema_success()`
- `test_validate_xbrl_schema_failure_missing_tags()`
- `test_validate_xbrl_schema_failure_invalid_values()`
- `test_validate_schema_with_custom_rules()`

#### ReportGeneratorTest.php
- `test_generate_report_orchestrates_extraction_and_formatting()`
- `test_generate_report_handles_missing_data()`
- `test_generate_report_respects_period_lock()`
- `test_generate_report_with_taxonomy_mapping()`

#### FormatConverterTest.php
- `test_convert_to_xbrl_format()`
- `test_convert_to_pdf_format()`
- `test_convert_to_csv_format()`
- `test_convert_to_json_format()`

#### FinanceDataExtractorTest.php
- `test_extract_gl_account_balances()`
- `test_extract_trial_balance()`
- `test_extract_with_taxonomy_mapping()`
- `test_extract_respects_tenant_context()`

---

### 4. Adapter Tests (8 tests)

#### DefaultAccountingAdapterTest.php
- `test_generate_basic_pl_statement()`
- `test_generate_basic_balance_sheet()`
- `test_metadata_returns_default_schema()`
- `test_adapter_returns_empty_taxonomy_tags()`

#### DefaultPayrollStatutoryAdapterTest.php
- `test_calculate_statutory_deductions_returns_zero()`
- `test_adapter_metadata_indicates_fallback()`
- `test_adapter_handles_all_deduction_types()`
- `test_adapter_does_not_throw_exceptions()`

---

### 5. Value Object Tests (8 tests)

#### FilingFrequencyTest.php
- `test_enum_has_monthly_case()`
- `test_enum_has_quarterly_case()`
- `test_enum_has_annual_case()`
- `test_enum_serialization()`

#### ReportFormatTest.php
- `test_enum_has_xbrl_case()`
- `test_enum_has_pdf_case()`
- `test_enum_has_csv_case()`
- `test_enum_has_json_case()`

---

### 6. Exception Tests (6 tests)

#### ExceptionTest.php
- `test_validation_exception_factory_methods()`
- `test_data_extraction_exception_factory_methods()`
- `test_calculation_exception_factory_methods()`
- `test_invalid_report_type_exception_factory_methods()`
- `test_invalid_deduction_type_exception_factory_methods()`
- `test_exception_inheritance_structure()`

---

### 7. Integration Tests (12 tests)

#### ReportGenerationIntegrationTest.php
- `test_end_to_end_xbrl_report_generation()`
- `test_end_to_end_pdf_report_generation()`
- `test_end_to_end_csv_report_generation()`
- `test_end_to_end_json_report_generation()`
- `test_report_generation_with_real_finance_data()`
- `test_report_generation_with_taxonomy_mapping()`

#### MultiFormatConversionIntegrationTest.php
- `test_convert_same_data_to_all_formats()`
- `test_format_conversion_consistency()`

#### TenantIsolationIntegrationTest.php
- `test_reports_scoped_by_tenant()`
- `test_mappings_scoped_by_tenant()`
- `test_cross_tenant_data_isolation()`
- `test_tenant_switching_clears_cache()`

---

### 8. Feature Tests (10 tests)

#### XBRLValidationFeatureTest.php
- `test_xbrl_validation_with_complete_schema()`
- `test_xbrl_validation_detects_missing_mandatory_tags()`
- `test_xbrl_validation_detects_invalid_data_types()`

#### TaxonomyMappingFeatureTest.php
- `test_create_taxonomy_mapping()`
- `test_update_taxonomy_mapping()`
- `test_delete_taxonomy_mapping()`
- `test_taxonomy_mapping_version_history()`

#### MultiTenantScenarioTest.php
- `test_tenant_a_uses_malaysian_ssm_adapter()`
- `test_tenant_b_uses_default_adapter()`
- `test_tenant_switching_loads_correct_adapter()`

---

## Testing Strategy

### What Is Tested

1. **Interface Contracts**
   - All public interfaces define required methods
   - Method signatures match documented API
   - Return types and exceptions are correct

2. **Service Layer Logic**
   - Report generation orchestration
   - Schema validation workflows
   - Taxonomy mapping management
   - Version history tracking
   - Event dispatching

3. **Core Engine Functionality**
   - XBRL schema validation
   - Multi-format conversion (XBRL, PDF, CSV, JSON)
   - Financial data extraction from Nexus\Finance
   - Report generation orchestration

4. **Adapter Pattern**
   - Default adapters provide safe fallbacks
   - Adapters implement required interfaces
   - Metadata correctly identifies adapter capabilities
   - Country-specific adapters bind correctly

5. **Multi-Tenancy**
   - Reports scoped by tenant_id
   - Taxonomy mappings isolated per tenant
   - Adapter binding respects tenant country
   - Cross-tenant data isolation

6. **Exception Handling**
   - Domain exceptions thrown for error conditions
   - Factory methods provide meaningful messages
   - Exception hierarchy correct

### What Is NOT Tested (and Why)

1. **Country-Specific Logic**
   - **Reason:** Country-specific implementations (SSM, MYS Payroll) are separate packages with their own test suites
   - **Example:** XBRL taxonomy for Malaysian Company Act tested in `nexus/statutory-accounting-ssm`

2. **Database Migrations**
   - **Reason:** Migrations are in application layer, not package
   - **Testing:** Application layer tests verify migration correctness

3. **Eloquent Models**
   - **Reason:** Models are in application layer implementing package interfaces
   - **Testing:** Application layer tests verify model behavior

4. **Real Government Portal Integration**
   - **Reason:** Direct submission to government portals not yet implemented
   - **Future:** Integration tests will mock portal APIs when feature added

5. **Performance Benchmarks**
   - **Reason:** Deferred to Phase 2; current focus is correctness
   - **Future:** Benchmark tests for large report generation (10K+ GL accounts)

---

## Critical Test Cases

### 1. XBRL Schema Validation
**Priority:** High  
**Rationale:** Core value proposition - reports must pass government validation

**Test Scenarios:**
- Valid XBRL with all mandatory tags → Validation passes
- Missing mandatory tag → Validation fails with specific error
- Invalid data type (string instead of decimal) → Validation fails
- Invalid taxonomy tag name → Validation fails

---

### 2. Multi-Format Conversion
**Priority:** High  
**Rationale:** Different jurisdictions require different formats

**Test Scenarios:**
- Same financial data converted to XBRL, PDF, CSV, JSON → All formats contain same data
- XBRL output passes schema validation
- PDF output is readable and formatted correctly
- CSV output has correct headers and delimiter
- JSON output has correct structure

---

### 3. GL Account to Taxonomy Mapping
**Priority:** Critical  
**Rationale:** Incorrect mapping → incorrect filings → regulatory penalties

**Test Scenarios:**
- Create mapping: GL account 1000 → taxonomy tag "Assets.CurrentAssets.Cash"
- Update mapping: Change GL account 1000 mapping to different tag
- Delete mapping: Remove mapping for GL account 1000
- Version history: Mapping changes tracked with timestamps and user
- Tenant isolation: Tenant A mappings do not affect Tenant B

---

### 4. Default Adapter Safety
**Priority:** Critical  
**Rationale:** Default adapters must never crash; safe fallback behavior

**Test Scenarios:**
- DefaultAccountingAdapter generates P&L with zero values → No exceptions
- DefaultPayrollStatutoryAdapter returns zero deductions → No exceptions
- Default adapters called with missing data → Returns empty/zero, no crash
- Default adapters metadata indicates "fallback" status

---

### 5. Tenant Isolation
**Priority:** Critical  
**Rationale:** Multi-tenant system must prevent cross-tenant data leakage

**Test Scenarios:**
- Tenant A generates report → Only Tenant A's GL accounts used
- Tenant A mappings do not appear in Tenant B queries
- Switching tenant context clears cached adapter
- Cross-tenant report generation attempt → Exception thrown

---

## Test Execution Metrics (Planned)

### Target Execution Time
- **Unit Tests:** < 5 seconds total
- **Integration Tests:** < 30 seconds total
- **Feature Tests:** < 60 seconds total
- **Full Suite:** < 2 minutes

### Test Data Requirements
- Mock GL accounts (20-50 accounts)
- Sample taxonomy tags (30-40 tags)
- Test XBRL schemas (2-3 jurisdictions)
- Sample financial data (2-3 periods)

---

## How to Run Tests

### Run All Tests
```bash
cd packages/Statutory
composer test
```

### Run Specific Test Suite
```bash
composer test -- --testsuite=Unit
composer test -- --testsuite=Feature
composer test -- --testsuite=Integration
```

### Run with Coverage
```bash
composer test:coverage
```

### Generate Coverage Report
```bash
composer test:coverage -- --coverage-html coverage/
open coverage/index.html
```

---

## CI/CD Integration

### GitHub Actions Workflow (Planned)

```yaml
name: Statutory Package Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: shivammathur/setup-php@v2
        with:
          php-version: 8.3
          coverage: xdebug
      - run: composer install
      - run: composer test:coverage
      - uses: codecov/codecov-action@v3
```

### Quality Gates
- **Minimum Coverage:** 90% line coverage
- **All Tests Passing:** Required for merge
- **No Security Vulnerabilities:** Required for merge

---

## Known Test Gaps

### 1. XBRL Instance Document Generation
**Gap:** Full XBRL instance document generation not tested (only schema validation)  
**Reason:** Instance document generation requires country-specific taxonomy (separate packages)  
**Mitigation:** Schema validation tests ensure structural correctness; instance document tests in country packages

### 2. Real Financial Data Volume
**Gap:** Tests use mock data (20-50 accounts); real systems have 100s-1000s of accounts  
**Reason:** Performance testing deferred to Phase 2  
**Mitigation:** Integration tests with realistic data volume planned for v2.0

### 3. Concurrent Report Generation
**Gap:** No tests for multiple users generating reports simultaneously  
**Reason:** Concurrency testing requires application layer infrastructure  
**Mitigation:** Application layer tests will cover concurrent scenarios

---

## Testing Best Practices

### 1. Mock External Dependencies
```php
// ✅ GOOD: Mock FinanceInterface
$finance = $this->createMock(FinanceInterface::class);
$finance->expects($this->once())
    ->method('getTrialBalance')
    ->willReturn($mockTrialBalance);

$manager = new StatutoryReportManager($finance, ...);
```

### 2. Test Tenant Isolation
```php
// ✅ GOOD: Test tenant scoping
$tenantA = 'tenant-a';
$tenantB = 'tenant-b';

$this->setTenant($tenantA);
$reportA = $manager->generateReport('financial_statement', $periodId);

$this->setTenant($tenantB);
$reportB = $manager->generateReport('financial_statement', $periodId);

$this->assertNotEquals($reportA->getData(), $reportB->getData());
```

### 3. Test Exception Handling
```php
// ✅ GOOD: Test specific exception
$this->expectException(ValidationException::class);
$this->expectExceptionMessage('Missing mandatory tag: Assets.CurrentAssets');

$validator->validate($invalidXbrl);
```

---

## Test Implementation Roadmap

### Phase 1: Core Tests (Weeks 1-2)
- [ ] Interface tests (5 tests)
- [ ] Exception tests (6 tests)
- [ ] Value object tests (8 tests)
- **Target:** 19 tests, 100% interface/exception/VO coverage

### Phase 2: Service & Engine Tests (Weeks 3-4)
- [ ] Service tests (10 tests)
- [ ] Core engine tests (16 tests)
- **Target:** 26 tests, 90%+ service/engine coverage

### Phase 3: Adapter & Integration Tests (Weeks 5-6)
- [ ] Adapter tests (8 tests)
- [ ] Integration tests (12 tests)
- **Target:** 20 tests, 88%+ adapter coverage, end-to-end validation

### Phase 4: Feature Tests & Polish (Week 7)
- [ ] Feature tests (10 tests)
- [ ] Coverage gap analysis
- [ ] Documentation updates
- **Target:** 55 tests, 90%+ overall coverage

---

**Test Suite Status:** ⏳ **Planned** (0/55 tests implemented)  
**Target Coverage:** 90%+ line coverage, 95%+ function coverage  
**Next Step:** Implement Phase 1 core tests (interface, exception, value object)
