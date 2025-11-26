# Package Creation Instructions for Nexus Monorepo

**Purpose:** Comprehensive guide for creating new packages in the Nexus monorepo with proper documentation standards.

**When to Use:** Creating a new package from scratch in `packages/` directory.

**Critical References:**
- **[`CODING_GUIDELINES.md`](../../CODING_GUIDELINES.md)** - All code standards, patterns, and anti-patterns
- **[`ARCHITECTURE.md`](../../ARCHITECTURE.md)** - Package structure and design principles
- **[`docs/NEXUS_PACKAGES_REFERENCE.md`](../../docs/NEXUS_PACKAGES_REFERENCE.md)** - Existing package capabilities

---

## 📁 Mandatory Package Structure

Every Nexus package **MUST** include the following structure and files:

```
packages/NewPackage/
├── composer.json              # REQUIRED: Package definition (require "php": "^8.3")
├── LICENSE                    # REQUIRED: MIT License
├── .gitignore                 # REQUIRED: Package-specific ignores
├── README.md                  # REQUIRED: Comprehensive usage guide
├── IMPLEMENTATION_SUMMARY.md  # REQUIRED: Implementation tracking
├── REQUIREMENTS.md            # REQUIRED: Detailed requirements
├── TEST_SUITE_SUMMARY.md      # REQUIRED: Test documentation
├── VALUATION_MATRIX.md        # REQUIRED: Package valuation metrics
├── docs/                      # REQUIRED: User documentation
│   ├── getting-started.md     # Quick start guide
│   ├── api-reference.md       # API documentation
│   ├── integration-guide.md   # Application layer integration examples
│   └── examples/              # Code examples
│       ├── basic-usage.php
│       └── advanced-usage.php
├── src/
│   ├── Contracts/             # REQUIRED: Interfaces
│   │   ├── EntityInterface.php
│   │   ├── RepositoryInterface.php
│   │   └── ManagerInterface.php
│   ├── Exceptions/            # REQUIRED: Domain exceptions
│   │   └── EntityNotFoundException.php
│   ├── Services/              # REQUIRED: Business logic
│   │   └── EntityManager.php
│   ├── Enums/                 # RECOMMENDED: Native PHP enums
│   │   └── EntityStatus.php
│   ├── ValueObjects/          # RECOMMENDED: Immutable domain data
│   │   └── Money.php
│   ├── Core/                  # OPTIONAL: Internal engine
│   │   ├── Engine/
│   │   ├── ValueObjects/
│   │   └── Entities/
│   └── ServiceProvider.php    # OPTIONAL: Framework integration helper
└── tests/                     # REQUIRED: Unit tests
    ├── Unit/
    └── Feature/
```

**For code structure, standards, and patterns, see [`CODING_GUIDELINES.md`](../../CODING_GUIDELINES.md).**

---

## 📄 Required Documentation Files

### 1. **README.md** (Mandatory)

**Purpose:** Primary package documentation for developers integrating the package.

**Required Sections:**
```markdown
# Nexus PackageName

## Overview
Brief description of the package's purpose and capabilities.

## Installation
```bash
composer require nexus/package-name:"*@dev"
```

## Features
- Feature 1
- Feature 2
- Feature 3

## Quick Start
Basic usage example with minimal code.

## Core Concepts
Explanation of key concepts and architecture.

## Usage Examples

### Basic Usage
```php
// Simple example
```

### Advanced Usage
```php
// Complex example
```

### Application Layer Integration
```php
// Laravel/Symfony integration example
```

## Available Interfaces
- `EntityManagerInterface` - Description
- `EntityRepositoryInterface` - Description

## Configuration
How to configure the package (if applicable).

## Testing
How to run package tests.

## License
MIT License
```

**Anti-Pattern:** ❌ Do NOT create duplicate README files in subdirectories or in the `docs/` folder with similar content.

---

### 2. **IMPLEMENTATION_SUMMARY.md** (Mandatory)

**Purpose:** Track implementation progress, decisions, and metrics for project valuation (for funding).

