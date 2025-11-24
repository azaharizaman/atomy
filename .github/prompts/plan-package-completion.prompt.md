# Plan Package Completion (10-20% Progress Increment)

**Purpose:** Analyze an existing Nexus package to identify incomplete work and create a comprehensive implementation plan to advance completion by 10-20%.

**When to Use:** When you want to systematically complete pending features in an existing package.

**Mode:** This prompt is designed for **Plan Mode** - it produces a detailed implementation plan as a chat response, NOT saved to files.

**Reference Standards:**
- Package structure: `.github/prompts/create-package-instruction.prompt.md`
- Documentation standards: `.github/prompts/apply-documentation-standards.prompt.md`
- Architecture guidelines: `.github/copilot-instructions.md`
- Package reference: `docs/NEXUS_PACKAGES_REFERENCE.md`

---

## 🎯 Objective

Given a package name (e.g., `EventStream`, `Receivable`, `Finance`), perform comprehensive analysis to:

1. **Scan package source code** for TODO comments and incomplete implementations
2. **Review IMPLEMENTATION_SUMMARY.md** for planned but unimplemented phases
3. **Review REQUIREMENTS.md** for requirements NOT marked as ✅ Complete
4. **Check documentation** for incomplete checklists or pending sections
5. **Analyze test coverage** for untested components
6. **Compile findings** into a prioritized implementation plan
7. **Estimate completion gain** (target: 10-20% progress toward 100%)

---

## 📋 Analysis Checklist

### 1. Package Metadata Analysis

**Files to Check:**
- `packages/PackageName/composer.json` - Verify package exists
- `packages/PackageName/IMPLEMENTATION_SUMMARY.md` - Current completion %
- `packages/PackageName/REQUIREMENTS.md` - Total requirements vs. completed

**Extract:**
- Current completion percentage (e.g., "60% Complete", "Phase 1: 80% Complete")
- Total requirements count
- Number of incomplete requirements (⏳ Pending, 🚧 In Progress, ❌ Blocked)

---

### 2. Source Code Scan

**Directories to Scan:**
```bash
packages/PackageName/src/
```

**Search Patterns:**
```bash
# Find TODO comments
grep -r "TODO" packages/PackageName/src/

# Find FIXME comments
grep -r "FIXME" packages/PackageName/src/

# Find @todo docblock annotations
grep -r "@todo" packages/PackageName/src/

# Find placeholder implementations
grep -r "throw new \\\\RuntimeException('Not implemented')" packages/PackageName/src/
grep -r "// Not implemented yet" packages/PackageName/src/
```

**Categorize Findings:**
- Critical TODOs (blocking functionality)
- Feature TODOs (planned enhancements)
- Refactoring TODOs (code quality improvements)
- Documentation TODOs (missing docblocks)

---

### 3. Requirements Analysis

**File:** `packages/PackageName/REQUIREMENTS.md`

**Query:**
```bash
# Count incomplete requirements
grep -c "⏳ Pending" packages/PackageName/REQUIREMENTS.md
grep -c "🚧 In Progress" packages/PackageName/REQUIREMENTS.md
grep -c "❌ Blocked" packages/PackageName/REQUIREMENTS.md

# List incomplete requirement codes
grep "⏳ Pending" packages/PackageName/REQUIREMENTS.md | awk -F'|' '{print $3}' | tr -d ' '
```

**Extract:**
- List of incomplete requirement codes (e.g., FUN-PKG-0015, BUS-PKG-0023)
- Requirement descriptions
- Associated files/folders
- Blocking reasons (if status is ❌ Blocked)

**Prioritize by:**
1. Architectural Requirements (ARC-*) - Highest priority
2. Business Requirements (BUS-*) - High priority
3. Functional Requirements (FUN-*) - Medium priority
4. Other requirements (PER, REL, SEC, INT, USA) - Lower priority

---

### 4. Implementation Summary Analysis

**File:** `packages/PackageName/IMPLEMENTATION_SUMMARY.md`

**Look for:**
- Section: **"## Implementation Plan"** or **"## Phases"**
  - Extract planned phases/tasks marked as `[ ]` (not completed)
  - Extract tasks marked as `🔄` (in progress)
  
- Section: **"## What Is Planned for Future"**
  - Extract planned features not yet started
  
- Section: **"## Known Limitations"**
  - Extract limitations that could be addressed

