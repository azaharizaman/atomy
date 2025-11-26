# GitHub Copilot Instructions for Nexus Package Monorepo

## 🎯 Critical: Read and Understand These Documents FIRST

Before implementing ANY feature or writing ANY code, you MUST fully read and understand these foundational documents:

1. **[`CODING_GUIDELINES.md`](../CODING_GUIDELINES.md)** - **MANDATORY COMPREHENSIVE READ**
   - All coding standards, patterns, and best practices
   - Repository interface design principles
   - PHP 8.3+ language standards
   - Architectural violation detection rules
   - Testing and documentation requirements
   - Complete anti-patterns reference

2. **[`ARCHITECTURE.md`](../ARCHITECTURE.md)** - **MANDATORY READ**
   - Package monorepo structure and philosophy
   - Framework agnosticism principles
   - Package design patterns
   - Stateless architecture requirements

3. **[`docs/NEXUS_PACKAGES_REFERENCE.md`](../docs/NEXUS_PACKAGES_REFERENCE.md)** - **MANDATORY READ**
   - Complete list of all 50+ available packages
   - Package capabilities and interfaces
   - "I Need To..." decision matrix
   - Prevents reimplementing existing functionality

**⚠️ WARNING:** These documents are not optional references. Every line must be understood and followed. Failure to consult these documents before implementation will result in architectural violations.

---

## 🚨 MANDATORY PRE-IMPLEMENTATION CHECKLIST

**BEFORE implementing ANY feature, you MUST:**

1. **Consult `docs/NEXUS_PACKAGES_REFERENCE.md`** - Check if a Nexus package already provides the functionality
2. **Review `CODING_GUIDELINES.md`** - Ensure your approach follows all coding standards
3. **Review `ARCHITECTURE.md`** - Verify architectural compliance
4. **Use existing packages FIRST** - If a Nexus package provides the functionality, you MUST use it via dependency injection
5. **Never reimplement package functionality** - Creating custom implementations when packages exist is an architectural violation

**Example Violations to Avoid:**
- ❌ Creating custom metrics collector when `Nexus\Monitoring` exists
- ❌ Building custom audit logger when `Nexus\AuditLogger` exists  
- ❌ Implementing file storage when `Nexus\Storage` exists
- ❌ Creating notification system when `Nexus\Notifier` exists

---

## Project Overview

You are working on **Nexus**, a **package-only monorepo** containing 50+ framework-agnostic PHP packages for ERP systems. This project is strictly focused on **atomic, reusable packages** that can be integrated into any PHP framework (Laravel, Symfony, Slim, etc.).

## Core Philosophy

**Framework Agnosticism is Mandatory.** The monorepo contains:

- **📦 `packages/`**: Pure, framework-agnostic business logic packages (the core focus)
- **📄 `docs/`**: Comprehensive implementation guides and API documentation
- **🧪 `tests/`**: Package-level unit and integration tests

**NO application layer. NO Laravel-specific code. Pure PHP packages only.**

## Directory Structure

