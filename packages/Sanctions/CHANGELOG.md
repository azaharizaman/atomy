# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2025-12-16

### Added
- Multi-list sanctions screening (OFAC, UN, EU, UK, AU, CA, JP, CH)
- Advanced fuzzy matching algorithms (Levenshtein, Soundex, Metaphone, token-based)
- PEP detection with FATF-compliant Enhanced Due Diligence (EDD) workflows
- Former PEP identification with 40% risk reduction after 12 months
- Risk-based periodic re-screening (DAILY, WEEKLY, MONTHLY, QUARTERLY, SEMI_ANNUALLY, ANNUALLY, ON_DEMAND)
- Batch processing capabilities with comprehensive error handling
- Performance tracking and detailed logging
- 4 Native PHP Enums: SanctionsList, MatchStrength, PepLevel, ScreeningFrequency
- 3 Immutable Value Objects: SanctionsMatch, PepProfile, ScreeningResult
- 4 Exception classes with factory methods
- 5 Interfaces for atomic architecture (no dependencies on other atomic packages)
- 3 Production-ready services with 1,450 lines of business logic
- 35+ comprehensive unit tests with >80% code coverage
- Comprehensive documentation (11 files)

### Features
- ✅ Multi-jurisdiction sanctions compliance (8 international lists)
- ✅ Advanced name matching with phonetic algorithms
- ✅ PEP classification with position-based risk assessment
- ✅ Related persons screening (family members and close associates)
- ✅ Monitoring frequency determination based on risk levels
- ✅ Batch screening with per-party error isolation
- ✅ Comprehensive validation and business rule enforcement
- ✅ Immutable domain models with rich behavior
- ✅ Type-safe enums with exhaustive match expressions
- ✅ PSR-3 compliant logging throughout

### Architecture
- ✅ True atomicity: Only depends on `nexus/common` and `psr/log`
- ✅ No circular dependencies with other atomic packages
- ✅ Interface-based design for orchestrator integration
- ✅ Independently testable without external package dependencies
- ✅ Framework-agnostic pure PHP 8.3+ implementation

### Regulatory Compliance
- ✅ FATF Recommendation 12 (PEP due diligence + ongoing monitoring)
- ✅ OFAC screening requirements (SDN list + 50% ownership rule)
- ✅ EU sanctions compliance
- ✅ Enhanced Due Diligence (EDD) determination
- ✅ Former PEP risk adjustment per FATF guidelines (12-month threshold)

### Documentation
- Comprehensive README with examples
- LICENSE (MIT)
- CONTRIBUTING guidelines
- SECURITY policy
- CODE_OF_CONDUCT
- IMPLEMENTATION_SUMMARY with metrics
- TEST_SUITE_SUMMARY with coverage details
- VALUATION_MATRIX with quality scores

[Unreleased]: https://github.com/nexus/sanctions/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/nexus/sanctions/releases/tag/v1.0.0
