## ADDED Requirements

### Requirement: Unit of Measure Normalization
The system SHALL convert diverse vendor Units of Measure (UoM) to the RFQ's base unit using defined conversion factors, enabling accurate unit price comparison.

#### Scenario: Complex UoM Parsing
- **GIVEN** an RFQ requesting "100 Units" of Pens
- **AND** a vendor quote offering "10 Boxes of 12" at $120.00/Box
- **WHEN** the quote is normalized
- **THEN** the system calculates the effective quantity as `120 Units` (10 * 12)
- **AND** the normalized unit price as `$10.00/Unit` ($120 / 12)

#### Scenario: Incompatible UoM Rejection
- **GIVEN** an RFQ requesting "Liters" of Fuel
- **AND** a vendor quote offering "Kilograms"
- **AND** no density conversion factor is available
- **WHEN** normalization is attempted
- **THEN** the system flags a `UomMismatchException`
- **AND** requests manual intervention for a conversion factor

### Requirement: Multi-Currency Normalization
The system SHALL convert all quoted prices to the tenant's base currency using the exchange rate effective at the time of quote submission or a locked RFQ rate.

#### Scenario: Currency Conversion at Lock Date
- **GIVEN** an RFQ with a locked exchange rate date of `2024-01-01` (1 EUR = 1.10 USD)
- **AND** a vendor quote of €100.00
- **WHEN** the quote is normalized
- **THEN** the normalized price is stored as `$110.00`
- **AND** the original price remains `€100.00`
- **AND** the exchange rate source and timestamp are logged
