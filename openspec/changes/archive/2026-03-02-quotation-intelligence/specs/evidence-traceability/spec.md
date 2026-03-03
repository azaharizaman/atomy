## ADDED Requirements

### Requirement: Evidence Traceability
The system SHALL capture and store the precise location (page, coordinates, text) of every extracted data point as an immutable `ExtractionEvidence` object, enabling visual audit trails.

#### Scenario: Visual Audit Link
- **GIVEN** an extracted unit price of $1,500.00
- **AND** the source PDF "Quote1.pdf"
- **WHEN** the extraction completes
- **THEN** an `ExtractionEvidence` VO is created with `page: 2`, `bbox: [100, 200, 50, 20]`
- **AND** the UI can render a highlight overlay on the PDF at those coordinates

### Requirement: Immutable Audit Logging
Any manual modification to an AI-extracted value SHALL be logged with a reference to the original AI value, the user who changed it, and the timestamp, ensuring GAAP/IFRS compliance.

#### Scenario: Manual Correction Audit
- **GIVEN** an AI-extracted quantity of "10" (confidence 0.6)
- **WHEN** a user manually corrects it to "100"
- **THEN** the system stores the new value "100" as `verified_value`
- **AND** retains "10" as `ai_value`
- **AND** creates an `AuditLog` entry: "User X changed Quantity from 10 to 100"