**Example Extraction:**
```markdown
### Phase 2: Advanced Features (Planned) - 30% INCOMPLETE
- [ ] Event Upcasting (fail-fast, mandatory testing)
- [ ] Stream Querying (dual pagination: offset + HMAC cursor)
- [x] Projection Infrastructure (locks, state persistence) ✅
- [ ] Snapshot Enhancements (retention, compression, validation)
```

---

### 5. Test Coverage Analysis

**File:** `packages/PackageName/TEST_SUITE_SUMMARY.md` (if exists)

**Extract:**
- Current line coverage percentage
- Uncovered components (services, value objects, etc.)
- Missing test types (unit, integration, feature)

**If TEST_SUITE_SUMMARY.md doesn't exist:**
```bash
# Run tests with coverage (if configured)
cd packages/PackageName
composer test:coverage

# Or count test files vs source files
find tests/ -name "*Test.php" | wc -l
find src/ -name "*.php" ! -path "*/Tests/*" | wc -l
```

**Identify:**
- Services without tests
- Value objects without tests
- Exceptions without tests
- Edge cases not covered

---

### 6. Documentation Completeness Check

**Files to Check:**
- `packages/PackageName/README.md`
- `packages/PackageName/docs/getting-started.md`
- `packages/PackageName/docs/api-reference.md`
- `packages/PackageName/docs/integration-guide.md`

**Look for:**
- Incomplete sections marked with `TODO`, `TBD`, `Coming soon`
- Empty sections
- Placeholder text like `[Description]`, `[Example]`, `[To be documented]`
- Missing examples in `docs/examples/`

**Check Documentation Standards Compliance:**
```bash
# Verify mandatory files exist
test -f packages/PackageName/.gitignore && echo "✓" || echo "✗ .gitignore missing"
test -f packages/PackageName/IMPLEMENTATION_SUMMARY.md && echo "✓" || echo "✗ IMPLEMENTATION_SUMMARY.md missing"
test -f packages/PackageName/REQUIREMENTS.md && echo "✓" || echo "✗ REQUIREMENTS.md missing"
test -f packages/PackageName/VALUATION_MATRIX.md && echo "✓" || echo "✗ VALUATION_MATRIX.md missing"
test -f packages/PackageName/TEST_SUITE_SUMMARY.md && echo "✓" || echo "✗ TEST_SUITE_SUMMARY.md missing"
test -d packages/PackageName/docs/examples && echo "✓" || echo "✗ docs/examples/ missing"
```

---

### 7. Code Quality Issues

**Run Static Analysis (if available):**
```bash
# PHPStan
vendor/bin/phpstan analyse packages/PackageName/src

# Psalm
vendor/bin/psalm packages/PackageName

# PHP_CodeSniffer
vendor/bin/phpcs packages/PackageName/src
```

**Manual Checks:**
- Missing type hints
- Missing docblocks on public methods
- Violations of framework-agnostic principle (framework facades, global helpers)
- Missing `declare(strict_types=1);`
- Properties not marked `readonly`

---

## 📊 Compilation & Prioritization

After gathering all findings, compile them into categories:

### Critical Issues (Must Fix - Blocks Core Functionality)
- Requirements with ❌ Blocked status
- TODOs in core services/managers
- Missing mandatory interfaces
- Test failures

### High Priority (Core Features - 10-15% Completion Gain)
- Requirements with 🚧 In Progress status
- Planned Phase 2/Phase 3 features from IMPLEMENTATION_SUMMARY.md
- Services with TODO comments
- Missing test coverage for critical paths

### Medium Priority (Enhancements - 5-10% Completion Gain)
- Requirements with ⏳ Pending status
- Refactoring TODOs
- Documentation gaps
- Missing examples

### Low Priority (Nice-to-Have - 1-5% Completion Gain)
- Code quality improvements
- Additional test cases
- Documentation polish
- Performance optimizations

---

## 🎯 Plan Generation Strategy

### Calculate Target Work

**Formula:**
```
Current Completion: X%
Target Completion: X% + 10-20% = (X+10)% to (X+20)%

Work Required:
- If current = 60%, target = 70-80%
- Prioritize items that collectively add 10-20% value
```

**Estimation Guidelines:**
- Complete 1 major requirement = +2-5% completion
- Complete 1 minor requirement = +0.5-1% completion
- Add comprehensive tests for 1 service = +1-2% completion
- Complete 1 documentation section = +0.5-1% completion
- Implement 1 planned phase = +10-30% completion

