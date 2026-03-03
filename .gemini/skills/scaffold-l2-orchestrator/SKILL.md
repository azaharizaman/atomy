---
name: scaffold-l2-orchestrator
description: Scaffolds a new Layer 2 (Orchestrator) package according to Nexus standards.
---

# Scaffold Layer 2 Orchestrator

This skill automates the creation of a new Layer 2 Orchestrator package in the `orchestrators/` directory, specifically for cross-package coordination.

## Usage
Activate this skill when you need to create a service that coordinates actions across multiple Layer 1 packages (e.g., `OrderFulfillment` coordinating `Inventory`, `Payment`, and `Shipping`).

## Inputs
- **Orchestrator Name**: The PascalCase name (e.g., `OrderFulfillment`).
- **Dependent Packages**: List of Layer 1 packages this orchestrator will coordinate.

## Actions

1.  **Create Directory Structure**:
    -   `orchestrators/{OrchestratorName}/`
    -   `orchestrators/{OrchestratorName}/src/Contracts/` (Define coordination interfaces)
    -   `orchestrators/{OrchestratorName}/src/Services/` (Stateless coordinators)
    -   `orchestrators/{OrchestratorName}/src/DTOs/` (Data Transfer Objects for cross-boundary data)
    -   `orchestrators/{OrchestratorName}/tests/`

2.  **Generate `composer.json`**:
    -   Define namespace: `Atomy\Orchestrators\{OrchestratorName}`
    -   **CRITICAL**: NO `illuminate/*` dependencies. Pure PHP.
    -   Require dependent Layer 1 packages (e.g., `atomy/packages-inventory`).

3.  **Create `README.md`**:
    -   Title: `{OrchestratorName}`
    -   Description: "Layer 2 Orchestrator. Stateless Coordination."
    -   List Dependencies.

4.  **Create Interface First**:
    -   `src/Contracts/{OrchestratorName}Interface.php` defining the high-level workflow methods.

5.  **Register in Root `composer.json`**:
    -   Add `"Atomy\Orchestrators\{OrchestratorName}": "orchestrators/{OrchestratorName}/src/"` to `autoload.psr-4`.
    -   Run `composer dump-autoload`.

## Constraints
-   **Stateless**: Services must not hold state. Use DTOs for data passing.
-   **Interface First**: Always define the contract before implementation.
-   **Strict Typing**: `declare(strict_types=1);` mandatory.
-   **Immutability**: `final readonly class` for services.

## Example Prompt
"Scaffold a new Orchestrator named 'CheckoutFlow' that coordinates 'Cart', 'Payment', and 'Inventory'."
