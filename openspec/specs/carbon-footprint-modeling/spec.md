# Capability: Carbon Footprint Modeling

## Purpose
TBD: Normalizing and calculating tCO2e across Scope 1, 2, and 3 emissions.

## Requirements

### Requirement: Carbon Normalization
The system SHALL normalize carbon emission data across different units and Scopes (1, 2, 3) into a standard tonnes of CO2 equivalent (tCO2e) basis.

#### Scenario: Convert Kilograms to Tonnes
- **GIVEN** an emission value of 5000 kg
- **WHEN** normalized to tCO2e
- **THEN** the result is 5.0 tCO2e

#### Scenario: Aggregate Across Scopes
- **GIVEN** Scope 1: 10 tCO2e
- **AND** Scope 2: 20 tCO2e
- **AND** Scope 3: 100 tCO2e
- **WHEN** total footprint is calculated
- **THEN** the result is 130 tCO2e
- **AND** the percentage breakdown is provided