**Required Sections:**
```markdown
# Implementation Summary: PackageName

**Package:** `Nexus\PackageName`  
**Status:** In Development | Feature Complete | Production Ready  (% of completion)
**Last Updated:** YYYY-MM-DD  
**Version:** 1.0.0

## Executive Summary
Brief overview of what was accomplished and current state.

## Implementation Plan

### Phase 1: Core Implementation (Completed)
- [x] Task 1
- [x] Task 2
- [ ] Task 3 (In Progress)

### Phase 2: Advanced Features (Planned)
- [ ] Feature 1
- [ ] Feature 2

## What Was Completed
Detailed list of implemented features with file references.

## What Is Planned for Future
Features planned but not yet implemented.

## What Was NOT Implemented (and Why)
List of planned features that were deprioritized or cancelled with justification.

## Key Design Decisions
- **Decision 1:** Rationale
- **Decision 2:** Rationale

## Metrics

### Code Metrics
- Total Lines of Code: X,XXX
- Total Lines of actual code (excluding comments/whitespace): X,XXX
- Total Lines of Documentation: XXX
- Cyclomatic Complexity: XX
- Number of Classes: XX
- Number of Interfaces: XX
- Number of Service Classes: XX
- Number of Value Objects: XX
- Number of Enums: XX

### Test Coverage
- Unit Test Coverage: XX%
- Integration Test Coverage: XX%
- Total Tests: XXX

### Dependencies
- External Dependencies: X
- Internal Package Dependencies: X

## Known Limitations
Current limitations and constraints.

## Integration Examples
Links to example implementations in consuming applications.

## References
- Requirements: `REQUIREMENTS.md`
- Tests: `TEST_SUITE_SUMMARY.md`
- API Docs: `docs/api-reference.md`
```

---

### 3. **REQUIREMENTS.md** (Mandatory)

**Purpose:** Comprehensive, traceable requirements for the package.

**Format:** Standardized table format (see example below)

**Required Structure:**
```markdown
# Requirements: PackageName

**Total Requirements:** XX

| Package Namespace | Requirements Type | Code | Requirement Statements | Files/Folders | Status | Notes on Status | Date Last Updated |
|-------------------|-------------------|------|------------------------|---------------|--------|-----------------|-------------------|
| `Nexus\PackageName` | Architectural Requirement | ARC-PKG-0001 | Package MUST be framework-agnostic | composer.json | ✅ Complete | No framework deps | YYYY-MM-DD |
| `Nexus\PackageName` | Business Requirements | BUS-PKG-0002 | System MUST validate input X | src/Services/Manager.php | ✅ Complete | - | YYYY-MM-DD |
| `Nexus\PackageName` | Functional Requirement | FUN-PKG-0003 | Provide method to do X | src/Contracts/Interface.php | ✅ Complete | - | YYYY-MM-DD |
| `Nexus\PackageName` | User Story | USE-PKG-0004 | As a developer, I want to... | src/Services/Manager.php | ⏳ Pending | Planned for v2 | YYYY-MM-DD |

```

**Requirement Types:**
- **Architectural Requirement (ARC):** Framework agnosticism, interface design, dependency patterns
- **Business Requirements (BUS):** Business rules, validation, domain logic
- **Functional Requirement (FUN):** API methods, capabilities, operations
- **User Story (USE):** User-facing requirements

**Status Indicators:**
- ✅ **Complete:** Fully implemented and tested
- ⏳ **Pending:** Planned but not yet implemented
- 🚧 **In Progress:** Currently being developed
- ❌ **Blocked:** Cannot proceed due to dependency
- 🔄 **Refactoring:** Implemented but needs improvement

**Reference Example:** See `docs/REQUIREMENTS_TENANT.md` for a complete example.

---

### 4. **TEST_SUITE_SUMMARY.md** (Mandatory)

**Purpose:** Document test coverage, results, and testing strategy.

