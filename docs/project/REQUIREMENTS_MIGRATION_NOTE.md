# Requirements Documentation Migration Note

**Date:** November 21, 2025

## Overview

The requirements documentation has been migrated from CSV format to Markdown format for better readability and maintainability.

## What Changed

### Before
- `REQUIREMENTS.csv` - Single large CSV file with ~4,260 lines covering 27 packages
- `REQUIREMENTS_PART2.csv` - Single large CSV file with ~1,020 lines covering 15 packages
- Both files were tab-delimited (TSV format)
- Total: 5,280 lines (including headers)

### After
- **35 individual Markdown files** in the `docs/` folder
- One file per package namespace (e.g., `REQUIREMENTS_FINANCE.md`, `REQUIREMENTS_PAYROLL.md`)
- Each file contains a pipe-delimited Markdown table
- Total: 5,275 requirements (header rows and invalid entries excluded)
- Files use naming convention: `REQUIREMENTS_{PACKAGE}.md`

## How to Find Requirements

### Old Way
Search through large CSV files or use line numbers (e.g., "REQUIREMENTS.csv lines 788-988")

### New Way
Navigate directly to the package-specific file:

- Finance requirements → `docs/REQUIREMENTS_FINANCE.md`
- Payroll requirements → `docs/REQUIREMENTS_PAYROLL.md`
- Tenant requirements → `docs/REQUIREMENTS_TENANT.md`
- etc.

## For Developers

If you encounter references to the old CSV files in existing documentation:

1. **Historical documents** (implementation summaries, status reports) - References are kept for historical accuracy
2. **New documentation** - Use the new package-specific markdown files
3. **Package READMEs** - Update references to point to the new markdown files when editing

## File Listing

All requirements files are located in `docs/` with the following counts:

| Package | File | Requirements |
|---------|------|--------------|
| Accounting | REQUIREMENTS_ACCOUNTING.md | 139 |
| Analytics | REQUIREMENTS_ANALYTICS.md | 78 |
| consuming application | REQUIREMENTS_ATOMY.md | 18 |
| AuditLogger | REQUIREMENTS_AUDITLOGGER.md | 229 |
| Backoffice | REQUIREMENTS_BACKOFFICE.md | 474 |
| Compliance | REQUIREMENTS_COMPLIANCE.md | 62 |
| Connector | REQUIREMENTS_CONNECTOR.md | 110 |
| CRM | REQUIREMENTS_CRM.md | 156 |
| DataProcessor | REQUIREMENTS_DATAPROCESSOR.md | 76 |
| Document | REQUIREMENTS_DOCUMENT.md | 68 |
| EventStream | REQUIREMENTS_EVENTSTREAM.md | 102 |
| FieldService | REQUIREMENTS_FIELDSERVICE.md | 100 |
| Finance | REQUIREMENTS_FINANCE.md | 194 |
| HRM | REQUIREMENTS_HRM.md | 622 |
| Identity | REQUIREMENTS_IDENTITY.md | 401 |
| Manufacturing | REQUIREMENTS_MANUFACTURING.md | 86 |
| Marketing | REQUIREMENTS_MARKETING.md | 126 |
| Notifier | REQUIREMENTS_NOTIFIER.md | 77 |
| OrgStructure | REQUIREMENTS_ORGSTRUCTURE.md | 250 |
| Payable | REQUIREMENTS_PAYABLE.md | 128 |
| Payroll | REQUIREMENTS_PAYROLL.md | 660 |
| Period | REQUIREMENTS_PERIOD.md | 145 |
| Procurement | REQUIREMENTS_PROCUREMENT.md | 88 |
| ProjectManagement | REQUIREMENTS_PROJECTMANAGEMENT.md | 114 |
| Receivable | REQUIREMENTS_RECEIVABLE.md | 128 |
| Sequencing | REQUIREMENTS_SEQUENCING.md | 67 |
| Setting | REQUIREMENTS_SETTING.md | 101 |
| Statutory | REQUIREMENTS_STATUTORY.md | 61 |
| Statutory.Accounting.MYS.Prop | REQUIREMENTS_STATUTORY_ACCOUNTING_MYS_PROP.md | 7 |
| Statutory.Accounting.SSM | REQUIREMENTS_STATUTORY_ACCOUNTING_SSM.md | 9 |
| Statutory.Payroll.MYS | REQUIREMENTS_STATUTORY_PAYROLL_MYS.md | 40 |
| Storage | REQUIREMENTS_STORAGE.md | 30 |
| Tenant | REQUIREMENTS_TENANT.md | 90 |
| UOM | REQUIREMENTS_UOM.md | 59 |
| Workflow | REQUIREMENTS_WORKFLOW.md | 180 |

**Total: 5,275 requirements across 35 packages**

## Data Integrity

All data from the original CSV files has been preserved:
- Package Namespace
- Requirements Type
- Code
- Requirement Statements
- Files/Folders/Class/Methods
- Status
- Notes on Status
- Date Last Updated

The only differences are:
1. Improved formatting (Markdown tables vs CSV)
2. Better organization (split by package)
3. Removal of duplicate headers and invalid rows
