## ADDED Requirements

### Requirement: Automated Regulatory Tagging
The system SHALL automatically tag domain metrics with regulatory framework codes (e.g., CSRD, IFRS S2) based on configurable mapping strategies.

#### Scenario: Tag Carbon Emission for CSRD
- **GIVEN** a normalized Carbon Emission metric of 500 tonnes
- **AND** a mapping strategy for "EU CSRD 2025"
- **WHEN** the regulatory mapping engine runs
- **THEN** the metric is tagged with `ESRS E1-6` (Gross Scope 1 GHG emissions)
- **AND** included in the automated disclosure draft

### Requirement: Multi-Framework Mapping
The system SHALL support mapping a single domain metric to multiple concurrent regulatory frameworks.

#### Scenario: Dual Reporting (NSRF and IFRS)
- **GIVEN** a water consumption metric
- **WHEN** processed by the mapping engine
- **THEN** it receives tags for both `Bursa Malaysia NSRF:Water` and `IFRS S2:WaterUsage`