**Required Sections:**
```markdown
# Test Suite Summary: PackageName

**Package:** `Nexus\PackageName`  
**Last Test Run:** YYYY-MM-DD HH:MM:SS  
**Status:** ✅ All Passing | ⚠️ Some Failures | ❌ Critical Failures

## Test Coverage Metrics

### Overall Coverage
- **Line Coverage:** XX.XX%
- **Function Coverage:** XX.XX%
- **Class Coverage:** XX.XX%
- **Complexity Coverage:** XX.XX%

### Detailed Coverage by Component
| Component | Lines Covered | Functions Covered | Coverage % |
|-----------|---------------|-------------------|------------|
| EntityManager | XXX/XXX | XX/XX | XX.XX% |
| Repository | XXX/XXX | XX/XX | XX.XX% |
| ValueObjects | XXX/XXX | XX/XX | XX.XX% |

## Test Inventory

### Unit Tests (XX tests)
- `EntityManagerTest.php` - XX tests
- `RepositoryTest.php` - XX tests
- `ValueObjectTest.php` - XX tests

### Integration Tests (XX tests)
- `EndToEndFlowTest.php` - XX tests

### Feature Tests (XX tests)
- `FeatureXTest.php` - XX tests

## Test Results Summary

### Latest Test Run
```bash
PHPUnit 11.x.x

Time: XX.XXs, Memory: XX.XXMb

OK (XXX tests, XXX assertions)
```

### Test Execution Time
- Fastest Test: X.XXms
- Slowest Test: X.XXms
- Average Test: X.XXms

## Testing Strategy

### What Is Tested
- All public methods in service classes
- All business logic paths
- Exception handling
- Input validation
- Contract implementations

### What Is NOT Tested (and Why)
- Framework-specific implementations (tested in consuming application)
- Database integration (mocked in unit tests)
- External API calls (mocked)

## Known Test Gaps
List of untested scenarios with justification.

## How to Run Tests
```bash
composer test
composer test:coverage
```

## CI/CD Integration
Description of automated testing setup.
```

---

### 5. **VALUATION_MATRIX.md** (Mandatory)

**Purpose:** Document package value metrics for project valuation during internal or seed funding rounds.

