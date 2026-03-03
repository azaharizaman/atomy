# Capability: ESG Data Lakehouse

## Purpose
TBD: Capturing and governing the raw sustainability data stream from IoT, utility bills, and manual logs.

## Requirements

### Requirement: Raw Sustainability Event Capture
The system SHALL provide a "Lakehouse" interface to capture raw sustainability events from IoT sensors, manual logs, and external utility feeds.

#### Scenario: Ingest IoT Energy Pulse
- **GIVEN** an IoT sensor "METER-001"
- **AND** a raw reading of "150.5" with unit "kWh"
- **WHEN** the reading is pushed to the `SustainabilityData` ingest endpoint
- **THEN** the event is stored with a high-resolution timestamp
- **AND** remains un-normalized until the aggregation pipeline runs

### Requirement: Cross-Domain Event Mapping
The system SHALL allow domain entities (e.g., a specific Machine in `Manufacturing`) to be linked to sustainability data sources.

#### Scenario: Link Machine to Energy Meter
- **GIVEN** Machine "CNC-X1" in the `Manufacturing` package
- **AND** Energy Meter "EM-01"
- **WHEN** a link is established in the metadata registry
- **THEN** all energy events from "EM-01" are automatically attributed to "CNC-X1" for carbon intensity calculations
