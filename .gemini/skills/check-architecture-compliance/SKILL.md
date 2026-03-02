# Check Architecture Compliance

This skill automates the validation of a package against the project's strict architectural mandates.

## Usage
Activate this skill to audit a package (Layer 1 or Layer 2) for prohibited patterns, missing strict types, or mutable state.

## Inputs
- **Package Path**: Relative path to the package (e.g., `packages/Accounting`).

## Actions

1.  **Scan for Prohibited Imports**:
    -   **Layer 1 & 2**: `grep` for `Illuminate` or `Symfony`. FAIL if found.
    -   **All Layers**: `grep` for generic `\Exception`. FAIL if found (must use domain exceptions).
    -   **All Layers**: `grep` for `extends Model` (Eloquent) in `src/`. FAIL if found.

2.  **Verify Strict Typing**:
    -   Check every `.php` file in `src/` for `declare(strict_types=1);`.
    -   Report any files missing this declaration.

3.  **Verify Immutability**:
    -   Check Service classes in `src/Services/` for `final readonly class`.
    -   Check VOs in `src/Models/` for `readonly` properties.

4.  **Verify Constructor Injection**:
    -   Check constructors for specific interfaces.
    -   Flag any usage of generic `object` or `mixed`.

5.  **Generate Report**:
    -   Output a Markdown checklist of compliance.
    -   Pass/Fail status for each check.

## Constraints
-   **Zero Tolerance**: Any `Illuminate` import in Layer 1 is a critical failure.
-   **Strict Typing**: Every file must comply.

## Example Prompt
"Check architecture compliance for packages/Billing."
