## ADDED Requirements

### Requirement: Weighted ESG Scoring
The system SHALL aggregate multiple sustainability metrics into a unified ESG score using configurable weights per dimension.

#### Scenario: Calculate Composite Score
- **GIVEN** an Environmental score of 80 (weight 0.5)
- **AND** a Social score of 70 (weight 0.3)
- **AND** a Governance score of 90 (weight 0.2)
- **WHEN** the composite score is calculated
- **THEN** the result is 79.0 ((80*0.5) + (70*0.3) + (90*0.2))

#### Scenario: Default Weights
- **GIVEN** no custom weights are provided
- **WHEN** a score is calculated
- **THEN** the system uses equal weights (0.33 each)
- **AND** flags the score as using "Standard Default Weights"
