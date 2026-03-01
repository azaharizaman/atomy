# Scaffold Layer 1 Package (Atomic Package)

This skill automates the creation of a new Layer 1 Atomic Package in the `packages/` directory, adhering strictly to the project's Three-Layer Architecture.

## Usage
Activate this skill when you need to create a new domain package that contains core business logic, entities, and value objects.

## Inputs
- **Package Name**: The PascalCase name of the package (e.g., `Accounting`, `Inventory`).
- **Description**: A brief description of the package's responsibility.

## Actions

1.  **Create Directory Structure**:
    -   `packages/{PackageName}/`
    -   `packages/{PackageName}/src/Contracts/` (Interfaces first!)
    -   `packages/{PackageName}/src/Exceptions/`
    -   `packages/{PackageName}/src/Models/` (Value Objects, Entities)
    -   `packages/{PackageName}/src/Services/`
    -   `packages/{PackageName}/tests/`

2.  **Generate `composer.json`**:
    -   Define namespace: `Atomy\Packages\{PackageName}`
    -   **CRITICAL**: Do NOT include `illuminate/*` or any framework dependencies. Only `php` and `ext-*`.
    -   Require `php: ^8.3`.
    -   Autoload `src/` and `tests/`.

3.  **Create `README.md`**:
    -   Title: `{PackageName}`
    -   Description.
    -   Architecture Note: "Layer 1 Atomic Package. Pure PHP. No Framework Dependencies."

4.  **Create `IMPLEMENTATION_SUMMARY.md`**:
    -   Initialize with "Status: Pending Implementation".

5.  **Create Placeholder Test**:
    -   `tests/ExampleTest.php` extending `PHPUnit\Framework\TestCase`.

6.  **Register in Root `composer.json`**:
    -   Add `"Atomy\Packages\{PackageName}": "packages/{PackageName}/src/"` to the `autoload.psr-4` section.
    -   Run `composer dump-autoload`.

## Constraints
-   **Strict Typing**: All generated PHP files MUST start with `declare(strict_types=1);`.
-   **Immutability**: Service classes MUST be `final readonly class`. Value Objects MUST have `readonly` properties.
-   **No Frameworks**: Verify no Laravel/Symfony imports are used.

## Example Prompt
"Scaffold a new Layer 1 package named 'LoyaltyProgram' for managing customer points."