**Required Sections:**
```markdown
# Valuation Matrix: PackageName

**Package:** `Nexus\PackageName`  
**Category:** [Core Infrastructure | Business Logic | Integration | Compliance | Analytics | UI/UX]
**Valuation Date:** YYYY-MM-DD  
**Status:** [Development | Beta | Production Ready | Mature]

## Executive Summary

**Package Purpose:** [One-line description of what this package does]

**Business Value:** [Why this package is valuable to the overall system]

**Market Comparison:** [Comparable commercial products/services, if any]

---

## Development Investment

### Time Investment
| Phase | Hours | Cost (@ $XX/hr) | Notes |
|-------|-------|-----------------|-------|
| Requirements Analysis | XX | $X,XXX | - |
| Architecture & Design | XX | $X,XXX | - |
| Implementation | XXX | $XX,XXX | - |
| Testing & QA | XX | $X,XXX | - |
| Documentation | XX | $X,XXX | - |
| Code Review & Refinement | XX | $X,XXX | - |
| **TOTAL** | **XXX** | **$XX,XXX** | - |

### Complexity Metrics
- **Lines of Code (LOC):** X,XXX lines
- **Cyclomatic Complexity:** XX (average per method)
- **Number of Interfaces:** XX
- **Number of Service Classes:** XX
- **Number of Value Objects:** XX
- **Number of Enums:** XX
- **Test Coverage:** XX%
- **Number of Tests:** XXX

---

## Technical Value Assessment

### Innovation Score (1-10)
| Criteria | Score | Justification |
|----------|-------|---------------|
| **Architectural Innovation** | X/10 | [Novel patterns, unique approach] |
| **Technical Complexity** | X/10 | [Difficulty to replicate] |
| **Code Quality** | X/10 | [PSR compliance, best practices] |
| **Reusability** | X/10 | [Framework-agnostic, portable] |
| **Performance Optimization** | X/10 | [Efficiency, scalability] |
| **Security Implementation** | X/10 | [Security measures, hardening] |
| **Test Coverage Quality** | X/10 | [Comprehensive, edge cases] |
| **Documentation Quality** | X/10 | [Completeness, clarity] |
| **AVERAGE INNOVATION SCORE** | **X.X/10** | - |

### Technical Debt
- **Known Issues:** [List any known bugs or limitations]
- **Refactoring Needed:** [Areas that need improvement]
- **Debt Percentage:** X% [Estimated technical debt as % of total codebase]

---

## Business Value Assessment

### Market Value Indicators
| Indicator | Value | Notes |
|-----------|-------|-------|
| **Comparable SaaS Product** | $XX/month | [Name of comparable service] |
| **Comparable Open Source** | [Yes/No] | [Name, if exists] |
| **Build vs Buy Cost Savings** | $XX,XXX | [Cost to license equivalent] |
| **Time-to-Market Advantage** | XX months | [Time saved vs building from scratch] |

### Strategic Value (1-10)
| Criteria | Score | Justification |
|----------|-------|---------------|
| **Core Business Necessity** | X/10 | [How essential to business operations] |
| **Competitive Advantage** | X/10 | [Unique capabilities vs competitors] |
| **Revenue Enablement** | X/10 | [Direct/indirect revenue impact] |
| **Cost Reduction** | X/10 | [Operational cost savings] |
| **Compliance Value** | X/10 | [Regulatory requirements met] |
| **Scalability Impact** | X/10 | [Supports business growth] |
| **Integration Criticality** | X/10 | [How many other packages depend on this] |
| **AVERAGE STRATEGIC SCORE** | **X.X/10** | - |

### Revenue Impact
- **Direct Revenue Generation:** $XXX,XXX/year [if applicable]
- **Cost Avoidance:** $XX,XXX/year [licensing, development costs avoided]
- **Efficiency Gains:** XX hours/month saved

---

## Intellectual Property Value

### IP Classification
- **Patent Potential:** [High | Medium | Low | None]
- **Trade Secret Status:** [Proprietary algorithms, business logic]
- **Copyright:** [Original code, documentation]
- **Licensing Model:** [MIT, Proprietary, Dual-License]

### Proprietary Value
- **Unique Algorithms:** [List any unique/novel algorithms]
- **Domain Expertise Required:** [Specialized knowledge needed]
- **Barrier to Entry:** [How difficult for competitors to replicate]

---

## Dependencies & Risk Assessment

### External Dependencies
| Dependency | Type | Risk Level | Mitigation |
|------------|------|------------|------------|
| PHP 8.3+ | Language | Low | Standard requirement |
| [Package Name] | Library | [High/Med/Low] | [Alternative, abstraction] |

### Internal Package Dependencies
- **Depends On:** [List Nexus packages this depends on]
- **Depended By:** [List Nexus packages that depend on this]
- **Coupling Risk:** [High/Medium/Low]

### Maintenance Risk
- **Bus Factor:** X developers [Number of developers who understand the package]
- **Update Frequency:** [Active | Stable | Legacy]
- **Breaking Change Risk:** [High/Medium/Low]

---

## Market Positioning

### Comparable Products/Services
| Product/Service | Price | Our Advantage |
|-----------------|-------|---------------|
| [Commercial Product 1] | $XX/month | [Feature/cost/flexibility advantage] |
| [Open Source Alternative] | Free | [Better features/support/integration] |
| [SaaS Service] | $XXX/month | [Control/customization/cost savings] |

### Competitive Advantages
1. **[Advantage 1]:** [Description]
2. **[Advantage 2]:** [Description]
3. **[Advantage 3]:** [Description]

---

## Valuation Calculation

### Cost-Based Valuation
```
Development Cost:        $XX,XXX
Documentation Cost:      $X,XXX
Testing & QA Cost:       $X,XXX
Multiplier (IP Value):   X.Xx    [Based on innovation & complexity]
----------------------------------------
Cost-Based Value:        $XXX,XXX
```

### Market-Based Valuation
```
Comparable Product Cost: $XX,XXX/year
Lifetime Value (5 years): $XXX,XXX
Customization Premium:   $XX,XXX  [vs off-the-shelf]
----------------------------------------
Market-Based Value:      $XXX,XXX
```

### Income-Based Valuation
```
Annual Cost Savings:     $XX,XXX
Annual Revenue Enabled:  $XX,XXX
Discount Rate:           XX%
Projected Period:        5 years
----------------------------------------
NPV (Income-Based):      $XXX,XXX
```

### **Final Package Valuation**
```
Weighted Average:
- Cost-Based (30%):      $XXX,XXX
- Market-Based (40%):    $XXX,XXX
- Income-Based (30%):    $XXX,XXX
========================================
ESTIMATED PACKAGE VALUE: $XXX,XXX
========================================
```

---

## Future Value Potential

### Planned Enhancements
- **Enhancement 1:** [Expected value add: $X,XXX]
- **Enhancement 2:** [Expected value add: $X,XXX]
- **Enhancement 3:** [Expected value add: $X,XXX]

### Market Growth Potential
- **Addressable Market Size:** $XXX million
- **Our Market Share Potential:** X%
- **5-Year Projected Value:** $XXX,XXX

---

## Valuation Summary

**Current Package Value:** $XXX,XXX  
**Development ROI:** XXX%  
**Strategic Importance:** [Critical | High | Medium | Low]  
**Investment Recommendation:** [Expand | Maintain | Monitor | Sunset]

### Key Value Drivers
1. **[Primary Driver]:** [Description]
2. **[Secondary Driver]:** [Description]
3. **[Tertiary Driver]:** [Description]

### Risks to Valuation
1. **[Risk 1]:** [Impact and mitigation]
2. **[Risk 2]:** [Impact and mitigation]

---

**Valuation Prepared By:** [Name/Team]  
**Review Date:** YYYY-MM-DD  
**Next Review:** YYYY-MM-DD (Quarterly/Annually)
```