---

### Plan Structure

Generate a plan with the following structure:

```markdown
# Implementation Plan: [PackageName] (Target: +10-20% Completion)

## Current State
- **Current Completion:** X%
- **Target Completion:** (X+10)% to (X+20)%
- **Total Requirements:** XXX (XX complete, XX incomplete)
- **Test Coverage:** XX%
- **Known TODOs:** XX items

## Analysis Summary

### Critical Issues Found (X items)
1. [Issue 1] - [Location] - [Impact]
2. [Issue 2] - [Location] - [Impact]

### High Priority Work (X items)
1. [Task 1] - [Estimated completion gain: +X%]
2. [Task 2] - [Estimated completion gain: +X%]

### Medium Priority Work (X items)
[List items]

### Low Priority Work (X items)
[List items]

## Recommended Scope for This Iteration

**Estimated Completion Gain:** +XX%

### Phase 1: Preparation (1-2 hours)
- [ ] Create feature branch: `feature/packagename-completion-phaseX`
- [ ] Review dependencies (check Context7 docs if needed)
- [ ] Set up TODO tracking

### Phase 2: Implementation (X hours)
- [ ] [Task 1 - Requirement: REQ-CODE-XXXX]
  - File: `src/Path/To/File.php`
  - Description: [What to implement]
  - Acceptance Criteria: [How to verify]
  
- [ ] [Task 2 - Requirement: REQ-CODE-XXXX]
  - File: `src/Path/To/File.php`
  - Description: [What to implement]
  - Acceptance Criteria: [How to verify]

### Phase 3: Testing (X hours)
- [ ] Write unit tests for [Component]
- [ ] Write integration tests for [Flow]
- [ ] Update TEST_SUITE_SUMMARY.md
- [ ] Verify test coverage increase

### Phase 4: Documentation (X hours)
- [ ] Update REQUIREMENTS.md (mark completed items as ✅)
- [ ] Update IMPLEMENTATION_SUMMARY.md (update metrics, phases)
- [ ] Update docs/api-reference.md (new interfaces/methods)
- [ ] Add code examples to docs/examples/
- [ ] Update README.md if needed

### Phase 5: Quality Assurance (1-2 hours)
- [ ] Run full test suite
- [ ] Verify no framework dependencies introduced
- [ ] Check all public methods have docblocks
- [ ] Verify PSR-12 compliance
- [ ] Update VALUATION_MATRIX.md (recalculate with new metrics)

### Phase 6: Version Control (1 hour)
- [ ] Review all changes
- [ ] Commit: "feat(packagename): [Brief description]"
- [ ] Commit: "test(packagename): Add tests for [Component]"
- [ ] Commit: "docs(packagename): Update documentation for Phase X"
- [ ] Create PR: "feat(PackageName): Phase X Completion (+XX%)"
- [ ] Request Copilot review (use `mcp_github_github_request_copilot_review`)

## Execution Strategy

### Git Workflow
```bash
# Create feature branch
git checkout -b feature/packagename-completion-phaseX

# Periodic commits (every 2-3 completed tasks)
git add .
git commit -m "feat(packagename): [Description]"

# Push and create PR at the end
git push origin feature/packagename-completion-phaseX
# Use GitHub MCP to create PR
```

### TODO Tracking
Use `manage_todo_list` tool to track:
1. Mark task as "in-progress" before starting
2. Mark task as "completed" immediately after finishing
3. Never batch completions

### External Documentation
When implementing features requiring external libraries:
```bash
# Use Context7 MCP to get library docs
# Example: Get Symfony UID documentation
mcp_upstash_conte_resolve-library-id "symfony/uid"
mcp_upstash_conte_get-library-docs "/symfony/uid" "ULID generation"
```

### GitHub Integration
Use GitHub MCP for:
- Creating PR: `mcp_github_github_create_pull_request`
- Requesting Copilot review: `mcp_github_github_request_copilot_review`
- Checking PR status: `mcp_github_github_pull_request_read`

## Success Criteria

- [ ] All planned tasks completed
- [ ] Requirements.md updated (XX new ✅ Complete)
- [ ] IMPLEMENTATION_SUMMARY.md updated (new completion %)
- [ ] TEST_SUITE_SUMMARY.md updated (coverage increased by XX%)
- [ ] All tests passing (100% pass rate)
- [ ] Documentation updated (API reference, examples)
- [ ] PR created and Copilot review requested
- [ ] Estimated completion gain achieved: +XX%

## Risk Mitigation

### Potential Blockers
1. **[Blocker 1]:** [Risk] - Mitigation: [Strategy]
2. **[Blocker 2]:** [Risk] - Mitigation: [Strategy]

### Rollback Plan
If issues arise:
1. Commit current work: `git commit -m "wip: [state]"`
2. Create backup branch: `git branch backup/packagename-phaseX`
3. Reset if needed: `git reset --hard origin/main`

## Next Steps After This Iteration

Once this phase is complete:
- [ ] Review completion percentage (should be X+10% to X+20%)
- [ ] Identify next 10-20% increment targets
- [ ] Update package roadmap in IMPLEMENTATION_SUMMARY.md
```

