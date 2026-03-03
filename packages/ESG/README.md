# Nexus\ESG

Framework-agnostic sustainability truth foundation for the Atomy (Nexus) ERP.

## Overview

This package provides the domain models, value objects, and mathematical utilities for tracking Environmental, Social, and Governance (ESG) metrics. It serves as the single source of truth for sustainability data across the enterprise.

## Key Features

- **Carbon Footprint Modeling**: Standardized tCO2e calculations across Scope 1, 2, and 3.
- **Weighted ESG Scoring**: Multi-criteria decision analysis (MCDA) for composite sustainability ratings.
- **Certification Management**: Tracking and validation of ISO 14001, 45001, and other industry standards.
- **Atomic Value Objects**: Immutable types for emissions, scores, and compliance levels.

## Usage

```php
use Nexus\ESG\ValueObjects\EmissionsAmount;
use Nexus\ESG\Services\CarbonNormalizer;

$amount = new EmissionsAmount(1000, 'kg');
$normalized = $normalizer->normalize($amount); // 1.0 tCO2e
```
