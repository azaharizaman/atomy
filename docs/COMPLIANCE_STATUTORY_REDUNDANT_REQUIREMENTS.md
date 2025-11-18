# Redundant Requirements Analysis - Compliance & Statutory System

## Date: 2025-11-18

## Overview
This document identifies redundant requirements found in `REQUIREMENTS.csv` that overlap with the newly defined Compliance and Statutory requirements in `REQUIREMENTS_PART2.csv`.

---

## Redundant Requirements to Review/Consolidate

### 1. Payroll Statutory Architecture Requirements

**Location:** `REQUIREMENTS.csv` Lines 1239-1258

**Requirements:**
- `ARC-PAY-0989`: Package MUST be country-agnostic with no hardcoded statutory calculation logic
- `ARC-PAY-0990`: All statutory calculations MUST be performed via StatutoryCalculatorInterface contract
- `ARC-PAY-1003`: StatutoryCalculatorInterface MUST define contract for all country-specific implementations
- `ARC-PAY-1004`: StatutoryCalculatorInterface MUST accept PayloadInterface and return array of DeductionResultInterface
- `ARC-PAY-1005`: Country-specific packages MUST be separate composer packages (e.g., payroll-mys-statutory, payroll-sgp-statutory)
- `ARC-PAY-1006`: Application layer binds specific StatutoryCalculatorInterface implementation at runtime
- `ARC-PAY-1007`: Support multi-country deployment by binding different calculators per tenant

**Status:** ✅ **KEEP BUT REFERENCE NEW REQUIREMENTS**

**Recommendation:** These are foundational requirements for `Nexus\Payroll` and should remain. However, they should cross-reference the new architectural requirements in `REQUIREMENTS_PART2.csv`:
- `ARC-PAY-8061`: MUST remove all country-specific statutory calculation logic
- `ARC-PAY-8062`: MUST only call injected PayrollStatutoryInterface for all deduction calculations
- `ARC-PAY-8063`: PayloadInterface MUST contain only generic fields
- `ARC-PAY-8064`: MUST NOT contain country-specific field references

**Action Required:** Add note in implementation that these requirements are superseded by the more detailed refactoring requirements in REQUIREMENTS_PART2.csv (ARC-PAY-8061 to ARC-PAY-8064).

---

### 2. Malaysian Statutory Business Requirements

**Location:** `REQUIREMENTS.csv` Lines 1259-1267

**Requirements:**
- `BUS-PAY-0170`: PCB calculations MUST use exact LHDN rounding rules
- `BUS-PAY-0174`: EPF contributions CANNOT exceed statutory ceiling (RM5,000 salary base)
- `BUS-PAY-0175`: SOCSO eligibility ends at age 60 for new contributors
- `BUS-PAY-0176`: EIS contributions required for employees earning below RM4,000
- `BUS-PAY-0178`: Additional remuneration MUST use special PCB calculation tables

**Status:** ⚠️ **RELOCATE TO STATUTORY.PAYROLL.MYS PACKAGE**

**Recommendation:** These are Malaysia-specific business rules and should NOT be in `Nexus\Payroll`. They belong in `Nexus\Statutory.Payroll.MYS` package requirements.

**Action Required:** 
1. Mark these as deprecated in `REQUIREMENTS.csv`
2. Migrate content to the new requirements:
   - `BUS-SPM-8119` to `BUS-SPM-8128` (already created in REQUIREMENTS_PART2.csv)
3. Remove from `REQUIREMENTS.csv` after implementation verification

---

### 3. Generic Compliance References

**Location:** Multiple locations in `REQUIREMENTS.csv`

**Requirements:**
- `SEC-ACC-0475`: Implement audit logging for all GL postings using ActivityLoggerContract
- `SEC-ACC-0476`: Enforce tenant isolation for all accounting data
- `SEC-FIN-2509`: Support SOX compliance with segregation of duties
- `SEC-FIN-2511`: Support GDPR compliance with data retention

**Status:** ✅ **KEEP - DIFFERENT SCOPE**

**Recommendation:** These are domain-specific security requirements, NOT operational compliance requirements. They define security controls, whereas the new `Nexus\Compliance` requirements define compliance scheme management and enforcement.

**Action Required:** No action needed. These serve different purposes.

---

### 4. HRM Integration with Payroll

**Location:** `REQUIREMENTS.csv` Lines 697, 796

**Requirements:**
- `FUN-HRM-0889`: Employee self-service for viewing payslips (integration with Nexus\Payroll)
- `USE-HRM-0978`: As a developer, I want to integrate HRM with payroll for salary processing

**Status:** ✅ **KEEP - GENERIC INTEGRATION**

**Recommendation:** These are generic integration requirements. The new requirements (`BUS-HRM-8129` to `BUS-HRM-8132`) specifically address compliance-driven mandatory fields, which is a different concern.

