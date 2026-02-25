## ADDED Requirements

### Requirement: Real-time Cost Aggregation
The system SHALL aggregate material, labor, and overhead costs to calculate the real-time Cost of Goods Sold (COGS) for a production order.

#### Scenario: Aggregate costs for active order
- **WHEN** cost aggregation is requested for an active production order
- **THEN** the system SHALL fetch material costs from the Inventory domain
- **AND** fetch labor costs from the Human Resource domain (via Attendance/Payroll interfaces)
- **AND** apply overhead rates as defined in the Manufacturing domain
- **AND** return a consolidated cost DTO

### Requirement: Unit of Measure Normalization
The system SHALL use the `UomServiceInterface` to normalize all material quantities and prices to a standard base unit before cost calculation.

#### Scenario: Handle multiple UoMs
- **WHEN** materials are recorded in 'kg' but costed in 'g'
- **THEN** the system SHALL perform the conversion before aggregation to ensure mathematical accuracy
