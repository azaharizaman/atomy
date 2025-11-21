# Requirements: Payroll

Total Requirements: 660

| Package Namespace | Requirements Type | Code | Requirement Statements | Files/Folders | Status | Notes on Status | Date Last Updated |
|-------------------|-------------------|------|------------------------|---------------|--------|-----------------|-------------------|
| `Nexus\Payroll` | Architechtural Requirement | ARC-PAY-0988 | Package MUST be framework-agnostic with no Laravel dependencies in core services |  |  |  |  |
| `Nexus\Payroll` | Architechtural Requirement | ARC-PAY-0989 | Package MUST be country-agnostic with no hardcoded statutory calculation logic |  |  |  |  |
| `Nexus\Payroll` | Architechtural Requirement | ARC-PAY-0990 | All statutory calculations MUST be performed via StatutoryCalculatorInterface contract |  |  |  |  |
| `Nexus\Payroll` | Architechtural Requirement | ARC-PAY-0991 | All data structures defined via interfaces (PayRunInterface, PaySlipInterface, PayrollComponentInterface) |  |  |  |  |
| `Nexus\Payroll` | Architechtural Requirement | ARC-PAY-0992 | All persistence operations via repository interfaces |  |  |  |  |
| `Nexus\Payroll` | Architechtural Requirement | ARC-PAY-0993 | Business logic in service layer (PayrollManager, GrossPayCalculator, NetPayCalculator) |  |  |  |  |
| `Nexus\Payroll` | Architechtural Requirement | ARC-PAY-0994 | All database migrations in application layer (apps/Atomy) |  |  |  |  |
| `Nexus\Payroll` | Architechtural Requirement | ARC-PAY-0995 | All Eloquent models in application layer |  |  |  |  |
| `Nexus\Payroll` | Architechtural Requirement | ARC-PAY-0996 | Repository implementations in application layer |  |  |  |  |
| `Nexus\Payroll` | Architechtural Requirement | ARC-PAY-0997 | IoC container bindings in application service provider |  |  |  |  |
| `Nexus\Payroll` | Architechtural Requirement | ARC-PAY-0998 | Package composer.json MUST NOT depend on laravel/framework |  |  |  |  |
| `Nexus\Payroll` | Architechtural Requirement | ARC-PAY-0999 | Support integration with Nexus\Hrm for employee data via EmployeeDataContract |  |  |  |  |
| `Nexus\Payroll` | Architechtural Requirement | ARC-PAY-1000 | Support integration with Nexus\Accounting for journal posting via AccountingInterface |  |  |  |  |
| `Nexus\Payroll` | Architechtural Requirement | ARC-PAY-1001 | Support integration with Nexus\AuditLogger for payroll change tracking |  |  |  |  |
| `Nexus\Payroll` | Architechtural Requirement | ARC-PAY-1002 | Provide event-driven architecture for payroll lifecycle events |  |  |  |  |
| `Nexus\Payroll` | Architechtural Requirement | ARC-PAY-1003 | StatutoryCalculatorInterface MUST define contract for all country-specific implementations |  |  |  |  |
| `Nexus\Payroll` | Architechtural Requirement | ARC-PAY-1004 | StatutoryCalculatorInterface MUST accept PayloadInterface and return array of DeductionResultInterface |  |  |  |  |
| `Nexus\Payroll` | Architechtural Requirement | ARC-PAY-1005 | Country-specific packages MUST be separate composer packages (e.g., payroll-mys-statutory, payroll-sgp-statutory) |  |  |  |  |
| `Nexus\Payroll` | Architechtural Requirement | ARC-PAY-1006 | Application layer binds specific StatutoryCalculatorInterface implementation at runtime |  |  |  |  |
| `Nexus\Payroll` | Architechtural Requirement | ARC-PAY-1007 | Support multi-country deployment by binding different calculators per tenant |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-0170 | PCB calculations MUST use exact LHDN rounding rules (to nearest sen) to match tax department calculations |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-0171 | Pay runs MUST be locked after completion to prevent accidental modifications |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-0172 | Only locked pay runs can generate payslips and post to accounting |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-0173 | Retroactive recalculations MUST recalculate all months from change date forward |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-0174 | EPF contributions CANNOT exceed statutory ceiling (RM5,000 salary base as of 2025) |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-0175 | SOCSO eligibility ends at age 60 for new contributors (existing contributors continue) |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-0176 | EIS contributions required for employees earning below RM4,000 per month |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-0177 | Payslip data MUST be immutable once generated (regeneration creates new record) |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-0178 | Additional remuneration MUST use special PCB calculation tables from LHDN |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-1008 | Pay run must be locked before payslips can be generated |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-1009 | Locked pay runs cannot be edited or deleted |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-1010 | Only locked pay runs can post GL journal entries |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-1011 | Pay run dates cannot overlap for same payroll group |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-1012 | Employee must have active employment during pay period to be included |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-1013 | Payroll components require effective date range validation |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-1014 | Recurring components must have start date; end date is optional |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-1015 | Variable components must be linked to specific pay run |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-1016 | Gross pay cannot be negative |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-1017 | Net pay can be negative if deductions exceed gross (e.g., final settlement) |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-1018 | Statutory deductions calculated via injected StatutoryCalculatorInterface only |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-1019 | YTD calculations must include all locked pay runs in fiscal year |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-1020 | Payslip data must be immutable once generated |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-1021 | Payslip regeneration creates new version with audit trail |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-1022 | Pro-rata salary calculation for mid-month joiners/leavers |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-1023 | Unpaid leave deductions calculated based on daily rate |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-1024 | Overtime payment based on hourly rate with configurable multipliers |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-1025 | Additional remuneration (bonus, commission) processed separately from regular pay |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-1026 | Retroactive pay adjustments require new pay run or amendment |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-1027 | Final settlement includes all outstanding payments and deductions |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-1028 | Payroll processing requires appropriate authorization permissions |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-1029 | All payroll state changes must be ACID-compliant transactions |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-1030 | Payroll data retention must comply with statutory requirements (minimum 7 years) |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-1031 | Employee can only access own payslips unless authorized |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-1032 | Payroll reports visible only to authorized HR and finance personnel |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-1033 | Payslip delivery method configurable (email, portal, print) |  |  |  |  |
| `Nexus\Payroll` | Compliance Requirement | COMP-PAY-1228 | Support statutory record retention (minimum 7 years) |  |  |  |  |
| `Nexus\Payroll` | Compliance Requirement | COMP-PAY-1229 | Generate audit trail for all payroll modifications |  |  |  |  |
| `Nexus\Payroll` | Compliance Requirement | COMP-PAY-1230 | Support regulatory reporting requirements via country-specific implementations |  |  |  |  |
| `Nexus\Payroll` | Compliance Requirement | COMP-PAY-1231 | Generate submission files for statutory authorities (format defined by country package) |  |  |  |  |
| `Nexus\Payroll` | Compliance Requirement | COMP-PAY-1232 | Track submission history and acknowledgments |  |  |  |  |
| `Nexus\Payroll` | Compliance Requirement | COMP-PAY-1233 | Support data anonymization for analytics while preserving audit trail |  |  |  |  |
| `Nexus\Payroll` | Compliance Requirement | COMP-PAY-1234 | Support right to access (employees can request payroll data) |  |  |  |  |
| `Nexus\Payroll` | Compliance Requirement | COMP-PAY-1235 | Support right to rectification (correct erroneous payroll data) |  |  |  |  |
| `Nexus\Payroll` | Compliance Requirement | COMP-PAY-1236 | Generate compliance reports for internal and external audits |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0299 | Execute monthly payroll runs for all active employees with automatic component calculation |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0300 | Support recurring payroll components (fixed allowances, deductions, employer contributions) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0301 | Process variable payroll items (overtime, claims, bonuses, commissions, unpaid leave deductions) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0302 | Calculate Year-to-Date (YTD) tracking for all earnings, deductions, and statutory contributions |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0303 | Implement pay run locking to prevent duplicate processing and enable rollback on errors |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0304 | Support multi-frequency payroll (monthly, semi-monthly, weekly, bonus-only runs) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0305 | Post automatic GL journal entries to nexus-accounting for salary expense and liabilities |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1034 | Provide PayrollManager as main orchestration service |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1035 | Support payroll group definition with frequency and calendar settings |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1036 | Support payroll frequencies (monthly, semi-monthly, bi-weekly, weekly, ad-hoc) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1037 | Support payroll calendars with pay period start/end and payment dates |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1038 | Support public holiday calendar integration for working days calculation |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1039 | Assign employees to payroll groups with effective dates |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1040 | Support multiple payroll groups per company (e.g., monthly staff, weekly workers) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1041 | Define payroll component types (earnings, deductions, employer contributions, reimbursements) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1042 | Define payroll component categories (basic salary, allowance, overtime, bonus, statutory, loan, advance) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1043 | Support calculation methods (fixed amount, percentage of basic, percentage of gross, formula-based) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1044 | Support formula-based components with arithmetic expressions |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1045 | Support component dependency (e.g., allowance calculated after basic) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1046 | Support component rounding rules (round up, down, nearest) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1047 | Support taxable/non-taxable flag for components |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1048 | Support statutory contribution flag for components |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1049 | Support employer contribution tracking separate from employee deductions |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1050 | Create recurring payroll components with start/end dates per employee |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1051 | Support recurring component types (monthly allowance, fixed deduction, loan installment) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1052 | Automatic activation/deactivation of recurring components based on dates |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1053 | Track loan balances with automatic deduction and repayment schedule |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1054 | Track advance payments with automatic recovery schedule |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1055 | Support loan early settlement with interest adjustment |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1056 | Create variable payroll inputs for specific pay run (overtime, claims, bonuses) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1057 | Support bulk import of variable inputs from CSV/Excel |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1058 | Validate variable inputs against employee active status |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1059 | Support overtime input with hours and overtime type |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1060 | Support claims input with amount and claim type |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1061 | Support commission input with amount and commission type |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1062 | Support bonus input with amount and bonus type |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1063 | Support unpaid leave deduction based on leave days from Nexus\Hrm |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1064 | Create pay run for specific payroll group and period |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1065 | Automatic pay run creation based on payroll calendar schedule |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1066 | Support pay run types (regular, bonus, final settlement, off-cycle) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1067 | Support pay run status (draft, calculating, calculated, locked, posted, cancelled) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1068 | Validate no overlapping pay runs for same group before creation |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1069 | Calculate gross pay for all employees in pay run |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1070 | GrossPayCalculator aggregates basic salary + recurring components + variable inputs |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1071 | Support pro-rata calculation for partial month employment |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1072 | Calculate working days in period excluding weekends and public holidays |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1073 | Calculate daily rate (monthly salary / working days) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1074 | Calculate hourly rate (monthly salary / standard working hours) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1075 | Apply overtime multipliers (1.5x for weekday OT, 2.0x for weekend, 3.0x for holiday) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1076 | Invoke StatutoryCalculatorInterface with PayloadInterface containing gross pay and employee data |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1077 | Receive array of DeductionResultInterface from statutory calculator |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1078 | Store statutory deductions with type, amount, and employee/employer split |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1079 | PayloadInterface provides employee attributes (age, marital status, tax status, etc.) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1080 | PayloadInterface provides gross earnings breakdown (basic, allowances, OT, bonuses) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1081 | PayloadInterface provides YTD amounts for progressive calculations |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1082 | DeductionResultInterface specifies deduction type identifier |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1083 | DeductionResultInterface specifies employee amount and employer amount |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1084 | DeductionResultInterface specifies calculation metadata for audit |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1085 | Calculate net pay (gross - employee deductions + reimbursements) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1086 | NetPayCalculator aggregates all deductions from multiple sources |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1087 | Apply rounding rules to final net pay amount |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1088 | Calculate total employer cost (gross + employer contributions) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1089 | Calculate YTD totals for gross, deductions, net, and employer cost |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1090 | Update cumulative YTD amounts upon pay run locking |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1091 | Support YTD reset at fiscal year end |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1092 | Store calculation audit trail (formulas, rates, amounts) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1093 | Lock pay run to prevent further changes |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1094 | Unlock pay run with authorization for corrections |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1095 | Validate all calculations complete before locking |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1096 | Store lock timestamp and user for audit |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1097 | Generate payslips for all employees in locked pay run |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1098 | Payslip contains employee details, pay period, earnings breakdown, deductions breakdown, net pay |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1099 | Payslip contains YTD totals for all categories |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1100 | Payslip contains employer contributions for transparency |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1101 | Payslip includes company branding and footer text |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1102 | Generate payslip PDF with configurable template |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1103 | Support payslip template customization per company |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1104 | Store payslip data with cryptographic hash for integrity verification |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1105 | Payslip regeneration creates new version without deleting original |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1106 | Distribute payslips via email with password-protected PDF |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1107 | Distribute payslips via employee self-service portal |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1108 | Track payslip delivery status (sent, opened, downloaded) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1109 | Support payslip re-send on employee request |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1110 | Generate GL journal entries from locked pay run |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1111 | Map payroll components to GL accounts via configuration |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1112 | Support multi-dimensional accounting (department, cost center, project) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1113 | Journal entry includes salary expense, statutory liabilities, and payable |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1114 | Post journal entries to Nexus\Accounting via AccountingInterface |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1115 | Support journal entry reversal for pay run corrections |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1116 | Generate payroll summary report by pay run |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1117 | Generate departmental payroll cost analysis |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1118 | Generate payroll register with all employee details |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1119 | Generate statutory contribution summary by type |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1120 | Generate bank transfer file in configurable format (CSV, NACHA, SEPA, GIRO) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1121 | Validate bank account details before generating payment file |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1122 | Support split payment (multiple bank accounts per employee) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1123 | Track payment status (pending, sent to bank, cleared, failed) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1124 | Generate payroll variance report comparing periods |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1125 | Generate payroll audit report with all changes and approvals |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1126 | Generate cost center allocation report |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1127 | Generate headcount and payroll cost trends |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1128 | Export all reports to Excel, PDF, CSV formats |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1129 | Support retroactive salary changes with automatic recalculation |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1130 | Calculate retroactive difference (arrears or recovery) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1131 | Create adjustment pay run for retroactive payments |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1132 | Recalculate statutory deductions on retroactive gross |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1133 | Update YTD amounts for affected periods |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1134 | Support one-time payments outside regular pay cycle |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1135 | Support off-cycle pay runs for urgent payments |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1136 | Process final settlement for terminated employees |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1137 | Calculate pro-rata salary for partial month |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1138 | Calculate leave encashment from Nexus\Hrm |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1139 | Apply outstanding loan/advance recoveries in full |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1140 | Apply notice period recovery if applicable |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1141 | Generate final settlement statement |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1142 | Support pay run comparison across periods |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1143 | Highlight variances and flag anomalies |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1144 | Support pre-payroll validation checks |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1145 | Validate employee bank details completeness |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1146 | Validate no duplicate employee in pay run |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1147 | Validate component formulas for syntax errors |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1148 | Generate validation report with issues and warnings |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1149 | Support approval workflow for pay run locking via Nexus\Workflow |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1150 | Support multi-level approval based on payroll amount thresholds |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1151 | Track approval history with comments |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1152 | Notify approvers via email/notification system |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1153 | Support payroll simulation without creating actual pay run |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1154 | Simulate what-if scenarios for salary changes |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1155 | Compare simulation results with historical data |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1156 | Support payroll data export for external systems |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1157 | Support payroll data import from external systems |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1158 | Provide RESTful API endpoints for all payroll operations |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1159 | Provide webhook notifications for payroll events |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1160 | Support batch processing for large payrolls (10,000+ employees) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1161 | Support parallel calculation for performance optimization |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1162 | Employee self-service portal to view payslips |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1163 | Employee self-service to download payslip history |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1164 | Employee self-service to view YTD summaries |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1165 | Employee self-service to update bank account details |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1166 | Employee self-service to view tax documents |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1167 | Manager dashboard showing team payroll summary |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1168 | Manager dashboard showing payroll cost trends for team |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1169 | Payroll admin dashboard showing all active pay runs |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1170 | Payroll admin dashboard showing pending approvals |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1171 | Payroll admin dashboard showing payment status |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1172 | Payroll admin dashboard showing statutory payment deadlines |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1173 | Finance dashboard showing payroll liabilities and expenses |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1174 | Finance dashboard showing cash flow projection for payroll |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1175 | Support payroll year-end processing |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1176 | Generate year-end tax forms via country-specific implementation |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1177 | Generate year-end summary reports for all employees |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1178 | Archive year-end data with compliance retention |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1179 | Support payroll calendar rollover to new fiscal year |  |  |  |  |
| `Nexus\Payroll` | Maintainability Requirement | MAINT-PAY-1218 | Framework-agnostic core with zero Laravel dependencies in packages/Payroll/src/ |  |  |  |  |
| `Nexus\Payroll` | Maintainability Requirement | MAINT-PAY-1219 | Country-agnostic core with zero hardcoded statutory logic |  |  |  |  |
| `Nexus\Payroll` | Maintainability Requirement | MAINT-PAY-1220 | Clear contract definitions in src/Contracts/ for extensibility |  |  |  |  |
| `Nexus\Payroll` | Maintainability Requirement | MAINT-PAY-1221 | Comprehensive test coverage (>85% code coverage, >95% for calculation logic) |  |  |  |  |
| `Nexus\Payroll` | Maintainability Requirement | MAINT-PAY-1222 | Support plugin architecture for custom payroll components |  |  |  |  |
| `Nexus\Payroll` | Maintainability Requirement | MAINT-PAY-1223 | Support multiple database backends (MySQL, PostgreSQL) |  |  |  |  |
| `Nexus\Payroll` | Maintainability Requirement | MAINT-PAY-1224 | Provide comprehensive API documentation with examples |  |  |  |  |
| `Nexus\Payroll` | Maintainability Requirement | MAINT-PAY-1225 | Provide sample country-specific calculator implementation |  |  |  |  |
| `Nexus\Payroll` | Maintainability Requirement | MAINT-PAY-1226 | Clear separation between business logic and persistence layers |  |  |  |  |
| `Nexus\Payroll` | Maintainability Requirement | MAINT-PAY-1227 | Use value objects for domain concepts (PaySlip, PayrollComponent, DeductionResult) |  |  |  |  |
| `Nexus\Payroll` | Performance Requirement | PERF-PAY-1180 | Pay run calculation for 1,000 employees < 2 minutes |  |  |  |  |
| `Nexus\Payroll` | Performance Requirement | PERF-PAY-1181 | Pay run calculation for 10,000 employees < 15 minutes with parallel processing |  |  |  |  |
| `Nexus\Payroll` | Performance Requirement | PERF-PAY-1182 | Statutory calculator invocation < 50ms per employee |  |  |  |  |
| `Nexus\Payroll` | Performance Requirement | PERF-PAY-1183 | Payslip generation for 1,000 employees < 3 minutes |  |  |  |  |
| `Nexus\Payroll` | Performance Requirement | PERF-PAY-1184 | Payslip PDF generation < 500ms per document |  |  |  |  |
| `Nexus\Payroll` | Performance Requirement | PERF-PAY-1185 | Employee payslip retrieval from portal < 200ms |  |  |  |  |
| `Nexus\Payroll` | Performance Requirement | PERF-PAY-1186 | Payroll report generation < 5 seconds for 1,000 employees |  |  |  |  |
| `Nexus\Payroll` | Performance Requirement | PERF-PAY-1187 | Dashboard metrics calculation < 1 second for company of 10,000 employees |  |  |  |  |
| `Nexus\Payroll` | Performance Requirement | PERF-PAY-1188 | Support concurrent pay run processing for different payroll groups |  |  |  |  |
| `Nexus\Payroll` | Performance Requirement | PER-PAY-0380 | Process monthly payroll for 5,000 employees |  |  |  |  |
| `Nexus\Payroll` | Performance Requirement | PER-PAY-0381 | Generate single payslip PDF |  |  |  |  |
| `Nexus\Payroll` | Performance Requirement | PER-PAY-0382 | Retroactive recalculation (12 months, 1,000 employees) |  |  |  |  |
| `Nexus\Payroll` | Performance Requirement | PER-PAY-0383 | PCB calculation with all reliefs |  |  |  |  |
| `Nexus\Payroll` | Performance Requirement | PER-PAY-0384 | EA Form generation for 5,000 employees |  |  |  |  |
| `Nexus\Payroll` | Reliability Requirement | REL-PAY-1201 | All payroll transactions must be ACID-compliant |  |  |  |  |
| `Nexus\Payroll` | Reliability Requirement | REL-PAY-1202 | Pay run calculation must be idempotent (safe to re-run) |  |  |  |  |
| `Nexus\Payroll` | Reliability Requirement | REL-PAY-1203 | Support pay run calculation rollback on error |  |  |  |  |
| `Nexus\Payroll` | Reliability Requirement | REL-PAY-1204 | Handle StatutoryCalculatorInterface failures gracefully with error messages |  |  |  |  |
| `Nexus\Payroll` | Reliability Requirement | REL-PAY-1205 | Prevent duplicate payslip generation via database constraints |  |  |  |  |
| `Nexus\Payroll` | Reliability Requirement | REL-PAY-1206 | Support automatic retry for transient failures in external integrations |  |  |  |  |
| `Nexus\Payroll` | Reliability Requirement | REL-PAY-1207 | Maintain data consistency across Nexus\Hrm and Nexus\Accounting integrations |  |  |  |  |
| `Nexus\Payroll` | Reliability Requirement | REL-PAY-1208 | Generate automated backups before pay run locking |  |  |  |  |
| `Nexus\Payroll` | Reliability Requirement | REL-PAY-1209 | Support disaster recovery with point-in-time restore |  |  |  |  |
| `Nexus\Payroll` | Scalability Requirement | SCL-PAY-1210 | Support horizontal scaling for calculation workloads |  |  |  |  |
| `Nexus\Payroll` | Scalability Requirement | SCL-PAY-1211 | Support queue-based processing for large payrolls |  |  |  |  |
| `Nexus\Payroll` | Scalability Requirement | SCL-PAY-1212 | Support multi-tenant deployment with tenant isolation |  |  |  |  |
| `Nexus\Payroll` | Scalability Requirement | SCL-PAY-1213 | Support processing multiple payroll groups concurrently |  |  |  |  |
| `Nexus\Payroll` | Scalability Requirement | SCL-PAY-1214 | Optimize database queries with proper indexing on pay_run_id, employee_id, period |  |  |  |  |
| `Nexus\Payroll` | Scalability Requirement | SCL-PAY-1215 | Support caching for frequently accessed configuration data |  |  |  |  |
| `Nexus\Payroll` | Scalability Requirement | SCL-PAY-1216 | Support partitioning of historical payroll data by year |  |  |  |  |
| `Nexus\Payroll` | Scalability Requirement | SCL-PAY-1217 | Support archival of old payroll data to cold storage |  |  |  |  |
| `Nexus\Payroll` | Security and Compliance Requirement | SEC-PAY-0497 | Encrypt all payroll data (salary, tax reliefs) at rest using Laravel encryption |  |  |  |  |
| `Nexus\Payroll` | Security and Compliance Requirement | SEC-PAY-0498 | Implement immutable payslip records with cryptographic hash verification |  |  |  |  |
| `Nexus\Payroll` | Security and Compliance Requirement | SEC-PAY-0499 | Enforce role-based access control for payroll processing operations |  |  |  |  |
| `Nexus\Payroll` | Security and Compliance Requirement | SEC-PAY-0500 | Audit all payroll changes using ActivityLoggerContract |  |  |  |  |
| `Nexus\Payroll` | Security and Compliance Requirement | SEC-PAY-0501 | Support tenant isolation via automatic scoping |  |  |  |  |
| `Nexus\Payroll` | Security and Compliance Requirement | SEC-PAY-0502 | Implement secure payslip access with employee-level authorization |  |  |  |  |
| `Nexus\Payroll` | Security Requirement | SEC-PAY-1189 | Encrypt all payroll data at rest using AES-256 |  |  |  |  |
| `Nexus\Payroll` | Security Requirement | SEC-PAY-1190 | Encrypt payslip PDFs with employee-specific password |  |  |  |  |
| `Nexus\Payroll` | Security Requirement | SEC-PAY-1191 | Store payslip hash for integrity verification (prevent tampering) |  |  |  |  |
| `Nexus\Payroll` | Security Requirement | SEC-PAY-1192 | Implement row-level security (employees can only view own payslips) |  |  |  |  |
| `Nexus\Payroll` | Security Requirement | SEC-PAY-1193 | Enforce role-based access control for payroll operations |  |  |  |  |
| `Nexus\Payroll` | Security Requirement | SEC-PAY-1194 | Require two-factor authentication for payroll locking and posting |  |  |  |  |
| `Nexus\Payroll` | Security Requirement | SEC-PAY-1195 | Mask sensitive data in logs and audit trails |  |  |  |  |
| `Nexus\Payroll` | Security Requirement | SEC-PAY-1196 | Implement secure token-based API authentication |  |  |  |  |
| `Nexus\Payroll` | Security Requirement | SEC-PAY-1197 | Track IP address and user agent for all payroll access |  |  |  |  |
| `Nexus\Payroll` | Security Requirement | SEC-PAY-1198 | Generate security alerts for suspicious payroll activities |  |  |  |  |
| `Nexus\Payroll` | Security Requirement | SEC-PAY-1199 | Support data export encryption for external transfers |  |  |  |  |
| `Nexus\Payroll` | Security Requirement | SEC-PAY-1200 | Comply with data protection regulations (GDPR, PDPA) |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1237 | As a payroll admin, I want to configure payroll groups and calendars without code changes |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1238 | As a payroll admin, I want to create monthly pay runs automatically based on calendar |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1239 | As a payroll admin, I want to import overtime and variable inputs from Excel |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1240 | As a payroll admin, I want to run payroll calculation for all employees in one click |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1241 | As a payroll admin, I want to review calculation results before locking |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1242 | As a payroll admin, I want to lock pay run after validation |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1243 | As a payroll admin, I want to generate and distribute payslips automatically |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1244 | As a payroll admin, I want to generate bank transfer file for salary payment |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1245 | As a payroll admin, I want to post payroll journal entries to accounting |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1246 | As a payroll admin, I want to unlock pay run for corrections when needed |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1247 | As a payroll admin, I want to process retroactive salary changes across multiple months |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1248 | As a payroll admin, I want to process final settlement for resigned employees |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1249 | As a payroll admin, I want to manage employee loans and track repayments |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1250 | As a payroll admin, I want to manage salary advances and recovery schedules |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1251 | As an HR manager, I want to configure recurring payroll components per employee |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1252 | As an HR manager, I want to set up salary structures for different positions |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1253 | As an HR manager, I want to track employer contributions separately from employee deductions |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1254 | As an employee, I want to view my current payslip from self-service portal |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1255 | As an employee, I want to download payslip history for loan applications |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1256 | As an employee, I want to view YTD earnings and deductions |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1257 | As an employee, I want to update my bank account details securely |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1258 | As an employee, I want to receive email notification when payslip is ready |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1259 | As a finance manager, I want to view payroll cost breakdown by department |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1260 | As a finance manager, I want to track statutory liabilities for payment |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1261 | As a finance manager, I want to reconcile payroll expenses with GL accounts |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1262 | As a finance manager, I want cash flow projection for upcoming payroll |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1263 | As a CFO, I want dashboard showing company-wide payroll metrics |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1264 | As a CFO, I want payroll cost trend analysis over time |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1265 | As a CFO, I want average salary and headcount reports |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1266 | As a CFO, I want payroll variance analysis comparing periods |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1267 | As a department manager, I want to view my team's payroll summary (if authorized) |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1268 | As a department manager, I want to track payroll costs against budget |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1269 | As a compliance officer, I want to audit all payroll changes and approvals |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1270 | As a compliance officer, I want to generate statutory submission files |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1271 | As a compliance officer, I want to track statutory payment deadlines |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1272 | As a compliance officer, I want to verify payroll data retention compliance |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1273 | As a developer, I want to implement country-specific StatutoryCalculatorInterface |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1274 | As a developer, I want to receive PayloadInterface with all required employee data |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1275 | As a developer, I want to return DeductionResultInterface array from my calculator |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1276 | As a developer, I want to bind my calculator implementation in application service provider |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1277 | As a developer, I want to test my calculator with mock PayloadInterface |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1278 | As a developer, I want to integrate payroll with HRM for employee data |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1279 | As a developer, I want to integrate payroll with accounting for GL posting |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1280 | As a developer, I want to integrate payroll with workflow for approval processes |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1281 | As a system admin, I want to configure payroll component formulas without code changes |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1282 | As a system admin, I want to configure GL account mapping for payroll components |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1283 | As a system admin, I want to configure payroll approval thresholds and workflows |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1284 | As a system admin, I want to configure bank file format for salary transfers |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1285 | As a system admin, I want to set up multi-country payroll with different calculators per country |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1286 | As an auditor, I want to review payroll calculation audit trails |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1287 | As an auditor, I want to verify payslip integrity using cryptographic hashes |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1288 | As an auditor, I want to export complete payroll history for specific period |  |  |  |  |
| `Nexus\Payroll` | Architechtural Requirement | ARC-PAY-0988 | Package MUST be framework-agnostic with no Laravel dependencies in core services |  |  |  |  |
| `Nexus\Payroll` | Architechtural Requirement | ARC-PAY-0989 | Package MUST be country-agnostic with no hardcoded statutory calculation logic |  |  |  |  |
| `Nexus\Payroll` | Architechtural Requirement | ARC-PAY-0990 | All statutory calculations MUST be performed via StatutoryCalculatorInterface contract |  |  |  |  |
| `Nexus\Payroll` | Architechtural Requirement | ARC-PAY-0991 | All data structures defined via interfaces (PayRunInterface, PaySlipInterface, PayrollComponentInterface) |  |  |  |  |
| `Nexus\Payroll` | Architechtural Requirement | ARC-PAY-0992 | All persistence operations via repository interfaces |  |  |  |  |
| `Nexus\Payroll` | Architechtural Requirement | ARC-PAY-0993 | Business logic in service layer (PayrollManager, GrossPayCalculator, NetPayCalculator) |  |  |  |  |
| `Nexus\Payroll` | Architechtural Requirement | ARC-PAY-0994 | All database migrations in application layer (apps/Atomy) |  |  |  |  |
| `Nexus\Payroll` | Architechtural Requirement | ARC-PAY-0995 | All Eloquent models in application layer |  |  |  |  |
| `Nexus\Payroll` | Architechtural Requirement | ARC-PAY-0996 | Repository implementations in application layer |  |  |  |  |
| `Nexus\Payroll` | Architechtural Requirement | ARC-PAY-0997 | IoC container bindings in application service provider |  |  |  |  |
| `Nexus\Payroll` | Architechtural Requirement | ARC-PAY-0998 | Package composer.json MUST NOT depend on laravel/framework |  |  |  |  |
| `Nexus\Payroll` | Architechtural Requirement | ARC-PAY-0999 | Support integration with Nexus\Hrm for employee data via EmployeeDataContract |  |  |  |  |
| `Nexus\Payroll` | Architechtural Requirement | ARC-PAY-1000 | Support integration with Nexus\Accounting for journal posting via AccountingInterface |  |  |  |  |
| `Nexus\Payroll` | Architechtural Requirement | ARC-PAY-1001 | Support integration with Nexus\AuditLogger for payroll change tracking |  |  |  |  |
| `Nexus\Payroll` | Architechtural Requirement | ARC-PAY-1002 | Provide event-driven architecture for payroll lifecycle events |  |  |  |  |
| `Nexus\Payroll` | Architechtural Requirement | ARC-PAY-1003 | StatutoryCalculatorInterface MUST define contract for all country-specific implementations |  |  |  |  |
| `Nexus\Payroll` | Architechtural Requirement | ARC-PAY-1004 | StatutoryCalculatorInterface MUST accept PayloadInterface and return array of DeductionResultInterface |  |  |  |  |
| `Nexus\Payroll` | Architechtural Requirement | ARC-PAY-1005 | Country-specific packages MUST be separate composer packages (e.g., payroll-mys-statutory, payroll-sgp-statutory) |  |  |  |  |
| `Nexus\Payroll` | Architechtural Requirement | ARC-PAY-1006 | Application layer binds specific StatutoryCalculatorInterface implementation at runtime |  |  |  |  |
| `Nexus\Payroll` | Architechtural Requirement | ARC-PAY-1007 | Support multi-country deployment by binding different calculators per tenant |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-0170 | PCB calculations MUST use exact LHDN rounding rules (to nearest sen) to match tax department calculations |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-0171 | Pay runs MUST be locked after completion to prevent accidental modifications |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-0172 | Only locked pay runs can generate payslips and post to accounting |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-0173 | Retroactive recalculations MUST recalculate all months from change date forward |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-0174 | EPF contributions CANNOT exceed statutory ceiling (RM5,000 salary base as of 2025) |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-0175 | SOCSO eligibility ends at age 60 for new contributors (existing contributors continue) |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-0176 | EIS contributions required for employees earning below RM4,000 per month |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-0177 | Payslip data MUST be immutable once generated (regeneration creates new record) |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-0178 | Additional remuneration MUST use special PCB calculation tables from LHDN |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-1008 | Pay run must be locked before payslips can be generated |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-1009 | Locked pay runs cannot be edited or deleted |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-1010 | Only locked pay runs can post GL journal entries |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-1011 | Pay run dates cannot overlap for same payroll group |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-1012 | Employee must have active employment during pay period to be included |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-1013 | Payroll components require effective date range validation |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-1014 | Recurring components must have start date; end date is optional |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-1015 | Variable components must be linked to specific pay run |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-1016 | Gross pay cannot be negative |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-1017 | Net pay can be negative if deductions exceed gross (e.g., final settlement) |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-1018 | Statutory deductions calculated via injected StatutoryCalculatorInterface only |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-1019 | YTD calculations must include all locked pay runs in fiscal year |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-1020 | Payslip data must be immutable once generated |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-1021 | Payslip regeneration creates new version with audit trail |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-1022 | Pro-rata salary calculation for mid-month joiners/leavers |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-1023 | Unpaid leave deductions calculated based on daily rate |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-1024 | Overtime payment based on hourly rate with configurable multipliers |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-1025 | Additional remuneration (bonus, commission) processed separately from regular pay |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-1026 | Retroactive pay adjustments require new pay run or amendment |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-1027 | Final settlement includes all outstanding payments and deductions |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-1028 | Payroll processing requires appropriate authorization permissions |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-1029 | All payroll state changes must be ACID-compliant transactions |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-1030 | Payroll data retention must comply with statutory requirements (minimum 7 years) |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-1031 | Employee can only access own payslips unless authorized |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-1032 | Payroll reports visible only to authorized HR and finance personnel |  |  |  |  |
| `Nexus\Payroll` | Business Requirements | BUS-PAY-1033 | Payslip delivery method configurable (email, portal, print) |  |  |  |  |
| `Nexus\Payroll` | Compliance Requirement | COMP-PAY-1228 | Support statutory record retention (minimum 7 years) |  |  |  |  |
| `Nexus\Payroll` | Compliance Requirement | COMP-PAY-1229 | Generate audit trail for all payroll modifications |  |  |  |  |
| `Nexus\Payroll` | Compliance Requirement | COMP-PAY-1230 | Support regulatory reporting requirements via country-specific implementations |  |  |  |  |
| `Nexus\Payroll` | Compliance Requirement | COMP-PAY-1231 | Generate submission files for statutory authorities (format defined by country package) |  |  |  |  |
| `Nexus\Payroll` | Compliance Requirement | COMP-PAY-1232 | Track submission history and acknowledgments |  |  |  |  |
| `Nexus\Payroll` | Compliance Requirement | COMP-PAY-1233 | Support data anonymization for analytics while preserving audit trail |  |  |  |  |
| `Nexus\Payroll` | Compliance Requirement | COMP-PAY-1234 | Support right to access (employees can request payroll data) |  |  |  |  |
| `Nexus\Payroll` | Compliance Requirement | COMP-PAY-1235 | Support right to rectification (correct erroneous payroll data) |  |  |  |  |
| `Nexus\Payroll` | Compliance Requirement | COMP-PAY-1236 | Generate compliance reports for internal and external audits |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0299 | Execute monthly payroll runs for all active employees with automatic component calculation |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0300 | Support recurring payroll components (fixed allowances, deductions, employer contributions) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0301 | Process variable payroll items (overtime, claims, bonuses, commissions, unpaid leave deductions) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0302 | Calculate Year-to-Date (YTD) tracking for all earnings, deductions, and statutory contributions |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0303 | Implement pay run locking to prevent duplicate processing and enable rollback on errors |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0304 | Support multi-frequency payroll (monthly, semi-monthly, weekly, bonus-only runs) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-0305 | Post automatic GL journal entries to nexus-accounting for salary expense and liabilities |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1034 | Provide PayrollManager as main orchestration service |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1035 | Support payroll group definition with frequency and calendar settings |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1036 | Support payroll frequencies (monthly, semi-monthly, bi-weekly, weekly, ad-hoc) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1037 | Support payroll calendars with pay period start/end and payment dates |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1038 | Support public holiday calendar integration for working days calculation |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1039 | Assign employees to payroll groups with effective dates |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1040 | Support multiple payroll groups per company (e.g., monthly staff, weekly workers) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1041 | Define payroll component types (earnings, deductions, employer contributions, reimbursements) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1042 | Define payroll component categories (basic salary, allowance, overtime, bonus, statutory, loan, advance) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1043 | Support calculation methods (fixed amount, percentage of basic, percentage of gross, formula-based) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1044 | Support formula-based components with arithmetic expressions |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1045 | Support component dependency (e.g., allowance calculated after basic) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1046 | Support component rounding rules (round up, down, nearest) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1047 | Support taxable/non-taxable flag for components |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1048 | Support statutory contribution flag for components |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1049 | Support employer contribution tracking separate from employee deductions |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1050 | Create recurring payroll components with start/end dates per employee |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1051 | Support recurring component types (monthly allowance, fixed deduction, loan installment) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1052 | Automatic activation/deactivation of recurring components based on dates |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1053 | Track loan balances with automatic deduction and repayment schedule |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1054 | Track advance payments with automatic recovery schedule |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1055 | Support loan early settlement with interest adjustment |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1056 | Create variable payroll inputs for specific pay run (overtime, claims, bonuses) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1057 | Support bulk import of variable inputs from CSV/Excel |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1058 | Validate variable inputs against employee active status |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1059 | Support overtime input with hours and overtime type |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1060 | Support claims input with amount and claim type |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1061 | Support commission input with amount and commission type |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1062 | Support bonus input with amount and bonus type |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1063 | Support unpaid leave deduction based on leave days from Nexus\Hrm |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1064 | Create pay run for specific payroll group and period |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1065 | Automatic pay run creation based on payroll calendar schedule |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1066 | Support pay run types (regular, bonus, final settlement, off-cycle) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1067 | Support pay run status (draft, calculating, calculated, locked, posted, cancelled) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1068 | Validate no overlapping pay runs for same group before creation |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1069 | Calculate gross pay for all employees in pay run |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1070 | GrossPayCalculator aggregates basic salary + recurring components + variable inputs |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1071 | Support pro-rata calculation for partial month employment |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1072 | Calculate working days in period excluding weekends and public holidays |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1073 | Calculate daily rate (monthly salary / working days) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1074 | Calculate hourly rate (monthly salary / standard working hours) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1075 | Apply overtime multipliers (1.5x for weekday OT, 2.0x for weekend, 3.0x for holiday) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1076 | Invoke StatutoryCalculatorInterface with PayloadInterface containing gross pay and employee data |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1077 | Receive array of DeductionResultInterface from statutory calculator |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1078 | Store statutory deductions with type, amount, and employee/employer split |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1079 | PayloadInterface provides employee attributes (age, marital status, tax status, etc.) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1080 | PayloadInterface provides gross earnings breakdown (basic, allowances, OT, bonuses) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1081 | PayloadInterface provides YTD amounts for progressive calculations |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1082 | DeductionResultInterface specifies deduction type identifier |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1083 | DeductionResultInterface specifies employee amount and employer amount |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1084 | DeductionResultInterface specifies calculation metadata for audit |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1085 | Calculate net pay (gross - employee deductions + reimbursements) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1086 | NetPayCalculator aggregates all deductions from multiple sources |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1087 | Apply rounding rules to final net pay amount |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1088 | Calculate total employer cost (gross + employer contributions) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1089 | Calculate YTD totals for gross, deductions, net, and employer cost |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1090 | Update cumulative YTD amounts upon pay run locking |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1091 | Support YTD reset at fiscal year end |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1092 | Store calculation audit trail (formulas, rates, amounts) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1093 | Lock pay run to prevent further changes |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1094 | Unlock pay run with authorization for corrections |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1095 | Validate all calculations complete before locking |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1096 | Store lock timestamp and user for audit |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1097 | Generate payslips for all employees in locked pay run |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1098 | Payslip contains employee details, pay period, earnings breakdown, deductions breakdown, net pay |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1099 | Payslip contains YTD totals for all categories |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1100 | Payslip contains employer contributions for transparency |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1101 | Payslip includes company branding and footer text |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1102 | Generate payslip PDF with configurable template |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1103 | Support payslip template customization per company |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1104 | Store payslip data with cryptographic hash for integrity verification |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1105 | Payslip regeneration creates new version without deleting original |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1106 | Distribute payslips via email with password-protected PDF |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1107 | Distribute payslips via employee self-service portal |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1108 | Track payslip delivery status (sent, opened, downloaded) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1109 | Support payslip re-send on employee request |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1110 | Generate GL journal entries from locked pay run |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1111 | Map payroll components to GL accounts via configuration |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1112 | Support multi-dimensional accounting (department, cost center, project) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1113 | Journal entry includes salary expense, statutory liabilities, and payable |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1114 | Post journal entries to Nexus\Accounting via AccountingInterface |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1115 | Support journal entry reversal for pay run corrections |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1116 | Generate payroll summary report by pay run |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1117 | Generate departmental payroll cost analysis |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1118 | Generate payroll register with all employee details |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1119 | Generate statutory contribution summary by type |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1120 | Generate bank transfer file in configurable format (CSV, NACHA, SEPA, GIRO) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1121 | Validate bank account details before generating payment file |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1122 | Support split payment (multiple bank accounts per employee) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1123 | Track payment status (pending, sent to bank, cleared, failed) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1124 | Generate payroll variance report comparing periods |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1125 | Generate payroll audit report with all changes and approvals |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1126 | Generate cost center allocation report |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1127 | Generate headcount and payroll cost trends |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1128 | Export all reports to Excel, PDF, CSV formats |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1129 | Support retroactive salary changes with automatic recalculation |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1130 | Calculate retroactive difference (arrears or recovery) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1131 | Create adjustment pay run for retroactive payments |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1132 | Recalculate statutory deductions on retroactive gross |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1133 | Update YTD amounts for affected periods |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1134 | Support one-time payments outside regular pay cycle |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1135 | Support off-cycle pay runs for urgent payments |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1136 | Process final settlement for terminated employees |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1137 | Calculate pro-rata salary for partial month |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1138 | Calculate leave encashment from Nexus\Hrm |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1139 | Apply outstanding loan/advance recoveries in full |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1140 | Apply notice period recovery if applicable |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1141 | Generate final settlement statement |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1142 | Support pay run comparison across periods |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1143 | Highlight variances and flag anomalies |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1144 | Support pre-payroll validation checks |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1145 | Validate employee bank details completeness |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1146 | Validate no duplicate employee in pay run |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1147 | Validate component formulas for syntax errors |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1148 | Generate validation report with issues and warnings |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1149 | Support approval workflow for pay run locking via Nexus\Workflow |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1150 | Support multi-level approval based on payroll amount thresholds |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1151 | Track approval history with comments |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1152 | Notify approvers via email/notification system |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1153 | Support payroll simulation without creating actual pay run |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1154 | Simulate what-if scenarios for salary changes |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1155 | Compare simulation results with historical data |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1156 | Support payroll data export for external systems |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1157 | Support payroll data import from external systems |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1158 | Provide RESTful API endpoints for all payroll operations |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1159 | Provide webhook notifications for payroll events |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1160 | Support batch processing for large payrolls (10,000+ employees) |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1161 | Support parallel calculation for performance optimization |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1162 | Employee self-service portal to view payslips |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1163 | Employee self-service to download payslip history |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1164 | Employee self-service to view YTD summaries |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1165 | Employee self-service to update bank account details |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1166 | Employee self-service to view tax documents |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1167 | Manager dashboard showing team payroll summary |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1168 | Manager dashboard showing payroll cost trends for team |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1169 | Payroll admin dashboard showing all active pay runs |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1170 | Payroll admin dashboard showing pending approvals |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1171 | Payroll admin dashboard showing payment status |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1172 | Payroll admin dashboard showing statutory payment deadlines |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1173 | Finance dashboard showing payroll liabilities and expenses |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1174 | Finance dashboard showing cash flow projection for payroll |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1175 | Support payroll year-end processing |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1176 | Generate year-end tax forms via country-specific implementation |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1177 | Generate year-end summary reports for all employees |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1178 | Archive year-end data with compliance retention |  |  |  |  |
| `Nexus\Payroll` | Functional Requirement | FUN-PAY-1179 | Support payroll calendar rollover to new fiscal year |  |  |  |  |
| `Nexus\Payroll` | Maintainability Requirement | MAINT-PAY-1218 | Framework-agnostic core with zero Laravel dependencies in packages/Payroll/src/ |  |  |  |  |
| `Nexus\Payroll` | Maintainability Requirement | MAINT-PAY-1219 | Country-agnostic core with zero hardcoded statutory logic |  |  |  |  |
| `Nexus\Payroll` | Maintainability Requirement | MAINT-PAY-1220 | Clear contract definitions in src/Contracts/ for extensibility |  |  |  |  |
| `Nexus\Payroll` | Maintainability Requirement | MAINT-PAY-1221 | Comprehensive test coverage (>85% code coverage, >95% for calculation logic) |  |  |  |  |
| `Nexus\Payroll` | Maintainability Requirement | MAINT-PAY-1222 | Support plugin architecture for custom payroll components |  |  |  |  |
| `Nexus\Payroll` | Maintainability Requirement | MAINT-PAY-1223 | Support multiple database backends (MySQL, PostgreSQL) |  |  |  |  |
| `Nexus\Payroll` | Maintainability Requirement | MAINT-PAY-1224 | Provide comprehensive API documentation with examples |  |  |  |  |
| `Nexus\Payroll` | Maintainability Requirement | MAINT-PAY-1225 | Provide sample country-specific calculator implementation |  |  |  |  |
| `Nexus\Payroll` | Maintainability Requirement | MAINT-PAY-1226 | Clear separation between business logic and persistence layers |  |  |  |  |
| `Nexus\Payroll` | Maintainability Requirement | MAINT-PAY-1227 | Use value objects for domain concepts (PaySlip, PayrollComponent, DeductionResult) |  |  |  |  |
| `Nexus\Payroll` | Performance Requirement | PERF-PAY-1180 | Pay run calculation for 1,000 employees < 2 minutes |  |  |  |  |
| `Nexus\Payroll` | Performance Requirement | PERF-PAY-1181 | Pay run calculation for 10,000 employees < 15 minutes with parallel processing |  |  |  |  |
| `Nexus\Payroll` | Performance Requirement | PERF-PAY-1182 | Statutory calculator invocation < 50ms per employee |  |  |  |  |
| `Nexus\Payroll` | Performance Requirement | PERF-PAY-1183 | Payslip generation for 1,000 employees < 3 minutes |  |  |  |  |
| `Nexus\Payroll` | Performance Requirement | PERF-PAY-1184 | Payslip PDF generation < 500ms per document |  |  |  |  |
| `Nexus\Payroll` | Performance Requirement | PERF-PAY-1185 | Employee payslip retrieval from portal < 200ms |  |  |  |  |
| `Nexus\Payroll` | Performance Requirement | PERF-PAY-1186 | Payroll report generation < 5 seconds for 1,000 employees |  |  |  |  |
| `Nexus\Payroll` | Performance Requirement | PERF-PAY-1187 | Dashboard metrics calculation < 1 second for company of 10,000 employees |  |  |  |  |
| `Nexus\Payroll` | Performance Requirement | PERF-PAY-1188 | Support concurrent pay run processing for different payroll groups |  |  |  |  |
| `Nexus\Payroll` | Performance Requirement | PER-PAY-0380 | Process monthly payroll for 5,000 employees |  |  |  |  |
| `Nexus\Payroll` | Performance Requirement | PER-PAY-0381 | Generate single payslip PDF |  |  |  |  |
| `Nexus\Payroll` | Performance Requirement | PER-PAY-0382 | Retroactive recalculation (12 months, 1,000 employees) |  |  |  |  |
| `Nexus\Payroll` | Performance Requirement | PER-PAY-0383 | PCB calculation with all reliefs |  |  |  |  |
| `Nexus\Payroll` | Performance Requirement | PER-PAY-0384 | EA Form generation for 5,000 employees |  |  |  |  |
| `Nexus\Payroll` | Reliability Requirement | REL-PAY-1201 | All payroll transactions must be ACID-compliant |  |  |  |  |
| `Nexus\Payroll` | Reliability Requirement | REL-PAY-1202 | Pay run calculation must be idempotent (safe to re-run) |  |  |  |  |
| `Nexus\Payroll` | Reliability Requirement | REL-PAY-1203 | Support pay run calculation rollback on error |  |  |  |  |
| `Nexus\Payroll` | Reliability Requirement | REL-PAY-1204 | Handle StatutoryCalculatorInterface failures gracefully with error messages |  |  |  |  |
| `Nexus\Payroll` | Reliability Requirement | REL-PAY-1205 | Prevent duplicate payslip generation via database constraints |  |  |  |  |
| `Nexus\Payroll` | Reliability Requirement | REL-PAY-1206 | Support automatic retry for transient failures in external integrations |  |  |  |  |
| `Nexus\Payroll` | Reliability Requirement | REL-PAY-1207 | Maintain data consistency across Nexus\Hrm and Nexus\Accounting integrations |  |  |  |  |
| `Nexus\Payroll` | Reliability Requirement | REL-PAY-1208 | Generate automated backups before pay run locking |  |  |  |  |
| `Nexus\Payroll` | Reliability Requirement | REL-PAY-1209 | Support disaster recovery with point-in-time restore |  |  |  |  |
| `Nexus\Payroll` | Scalability Requirement | SCL-PAY-1210 | Support horizontal scaling for calculation workloads |  |  |  |  |
| `Nexus\Payroll` | Scalability Requirement | SCL-PAY-1211 | Support queue-based processing for large payrolls |  |  |  |  |
| `Nexus\Payroll` | Scalability Requirement | SCL-PAY-1212 | Support multi-tenant deployment with tenant isolation |  |  |  |  |
| `Nexus\Payroll` | Scalability Requirement | SCL-PAY-1213 | Support processing multiple payroll groups concurrently |  |  |  |  |
| `Nexus\Payroll` | Scalability Requirement | SCL-PAY-1214 | Optimize database queries with proper indexing on pay_run_id, employee_id, period |  |  |  |  |
| `Nexus\Payroll` | Scalability Requirement | SCL-PAY-1215 | Support caching for frequently accessed configuration data |  |  |  |  |
| `Nexus\Payroll` | Scalability Requirement | SCL-PAY-1216 | Support partitioning of historical payroll data by year |  |  |  |  |
| `Nexus\Payroll` | Scalability Requirement | SCL-PAY-1217 | Support archival of old payroll data to cold storage |  |  |  |  |
| `Nexus\Payroll` | Security and Compliance Requirement | SEC-PAY-0497 | Encrypt all payroll data (salary, tax reliefs) at rest using Laravel encryption |  |  |  |  |
| `Nexus\Payroll` | Security and Compliance Requirement | SEC-PAY-0498 | Implement immutable payslip records with cryptographic hash verification |  |  |  |  |
| `Nexus\Payroll` | Security and Compliance Requirement | SEC-PAY-0499 | Enforce role-based access control for payroll processing operations |  |  |  |  |
| `Nexus\Payroll` | Security and Compliance Requirement | SEC-PAY-0500 | Audit all payroll changes using ActivityLoggerContract |  |  |  |  |
| `Nexus\Payroll` | Security and Compliance Requirement | SEC-PAY-0501 | Support tenant isolation via automatic scoping |  |  |  |  |
| `Nexus\Payroll` | Security and Compliance Requirement | SEC-PAY-0502 | Implement secure payslip access with employee-level authorization |  |  |  |  |
| `Nexus\Payroll` | Security Requirement | SEC-PAY-1189 | Encrypt all payroll data at rest using AES-256 |  |  |  |  |
| `Nexus\Payroll` | Security Requirement | SEC-PAY-1190 | Encrypt payslip PDFs with employee-specific password |  |  |  |  |
| `Nexus\Payroll` | Security Requirement | SEC-PAY-1191 | Store payslip hash for integrity verification (prevent tampering) |  |  |  |  |
| `Nexus\Payroll` | Security Requirement | SEC-PAY-1192 | Implement row-level security (employees can only view own payslips) |  |  |  |  |
| `Nexus\Payroll` | Security Requirement | SEC-PAY-1193 | Enforce role-based access control for payroll operations |  |  |  |  |
| `Nexus\Payroll` | Security Requirement | SEC-PAY-1194 | Require two-factor authentication for payroll locking and posting |  |  |  |  |
| `Nexus\Payroll` | Security Requirement | SEC-PAY-1195 | Mask sensitive data in logs and audit trails |  |  |  |  |
| `Nexus\Payroll` | Security Requirement | SEC-PAY-1196 | Implement secure token-based API authentication |  |  |  |  |
| `Nexus\Payroll` | Security Requirement | SEC-PAY-1197 | Track IP address and user agent for all payroll access |  |  |  |  |
| `Nexus\Payroll` | Security Requirement | SEC-PAY-1198 | Generate security alerts for suspicious payroll activities |  |  |  |  |
| `Nexus\Payroll` | Security Requirement | SEC-PAY-1199 | Support data export encryption for external transfers |  |  |  |  |
| `Nexus\Payroll` | Security Requirement | SEC-PAY-1200 | Comply with data protection regulations (GDPR, PDPA) |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1237 | As a payroll admin, I want to configure payroll groups and calendars without code changes |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1238 | As a payroll admin, I want to create monthly pay runs automatically based on calendar |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1239 | As a payroll admin, I want to import overtime and variable inputs from Excel |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1240 | As a payroll admin, I want to run payroll calculation for all employees in one click |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1241 | As a payroll admin, I want to review calculation results before locking |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1242 | As a payroll admin, I want to lock pay run after validation |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1243 | As a payroll admin, I want to generate and distribute payslips automatically |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1244 | As a payroll admin, I want to generate bank transfer file for salary payment |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1245 | As a payroll admin, I want to post payroll journal entries to accounting |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1246 | As a payroll admin, I want to unlock pay run for corrections when needed |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1247 | As a payroll admin, I want to process retroactive salary changes across multiple months |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1248 | As a payroll admin, I want to process final settlement for resigned employees |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1249 | As a payroll admin, I want to manage employee loans and track repayments |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1250 | As a payroll admin, I want to manage salary advances and recovery schedules |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1251 | As an HR manager, I want to configure recurring payroll components per employee |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1252 | As an HR manager, I want to set up salary structures for different positions |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1253 | As an HR manager, I want to track employer contributions separately from employee deductions |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1254 | As an employee, I want to view my current payslip from self-service portal |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1255 | As an employee, I want to download payslip history for loan applications |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1256 | As an employee, I want to view YTD earnings and deductions |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1257 | As an employee, I want to update my bank account details securely |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1258 | As an employee, I want to receive email notification when payslip is ready |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1259 | As a finance manager, I want to view payroll cost breakdown by department |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1260 | As a finance manager, I want to track statutory liabilities for payment |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1261 | As a finance manager, I want to reconcile payroll expenses with GL accounts |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1262 | As a finance manager, I want cash flow projection for upcoming payroll |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1263 | As a CFO, I want dashboard showing company-wide payroll metrics |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1264 | As a CFO, I want payroll cost trend analysis over time |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1265 | As a CFO, I want average salary and headcount reports |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1266 | As a CFO, I want payroll variance analysis comparing periods |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1267 | As a department manager, I want to view my team's payroll summary (if authorized) |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1268 | As a department manager, I want to track payroll costs against budget |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1269 | As a compliance officer, I want to audit all payroll changes and approvals |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1270 | As a compliance officer, I want to generate statutory submission files |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1271 | As a compliance officer, I want to track statutory payment deadlines |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1272 | As a compliance officer, I want to verify payroll data retention compliance |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1273 | As a developer, I want to implement country-specific StatutoryCalculatorInterface |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1274 | As a developer, I want to receive PayloadInterface with all required employee data |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1275 | As a developer, I want to return DeductionResultInterface array from my calculator |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1276 | As a developer, I want to bind my calculator implementation in application service provider |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1277 | As a developer, I want to test my calculator with mock PayloadInterface |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1278 | As a developer, I want to integrate payroll with HRM for employee data |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1279 | As a developer, I want to integrate payroll with accounting for GL posting |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1280 | As a developer, I want to integrate payroll with workflow for approval processes |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1281 | As a system admin, I want to configure payroll component formulas without code changes |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1282 | As a system admin, I want to configure GL account mapping for payroll components |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1283 | As a system admin, I want to configure payroll approval thresholds and workflows |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1284 | As a system admin, I want to configure bank file format for salary transfers |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1285 | As a system admin, I want to set up multi-country payroll with different calculators per country |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1286 | As an auditor, I want to review payroll calculation audit trails |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1287 | As an auditor, I want to verify payslip integrity using cryptographic hashes |  |  |  |  |
| `Nexus\Payroll` | User Story | USE-PAY-1288 | As an auditor, I want to export complete payroll history for specific period |  |  |  |  |
| `Nexus\Payroll` | Architechtural Requirement | ARC-PAY-8061 | MUST remove all country-specific statutory calculation logic | packages/Payroll/ (PayrollStatutoryInterface injection point) | Deferred | To be implemented when Payroll package is created | 2025-11-18 |
| `Nexus\Payroll` | Architechtural Requirement | ARC-PAY-8062 | MUST only call injected PayrollStatutoryInterface for all deduction calculations | packages/Payroll/ (statutory calculation integration) | Deferred | To be implemented when Payroll package is created | 2025-11-18 |
| `Nexus\Payroll` | Architechtural Requirement | ARC-PAY-8063 | PayloadInterface MUST contain only generic fields (grossSalary, employmentStatus, employeeId) | packages/Payroll/ (PayloadInterface definition) | Deferred | To be implemented when Payroll package is created | 2025-11-18 |
| `Nexus\Payroll` | Architechtural Requirement | ARC-PAY-8064 | MUST NOT contain country-specific field references (e.g., epfNumber, socsoNumber) |  |  |  |  |
