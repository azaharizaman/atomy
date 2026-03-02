---
name: test-improvement
description: Analyzes existing tests and proposes improvements for better coverage and reliability.
---

# Test Improvement (QA Automation)

This skill automates the expansion of test coverage, ensuring high-quality verification with modern PHP syntax.

## Usage
Activate this skill when a package or collection of packages has low coverage (<80%) or needs additional edge-case testing.

## Inputs
- **Package Path**: The directory to improve (e.g., `packages/Inventory`, `orchestrators/AccountingOperations`).
- **Target Improvement**: Default is ~20% increase.

## Actions

1.  **Analyze Current Coverage**: Run the project's root test command (e.g., `vendor/bin/phpunit`) for the specified directory.
2.  **Generate Tests**:
    -   **L1/L2 (Core)**: Use **PHPUnit 10+** with modern syntax.
        -   Use **PHP Attributes** (e.g., `#[Test]`, `#[DataProvider]`) instead of docblock annotations.
        -   Strictly type all test methods and properties.
    -   **L3 (Adapters)**: Use **Pest** (if configured) or Laravel's native PHPUnit wrappers.
    -   **Apps**: Use Integration tests (HTTP tests, Console tests).
3.  **Execute & Verify**:
    -   **ROOT EXECUTION**: All tests MUST be runnable from the project root. NO `cd` into packages.
    -   Use `CI=true` for non-interactive runs.
4.  **Requirement**: Add tests for edge cases (null-check, division-by-zero, empty-array, cross-tenant isolation).

## Constraints
-   **No Duplication**: Check for existing tests before writing new ones.
-   **Architecture Compliance**: Tests must respect Layer boundaries (mock L1 when testing L2).

## Example Prompt
"Improve test coverage for packages/Inventory by 20% using modern PHPUnit attributes."