```
nexus/
├── packages/               # 50+ Atomic, publishable PHP packages
│   ├── Accounting/         # Financial accounting
│   ├── Analytics/          # Business intelligence
│   ├── Assets/             # Fixed asset management
│   ├── AuditLogger/        # Audit logging (timeline/feed views)
│   ├── Backoffice/         # Company structure
│   ├── Budget/             # Budget planning
│   ├── CashManagement/     # Bank reconciliation
│   ├── Compliance/         # Compliance engine
│   ├── Connector/          # Integration hub
│   ├── Crm/                # Customer relationship management
│   ├── Crypto/             # Cryptographic operations
│   ├── Currency/           # Multi-currency management
│   ├── DataProcessor/      # Data processing (OCR, ETL)
│   ├── Document/           # Document management
│   ├── EventStream/        # Event sourcing engine
│   ├── Export/             # Multi-format export
│   ├── FeatureFlags/       # Feature flag management
│   ├── FieldService/       # Field service management
│   ├── Finance/            # General ledger
│   ├── Geo/                # Geocoding and geofencing
│   ├── Hrm/                # Human resources
│   ├── Identity/           # Authentication & authorization
│   ├── Import/             # Data import
│   ├── Intelligence/       # AI-assisted automation
│   ├── Inventory/          # Inventory management
│   ├── Manufacturing/      # MRP II: BOM, Routing, Work Orders, Capacity Planning
│   ├── Marketing/          # Marketing campaigns
│   ├── Monitoring/         # Observability & telemetry
│   ├── Notifier/           # Multi-channel notifications
│   ├── OrgStructure/       # Organizational hierarchy
│   ├── Party/              # Customer/vendor management
│   ├── Payable/            # Accounts payable
│   ├── Payroll/            # Payroll processing
│   ├── PayrollMysStatutory/ # Malaysian payroll statutory
│   ├── Period/             # Fiscal period management
│   ├── Procurement/        # Purchase management
│   ├── Product/            # Product catalog
│   ├── ProjectManagement/  # Project tracking
│   ├── Receivable/         # Accounts receivable
│   ├── Reporting/          # Report engine
│   ├── Routing/            # Route optimization
│   ├── Sales/              # Sales order management
│   ├── Scheduler/          # Task scheduling
│   ├── Sequencing/         # Auto-numbering
│   ├── Setting/            # Settings management
│   ├── Statutory/          # Statutory reporting
│   ├── Storage/            # File storage abstraction
│   ├── Tenant/             # Multi-tenancy
│   ├── Uom/                # Unit of measurement
│   ├── Warehouse/          # Warehouse management
│   └── Workflow/           # Workflow engine
├── docs/                   # Implementation guides & references
└── composer.json           # Monorepo package registry
```

---

## Essential References for Specific Tasks

### Creating a New Package
**See:** [`.github/prompts/create-package-instruction.prompt.md`](prompts/create-package-instruction.prompt.md)

### Analyzing Package Architectural Violations
**See:** [`.github/prompts/analyze-package-architectural-violations.prompt.md`](prompts/analyze-package-architectural-violations.prompt.md)

### Applying Documentation Standards
**See:** [`.github/prompts/apply-documentation-standards.prompt.md`](prompts/apply-documentation-standards.prompt.md)

### Planning Package Completion
**See:** [`.github/prompts/plan-package-completion.prompt.md`](prompts/plan-package-completion.prompt.md)

---

## Key Reminders (Summary)

All detailed guidelines are in `CODING_GUIDELINES.md`. Here's a quick summary:

1. **Packages are pure engines**: Pure logic, no persistence, no framework coupling
2. **Interfaces define needs**: Every external dependency is an interface
3. **Consumers provide implementations**: Applications bind concrete classes to interfaces
4. **Always check NEXUS_PACKAGES_REFERENCE.md** before creating new functionality
5. **When in doubt, inject an interface**
6. **PHP 8.3+ required**: All packages must require `"php": "^8.3"`
7. **All dependencies must be interfaces**, never concrete classes
8. **All properties must be `readonly`**
9. **Use `declare(strict_types=1);`** at top of every file
10. **No framework facades or global helpers** in `packages/`

---

## Important Documentation

- **Coding Guidelines:** [`CODING_GUIDELINES.md`](../CODING_GUIDELINES.md) - **MANDATORY COMPREHENSIVE READ**
- **Architecture Guidelines:** [`ARCHITECTURE.md`](../ARCHITECTURE.md) - **MANDATORY READ**
- **Package Reference:** [`docs/NEXUS_PACKAGES_REFERENCE.md`](../docs/NEXUS_PACKAGES_REFERENCE.md) - **MANDATORY READ**
- **Package Requirements:** `docs/REQUIREMENTS_*.md`
- **Implementation Summaries:** `docs/*_IMPLEMENTATION_SUMMARY.md`

---

**Last Updated:** November 26, 2025  
**Maintained By:** Nexus Architecture Team  
**Enforcement:** Mandatory for all coding agents and developers
