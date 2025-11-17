# Changelog

All notable changes to the Nexus\Sequencing package will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2025-11-17

### Added

#### Core Features
- **Framework-agnostic sequence generation engine** with pure PHP business logic
- **Atomic number generation** using database-level locking (`SELECT FOR UPDATE`)
- **Transaction-safe counter management** with automatic rollback support
- **Flexible pattern system** supporting built-in and custom variables:
  - Built-in: `{YEAR}`, `{YY}`, `{MONTH}`, `{DAY}`, `{COUNTER}`
  - Custom context variables (e.g., `{DEPARTMENT}`, `{BRANCH}`)
  - Optional padding syntax (e.g., `{COUNTER:5}` for zero-padded numbers)

#### Counter Management
- **Multiple reset strategies**:
  - Never (continuous incrementing)
  - Daily, Monthly, Yearly (time-based resets)
  - Count-based reset limits
- **Manual counter override** with validation (must be greater than current value)
- **Counter lock/unlock** functionality to prevent generation during audits
- **Preview mode** to see next number without consuming counter

#### Advanced Features
- **Bulk generation** with optimized single-lock approach for batch operations
- **Number reservation system** with configurable TTL
- **Gap management** with three policies:
  - `allow_gaps`: Default behavior
  - `fill_gaps`: Reuse voided/cancelled numbers
  - `report_gaps_only`: Track but don't fill
- **Pattern versioning** with effective date ranges
- **Automatic pattern migration** when counters approach exhaustion
- **Exhaustion monitoring** with configurable thresholds (default: 90%)

#### Repository Interfaces
- `SequenceRepositoryInterface` - Sequence CRUD operations
- `CounterRepositoryInterface` - Counter state management with locking
- `ReservationRepositoryInterface` - Number reservation management
- `GapRepositoryInterface` - Gap tracking and reclamation
- `PatternVersionRepositoryInterface` - Pattern version management
- `SequenceAuditInterface` - Comprehensive audit logging

#### Value Objects
- `ResetPeriod` - Enum for reset period types
- `GapPolicy` - Enum for gap handling policies
- `OverflowBehavior` - Enum for counter overflow handling
- `PatternVariable` - Immutable representation of pattern variables
- `SequenceMetrics` - Comprehensive sequence metrics and statistics

#### Services
- `SequenceManager` - Main public API for sequence operations
- `PatternParser` - Pattern parsing and number generation
- `CounterService` - Counter state management and validation
- `BulkGeneratorService` - Optimized bulk number generation
- `GapManager` - Gap tracking and reclamation
- `ReservationService` - Number reservation with TTL
- `PatternVersionManager` - Time-based pattern versioning
- `PatternMigrationService` - Automatic pattern migration
- `ExhaustionMonitor` - Counter exhaustion detection and alerting
- `SequenceValidationService` - Pattern syntax and collision detection
- `SequenceMetricsService` - Comprehensive metrics collection

#### Domain Exceptions
- `SequenceNotFoundException` - Sequence lookup failures
- `SequenceLockedException` - Operations on locked sequences
- `SequenceExhaustedException` - Counter exhaustion scenarios
- `InvalidPatternException` - Pattern syntax errors
- `InvalidCounterValueException` - Invalid counter operations
- `NoActivePatternException` - Missing active pattern version
- `PatternVersionConflictException` - Overlapping pattern versions
- `CounterOverflowException` - Counter overflow scenarios
- `PatternCollisionException` - Pattern collision detection
- `ReservationExpiredException` - Expired reservation operations
- `InvalidResetPeriodException` - Invalid reset period values
- `InvalidGapPolicyException` - Invalid gap policy values
- `InvalidOverflowBehaviorException` - Invalid overflow behavior values

### Laravel Implementation (apps/Atomy)

#### Models
- `Sequence` - Eloquent model implementing `SequenceInterface`
- `SequenceCounter` - Counter state with automatic ULID generation
- `SequenceReservation` - Reservation tracking with scopes
- `SequenceGap` - Gap tracking with status
- `SequencePatternVersion` - Pattern version history

#### Repositories
- `DbSequenceRepository` - Eloquent implementation
- `DbCounterRepository` - Counter management with `lockForUpdate()`
- `DbReservationRepository` - Reservation CRUD operations
- `DbGapRepository` - Gap management with query scopes
- `DbPatternVersionRepository` - Version management with date validation

#### Service Provider
- `SequencingServiceProvider` - Complete IoC container bindings
- Configuration publishing support
- Migration publishing support

#### Database
- Complete migration with all tables:
  - `sequences` - Main sequence definitions
  - `sequence_counters` - Counter state
  - `sequence_reservations` - Reserved numbers
  - `sequence_gaps` - Gap tracking
  - `sequence_pattern_versions` - Pattern versioning
- Proper foreign key constraints and indexes
- Composite unique constraint on `name` + `scope_identifier`

#### Configuration
- Default sequence settings
- Predefined sequences (invoice, purchase order, quotation)
- Audit logging toggle
- Lock timeout configuration
- Reservation TTL defaults

#### Audit Logging
- `SequenceAuditLogger` - Laravel Log integration
- Comprehensive event logging:
  - Pattern creation and modification
  - Counter resets and overrides
  - Exhaustion threshold alerts
  - Pattern version changes
  - Number generation (debug level)
  - Gap reclamation
  - Lock status changes

### Business Rules Implemented

- ✅ Sequence name + scope identifier composite unique key
- ✅ Generated numbers are immutable once consumed
- ✅ Pattern variable padding enforced (e.g., `{COUNTER:5}` → `00001`)
- ✅ Manual override must be greater than last generated number
- ✅ Counter incremented only after successful lock and generation
- ✅ Preview does not increment counter
- ✅ Package generates base identifier; sub-identifiers are app responsibility
- ✅ Gap filling respects immutability rules
- ✅ Reserved numbers count toward exhaustion detection
- ✅ Pattern version changes don't reset counter unless configured

### Performance Characteristics

- Number generation: < 50ms (excluding queue operations)
- Bulk generation (100 numbers): < 100ms with single lock
- Concurrent request handling via database-level locking
- Optimized for high-volume ERP deployments

### Documentation

- Comprehensive README.md with architecture overview
- Usage examples for all major features
- Business rules documentation
- Integration guide for Laravel applications

## [0.1.0] - 2025-11-17

### Added
- Initial skeleton structure
- Core interfaces and contracts
- Basic value objects
- Empty service classes
- Database schema design

[Unreleased]: https://github.com/nexus/monorepo/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/nexus/monorepo/releases/tag/v1.0.0
[0.1.0]: https://github.com/nexus/monorepo/releases/tag/v0.1.0