---

## 🛠️ Tools to Use During Execution

### 1. manage_todo_list
**Purpose:** Track implementation tasks systematically

**Usage:**
```
Before starting: Mark task as "in-progress"
After completing: Mark task as "completed" (immediately, not batched)
```

### 2. mcp_upstash_conte (Context7 MCP)
**Purpose:** Retrieve external library documentation

**When to Use:**
- Implementing features using external packages (Symfony components, PSR interfaces)
- Need code examples from library documentation
- Understanding library APIs

**Example:**
```
# Resolve library ID
mcp_upstash_conte_resolve-library-id "symfony/uid"

# Get documentation
mcp_upstash_conte_get-library-docs "/symfony/uid" "ULID validation"
```

### 3. mcp_github_github_* (GitHub MCP)
**Purpose:** GitHub operations (PR creation, review, status checks)

**When to Use:**
- Creating feature branch (can use terminal, but MCP provides more control)
- Creating pull request at the end
- Requesting Copilot code review
- Checking PR status and review comments

**Example:**
```
# Create PR
mcp_github_github_create_pull_request(
    owner: "azaharizaman",
    repo: "atomy",
    title: "feat(EventStream): Phase 2 Completion (+15%)",
    body: "[PR description]",
    head: "feature/eventstream-completion-phase2",
    base: "main"
)

# Request Copilot review
mcp_github_github_request_copilot_review(
    owner: "azaharizaman",
    repo: "atomy",
    pullNumber: [PR_NUMBER]
)
```

### 4. semantic_search
**Purpose:** Find existing implementations in codebase

**When to Use:**
- Looking for similar implementations in other packages
- Finding how a pattern is used elsewhere
- Checking if functionality already exists

### 5. grep_search
**Purpose:** Find specific patterns in code

**When to Use:**
- Finding all TODO comments
- Finding all usages of an interface
- Checking for framework violations (facades, global helpers)

---

## 📝 Documentation Update Checklist

After implementation, ensure ALL documentation is updated:

### REQUIREMENTS.md
- [ ] Mark completed requirements as ✅ Complete
- [ ] Update "Files/Folders" column with actual file paths
- [ ] Update "Date Last Updated" to current date
- [ ] Update total count at top of file

### IMPLEMENTATION_SUMMARY.md
- [ ] Update "Status" and completion percentage
- [ ] Update "Last Updated" date
- [ ] Mark completed tasks in Implementation Plan as [x]
- [ ] Update "What Was Completed" section
- [ ] Update Code Metrics (LOC, classes, interfaces, etc.)
- [ ] Update Test Coverage metrics
- [ ] Update Dependencies if new packages added
- [ ] Remove completed items from "What Is Planned for Future"

### TEST_SUITE_SUMMARY.md
- [ ] Update "Last Test Run" date and time
- [ ] Update coverage metrics (line, function, class coverage)
- [ ] Add new tests to "Test Inventory"
- [ ] Update "Test Results Summary" with latest run
- [ ] Document any new testing strategies

### VALUATION_MATRIX.md
- [ ] Update Development Investment hours
- [ ] Update Complexity Metrics (LOC, classes, interfaces, tests)
- [ ] Recalculate Innovation Score if applicable
- [ ] Update Technical Debt percentage
- [ ] Recalculate Final Package Valuation
- [ ] Update "Review Date" and "Next Review" dates

