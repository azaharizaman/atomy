## ADDED Requirements

### Requirement: Extraction of ESG Data from Utility Bills
The system SHALL use LLM-based extraction agents to parse PDF utility invoices and environmental permits into structured sustainability metrics.

#### Scenario: Extract kWh from PDF Invoice
- **GIVEN** a PDF invoice from "Tenaga Nasional Berhad"
- **WHEN** processed by the `ESGOperations` pipeline
- **THEN** the system extracts `total_usage: 4500`, `unit: kWh`, and `period: 2026-02`
- **AND** creates an `ExtractionEvidence` snippet linking back to the PDF coordinates

### Requirement: ESG Claim Validation
The system SHALL scan environmental permits and vendor "Green Claims" to validate compliance with regulatory thresholds.

#### Scenario: Validate Permit Threshold
- **GIVEN** an Environmental Permit PDF
- **WHEN** processed by the NLP agent
- **THEN** it identifies an emission limit of "50mg/m3"
- **AND** flags a "Compliance Risk" if current sensor data from `SustainabilityData` exceeds this limit
