# Package Architectural Violations Analysis Prompt

**Purpose:** Reusable template for auditing Nexus packages against architectural standards.

**When to Use:** Before merging package code, during package review, or when refactoring existing packages.

**Critical References:**
- **[`CODING_GUIDELINES.md`](../../CODING_GUIDELINES.md)** - Section 11: Architectural Violation Detection
- **[`ARCHITECTURE.md`](../../ARCHITECTURE.md)** - Repository interface design principles
- **[`docs/NEXUS_PACKAGES_REFERENCE.md`](../../docs/NEXUS_PACKAGES_REFERENCE.md)** - Existing package capabilities

---

## 🎯 Analysis Scope

**Package Being Analyzed:** `Nexus\[PackageName]`  
**Analysis Date:** YYYY-MM-DD  
**Analyst:** [Name/Team]  
**Analysis Type:** [Pre-Merge Review | Existing Package Audit | Refactoring Assessment]

---

## 📋 Automated Violation Detection

### Step 1: Run Quick Scan Scripts

**For complete violation scan commands and criteria, see [`CODING_GUIDELINES.md` - Section 11: Architectural Violation Detection](../../CODING_GUIDELINES.md#11-architectural-violation-detection).**

Execute these commands from the package root directory:

```bash
# Navigate to package directory
cd packages/[PackageName]

# ISP Violations (Fat Interfaces)
echo "=== ISP Violations ==="
grep -r "RepositoryInterface" src/Contracts/ | grep -E "(get|calculate|find|validate|create)" | wc -l
grep -r "ManagerInterface" src/Contracts/ | grep -E "(get|calculate|find|validate|create)" | wc -l

# Framework References (Framework Coupling)
echo "=== Framework References ==="
grep -ri "eloquent\|laravel\|symfony\|doctrine" src/

# Global Helpers (Framework Coupling)
echo "=== Global Helpers ==="
grep -r "now()\|config()\|app()\|dd()\|env()\|Cache::\|DB::\|Log::\|Event::" src/

# CQRS Violations (Pagination in Domain Layer)
echo "=== CQRS Violations ==="
grep -r "paginate\|PaginatedResult\|LengthAwarePaginator" src/Contracts/

# CQRS Type Consistency (QueryInterface returning raw arrays)
echo "=== CQRS Type Consistency ==="
grep -E "public function find.*\): \?array" src/Contracts/*QueryInterface.php

# CQRS Method Overlap (PersistInterface with both save and create)
echo "=== CQRS Method Overlap ==="
grep -l "public function save" src/Contracts/*PersistInterface.php | xargs grep -l "public function create"

# Missing PHPDoc Array Types
echo "=== Missing PHPDoc Array Types ==="
grep -B2 "public function.*\): array" src/Contracts/ | grep -v "@return array<" | grep "public function"

# Stateless Violations (Mutable State in Services)
echo "=== Stateless Violations ==="
grep -r "private array\|private int\|private string\|private Collection" src/Services/ | grep -v "readonly"

# composer.json Framework Dependencies
echo "=== Composer Framework Dependencies ==="
grep -E "laravel/framework|symfony/symfony|illuminate/" composer.json

# Target PHP Version Check
echo "=== PHP Version Requirement ==="
grep '"php":' composer.json
```

**Expected Output:**
- ISP Violations: **0 matches** (each interface should have single responsibility)
- Framework References: **0 matches** in `src/` directory
- Global Helpers: **0 matches** in `src/` directory
- CQRS Violations: **0 matches** in `src/Contracts/`
- CQRS Type Consistency: **0 matches** (QueryInterface methods should return typed entities, not raw arrays)
- CQRS Method Overlap: **0 matches** (PersistInterface should not have both `save()` and `create()`)
- Missing PHPDoc Array Types: **0 matches** (all array returns need `@return array<Type>`)
- Stateless Violations: **0 non-readonly properties** in `src/Services/`
- Framework Dependencies: **0 framework packages** in composer.json (PSR packages OK)
- PHP Version: **"php": "^8.3"**

---

## 🔍 Manual Violation Review

**For detailed violation criteria, examples, and rejection rules, see [`CODING_GUIDELINES.md` - Section 11](../../CODING_GUIDELINES.md#11-architectural-violation-detection).**

### Category 1: ISP (Interface Segregation Principle) Violations

**Checklist:**
- [ ] No interface has more than 7-10 methods
- [ ] Repository interfaces contain ONLY persistence methods (create, update, delete, find)
- [ ] Query operations separated into `*QueryInterface`
- [ ] Validation operations separated into `*ValidationInterface`
- [ ] Business logic NOT in repository interfaces (e.g., no `getExpiredTrials()`)
- [ ] Each interface has single, focused responsibility
- [ ] DocBlocks do NOT say "This interface handles X, Y, and Z"

**Violations Found:**
```
[List violations here]

Example:
- File: src/Contracts/TenantRepositoryInterface.php
- Issue: Interface contains create(), update(), delete(), findById(), all(), getExpiredTrials()
- Severity: High
- Fix: Split into TenantPersistInterface, TenantQueryInterface, TenantStatusService
```

### Category 2: CQRS (Command Query Responsibility Segregation) Violations

**Checklist:**
- [ ] Repository interfaces do NOT contain both write (create, update) AND read (find, get) methods
- [ ] No pagination parameters (`int $page`, `int $perPage`) in domain layer interfaces
- [ ] Query methods return raw arrays (`array<EntityInterface>`), NOT paginated objects
- [ ] Reporting methods (aging reports, statistics) NOT in repository interfaces
- [ ] Read models separated from write models
- [ ] **Query interfaces have consistent return types** (no mixing `?array` with `?EntityInterface`)
- [ ] **All array return types have PHPDoc annotations** (`@return array<Type>`)
- [ ] **Persist interfaces avoid method overlap** (use `save()` for create/update, not both `save()` and `create()`)

**Violations Found:**
```
[List violations here]

Example:
- File: src/Contracts/TenantRepositoryInterface.php
- Issue: Method signature: all(array $filters, int $page, int $perPage): LengthAwarePaginator
- Severity: High
- Fix: Remove pagination from domain interface, return array<TenantInterface>, apply pagination in application layer

Example (Type Consistency):
- File: src/Contracts/MfaEnrollmentQueryInterface.php
- Issue: findById() returns ?MfaEnrollmentInterface but findPendingByUserAndMethod() returns ?array
- Severity: High
- Fix: Change return type to ?MfaEnrollmentInterface for consistent typing

Example (Missing PHPDoc):
- File: src/Contracts/MfaEnrollmentQueryInterface.php
- Issue: findActiveBackupCodes() returns array without PHPDoc type annotation
- Severity: Medium
- Fix: Add @return array<MfaEnrollmentInterface> annotation

Example (Method Overlap):
- File: src/Contracts/MfaEnrollmentPersistInterface.php
- Issue: Has both save(MfaEnrollmentInterface) and create(array) methods with overlapping responsibilities
- Severity: High
- Fix: Remove create() method, use save() for both create and update operations
```

### Category 3: Stateless Architecture Violations

**Checklist:**
- [ ] Service classes are `final readonly class`
- [ ] All constructor dependencies are `readonly` and interfaces
- [ ] No private properties storing long-term state (e.g., `private array $cache = []`)
- [ ] Long-term state externalized via `*StorageInterface`
- [ ] Only request-scoped ephemeral state allowed (e.g., current tenant ID in context manager)
- [ ] No direct I/O operations (database, file system) in domain services

**Violations Found:**
```
[List violations here]

Example:
- File: src/Services/TenantManager.php
- Issue: private array $cache = []; (in-memory state storage)
- Severity: High
- Fix: Inject CacheRepositoryInterface, externalize state to Redis/Database
```

### Category 4: Framework Agnosticism Violations

**Checklist:**
- [ ] DocBlocks do NOT mention "Eloquent", "Laravel", "Symfony", "Doctrine"
- [ ] No framework-specific type hints (`Illuminate\Http\Request`, `Symfony\Component\HttpFoundation\Request`)
- [ ] No framework facades (`DB::`, `Cache::`, `Log::`, `Event::`, `Route::`)
- [ ] No global helpers (`now()`, `config()`, `app()`, `dd()`, `env()`)
- [ ] composer.json requires ONLY `php: ^8.3`, PSR packages, or other Nexus packages
- [ ] All dependencies are PSR interfaces or package-defined interfaces

**Violations Found:**
```
[List violations here]

Example:
- File: src/Services/TenantManager.php
- Issue: DocBlock says "This interface must be implemented using Eloquent"
- Severity: Critical
- Fix: Remove framework reference, change to "Consuming application provides implementation"
```

---

## 📊 Violation Summary

### Severity Classification

| Severity | Criteria | Action Required |
|----------|----------|-----------------|
| **Critical** | Framework coupling in src/, facades/helpers in domain code | REJECT - Immediate refactoring required |
| **High** | ISP violations (fat interfaces), CQRS violations (mixed operations), stateful services | REJECT - Refactoring required before merge |
| **Medium** | Minor ISP issues (1-2 extra methods), incomplete readonly usage | Request refactoring, can merge with tech debt ticket |
| **Low** | Documentation issues, missing type hints | Request improvement, OK to merge |

### Violations by Category

| Category | Critical | High | Medium | Low | Total | Pass/Fail |
|----------|----------|------|--------|-----|-------|-----------|
| ISP Violations | 0 | 0 | 0 | 0 | 0 | ✅ PASS |
| CQRS Violations | 0 | 0 | 0 | 0 | 0 | ✅ PASS |
| Stateless Violations | 0 | 0 | 0 | 0 | 0 | ✅ PASS |
| Framework Violations | 0 | 0 | 0 | 0 | 0 | ✅ PASS |
| **TOTAL** | **0** | **0** | **0** | **0** | **0** | **✅ PASS** |

### Overall Assessment

**Status:** [✅ PASS | ⚠️ PASS WITH CONDITIONS | ❌ FAIL]

**Auto-Rejection Criteria:**
- [ ] Package has > 3 violations from quick scans → REJECT
- [ ] Any violation in `src/Contracts/` → REJECT immediately
- [ ] Framework references in docblocks → REJECT (leaky abstraction)
- [ ] Framework dependencies in composer.json → REJECT

**Recommendation:** [Approve for merge | Request refactoring | Reject pending major rework]

---

## 🛠️ Refactoring Plan

### Priority 1: Critical Violations (Must Fix Before Merge)

```
[List critical violations with specific refactoring steps]

Example:
1. Framework Coupling in TenantManager
   - Current: Uses Cache::get() facade
   - Fix: Inject CacheRepositoryInterface in constructor
   - Files: src/Services/TenantManager.php
   - Estimate: 30 minutes
```

### Priority 2: High-Severity Violations (Must Fix Before Merge)

```
[List high-severity violations with specific refactoring steps]

Example:
1. ISP Violation in TenantRepositoryInterface
   - Current: 15 methods mixing CRUD, queries, validation
   - Fix: Split into 3 interfaces (TenantPersistenceInterface, TenantQueryInterface, TenantValidationInterface)
   - Files: src/Contracts/TenantRepositoryInterface.php, src/Services/TenantManager.php
   - Estimate: 2 hours
```

### Priority 3: Medium/Low Violations (Can Address Post-Merge)

```
[List medium/low violations with technical debt tickets]

Example:
1. Missing readonly on 2 properties in TenantContext
   - Current: private string $tenantId; (mutable)
   - Fix: private readonly string $tenantId;
   - Files: src/Services/TenantContext.php
   - Tech Debt Ticket: #[TICKET_NUMBER]
```

---

## 📝 Compliance Metrics

### Code Quality Metrics

| Metric | Value | Target | Status |
|--------|-------|--------|--------|
| Interface Segregation | 100% | 100% | ✅ |
| Framework Agnosticism | 100% | 100% | ✅ |
| Stateless Architecture | 100% | 100% | ✅ |
| CQRS Separation | 100% | 100% | ✅ |
| `readonly` Property Usage | 95% | 90% | ✅ |
| PHP 8.3+ Compliance | Yes | Yes | ✅ |
| PSR-12 Compliance | Yes | Yes | ✅ |

### Architectural Compliance Score

```
Calculation:
- ISP Violations: 0 → 25 points
- CQRS Violations: 0 → 25 points
- Stateless Violations: 0 → 25 points
- Framework Violations: 0 → 25 points
=====================================
Total Score: 100/100 (100%)
```

**Compliance Grade:** [A+ (95-100%) | A (90-94%) | B (80-89%) | C (70-79%) | F (<70%)]

**Pass Threshold:** 90% (Grade A or higher)

---

## 🔄 Post-Refactoring Validation

### Re-run Quick Scans

After refactoring, re-run all automated scans from Step 1 to confirm violations are resolved.

```bash
# Re-run all scans
cd packages/[PackageName]

# ISP Check
grep -r "RepositoryInterface" src/Contracts/ | grep -E "(get|calculate|find|validate|create)" | wc -l
# Expected: 0

# Framework Check
grep -ri "eloquent\|laravel\|symfony" src/ | wc -l
# Expected: 0

# Global Helpers Check
grep -r "now()\|config()\|app()\|dd()\|env()" src/ | wc -l
# Expected: 0

# CQRS Check
grep -r "paginate\|PaginatedResult\|LengthAwarePaginator" src/Contracts/ | wc -l
# Expected: 0

# Stateless Check
grep -r "private array\|private int\|private string" src/Services/ | grep -v "readonly" | wc -l
# Expected: 0
```

### Manual Code Review

- [ ] All interfaces have single responsibility
- [ ] All service classes are `final readonly class`
- [ ] All dependencies are injected interfaces
- [ ] No framework-specific code in `src/` directory
- [ ] DocBlocks are framework-agnostic
- [ ] composer.json requires only `php: ^8.3` and PSR packages

### Test Coverage Validation

- [ ] Unit tests pass
- [ ] Integration tests pass (if applicable)
- [ ] Coverage >= 90% for critical paths
- [ ] All public methods tested

---

## 📋 Final Checklist

### Documentation
- [ ] REFACTORING_SUMMARY.md updated with all violations fixed
- [ ] IMPLEMENTATION_SUMMARY.md updated with architectural compliance metrics
- [ ] REQUIREMENTS.md updated with architectural requirements marked ✅ Complete
- [ ] README.md updated with correct usage examples (no framework coupling shown)
- [ ] docs/api-reference.md updated with correct interface definitions

### Code Quality
- [ ] All ISP violations resolved
- [ ] All CQRS violations resolved
- [ ] All stateless violations resolved
- [ ] All framework agnosticism violations resolved
- [ ] PHP 8.3+ features used (readonly, enums, match, etc.)
- [ ] PSR-12 coding standards followed

### Testing
- [ ] Unit tests cover all refactored code
- [ ] Test suite passes (100% passing tests)
- [ ] No regressions introduced

### Git Workflow
- [ ] All changes committed with descriptive messages
- [ ] Branch pushed to remote
- [ ] Pull request created with comprehensive description
- [ ] PR links to this analysis document

---

## 📚 References

- **Architectural Guidelines:** `ARCHITECTURE.md` (Section 7: Architectural Violations & Prevention)
- **Copilot Instructions:** `.github/copilot-instructions.md` (Violation Detection Rules)
- **Package Standards:** `.github/prompts/create-package-instruction.prompt.md`
- **ISP Principle:** Martin, Robert C. "Agile Software Development, Principles, Patterns, and Practices"
- **CQRS Pattern:** Fowler, Martin. "CQRS" (martinfowler.com/bliki/CQRS.html)

---

## 🎓 Learning Outcomes

### Key Lessons from This Analysis

```
[Document key lessons learned from violations found]

Example:
1. Fat Interfaces (ISP): TenantRepositoryInterface had 15 methods mixing persistence, queries, and business logic. Splitting into 3 focused interfaces improved testability and reduced coupling.

2. Framework Coupling: DocBlocks mentioning "Eloquent" created leaky abstraction. Removing framework references makes package truly portable.

3. Stateful Services: In-memory cache in TenantManager prevented horizontal scaling. Externalizing state via CacheRepositoryInterface resolved this.
```

### Best Practices for Future Packages

```
[Document best practices to prevent similar violations]

Example:
1. Always define repository interfaces with ONLY CRUD methods (create, update, delete, find)
2. Create separate query interfaces for read operations
3. Never mention framework names in docblocks
4. Always declare service classes as "final readonly class"
5. Externalize all long-term state via *StorageInterface dependencies
```

---

**Analysis Completed By:** [Name/Team]  
**Review Date:** YYYY-MM-DD  
**Next Analysis:** [Date or trigger event]  

---

**Status:** [✅ Analysis Complete | 🔄 Refactoring In Progress | ⏳ Pending Review]
