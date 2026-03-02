## 1. Scaffold ESG Package

- [x] 1.1 Create `packages/ESG` directory structure (src, tests, docs).
- [x] 1.2 Create `composer.json` for `nexus/esg` with `nexus/common` dependency.
- [x] 1.3 Create `README.md` and `IMPLEMENTATION_SUMMARY.md`.
- [x] 1.4 Register package in root `composer.json` and run `composer dump-autoload`.

## 2. Define Core Value Objects

- [x] 2.1 Implement `src/ValueObjects/EmissionsAmount.php` (Encapsulates tCO2e math).
- [x] 2.2 Implement `src/ValueObjects/SustainabilityScore.php` (0-100 range validation).
- [x] 2.3 Implement `src/ValueObjects/CertificationMetadata.php` (Expiry, Issuer).
- [x] 2.4 Implement `src/ValueObjects/Dimension.php` (Enum for Environmental, Social, Governance).

## 3. Implement Scoring Logic

- [x] 3.1 Define `src/Contracts/ScoringEngineInterface.php`.
- [x] 3.2 Implement `src/Services/WeightedScoringEngine.php`.
- [x] 3.3 Implement `src/Strategies/DefaultWeightingStrategy.php` (0.33 each).
- [x] 3.4 Write unit tests for scoring aggregation.

## 4. Implement Carbon Normalization

- [x] 4.1 Define `src/Contracts/CarbonNormalizerInterface.php`.
- [x] 4.2 Implement `src/Services/CarbonNormalizer.php` (Unit conversion: kg -> tonnes).
- [x] 4.3 Implement scope aggregation logic.
- [x] 4.4 Write unit tests for carbon math.

## 5. Certification Validation

- [x] 5.1 Implement `src/Services/CertificationValidator.php`.
- [x] 5.2 Add logic for expiry checking against provided clock.
- [x] 5.3 Write unit tests for certification lifecycle.

## 6. Finalization

- [x] 6.1 Update `packages/ESG/IMPLEMENTATION_SUMMARY.md`.
- [x] 6.2 Run `composer test` in `packages/ESG`.
- [x] 6.3 Ensure zero framework dependencies in `src/`.