**Notes:**
- Update this document quarterly or when major changes occur
- Use consistent hourly rates across all packages for comparability
- Base valuations on conservative estimates
- Include both quantitative metrics and qualitative assessments
- Document assumptions clearly

---

### 6. **docs/ Folder** (Mandatory)

**Purpose:** Detailed user-facing documentation for package consumers (system developers).

**Required Files:**

#### `docs/getting-started.md`
```markdown
# Getting Started with Nexus PackageName

## Prerequisites
- PHP 8.3+
- Composer

## Installation
Step-by-step installation guide.

## Basic Configuration
How to set up the package.

## Your First Integration
Simple example to get started.

## Next Steps
Links to advanced documentation.
```

#### `docs/api-reference.md`
```markdown
# API Reference: PackageName

## Interfaces

### EntityManagerInterface
```php
interface EntityManagerInterface
{
    /**
     * Description
     * 
     * @param string $id
     * @return EntityInterface
     * @throws EntityNotFoundException
     */
    public function findById(string $id): EntityInterface;
}
```

## Services

### EntityManager
Detailed documentation of all public methods.

## Value Objects

### EntityValue
Properties, validation, and usage.

## Enums

### EntityStatus
All enum cases with descriptions.
```

#### `docs/integration-guide.md`
```markdown
# Integration Guide: PackageName

## Laravel Integration
Complete example of Laravel integration.

## Symfony Integration
Complete example of Symfony integration.

## Dependency Injection Setup
How to bind interfaces to implementations.

## Common Patterns
Recommended integration patterns.

## Troubleshooting
Common issues and solutions.
```

#### `docs/examples/`
Practical code examples:
- `basic-usage.php`
- `advanced-usage.php`
- `custom-implementation.php`

---

## ❌ Documentation Anti-Patterns (FORBIDDEN)

**Do NOT create these files/patterns:**

