## ADDED Requirements

### Requirement: Recursive BOM Resolution
The system SHALL recursively resolve multi-level Bill of Materials (BOM) into a flat list of raw material requirements.

#### Scenario: Resolve two-level BOM
- **WHEN** the system reconciles a production order for a finished good with sub-assemblies
- **THEN** it SHALL resolve all sub-assemblies down to their raw material components
- **AND** aggregate the total quantity required for each unique raw material

### Requirement: Availability Assessment
The system SHALL check the aggregated material requirements against current stock levels via the `StockProviderInterface`.

#### Scenario: Identify shortages
- **WHEN** the aggregated requirements exceed the available stock for a component
- **THEN** the system SHALL mark that component as a 'Shortage'
- **AND** provide the exact quantity needed to fulfill the requirement
