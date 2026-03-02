# Capability: Semantic Mapping

## Purpose
TBD: The AI-driven process of mapping extracted line items to standard taxonomies (UNSPSC/eCl@ss).

## Requirements

### Requirement: AI-Driven Taxonomy Classification
The system SHALL use machine learning models to classify extracted line item descriptions into standard UNSPSC (or configured taxonomy) codes.

#### Scenario: High-Confidence Mapping
- **GIVEN** a line item description "Dell Latitude 5420 Laptop 14-inch"
- **WHEN** the description is processed by the classification service
- **THEN** the system assigns UNSPSC code `43211503` (Notebook computers)
- **AND** the confidence score is > 0.95
- **AND** the mapping is marked as `auto-verified`

#### Scenario: Low-Confidence Mapping (HITL Trigger)
- **GIVEN** an ambiguous description "Project Alpha Supply Pack"
- **WHEN** the description is processed
- **THEN** the system assigns the closest match `44120000` (Office Supplies)
- **AND** the confidence score is 0.45
- **AND** the mapping is marked as `requires-review`
- **AND** an alert is raised for the procurement manager

### Requirement: Taxonomy Code Validation
The system SHALL validate that all mapped codes exist within the active version of the tenant's taxonomy set.

#### Scenario: Validate Code Existence
- **GIVEN** an extracted code `99999999`
- **WHEN** validated against the UNSPSC v25 database
- **THEN** the system flags the code as invalid
- **AND** suggests the nearest valid parent category
