# PolicyEngine Requirements (v1)

## In Scope

1. Evaluate authorization policies deterministically.
2. Execute workflow policies deterministically.
3. Enforce threshold/limit policies using numeric comparators (`GT`, `GTE`, `LT`, `LTE`, `BETWEEN`).
4. Enforce tenant-scoped policy lookup and evaluation.
5. Validate policy structure before evaluation.
6. Produce machine-consumable decision output with reason codes and obligations.
7. Decode JSON policy payloads into typed domain models without framework dependencies.

## Out of Scope (Documented)

1. Persistence adapters and storage schema.
2. Admin policy authoring UI.
