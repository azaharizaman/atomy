# CRITICAL FOUNDATION PACKAGES: IMPLEMENTATION DIRECTIVE

Execute 100% implementation of 7 critical foundation packages: Tenant, Identity, AuditLogger, Setting, Period, Uom, Sequencing.

---

## ARCHITECTURAL CONSTRAINTS (MANDATORY)

1. **Framework Agnosticism**: NO Laravel dependencies in packages/*/src/. All framework code in apps/Atomy/.
2. **Interface-Driven**: All data structures via interfaces. All persistence via repository interfaces.
3. **IoC Binding**: All implementations bound in apps/Atomy/app/Providers/.
4. **Database Layer**: Migrations, models, repositories ONLY in apps/Atomy/.
5. **Composer Independence**: Package composer.json MUST NOT depend on laravel/framework.

Consult .github/copilot-instructions.md and ARCHITECTURE.md for complete architectural rules.

---

## IMPLEMENTATION PRIORITY MATRIX

| Package | Current % | Target % | Priority | Blocking Status |
|---------|-----------|----------|----------|-----------------|
| Sequencing | 40% | 100% | P0 | CRITICAL BLOCKER - No database layer exists |
| Period | 85% | 100% | P0 | Missing createNextPeriod(), tests |
| Tenant | 76% | 90% | P0 | Missing queue context propagation |
| Identity | 70% | 95% | P0 | Missing MFA/SSO |
| AuditLogger | 65% | 85% | P1 | Missing retention automation, exports, timeline feed |
| Setting | 60% | 90% | P1 | Missing encryption, API layer |
| Uom | 55% | 90% | P1 | Missing repository layer, seeding, API |

---

## PHASE 1: CRITICAL BLOCKERS (P0)

### 1.1 SEQUENCING DATABASE LAYER (4 days)

**Objective**: Implement complete database persistence layer.

**Tasks**:
1. Create migration: `2025_11_19_000000_create_sequencing_tables.php`
   - Tables: sequences, sequence_counters, sequence_pattern_versions, sequence_gaps, sequence_reservations, sequence_audits
   - Indexes: idx_sequences_name_scope, idx_counters_sequence_lock, idx_reservations_expires_at

2. Create Eloquent models in apps/Atomy/app/Models/:
   - Sequence.php (implements SequenceInterface)
   - SequenceCounter.php, SequencePatternVersion.php, SequenceGap.php, SequenceReservation.php

3. Implement repositories in apps/Atomy/app/Repositories/:
   - DbSequenceRepository, DbCounterRepository (with SELECT FOR UPDATE locking)
   - DbReservationRepository (TTL handling), DbGapRepository (immutability), DbPatternVersionRepository

4. Update apps/Atomy/app/Providers/SequencingServiceProvider.php with all bindings

5. Test: 100 parallel requests MUST generate zero duplicates

**Requirements**: ARC-SEQ-0023 to ARC-SEQ-0026, FUN-SEQ-0211, FUN-SEQ-0212, PER-SEQ-0336, TEST-SEQ-0401

---

### 1.2 TENANT QUEUE CONTEXT PROPAGATION (1 day)

**Objective**: Preserve tenant context across queued jobs.

**Tasks**:
1. Create apps/Atomy/app/Jobs/Middleware/SetTenantContext.php
   - Serialize tenant_id with job payload
   - Restore context before handle()

2. Register in TenantServiceProvider::boot()

3. Test: Job dispatched from Tenant A executes in Tenant A context

**Requirements**: ARC-TEN-0587

---

### 1.3 PERIOD COMPLETION (2 days)

**Objective**: Complete Period package to 100%.

**Tasks**:
1. Implement PeriodManager::createNextPeriod(PeriodInterface): PeriodInterface
   - Calculate dates based on period type
   - Prevent gaps, ensure sequential periods

2. Unit tests: overlap detection, status transitions, fiscal year calculations (>90% coverage)

3. Performance validation: canPost() <5ms (p95)

4. Implement PeriodAuthorizationService::canReopen() with Nexus\Identity integration

**Requirements**: Documented in docs/PERIOD_IMPLEMENTATION_SUMMARY.md

---

## PHASE 2: CORE SERVICES (P0-P1)

### 2.1 IDENTITY MFA & SSO (3 days)

**Tasks**:
1. Implement MfaEnrollmentService::enrollTotp() using pragmarx/google2fa
   - Generate QR code, store encrypted backup codes
   - Create apps/Atomy/app/Models/MfaEnrollment.php

2. Implement MfaVerifierService::verifyTotp()
   - Backup code validation (one-time use)
   - Trusted device management

3. Implement SsoProviderInterface for SAML 2.0 (onelogin/php-saml)
   - JIT user provisioning

4. Implement OAuth2/OIDC adapter (league/oauth2-client)
   - Test with Google OAuth2

**Requirements**: FUN-IDE-1395 to FUN-IDE-1402, BUS-IDE-1336 to BUS-IDE-1343

---

### 2.2 AUDITLOGGER ENHANCEMENTS (3 days)

**Tasks**:
1. Implement RetentionPolicyService::applyRetentionPolicy()
   - Laravel Scheduler integration for automated purging

2. Implement AuditLogExportService
   - exportToCsv(), exportToJson(), exportToPdf() (use barryvdh/laravel-dompdf)

3. Implement SensitiveDataMasker::maskField()
   - Patterns: credit cards (xxxx-xxxx-xxxx-1234), passwords (********), API keys (sk_***abc)

4. Implement AuditLogTimelineFeedService::getTimelineForEntity()
   - Format: {actor, action, target, timestamp}

**Requirements**: FUN-AUD-0191, FUN-AUD-0192, FUN-AUD-0194, FUN-AUD-0201 to FUN-AUD-0210, SEC-AUD-0488

---

### 2.3 SETTING COMPLETION (3 days)

**Tasks**:
1. Implement EncryptedSetting value object with encrypt()/decrypt()
   - Use Laravel Crypt in application layer only

2. Implement SettingsSchemaRegistry::register()
   - Define schemas: tenant.timezone, feature.mfa_enabled
   - Validation: type, enum, min/max, required

3. Create apps/Atomy/app/Http/Controllers/Api/SettingController.php
   - Routes: GET /api/settings, GET /api/settings/{key}, PUT /api/settings/{key}, DELETE /api/settings/{key}
   - Authorization: settings.manage permission

4. Implement SettingsManager::setBulk(), export(), import()
   - Transaction-safe bulk updates

**Requirements**: Documented in packages/Setting/README.md

---

## PHASE 3: REPOSITORY & API LAYER (P1)

### 3.1 UOM REPOSITORY IMPLEMENTATION (3 days)

**Tasks**:
1. Implement EloquentUomRepository
   - findUnitByCode(), findDimensionByCode(), findConversion()
   - All 15+ methods in UomRepositoryInterface

2. Create apps/Atomy/database/seeders/UomSeeder.php
   - SI units: m, cm, mm, km, kg, g, mg, L, mL, °C, K
   - Imperial: in, ft, yd, mi, lb, oz, gal, qt, pt, fl oz, °F
   - Currency: MYR, USD, EUR, SGD, GBP, JPY, CNY
   - All metric/imperial conversion rules

3. Create apps/Atomy/app/Http/Controllers/Api/UomController.php
   - Routes: GET /api/uom/dimensions, GET /api/uom/units, POST /api/uom/convert
   - Authorization: uom.view, uom.manage

4. Implement conversion caching with 1-hour TTL
   - Cache invalidation on rule changes

**Requirements**: ARC-UOM-0027, PER-UOM-104, Documented in docs/UOM_IMPLEMENTATION.md

---

## PHASE 4: TESTING & VALIDATION

### 4.1 COMPREHENSIVE TESTING (4 days)

**Coverage Targets**:
- Tenant: >90% (lifecycle, resolution, impersonation, quota)
- Identity: >90% (auth, RBAC, MFA, SSO, performance: permission check <10ms cached)
- AuditLogger: >85% (creation, search, retention, masking, timeline, performance: search <500ms for 100K entries)
- Setting: >85% (hierarchical resolution, schema validation, encryption, bulk operations)
- Period: >90% (validation, fiscal calculations, stress test with 10K+ periods)
- Uom: >85% (conversion accuracy, circular detection, incompatible units)
- Sequencing: >90% (concurrency, gap filling, pattern versioning, exhaustion detection)

**Integration Tests**:
- Finance → Period: posting rejects when period closed
- Finance → Sequencing: journal entry number generation
- Finance → Uom: multi-currency transactions
- All → Identity: permission checks
- All → AuditLogger: audit log creation
- All → Tenant: isolation enforcement, queue context preservation

**Requirements**: TEST-* codes in REQUIREMENTS.csv, performance benchmarks in PER-* codes

---

### 4.2 PERFORMANCE OPTIMIZATION (2 days)

**Benchmarks (MANDATORY)**:
- Period.canPost(): <5ms (p95)
- Identity.can(): <10ms cached
- Sequencing.generate(): <50ms (p95)
- AuditLogger.search(): <500ms for 100K entries
- Uom.convert(): <50ms cached

**Tasks**:
1. Database indexing audit - add composite indexes
2. Eliminate N+1 queries - add eager loading
3. Cache strategy review - verify tenant scoping
4. Benchmark all critical paths

---

### 4.3 DOCUMENTATION (3 days)

**Tasks**:
1. Update package READMEs with latest implementation
2. Generate OpenAPI/Swagger specs for all API endpoints
3. Update implementation guides:
   - TENANT_IMPLEMENTATION.md (queue context)
   - IDENTITY_IMPLEMENTATION.md (MFA/SSO)
   - AUDITLOGGER_IMPLEMENTATION.md (timeline feed)
   - PERIOD_IMPLEMENTATION_SUMMARY.md (complete status)
   - Create UOM_IMPLEMENTATION_SUMMARY.md
   - Create SEQUENCING_IMPLEMENTATION_SUMMARY.md

---

## EXECUTION RULES

### Code Standards
1. Use strict types: `declare(strict_types=1);`
2. Constructor property promotion with readonly
3. Native PHP enums for fixed values
4. Match expressions instead of switch
5. PHP 8 attributes instead of DocBlock annotations
6. Type hints for all parameters and return types

### Git Workflow
1. Branch naming: `feature/package-name-feature-description`
2. Commit format: `feat(package): Description (REQUIREMENT-CODE)`
3. Atomic commits - one logical change per commit
4. PR after each phase completion

### Definition of Done
- [ ] Code follows architectural constraints
- [ ] Unit tests written with target coverage
- [ ] Integration tests pass
- [ ] Performance benchmarks met
- [ ] Documentation updated
- [ ] Requirement codes tracked in commits

---

## DEPENDENCY CHAIN

Execute in order to avoid blockers:

**Phase 1 (Parallel)**:
- Sequencing (depends only on Tenant 76%)
- Tenant queue context (isolated task)
- Period completion (depends on Tenant, Identity, AuditLogger - all >65%)

**Phase 2 (Parallel)**:
- Identity MFA/SSO (depends on Tenant 90%)
- AuditLogger enhancements (depends on Tenant 90%)
- Setting completion (depends on Tenant 90%)

**Phase 3**:
- Uom (depends on Tenant 90%, Identity 95%)

**Phase 4**:
- Testing (depends on all packages complete)
- Documentation (depends on testing complete)

---

## SUCCESS CRITERIA

**Package Completion**:
- Tenant: 76% → 90%
- Identity: 70% → 95%
- AuditLogger: 65% → 85%
- Setting: 60% → 90%
- Period: 85% → 100%
- Uom: 55% → 90%
- Sequencing: 40% → 100%

**Overall**: 64% → 93%

**Validation**:
- All tests pass
- All performance benchmarks met
- Zero architectural violations
- Finance package can start development without blockers

---

## REFERENCES

- ARCHITECTURE.md - Core architectural principles
- .github/copilot-instructions.md - Coding standards (MANDATORY READ)
- REQUIREMENTS.csv - Primary requirements (2,588 lines)
- REQUIREMENTS_PART2.csv - Additional requirements (1,021 lines)
- docs/IMPLEMENTATION_STATUS.md - Current state
- docs/*_IMPLEMENTATION*.md - Package-specific guides

---

**DIRECTIVE**: Begin implementation immediately. Prioritize Phase 1 tasks. Execute with strict adherence to architectural constraints. Report completion of each phase before proceeding to next. Maintain requirement traceability in all commits.