1. ❌ **Duplicate README files** (`docs/README.md`, `src/README.md`)
2. ❌ **CHANGELOG.md per package** (maintain in root if needed)
3. ❌ **Architecture diagrams as separate files** (embed in README.md or docs/)
4. ❌ **TODO.md files** (use GitHub Issues or IMPLEMENTATION_SUMMARY.md)
5. ❌ **Random markdown files** without clear purpose
6. ❌ **Migration guides** (packages have no migrations - that's application layer)
7. ❌ **Deployment guides** (packages are libraries, not deployable)
8. ❌ **CONTRIBUTING.md per package** (use root-level if needed)
9. ❌ **Separate versioning docs** (use composer.json version)
10. ❌ **Status update files** (e.g., `STATUS.md`, `PROGRESS.md` - use IMPLEMENTATION_SUMMARY.md)
11. ❌ **Separate valuation files** (use VALUATION_MATRIX.md only)

**Principle:** Each document must serve a **unique, non-overlapping purpose**. Avoid documentation duplication at all costs.

---

## 📋 Package Documentation Checklist

Before considering a package "complete", verify:

- [ ] **README.md** - Comprehensive with examples and integration guide
- [ ] **IMPLEMENTATION_SUMMARY.md** - Complete with metrics and status
- [ ] **REQUIREMENTS.md** - All requirements documented in standard format
- [ ] **TEST_SUITE_SUMMARY.md** - Coverage metrics and test inventory
- [ ] **VALUATION_MATRIX.md** - Complete valuation metrics and calculations
- [ ] **docs/getting-started.md** - Quick start guide exists
- [ ] **docs/api-reference.md** - All public APIs documented
- [ ] **docs/integration-guide.md** - Application layer examples provided
- [ ] **docs/examples/** - At least 2 working code examples
- [ ] **LICENSE** - MIT License file present
- [ ] **.gitignore** - Package-specific ignores configured
- [ ] **composer.json** - Proper metadata and autoloading
- [ ] **tests/** - Comprehensive test suite exists
- [ ] **No duplicate documentation** - Each file serves unique purpose
- [ ] **No unnecessary files** - Only required documentation present

---

## 🎯 Documentation Quality Standards

1. **Clarity:** Documentation must be clear enough for a new developer to integrate the package without assistance
2. **Completeness:** All public APIs must be documented with examples
3. **Accuracy:** Documentation must match current implementation (no outdated docs)
4. **Consistency:** Use consistent terminology and structure across all packages
5. **Maintainability:** Update documentation with every feature change
6. **No Duplication:** Each piece of information documented exactly once

---

## 🔧 Package Creation Workflow

### Step 1: Initialize Package Structure

```bash
# Create package directory
mkdir -p packages/PackageName

# Initialize composer
cd packages/PackageName
composer init
# Set name: nexus/package-name
# Require: php ^8.3
```

Create `.gitignore`:
```
/vendor/
composer.lock
.phpunit.result.cache
.DS_Store
```

Copy LICENSE file (MIT).

### Step 2: Create Required Documentation (BEFORE Writing Code)

1. Create `REQUIREMENTS.md` with initial requirements in standard format
2. Create `IMPLEMENTATION_SUMMARY.md` with implementation plan
3. Create `README.md` with package overview (update as you build)
4. Create `TEST_SUITE_SUMMARY.md` placeholder (update with tests)
5. Create `VALUATION_MATRIX.md` with initial estimates (update as you build)
6. Create `docs/` folder structure:
   - `docs/getting-started.md`
   - `docs/api-reference.md`
   - `docs/integration-guide.md`
   - `docs/examples/` directory

### Step 3: Implement Package Code

1. Create Contracts in `src/Contracts/`
2. Create Services in `src/Services/`
3. Create Exceptions in `src/Exceptions/`
4. Create Enums in `src/Enums/` (if needed)
5. Create Value Objects in `src/ValueObjects/` (if needed)
6. Create Core engine in `src/Core/` (only if complex package)

### Step 4: Write Tests

1. Create `tests/Unit/` directory
2. Create `tests/Feature/` directory
3. Write comprehensive tests for all public methods
4. Update `TEST_SUITE_SUMMARY.md` with coverage metrics

### Step 5: Update Documentation

1. Complete `README.md` with full examples
2. Update `IMPLEMENTATION_SUMMARY.md` with final metrics
3. Update `REQUIREMENTS.md` with status for each requirement
4. Update `VALUATION_MATRIX.md` with actual development hours, LOC, and final calculations
5. Complete `docs/api-reference.md` with all interfaces
6. Add working examples to `docs/examples/`

### Step 6: Register in Monorepo

1. Update root `composer.json` repositories array
2. Install in monorepo: `composer require nexus/package-name:"*@dev"`

### Step 7: Final Validation

1. Run package tests: `composer test`
2. Verify documentation completeness (use checklist above)
3. Ensure no framework dependencies in `composer.json`
4. Confirm all requirements marked as Complete or Pending with justification

---

## 📝 composer.json Template

```json
{
    "name": "nexus/package-name",
    "description": "Brief description of the package",
    "type": "library",
    "license": "MIT",
    "require": {
        "php": "^8.3"
    },
    "require-dev": {
        "phpunit/phpunit": "^11.0"
    },
    "autoload": {
        "psr-4": {
            "Nexus\\PackageName\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Nexus\\PackageName\\Tests\\": "tests/"
        }
    },
    "minimum-stability": "dev",
    "prefer-stable": true
}
```

---

## 🔍 Package Organization: When to Use `Core/` Folder

Create a `Core/` folder when your package is **complex** and contains internal components that should **never be accessed directly** by consumers.

**When to Create:**
- High complexity (Analytics, Workflow, Manufacturing)
- Internal contracts for engine components
- Value Objects or Internal Entities that should only be handled by the main Manager
- Engine logic where the main Manager is merely an orchestrator

**When to Skip:**
- Simple packages (Uom, Tenant)
- Fewer than 10 total files
- Manager class under 200 lines

---

## 🎓 Feature Implementation Workflow

When adding a new feature to an existing package:

### 1. Requirements Analysis
- Check if logic exists → Consult `docs/NEXUS_PACKAGES_REFERENCE.md`
- Add new requirements to `REQUIREMENTS.md` with proper codes
- Update `IMPLEMENTATION_SUMMARY.md` with feature plan

### 2. Implementation
- Define contracts → Create/update interfaces in `src/Contracts/`
- Implement services → Create/update manager/service classes
- Create exceptions → Define domain-specific errors
- Update `docs/api-reference.md` with new interfaces/methods

### 3. Testing
- Write tests → Unit tests for all business logic
- Update `TEST_SUITE_SUMMARY.md` with new tests and coverage

### 4. Documentation
- Update `README.md` with new feature examples
- Add examples to `docs/examples/` if applicable
- Update `docs/getting-started.md` if feature affects setup
- Update `docs/integration-guide.md` with new integration patterns
- Mark requirements as Complete in `REQUIREMENTS.md`
- Update metrics in `IMPLEMENTATION_SUMMARY.md`

**Remember:** A feature is not complete until all documentation is updated.

---

## 🚫 Common Mistakes to Avoid

1. **Starting code before documentation** - Always create REQUIREMENTS.md and IMPLEMENTATION_SUMMARY.md first
2. **Incomplete README.md** - Must include examples and application layer integration
3. **Missing TEST_SUITE_SUMMARY.md** - Test documentation is mandatory
4. **Framework dependencies in composer.json** - Packages must be framework-agnostic
5. **No .gitignore file** - Always include package-specific ignores
6. **Missing LICENSE file** - MIT License is mandatory
7. **Outdated documentation** - Update docs with every code change
8. **Duplicate documentation** - Each piece of info should exist in only one place
9. **Missing integration examples** - Always show how to use in Laravel/Symfony
10. **Incomplete REQUIREMENTS.md** - All requirements must be tracked with status

---

**Last Updated:** November 24, 2025  
**Maintained By:** Nexus Architecture Team  
**Enforcement:** Use this prompt when creating new packages or major package updates
