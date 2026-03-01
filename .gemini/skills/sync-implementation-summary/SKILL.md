# Sync Implementation Summary

This skill automates the maintenance of `IMPLEMENTATION_SUMMARY.md` by analyzing the current codebase of a package.

## Usage
Activate this skill after making significant changes to a package (Layer 1 or Layer 2) to ensure the documentation reflects the reality of the code.

## Inputs
- **Package Path**: Relative path to the package (e.g., `packages/Accounting` or `orchestrators/CheckoutFlow`).

## Actions

1.  **Analyze Codebase**:
    -   Scan `src/Contracts/` for all interfaces.
    -   Scan `src/Models/` for entities and Value Objects.
    -   Scan `src/Services/` for service classes.
    -   Scan `tests/` for test coverage (count test files/methods).

2.  **Generate Summary Content**:
    -   **Title**: Package Name
    -   **Status**: Active/Stable/Beta
    -   **Public API**: List all interfaces in `Contracts/` with method signatures.
    -   **Domain Model**: List all Entities/VOs in `Models/`.
    -   **Services**: List all service classes and their primary responsibility.
    -   **Test Coverage**: Summary of test files found.

3.  **Update File**:
    -   Overwrite or create `{PackagePath}/IMPLEMENTATION_SUMMARY.md` with the generated content.
    -   Ensure standard Markdown formatting.

## Constraints
-   **No Hallucinations**: Only document what actually exists in the files.
-   **Method Signatures**: Include return types in the API listing.

## Example Prompt
"Sync the implementation summary for packages/Inventory."
