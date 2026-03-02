## ADDED Requirements

### Requirement: Pricing Anomaly Detection
The system SHALL identify and flag pricing outliers that deviate significantly from historical norms or peer group averages (e.g., >50% variance).

#### Scenario: Predatory Pricing Flag
- **GIVEN** a historical average unit price of $100.00 for "Industrial Pump"
- **AND** a new vendor quote of $25.00 (-75%)
- **WHEN** the risk assessment pipeline runs
- **THEN** the line item is flagged with `Risk: Extreme Low Price`
- **AND** a warning "Possible quality issue or counterfeit" is attached

#### Scenario: Overpricing Alert
- **GIVEN** peer bids averaging $50.00
- **AND** one vendor quoting $200.00 (+300%)
- **WHEN** the risk assessment runs
- **THEN** the line item is flagged `Risk: High Price Variance`

### Requirement: Commercial Terms Analysis
The system SHALL analyze the "General Conditions" section of quotes to identify deviations from requested commercial terms (Incoterms, Payment Terms).

#### Scenario: Incoterm Mismatch
- **GIVEN** an RFQ requesting `DDP` (Delivered Duty Paid)
- **AND** a vendor quote specifying `EXW` (Ex Works) in fine print
- **WHEN** the terms analysis runs
- **THEN** the quote is flagged with `Risk: Incoterm Deviation`
- **AND** the specific clause "Ex Works Warehouse" is highlighted