**Action Required:** No action needed. Both sets serve different purposes.

---

## Summary of Actions Required

### Immediate Actions (Implementation Phase)

1. **Deprecate Malaysian Statutory Rules in Generic Payroll**
   - Mark `BUS-PAY-0170`, `BUS-PAY-0174`, `BUS-PAY-0175`, `BUS-PAY-0176`, `BUS-PAY-0178` as deprecated
   - Add notes: "MOVED TO Nexus\Statutory.Payroll.MYS (see BUS-SPM-8119 to BUS-SPM-8128)"

2. **Add Cross-References**
   - Update `ARC-PAY-0989` to `ARC-PAY-1007` with note: "See refactoring requirements ARC-PAY-8061 to ARC-PAY-8064 for implementation details"

3. **Verify No Overlap**
   - Confirm that Finance/Accounting compliance requirements remain domain-specific
   - Ensure HRM integration requirements don't conflict with statutory field requirements

### Post-Implementation Actions

1. **Remove Deprecated Requirements**
   - After `Nexus\Statutory.Payroll.MYS` is implemented, remove Malaysian-specific rules from generic `Nexus\Payroll` requirements

2. **Consolidate Documentation**
   - Update `ARCHITECTURE.md` to reflect the final separation of concerns
   - Update package README files to reference the correct requirement codes

---

## New Packages Introduced

The following new packages are defined by the requirements in REQUIREMENTS_PART2.csv:

1. **`Nexus\Compliance`** (Operational Compliance Orchestrator)
   - Requirements: ARC-CMP-8001 to ARC-CMP-8020, BUS-CMP-8101 to BUS-CMP-8110, FUN-CMP-8201 to FUN-CMP-8210, etc.
   - Total: 60+ requirements

2. **`Nexus\Statutory`** (Statutory Reporting Contract Hub)
   - Requirements: ARC-STT-8021 to ARC-STT-8041, BUS-STT-8111 to BUS-STT-8118, FUN-STT-8211 to FUN-STT-8220, etc.
   - Total: 40+ requirements

3. **`Nexus\Statutory.Payroll.MYS`** (Malaysia Payroll Statutory - Open Source)
   - Requirements: ARC-SPM-8042 to ARC-SPM-8047, BUS-SPM-8119 to BUS-SPM-8128, FUN-SPM-8221 to FUN-SPM-8230, etc.
   - Total: 30+ requirements

4. **`Nexus\Statutory.Accounting.SSM`** (Malaysia SSM MBRS - Paid)
   - Requirements: FUN-SAS-8231 to FUN-SAS-8237, INT-SAS-8614 to INT-SAS-8615
   - Total: 10+ requirements

5. **`Nexus\Statutory.Accounting.MYS.Prop`** (Malaysia Proprietorship - Open Source)
   - Requirements: ARC-SAP-8048 to ARC-SAP-8051, FUN-SAP-8238 to FUN-SAP-8240
   - Total: 6+ requirements

---

## Requirements Count Summary

### REQUIREMENTS_PART2.csv Additions
- **Architectural Requirements (ARC):** 71
- **Business Requirements (BUS):** 32
- **Functional Requirements (FUN):** 48
- **Performance Requirements (PER):** 8
- **Reliability Requirements (REL):** 8
- **Security & Compliance Requirements (SEC):** 14
- **Integration Requirements (INT):** 15
- **User Stories (USE):** 18

**Total New Requirements:** 214

### Requirements to Deprecate from REQUIREMENTS.csv
- **Malaysian Statutory Rules:** 5 requirements (BUS-PAY-0170, 0174, 0175, 0176, 0178)

---

## Notes for Implementation Team

1. The new architecture enforces **strict separation of concerns**:
   - `Nexus\Compliance` = Process enforcement (HOW the system must operate)
   - `Nexus\Statutory` = Data formatting (WHAT reports to generate)

2. **Feature Gating** is critical:
   - Default implementations MUST be provided in `Nexus\Statutory`
   - Paid implementations are separate packages bound conditionally in `Nexus\Atomy`

3. **Refactoring Priority**:
   - Phase 1: Create `Nexus\Compliance` and `Nexus\Statutory` packages
   - Phase 2: Refactor `Nexus\Payroll`, `Nexus\Accounting`, `Nexus\Finance` to remove country-specific logic
   - Phase 3: Implement country-specific packages (`Nexus\Statutory.Payroll.MYS`, etc.)
   - Phase 4: Update `Nexus\Atomy` with conditional bindings and feature flags

4. **Testing Strategy**:
   - Test default implementations work correctly (zero deductions, basic reports)
   - Test conditional binding logic in `Nexus\Atomy`
   - Test multi-tenant scenarios with different countries
   - Test feature activation/deactivation workflows

---

*This document was generated as part of the Compliance & Statutory Requirements Definition task on 2025-11-18.*