### docs/api-reference.md
- [ ] Document new interfaces with all methods
- [ ] Document new services
- [ ] Document new value objects
- [ ] Document new enums
- [ ] Document new exceptions
- [ ] Add usage examples for new features

### docs/getting-started.md
- [ ] Update if new prerequisites added
- [ ] Update if core concepts changed
- [ ] Add new configuration steps if needed

### docs/integration-guide.md
- [ ] Add examples for new features (Laravel & Symfony)
- [ ] Update troubleshooting section if needed
- [ ] Add new common patterns

### docs/examples/
- [ ] Update basic-usage.php if core flows changed
- [ ] Update advanced-usage.php with new advanced features
- [ ] Create new example files if needed

### README.md
- [ ] Update feature list if major features added
- [ ] Update usage examples if API changed
- [ ] Update documentation links if structure changed

---

## ⚠️ Critical Constraints

### Framework Agnosticism (MANDATORY)
**Before ANY implementation:**
- [ ] Verify no framework facades used (`Log::`, `Cache::`, `DB::`, etc.)
- [ ] Verify no global helpers used (`config()`, `app()`, `now()`, `dd()`, etc.)
- [ ] Verify all dependencies are interfaces (check constructor parameters)
- [ ] Verify `declare(strict_types=1);` at top of every new file
- [ ] Verify all properties are `readonly`
- [ ] Verify native PHP enums used (not class constants)

**Reference:** `.github/copilot-instructions.md` section "Strict Anti-Pattern: Facade & Global Helper Prohibition"

### Package Isolation
- [ ] Check `docs/NEXUS_PACKAGES_REFERENCE.md` - Does functionality already exist in another package?
- [ ] If yes, use dependency injection via interface (don't reimplement)
- [ ] If package needs another package, inject interface (don't couple concrete classes)

### Code Quality
- [ ] All public methods have complete docblocks
- [ ] All parameters have type hints
- [ ] All return types declared
- [ ] All exceptions documented with `@throws`
- [ ] PSR-12 coding standards followed

---

## 🎓 Example Prompt for Execution

When using this prompt in Plan Mode, say:

> "Plan completion increment for Nexus\EventStream package using `.github/prompts/plan-package-completion.prompt.md`. Analyze current state, identify incomplete work, and create a comprehensive plan to advance completion by 10-20%. Present the plan as a chat response with TODO tracking, git workflow, MCP tool usage, and documentation update checklist."

Replace `EventStream` with any package: `Receivable`, `Finance`, `Payable`, `Inventory`, etc.

---

## 📊 Output Format

The generated plan should be presented as a **chat response** (NOT saved to file) with:

1. **Executive Summary** (3-5 sentences)
   - Current completion %
   - Target completion %
   - Key findings
   - Recommended scope

2. **Analysis Results** (bulleted lists)
   - Critical issues
   - High priority work
   - Medium priority work
   - Low priority work

3. **Recommended Implementation Plan** (detailed task breakdown)
   - Phase 1: Preparation
   - Phase 2: Implementation (with TODO items)
   - Phase 3: Testing
   - Phase 4: Documentation
   - Phase 5: Quality Assurance
   - Phase 6: Version Control

4. **Execution Strategy** (step-by-step)
   - Git workflow commands
   - TODO tracking approach
   - MCP tool usage examples
   - Documentation update checklist

5. **Success Criteria** (checklist)
   - Completion targets
   - Quality gates
   - Documentation updates

6. **Risk Mitigation** (potential blockers + mitigation)

---

## 🔍 Advanced Analysis Techniques

### Dependency Graph Analysis
Identify which requirements depend on others:
```bash
# Find interfaces that other services depend on
grep -r "use Nexus\\PackageName\\Contracts\\" packages/PackageName/src/
```

### Code Churn Analysis
Identify frequently changing files (may indicate instability):
```bash
git log --all --format=format: --name-only packages/PackageName/ | sort | uniq -c | sort -rg | head -10
```

### Technical Debt Estimation
Calculate debt ratio:
```
Debt Ratio = (TODO count + FIXME count + Incomplete Requirements) / Total LOC × 1000
```

Benchmark:
- < 5: Low debt
- 5-15: Medium debt
- > 15: High debt (needs refactoring before new features)

---

**Last Updated:** 2025-11-24  
**Maintained By:** Nexus Architecture Team  
**Usage:** Plan Mode - Generates implementation plan as chat response
