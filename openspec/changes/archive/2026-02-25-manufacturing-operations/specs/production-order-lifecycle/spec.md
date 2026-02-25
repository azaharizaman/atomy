## ADDED Requirements

### Requirement: Orchestrated Production Order Release
The system SHALL coordinate the release of a production order by verifying stock availability, reserving materials, and initializing quality control checkpoints.

#### Scenario: Successful release with all materials available
- **WHEN** a production order is released
- **AND** all required materials are available in stock
- **THEN** the system SHALL reserve the materials in Inventory
- **AND** create the initial Quality Control checklist
- **AND** update the production order status to InProgress

#### Scenario: Atomic Release with Compensation
- **WHEN** a production order is being released
- **AND** any step fails after materials are reserved (e.g., QC initialization or status update)
- **THEN** the system SHALL perform compensating actions to maintain consistency:
    - **SHALL** unreserve any materials reserved in Inventory for this release
    - **SHALL** revert/delete any partially created Quality Control artifacts
    - **SHALL** NOT update the production order status to InProgress

#### Scenario: Release blocked by material shortage
- **WHEN** a production order is released
- **AND** there is a shortage of one or more materials
- **THEN** the system SHALL NOT release the order
- **AND** SHALL return a detailed list of missing materials and quantities

### Requirement: Multi-Tenant Context Enforcement
The system MUST ensure that all orchestration operations (Stock check, Quality initialization) are scoped strictly to the provided `tenantId`.

#### Scenario: Isolation across tenants
- **WHEN** Tenant A releases a production order
- **THEN** the system SHALL ONLY check and reserve stock belonging to Tenant A
- **AND** SHALL NOT leak data or reserve stock from Tenant B
