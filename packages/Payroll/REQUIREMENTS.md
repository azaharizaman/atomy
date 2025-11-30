# Requirements: Payroll

**Total Requirements:** 85 (Core Package Scope)

> **Note:** This file documents requirements specific to the `Nexus\Payroll` atomic package. Application layer requirements (API controllers, Eloquent models, migrations) are outside package scope and documented in root `docs/REQUIREMENTS_PAYROLL.md`.

| Package Namespace | Requirements Type | Code | Requirement Statements | Files/Folders | Status | Notes on Status | Date Last Updated |
|-------------------|-------------------|------|------------------------|---------------|--------|-----------------|-------------------|
| `Nexus\Payroll` | Architectural Requirement | ARC-PAY-0001 | Package MUST be framework-agnostic with no Laravel dependencies in core services | `composer.json`, `src/` | ✅ Complete | Zero framework dependencies | 2025-01-15 |
| `Nexus\Payroll` | Architectural Requirement | ARC-PAY-0002 | Package MUST be country-agnostic with no hardcoded statutory calculation logic | `src/Services/` | ✅ Complete | All statutory via interface | 2025-01-15 |
| `Nexus\Payroll` | Architectural Requirement | ARC-PAY-0003 | All statutory calculations MUST be performed via StatutoryCalculatorInterface contract | `src/Contracts/StatutoryCalculatorInterface.php` | ✅ Complete | Interface defined | 2025-01-15 |
| `Nexus\Payroll` | Architectural Requirement | ARC-PAY-0004 | All data structures defined via interfaces (PayslipInterface, ComponentInterface) | `src/Contracts/` | ✅ Complete | 6 entity interfaces | 2025-01-15 |
| `Nexus\Payroll` | Architectural Requirement | ARC-PAY-0005 | All persistence operations via CQRS repository interfaces (Query + Persist) | `src/Contracts/*QueryInterface.php`, `src/Contracts/*PersistInterface.php` | ✅ Complete | CQRS refactored | 2025-01-15 |
| `Nexus\Payroll` | Architectural Requirement | ARC-PAY-0006 | Business logic in service layer (PayrollEngine, ComponentManager, PayslipManager) | `src/Services/` | ✅ Complete | 3 services implemented | 2025-01-15 |
| `Nexus\Payroll` | Architectural Requirement | ARC-PAY-0007 | All service classes MUST be final readonly class | `src/Services/` | ✅ Complete | Refactored Jan 2025 | 2025-01-15 |
| `Nexus\Payroll` | Architectural Requirement | ARC-PAY-0008 | Package composer.json MUST NOT depend on laravel/framework | `composer.json` | ✅ Complete | Only PHP 8.3 required | 2025-01-15 |
| `Nexus\Payroll` | Architectural Requirement | ARC-PAY-0009 | All dependencies MUST be injected via constructor as interfaces | `src/Services/` | ✅ Complete | DI pattern followed | 2025-01-15 |
| `Nexus\Payroll` | Architectural Requirement | ARC-PAY-0010 | Repository interfaces MUST follow CQRS separation (read/write) | `src/Contracts/` | ✅ Complete | 6 CQRS interfaces | 2025-01-15 |
| `Nexus\Payroll` | Architectural Requirement | ARC-PAY-0011 | Country-specific packages MUST be separate composer packages | N/A | ✅ Complete | PayrollMysStatutory exists | 2025-01-15 |
| `Nexus\Payroll` | Architectural Requirement | ARC-PAY-0012 | StatutoryCalculatorInterface MUST accept PayloadInterface and return DeductionResultInterface | `src/Contracts/` | ✅ Complete | Contract defined | 2025-01-15 |
| `Nexus\Payroll` | Business Requirement | BUS-PAY-0001 | Payroll components MUST support earnings, deductions, and employer contributions | `src/ValueObjects/ComponentType.php` | ✅ Complete | 3 types defined | 2025-01-15 |
| `Nexus\Payroll` | Business Requirement | BUS-PAY-0002 | Components MUST support multiple calculation methods (fixed, percentage, formula) | `src/ValueObjects/CalculationMethod.php` | ✅ Complete | 4 methods defined | 2025-01-15 |
| `Nexus\Payroll` | Business Requirement | BUS-PAY-0003 | Payslips MUST track status lifecycle (draft, calculated, approved, paid, cancelled) | `src/ValueObjects/PayslipStatus.php` | ✅ Complete | 5 statuses defined | 2025-01-15 |
| `Nexus\Payroll` | Business Requirement | BUS-PAY-0004 | Employee components MUST have effective date range for activation/deactivation | `src/Contracts/EmployeeComponentInterface.php` | ✅ Complete | Date range in interface | 2025-01-15 |
| `Nexus\Payroll` | Business Requirement | BUS-PAY-0005 | Payslip data MUST be immutable once generated (regeneration creates new record) | `src/Contracts/PayslipInterface.php` | ✅ Complete | Immutable by design | 2025-01-15 |
| `Nexus\Payroll` | Business Requirement | BUS-PAY-0006 | Statutory deductions calculated via injected StatutoryCalculatorInterface only | `src/Services/PayrollEngine.php` | ✅ Complete | Dependency injected | 2025-01-15 |
| `Nexus\Payroll` | Business Requirement | BUS-PAY-0007 | Gross pay cannot be negative | `src/Services/PayrollEngine.php` | ⏳ Pending | Validation needed | 2025-01-15 |
| `Nexus\Payroll` | Business Requirement | BUS-PAY-0008 | Net pay can be negative if deductions exceed gross (e.g., final settlement) | `src/Services/PayrollEngine.php` | ✅ Complete | Allowed by design | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0001 | Provide PayrollEngine as main orchestration service | `src/Services/PayrollEngine.php` | ✅ Complete | Service implemented | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0002 | Provide ComponentManager for component lifecycle | `src/Services/ComponentManager.php` | ✅ Complete | Service implemented | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0003 | Provide PayslipManager for payslip CRUD | `src/Services/PayslipManager.php` | ✅ Complete | Service implemented | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0004 | ComponentQueryInterface MUST provide findById method | `src/Contracts/ComponentQueryInterface.php` | ✅ Complete | Method defined | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0005 | ComponentQueryInterface MUST provide findByCode method | `src/Contracts/ComponentQueryInterface.php` | ✅ Complete | Method defined | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0006 | ComponentQueryInterface MUST provide getActiveComponents method | `src/Contracts/ComponentQueryInterface.php` | ✅ Complete | Method defined | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0007 | ComponentPersistInterface MUST provide create method | `src/Contracts/ComponentPersistInterface.php` | ✅ Complete | Method defined | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0008 | ComponentPersistInterface MUST provide update method | `src/Contracts/ComponentPersistInterface.php` | ✅ Complete | Method defined | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0009 | ComponentPersistInterface MUST provide delete method | `src/Contracts/ComponentPersistInterface.php` | ✅ Complete | Method defined | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0010 | EmployeeComponentQueryInterface MUST provide findById method | `src/Contracts/EmployeeComponentQueryInterface.php` | ✅ Complete | Method defined | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0011 | EmployeeComponentQueryInterface MUST provide getActiveComponentsForEmployee method | `src/Contracts/EmployeeComponentQueryInterface.php` | ✅ Complete | Method defined | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0012 | EmployeeComponentPersistInterface MUST provide create method | `src/Contracts/EmployeeComponentPersistInterface.php` | ✅ Complete | Method defined | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0013 | EmployeeComponentPersistInterface MUST provide update method | `src/Contracts/EmployeeComponentPersistInterface.php` | ✅ Complete | Method defined | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0014 | EmployeeComponentPersistInterface MUST provide delete method | `src/Contracts/EmployeeComponentPersistInterface.php` | ✅ Complete | Method defined | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0015 | PayslipQueryInterface MUST provide findById method | `src/Contracts/PayslipQueryInterface.php` | ✅ Complete | Method defined | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0016 | PayslipQueryInterface MUST provide findByPayslipNumber method | `src/Contracts/PayslipQueryInterface.php` | ✅ Complete | Method defined | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0017 | PayslipQueryInterface MUST provide getEmployeePayslips method | `src/Contracts/PayslipQueryInterface.php` | ✅ Complete | Method defined | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0018 | PayslipQueryInterface MUST provide getPayslipsForPeriod method | `src/Contracts/PayslipQueryInterface.php` | ✅ Complete | Method defined | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0019 | PayslipPersistInterface MUST provide create method | `src/Contracts/PayslipPersistInterface.php` | ✅ Complete | Method defined | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0020 | PayslipPersistInterface MUST provide update method | `src/Contracts/PayslipPersistInterface.php` | ✅ Complete | Method defined | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0021 | PayslipPersistInterface MUST provide delete method | `src/Contracts/PayslipPersistInterface.php` | ✅ Complete | Method defined | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0022 | StatutoryCalculatorInterface MUST provide calculate method | `src/Contracts/StatutoryCalculatorInterface.php` | ✅ Complete | Method defined | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0023 | StatutoryCalculatorInterface MUST provide getSupportedCountryCode method | `src/Contracts/StatutoryCalculatorInterface.php` | ✅ Complete | Method defined | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0024 | StatutoryCalculatorInterface MUST provide getRequiredEmployeeFields method | `src/Contracts/StatutoryCalculatorInterface.php` | ✅ Complete | Method defined | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0025 | PayloadInterface MUST provide getEmployeeId method | `src/Contracts/PayloadInterface.php` | ✅ Complete | Method defined | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0026 | PayloadInterface MUST provide getGrossPay method | `src/Contracts/PayloadInterface.php` | ✅ Complete | Method defined | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0027 | PayloadInterface MUST provide getTaxableIncome method | `src/Contracts/PayloadInterface.php` | ✅ Complete | Method defined | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0028 | PayloadInterface MUST provide getMetadata method | `src/Contracts/PayloadInterface.php` | ✅ Complete | Method defined | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0029 | DeductionResultInterface MUST provide getTotalDeductions method | `src/Contracts/DeductionResultInterface.php` | ✅ Complete | Method defined | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0030 | DeductionResultInterface MUST provide getBreakdown method | `src/Contracts/DeductionResultInterface.php` | ✅ Complete | Method defined | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0031 | DeductionResultInterface MUST provide getEmployerContributions method | `src/Contracts/DeductionResultInterface.php` | ✅ Complete | Method defined | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0032 | ComponentInterface MUST define getId method | `src/Contracts/ComponentInterface.php` | ✅ Complete | Method defined | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0033 | ComponentInterface MUST define getCode method | `src/Contracts/ComponentInterface.php` | ✅ Complete | Method defined | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0034 | ComponentInterface MUST define getName method | `src/Contracts/ComponentInterface.php` | ✅ Complete | Method defined | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0035 | ComponentInterface MUST define getType method returning ComponentType | `src/Contracts/ComponentInterface.php` | ✅ Complete | Method defined | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0036 | ComponentInterface MUST define getCalculationMethod method returning CalculationMethod | `src/Contracts/ComponentInterface.php` | ✅ Complete | Method defined | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0037 | PayslipInterface MUST define getId method | `src/Contracts/PayslipInterface.php` | ✅ Complete | Method defined | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0038 | PayslipInterface MUST define getEmployeeId method | `src/Contracts/PayslipInterface.php` | ✅ Complete | Method defined | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0039 | PayslipInterface MUST define getGrossPay method | `src/Contracts/PayslipInterface.php` | ✅ Complete | Method defined | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0040 | PayslipInterface MUST define getNetPay method | `src/Contracts/PayslipInterface.php` | ✅ Complete | Method defined | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0041 | PayslipInterface MUST define getStatus method returning PayslipStatus | `src/Contracts/PayslipInterface.php` | ✅ Complete | Method defined | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0042 | PayslipInterface MUST define getPeriodStart method | `src/Contracts/PayslipInterface.php` | ✅ Complete | Method defined | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0043 | PayslipInterface MUST define getPeriodEnd method | `src/Contracts/PayslipInterface.php` | ✅ Complete | Method defined | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0044 | EmployeeComponentInterface MUST define getId method | `src/Contracts/EmployeeComponentInterface.php` | ✅ Complete | Method defined | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0045 | EmployeeComponentInterface MUST define getEmployeeId method | `src/Contracts/EmployeeComponentInterface.php` | ✅ Complete | Method defined | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0046 | EmployeeComponentInterface MUST define getComponentId method | `src/Contracts/EmployeeComponentInterface.php` | ✅ Complete | Method defined | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0047 | EmployeeComponentInterface MUST define getAmount method | `src/Contracts/EmployeeComponentInterface.php` | ✅ Complete | Method defined | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0048 | EmployeeComponentInterface MUST define getEffectiveFrom method | `src/Contracts/EmployeeComponentInterface.php` | ✅ Complete | Method defined | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0049 | EmployeeComponentInterface MUST define getEffectiveTo method | `src/Contracts/EmployeeComponentInterface.php` | ✅ Complete | Method defined | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0050 | PayrollEngine MUST provide processPeriod method | `src/Services/PayrollEngine.php` | ✅ Complete | Method implemented | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0051 | PayrollEngine MUST provide processEmployee method | `src/Services/PayrollEngine.php` | ✅ Complete | Method implemented | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0052 | ComponentManager MUST provide createComponent method | `src/Services/ComponentManager.php` | ✅ Complete | Method implemented | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0053 | ComponentManager MUST provide updateComponent method | `src/Services/ComponentManager.php` | ✅ Complete | Method implemented | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0054 | ComponentManager MUST provide deleteComponent method | `src/Services/ComponentManager.php` | ✅ Complete | Method implemented | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0055 | ComponentManager MUST provide getComponent method | `src/Services/ComponentManager.php` | ✅ Complete | Method implemented | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0056 | PayslipManager MUST provide createPayslip method | `src/Services/PayslipManager.php` | ✅ Complete | Method implemented | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0057 | PayslipManager MUST provide updatePayslipStatus method | `src/Services/PayslipManager.php` | ✅ Complete | Method implemented | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0058 | PayslipManager MUST provide getPayslip method | `src/Services/PayslipManager.php` | ✅ Complete | Method implemented | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0059 | PayslipManager MUST provide getEmployeePayslips method | `src/Services/PayslipManager.php` | ✅ Complete | Method implemented | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0060 | Package MUST provide PayrollException base exception | `src/Exceptions/PayrollException.php` | ✅ Complete | Exception defined | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0061 | Package MUST provide ComponentNotFoundException | `src/Exceptions/ComponentNotFoundException.php` | ✅ Complete | Exception defined | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0062 | Package MUST provide PayslipNotFoundException | `src/Exceptions/PayslipNotFoundException.php` | ✅ Complete | Exception defined | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0063 | Package MUST provide PayloadValidationException | `src/Exceptions/PayloadValidationException.php` | ✅ Complete | Exception defined | 2025-01-15 |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0064 | Package MUST provide PayslipValidationException | `src/Exceptions/PayslipValidationException.php` | ✅ Complete | Exception defined | 2025-01-15 |
| `Nexus\Payroll` | Integration Requirement | INT-PAY-0001 | Package MUST support integration with Nexus\Hrm via interface contract | N/A | ⏳ Pending | Interface not defined | 2025-01-15 |
| `Nexus\Payroll` | Integration Requirement | INT-PAY-0002 | Package MUST support integration with Nexus\Accounting via interface contract | N/A | ⏳ Pending | Interface not defined | 2025-01-15 |
| `Nexus\Payroll` | Integration Requirement | INT-PAY-0003 | Package MUST support integration with Nexus\AuditLogger via interface contract | N/A | ⏳ Pending | Interface not defined | 2025-01-15 |
