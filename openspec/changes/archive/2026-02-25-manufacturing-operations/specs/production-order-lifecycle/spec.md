## ADDED Requirements

### Requirement: Orchestrated Production Order Planning
The system SHALL coordinate the planning of a production order by validating the Bill of Materials (BOM) and estimating production costs.

#### Scenario: Successful planning
- **WHEN** a production order is planned
- **THEN** the system SHALL validate the BOM for the product
- **AND** calculate estimated material, labor, and overhead costs
- **AND** dispatch an `OrderPlanned` domain event
- **AND** create a production order in `Planned` status

### Requirement: Orchestrated Production Order Release
The system SHALL coordinate the release of a production order by verifying stock availability, reserving materials, and initializing quality control checkpoints.

#### Scenario: Successful release with all materials available
- **WHEN** a production order is released
- **AND** all required materials are available in stock
- **THEN** the system SHALL reserve the materials in Inventory
- **AND** create the initial Quality Control checklist
- **AND** update the production order status to `Released`
- **AND** dispatch an `OrderReleased` domain event upon successful release

#### Scenario: Atomic Release with Compensation
- **WHEN** a production order is being released
- **AND** any step fails after materials are reserved (e.g., QC initialization or status update)
- **THEN** the system SHALL perform compensating actions to maintain consistency:
    - **SHALL** unreserve any materials reserved in Inventory for this release
    - **SHALL** revert/delete any partially created Quality Control artifacts
    - **SHALL** revert the production order status to its pre-release state (e.g., `Planned`)
- **IF** a compensating action fails, the system SHALL retry up to 3 times, log a critical error, and prevent further automated state transitions for the order.

#### Scenario: Release blocked by material shortage
- **WHEN** a production order is released
- **AND** there is a shortage of one or more materials
- **THEN** the system SHALL NOT release the order
- **AND** SHALL return a detailed list of missing materials and quantities
- **AND** any materials reserved or allocated prior to detecting the shortage MUST be released/rolled back atomically (referencing the Compensation scenario).

#### Scenario: Release blocked by invalid order state
- **WHEN** a production order release is attempted
- **AND** the order is already in `InProgress` or `Completed` status
- **THEN** the system SHALL NOT proceed with material reservation, quality checklist creation, or status change
- **AND** SHALL return an error denoting the invalid state transition.

### Requirement: Orchestrated Production Order Completion
The system SHALL coordinate the completion of a production order by verifying quality compliance, consuming materials, receiving finished goods, and recording actual costs.

#### Scenario: Successful completion
- **WHEN** a production order is completed
- **AND** all quality inspections have passed
- **THEN** the system SHALL consume reserved materials from Inventory
- **AND** receive the finished goods into the target warehouse
- **AND** calculate and record actual production costs
- **AND** update the production order status to `Completed`
- **AND** dispatch an `OrderCompleted` domain event upon successful completion

### Requirement: Multi-Tenant Context Enforcement
The system MUST ensure that all orchestration operations (Stock check, Quality initialization, Costing) are scoped strictly to the provided `tenantId`.

#### Scenario: Isolation across tenants
- **WHEN** Tenant A releases a production order
- **THEN** the system SHALL ONLY check and reserve stock belonging to Tenant A
- **AND** SHALL ONLY create Quality Control artifacts belonging to Tenant A
- **AND** SHALL NOT leak data or reserve stock from Tenant B
