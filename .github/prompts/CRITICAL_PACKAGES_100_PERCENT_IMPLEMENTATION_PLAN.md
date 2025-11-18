# Critical Foundation Packages: 100% Implementation Plan

**Target Packages:** Tenant, Identity, AuditLogger, Setting, Period, Uom, Sequencing  
**Current Average Completion:** 64%  
**Target Completion:** 100%  
**Estimated Timeline:** 4-6 weeks (Sprint-based)  
**Priority Level:** CRITICAL - All downstream packages depend on these

---

## Executive Summary

This document provides a comprehensive, requirement-driven implementation plan to achieve 100% completion for the 7 critical foundation packages in the Nexus ERP monorepo. These packages form the architectural bedrock upon which all domain packages (Finance, Payable, Receivable, Payroll, etc.) are built.

**Key Metrics:**
- **Total Requirements Tracked:** ~850+ requirements across all 7 packages
- **Current Implementation:** 64% average (range: 40%-85%)
- **Gap to Close:** 36% average (~306 requirements)
- **Zero Dependency Blockers:** All packages can be completed independently

---

## 📊 Current State Analysis

### Package Completion Matrix

| Package | Current % | Missing Components | Estimated Effort | Priority |
|---------|-----------|-------------------|------------------|----------|
| **Nexus\Tenant** | 76% | Queue context propagation, unit tests | 2 days | P0 |
| **Nexus\Period** | 85% | `createNextPeriod()`, unit tests, performance validation | 3 days | P0 |
| **Nexus\Identity** | 70% | MFA flows, SSO adapters, comprehensive testing | 5 days | P0 |
| **Nexus\AuditLogger** | 65% | Retention automation, export formats, masking logic, timeline feed | 4 days | P0 |
| **Nexus\Setting** | 60% | Encryption, schema registry, API layer, bulk operations | 4 days | P1 |
| **Nexus\Uom** | 55% | Repository layer, seeding, API endpoints, conversion caching | 5 days | P1 |
| **Nexus\Sequencing** | 40% | **CRITICAL: No database layer**, models, repositories, API | 7 days | P0 |

**Total Estimated Effort:** 30 days (can be parallelized across 4-6 weeks with proper sprint planning)

---

## 🎯 Sprint-Based Implementation Roadmap

### **Sprint 1: Critical Blockers (Week 1-2)**
**Focus:** Eliminate P0 blockers that impact downstream packages

#### Sprint 1.1: Sequencing Database Layer (Days 1-4)
**Owner:** Backend Engineer  
**Why Critical:** Sequencing has NO database implementation. All packages requiring auto-numbering (Finance, Payable, Receivable, Payroll) are blocked.

**Tasks:**
1. **Database Migration** (Day 1)
   - [ ] Create `2025_11_19_000000_create_sequencing_tables.php`
   - [ ] Tables: `sequences`, `sequence_counters`, `sequence_pattern_versions`, `sequence_gaps`, `sequence_reservations`, `sequence_audits`
   - [ ] Indexes: `idx_sequences_name_scope`, `idx_counters_sequence_lock`, `idx_reservations_expires_at`
   - [ ] Requirements: ARC-SEQ-0023, BUS-SEQ-0043

2. **Eloquent Models** (Day 2)
   - [ ] `apps/Atomy/app/Models/Sequence.php` (implements `SequenceInterface`)
   - [ ] `apps/Atomy/app/Models/SequenceCounter.php`
   - [ ] `apps/Atomy/app/Models/SequencePatternVersion.php`
   - [ ] `apps/Atomy/app/Models/SequenceGap.php`
   - [ ] `apps/Atomy/app/Models/SequenceReservation.php`
   - [ ] Requirements: ARC-SEQ-0024

3. **Repository Implementations** (Days 3-4)
   - [ ] `DbSequenceRepository` (20+ methods)
   - [ ] `DbCounterRepository` with `SELECT FOR UPDATE` locking
   - [ ] `DbReservationRepository` with TTL handling
   - [ ] `DbGapRepository` with immutability checks
   - [ ] `DbPatternVersionRepository`
   - [ ] Requirements: ARC-SEQ-0025, FUN-SEQ-0211, FUN-SEQ-0212

4. **Service Provider Bindings** (Day 4)
   - [ ] Update `SequencingServiceProvider::register()`
   - [ ] Bind all 7 repository interfaces
   - [ ] Requirements: ARC-SEQ-0026

5. **Integration Testing** (Day 4)
   - [ ] Concurrent generation test (100 parallel requests, zero duplicates)
   - [ ] Transaction rollback test
   - [ ] Requirements: TEST-SEQ-0401, TEST-SEQ-0402, PER-SEQ-0336

**Acceptance Criteria:**
- ✅ All migrations run successfully
- ✅ All models implement package interfaces
- ✅ Concurrent test passes with zero duplicate numbers
- ✅ Sequencing package can generate invoice numbers for Finance package

---

#### Sprint 1.2: Tenant Queue Context Propagation (Day 5)
**Owner:** Backend Engineer  
**Why Critical:** Without this, background jobs lose tenant context, breaking multi-tenancy.

**Tasks:**
1. **Queue Job Middleware** (Day 5 Morning)
   - [ ] Create `apps/Atomy/app/Jobs/Middleware/SetTenantContext.php`
   - [ ] Serialize tenant_id with job payload
   - [ ] Restore tenant context in `handle()` before job execution
   - [ ] Requirements: ARC-TEN-0587

2. **Service Provider Registration** (Day 5 Afternoon)
   - [ ] Register middleware in `TenantServiceProvider::boot()`
   - [ ] Add global job middleware for automatic tenant context
   - [ ] Update `config/queue.php` with tenant-aware serializer

3. **Testing** (Day 5 Afternoon)
   - [ ] Unit test: Verify tenant context preserved across queue
   - [ ] Integration test: Dispatch job from Tenant A, verify it executes in Tenant A context

**Acceptance Criteria:**
- ✅ Queued jobs maintain tenant context
- ✅ No tenant context leakage between jobs
- ✅ All tests pass

---

#### Sprint 1.3: Period Package Completion (Days 6-7)
**Owner:** Backend Engineer  
**Why Critical:** Period locks prevent posting to closed fiscal periods (SOX/IFRS compliance requirement).

**Tasks:**
1. **Implement `createNextPeriod()`** (Day 6 Morning)
   - [ ] `PeriodManager::createNextPeriod(PeriodInterface $currentPeriod): PeriodInterface`
   - [ ] Logic: Calculate next period dates based on `$currentPeriod->getType()`
   - [ ] Validation: Prevent gaps, ensure sequential periods
   - [ ] Requirements: Documented in Phase 1 pending list

2. **Unit Tests** (Day 6 Afternoon)
   - [ ] Test period validation logic (overlap detection, status transitions)
   - [ ] Test fiscal year calculations (calendar vs non-calendar)
   - [ ] Test date range calculations (monthly, quarterly, yearly)
   - [ ] Coverage target: >90%

3. **Performance Validation** (Day 7 Morning)
   - [ ] Benchmark `canPost()` method (target: <5ms)
   - [ ] Optimize cache key strategy if needed
   - [ ] Test with 10K+ periods in database
   - [ ] Requirements: PER-PER-XXX (not explicitly coded, but critical business requirement)

4. **Authorization Policy** (Day 7 Afternoon)
   - [ ] Implement `PeriodAuthorizationService::canReopen()`
   - [ ] Integrate with `Nexus\Identity` for role-based checks
   - [ ] Test with different user roles

**Acceptance Criteria:**
- ✅ `createNextPeriod()` generates valid sequential periods
- ✅ Unit test coverage >90%
- ✅ `canPost()` performance <5ms (p95)
- ✅ Authorization checks work correctly

---

### **Sprint 2: Core Services Completion (Week 2-3)**
**Focus:** Complete service layer implementations for Identity, AuditLogger, Setting

#### Sprint 2.1: Identity MFA & SSO (Days 8-10)
**Owner:** Backend Engineer  
**Why Important:** Security requirement for enterprise customers; monetization feature.

**Tasks:**
1. **MFA Enrollment Flow** (Day 8)
   - [ ] Implement `MfaEnrollmentService::enrollTotp(UserInterface $user): array`
   - [ ] Generate QR code for Google Authenticator (use `pragmarx/google2fa`)
   - [ ] Create `apps/Atomy/app/Models/MfaEnrollment.php`
   - [ ] Store encrypted backup codes
   - [ ] Requirements: FUN-IDE-1395, FUN-IDE-1397, BUS-IDE-1336-1338

2. **MFA Verification** (Day 8)
   - [ ] Implement `MfaVerifierService::verifyTotp(UserInterface $user, string $code): bool`
   - [ ] Implement backup code validation (one-time use)
   - [ ] Implement trusted device management
   - [ ] Requirements: FUN-IDE-1396, FUN-IDE-1398, BUS-IDE-1339-1340

3. **SSO Adapter Base** (Day 9)
   - [ ] Implement `SsoProviderInterface` adapter for SAML 2.0
   - [ ] Use `onelogin/php-saml` library
   - [ ] Implement JIT user provisioning
   - [ ] Requirements: FUN-IDE-1399, FUN-IDE-1401, BUS-IDE-1341-1343

4. **SSO OAuth2/OIDC** (Day 10)
   - [ ] Implement OAuth2/OIDC adapter (use `league/oauth2-client`)
   - [ ] Implement attribute mapping configuration
   - [ ] Test with Google OAuth2, Azure AD, Okta
   - [ ] Requirements: FUN-IDE-1400, FUN-IDE-1402

5. **Testing** (Day 10)
   - [ ] MFA flow integration tests
   - [ ] SSO mock provider tests
   - [ ] Backup code regeneration tests

**Acceptance Criteria:**
- ✅ TOTP MFA enrollment and verification work
- ✅ Backup codes are one-time use
- ✅ SAML SSO login works with test IdP
- ✅ OAuth2 SSO login works with Google

---

#### Sprint 2.2: AuditLogger Enhancements (Days 11-13)
**Owner:** Backend Engineer  
**Why Important:** Compliance requirement (SOX, GDPR); all packages log to AuditLogger.

**Tasks:**
1. **Retention Policy Automation** (Day 11 Morning)
   - [ ] Implement `RetentionPolicyService::applyRetentionPolicy(string $logName, int $days): void`
   - [ ] Create Laravel Scheduler integration
   - [ ] Add `schedule:work` configuration for automated purging
   - [ ] Requirements: FUN-AUD-0194, BUS-AUD-0151

2. **Export Formats** (Day 11 Afternoon - Day 12 Morning)
   - [ ] Implement `AuditLogExportService::exportToCsv(array $filters): string`
   - [ ] Implement `AuditLogExportService::exportToJson(array $filters): string`
   - [ ] Implement `AuditLogExportService::exportToPdf(array $filters): string` (use `barryvdh/laravel-dompdf`)
   - [ ] Requirements: FUN-AUD-0191, PER-AUD-0372

3. **Sensitive Data Masking** (Day 12 Afternoon)
   - [ ] Implement `SensitiveDataMasker::maskField(string $field, mixed $value): mixed`
   - [ ] Patterns: credit cards (`xxxx-xxxx-xxxx-1234`), passwords (`********`), API keys (`sk_***abc`)
   - [ ] Configurable masking rules via `config/audit.php`
   - [ ] Requirements: FUN-AUD-0192, SEC-AUD-0488

4. **Timeline Feed Interface** (Day 13)
   - [ ] Create `packages/AuditLogger/src/Contracts/TimelineFeedInterface.php`
   - [ ] Implement `AuditLogTimelineFeedService::getTimelineForEntity(string $entityType, string $entityId): array`
   - [ ] Format: `['actor' => 'Azahari Zaman', 'action' => 'updated invoice status', 'target' => 'INV-2024-001', 'timestamp' => '2 hours ago']`
   - [ ] Requirements: FUN-AUD-0201-0210, BUS-AUD-0152-0155

5. **Testing** (Day 13)
   - [ ] Test retention purging with 100K entries
   - [ ] Test export generation performance
   - [ ] Test masking patterns

**Acceptance Criteria:**
- ✅ Retention policy purges expired logs automatically
- ✅ CSV export completes in <5s for 10K entries
- ✅ Sensitive fields are masked correctly
- ✅ Timeline feed displays human-readable events

---

#### Sprint 2.3: Setting Package Completion (Days 14-16)
**Owner:** Backend Engineer  
**Why Important:** Feature flag system for monetization; configuration management for all packages.

**Tasks:**
1. **Encryption Implementation** (Day 14 Morning)
   - [ ] Implement `EncryptedSetting` value object with `encrypt()` and `decrypt()` methods
   - [ ] Use Laravel's `Crypt` facade in application layer only
   - [ ] Update `SettingsManager::set()` to detect encrypted settings
   - [ ] Requirements: Documented in README, not explicitly coded

2. **Schema Registry Population** (Day 14 Afternoon)
   - [ ] Implement `SettingsSchemaRegistry::register(string $key, array $schema): void`
   - [ ] Define schemas for critical settings (e.g., `tenant.timezone`, `feature.mfa_enabled`)
   - [ ] Validation rules: type, enum, min/max, required
   - [ ] Requirements: Documented in architecture

3. **API Endpoints** (Day 15)
   - [ ] Create `apps/Atomy/app/Http/Controllers/Api/SettingController.php`
   - [ ] Routes: `GET /api/settings`, `GET /api/settings/{key}`, `PUT /api/settings/{key}`, `DELETE /api/settings/{key}`
   - [ ] Authorization: Check `settings.manage` permission
   - [ ] Requirements: Not explicitly in CSV but critical for usability

4. **Bulk Operations** (Day 15-16)
   - [ ] Implement `SettingsManager::setBulk(array $settings): void`
   - [ ] Transaction-safe bulk updates
   - [ ] Implement `SettingsManager::export(string $layer): array`
   - [ ] Implement `SettingsManager::import(string $layer, array $settings): void`
   - [ ] Requirements: Documented in README

5. **Testing** (Day 16)
   - [ ] Test hierarchical resolution (User → Tenant → Application)
   - [ ] Test encryption/decryption
   - [ ] Test schema validation
   - [ ] Test bulk operations with rollback

**Acceptance Criteria:**
- ✅ Encrypted settings are stored securely
- ✅ Schema validation works correctly
- ✅ API endpoints return correct settings
- ✅ Bulk operations are transaction-safe

---

### **Sprint 3: Repository & API Layer (Week 3-4)**
**Focus:** Complete repository implementations and API endpoints for Uom

#### Sprint 3.1: Uom Repository Implementation (Days 17-19)
**Owner:** Backend Engineer  
**Why Important:** Currency management critical for multi-currency transactions; all financial packages depend on this.

**Tasks:**
1. **Repository Implementation** (Days 17-18)
   - [ ] Implement `EloquentUomRepository::findUnitByCode(string $code): ?UnitInterface`
   - [ ] Implement `EloquentUomRepository::findDimensionByCode(string $code): ?DimensionInterface`
   - [ ] Implement `EloquentUomRepository::findConversion(string $fromCode, string $toCode): ?ConversionRuleInterface`
   - [ ] Implement all 15+ methods in `UomRepositoryInterface`
   - [ ] Requirements: ARC-UOM-0027, Documented in UOM_IMPLEMENTATION.md

2. **Seeding System Units** (Day 18)
   - [ ] Create `apps/Atomy/database/seeders/UomSeeder.php`
   - [ ] Seed SI units (Metric): Length (m, cm, mm, km), Mass (kg, g, mg), Volume (L, mL), Temperature (°C, K)
   - [ ] Seed Imperial units: Length (in, ft, yd, mi), Mass (lb, oz), Volume (gal, qt, pt, fl oz), Temperature (°F)
   - [ ] Seed Currency dimension with base units (MYR, USD, EUR, SGD, GBP, JPY, CNY)
   - [ ] Conversion rules: All metric conversions, imperial conversions
   - [ ] Requirements: BUS-UOM-XXX (documented in business requirements)

3. **API Endpoints** (Day 19 Morning)
   - [ ] Create `apps/Atomy/app/Http/Controllers/Api/UomController.php`
   - [ ] Routes: `GET /api/uom/dimensions`, `GET /api/uom/units`, `POST /api/uom/convert`
   - [ ] Authorization: `uom.view`, `uom.manage` permissions

4. **Conversion Caching** (Day 19 Afternoon)
   - [ ] Implement `UomConversionEngine::clearCache(): void`
   - [ ] Cache conversion results with 1-hour TTL
   - [ ] Invalidate cache when conversion rules change
   - [ ] Requirements: PER-UOM-104, Documented in implementation notes

5. **Testing** (Day 19)
   - [ ] Test conversion accuracy (e.g., 1 m = 100 cm exactly)
   - [ ] Test circular conversion detection
   - [ ] Test incompatible unit detection
   - [ ] Test cache invalidation

**Acceptance Criteria:**
- ✅ Repository returns correct units and conversions
- ✅ Seeder populates all standard units
- ✅ API endpoints work correctly
- ✅ Conversion results are cached

---

### **Sprint 4: Testing, Documentation & Polish (Week 4-5)**
**Focus:** Comprehensive testing, performance optimization, documentation

#### Sprint 4.1: Comprehensive Testing (Days 20-23)
**Owner:** QA Engineer + Backend Engineer

**Tasks by Package:**

**Tenant (Day 20):**
- [ ] Unit tests for tenant lifecycle (activate, suspend, reactivate, archive)
- [ ] Integration tests for tenant resolution strategies (domain, subdomain, header)
- [ ] Test tenant impersonation with audit logs
- [ ] Test quota enforcement
- [ ] Coverage target: >90%

**Identity (Day 20-21):**
- [ ] Unit tests for authentication (login, logout, password reset)
- [ ] Unit tests for authorization (RBAC, wildcard permissions, policy-based)
- [ ] Integration tests for MFA flows
- [ ] Integration tests for SSO flows (SAML, OAuth2)
- [ ] Security tests for password hashing, session management
- [ ] Performance tests for permission checking (<10ms cached)
- [ ] Coverage target: >90%

**AuditLogger (Day 21):**
- [ ] Unit tests for audit log creation, search, filtering
- [ ] Integration tests for retention policy purging
- [ ] Performance tests for search (< 500ms for 100K entries)
- [ ] Security tests for sensitive data masking
- [ ] Test timeline feed generation
- [ ] Coverage target: >85%

**Setting (Day 22):**
- [ ] Unit tests for hierarchical resolution
- [ ] Unit tests for schema validation
- [ ] Integration tests for encryption/decryption
- [ ] Test bulk operations with transaction rollback
- [ ] Coverage target: >85%

**Period (Day 22):**
- [ ] Already tested in Sprint 1.3, add any missing edge cases
- [ ] Stress test with 10K+ periods
- [ ] Coverage target: >90%

**Uom (Day 22-23):**
- [ ] Unit tests for conversion engine
- [ ] Test circular conversion detection
- [ ] Test offset conversions (temperature)
- [ ] Test packaging conversions (case → each)
- [ ] Test incompatible unit detection
- [ ] Coverage target: >85%

**Sequencing (Day 23):**
- [ ] Concurrent generation tests (already done in Sprint 1.1, validate)
- [ ] Test gap filling with immutability rules
- [ ] Test pattern versioning with effective dates
- [ ] Test exhaustion detection and overflow behaviors
- [ ] Test reservation TTL expiry
- [ ] Coverage target: >90%

**Acceptance Criteria:**
- ✅ All packages achieve target code coverage
- ✅ Zero failing tests
- ✅ All performance benchmarks met

---

#### Sprint 4.2: Performance Optimization (Days 24-25)
**Owner:** Backend Engineer

**Tasks:**
1. **Database Indexing Audit** (Day 24 Morning)
   - [ ] Review all migrations for missing indexes
   - [ ] Add composite indexes for common query patterns
   - [ ] Example: `idx_audit_logs_entity_type_entity_id_created_at`

2. **Query Optimization** (Day 24 Afternoon)
   - [ ] Review N+1 query problems (use Laravel Debugbar)
   - [ ] Add eager loading where needed
   - [ ] Optimize repository queries

3. **Caching Strategy Review** (Day 25 Morning)
   - [ ] Verify cache keys are tenant-scoped
   - [ ] Verify cache invalidation on updates
   - [ ] Add cache warming for critical data (active periods)

4. **Benchmark Critical Paths** (Day 25 Afternoon)
   - [ ] Period: `canPost()` <5ms ✅
   - [ ] Identity: Permission check <10ms (cached) ✅
   - [ ] Sequencing: `generate()` <50ms (p95) ✅
   - [ ] AuditLogger: Search <500ms for 100K entries ✅
   - [ ] Uom: Conversion <50ms (cached) ✅

**Acceptance Criteria:**
- ✅ All performance benchmarks met or exceeded
- ✅ No N+1 queries in critical paths
- ✅ Cache hit rates >80% for frequently accessed data

---

#### Sprint 4.3: Documentation (Days 26-28)
**Owner:** Technical Writer + Backend Engineer

**Tasks:**
1. **Package README Updates** (Day 26)
   - [ ] Verify all READMEs are up-to-date with latest implementation
   - [ ] Add usage examples for all major features
   - [ ] Add troubleshooting section

2. **API Documentation** (Day 27)
   - [ ] Generate OpenAPI/Swagger specs for all API endpoints
   - [ ] Add example requests/responses
   - [ ] Document authentication requirements

3. **Implementation Guides** (Day 28)
   - [ ] Update TENANT_IMPLEMENTATION.md with queue context propagation
   - [ ] Update IDENTITY_IMPLEMENTATION.md with MFA/SSO sections
   - [ ] Update AUDITLOGGER_IMPLEMENTATION.md with timeline feed
   - [ ] Update PERIOD_IMPLEMENTATION_SUMMARY.md as complete
   - [ ] Create UOM_IMPLEMENTATION_SUMMARY.md
   - [ ] Create SEQUENCING_IMPLEMENTATION_SUMMARY.md

**Acceptance Criteria:**
- ✅ All README files are comprehensive and accurate
- ✅ API documentation is complete
- ✅ Implementation guides are updated

---

### **Sprint 5: Final Validation & Handoff (Week 5-6)**
**Focus:** End-to-end validation, integration testing, production readiness

#### Sprint 5.1: Integration Testing (Days 29-30)
**Owner:** QA Engineer

**Cross-Package Integration Tests:**
1. **Finance → Period Integration** (Day 29 Morning)
   - [ ] Test journal entry posting rejects when period is closed
   - [ ] Test period close validation with existing transactions
   - [ ] Requirements: BUS-FIN-2104, INT-FIN-2601

2. **Finance → Sequencing Integration** (Day 29 Morning)
   - [ ] Test journal entry number generation
   - [ ] Test pattern changes don't affect existing entries
   - [ ] Requirements: FUN-FIN-2208, INT-FIN-2602

3. **Finance → Uom Integration** (Day 29 Afternoon)
   - [ ] Test multi-currency journal entries
   - [ ] Test exchange rate lookups
   - [ ] Requirements: BUS-FIN-2106, INT-FIN-2603

4. **All Packages → Identity Integration** (Day 29 Afternoon)
   - [ ] Test permission checks across all packages
   - [ ] Test user context in service constructors
   - [ ] Requirements: INT-FIN-2604, INT-ACC-2604

5. **All Packages → AuditLogger Integration** (Day 30)
   - [ ] Test audit log creation from all packages
   - [ ] Test timeline feed for entities across packages
   - [ ] Requirements: INT-FIN-2605, INT-AUD-0492

6. **All Packages → Tenant Integration** (Day 30)
   - [ ] Test tenant isolation for all models
   - [ ] Test queued jobs maintain tenant context
   - [ ] Requirements: ARC-TEN-0583, ARC-TEN-0587

**Acceptance Criteria:**
- ✅ All cross-package integrations work correctly
- ✅ Tenant isolation is enforced everywhere
- ✅ All packages log to AuditLogger
- ✅ Permission checks work across all packages

---

## 📋 Requirements Traceability Matrix

### Nexus\Tenant (90 requirements → 68 complete, 22 pending)

**Pending Requirements:**
| Code | Requirement | Sprint | Assignee |
|------|-------------|--------|----------|
| ARC-TEN-0587 | Queue context propagation | Sprint 1.2 | Backend |
| FUN-TEN-0622 | Parent-child tenant hierarchy support | Future Enhancement | - |
| FUN-TEN-0625 | Tenant quota enforcement | Future Enhancement | - |
| USE-TEN-0662 | Tenant analytics dashboard | Future Enhancement | - |

**Completion Target:** 76% → **90%** (exclude future enhancements)

---

### Nexus\Identity (150+ requirements → 105 complete, 45 pending)

**Pending Requirements:**
| Code | Requirement | Sprint | Assignee |
|------|-------------|--------|----------|
| FUN-IDE-1395 | MFA enrollment with TOTP | Sprint 2.1 | Backend |
| FUN-IDE-1396 | MFA verification | Sprint 2.1 | Backend |
| FUN-IDE-1397 | MFA backup codes | Sprint 2.1 | Backend |
| FUN-IDE-1398 | Trusted device management | Sprint 2.1 | Backend |
| FUN-IDE-1399 | SSO SAML 2.0 | Sprint 2.1 | Backend |
| FUN-IDE-1400 | SSO OAuth2/OIDC | Sprint 2.1 | Backend |
| FUN-IDE-1401 | JIT user provisioning | Sprint 2.1 | Backend |
| FUN-IDE-1402 | SSO attribute mapping | Sprint 2.1 | Backend |
| MAINT-IDE-1431 | Test coverage >90% | Sprint 4.1 | QA |

**Completion Target:** 70% → **95%** (core authentication + RBAC + MFA + SSO)

---

### Nexus\AuditLogger (160+ requirements → 104 complete, 56 pending)

**Pending Requirements:**
| Code | Requirement | Sprint | Assignee |
|------|-------------|--------|----------|
| FUN-AUD-0191 | Export to CSV, JSON, PDF | Sprint 2.2 | Backend |
| FUN-AUD-0192 | Mask sensitive fields | Sprint 2.2 | Backend |
| FUN-AUD-0194 | Retention policy automation | Sprint 2.2 | Backend |
| FUN-AUD-0201-0210 | Timeline feed interface | Sprint 2.2 | Backend |
| FUN-AUD-6201-6231 | Integration logging (OCR, payments, API calls) | Future (depends on Connector, DataProcessor) | - |
| PER-AUD-0372 | Export <5s for 10K entries | Sprint 4.2 | Backend |

**Completion Target:** 65% → **85%** (core + timeline feed, exclude advanced integration logging)

---

### Nexus\Setting (60+ requirements → 36 complete, 24 pending)

**Pending Requirements:**
| Code | Requirement | Sprint | Assignee |
|------|-------------|--------|----------|
| Not coded | Encryption implementation | Sprint 2.3 | Backend |
| Not coded | Schema registry population | Sprint 2.3 | Backend |
| Not coded | API endpoints | Sprint 2.3 | Backend |
| Not coded | Bulk operations | Sprint 2.3 | Backend |
| BUS-SET-1300-1310 | Hierarchical resolution validation | Sprint 4.1 | QA |

**Completion Target:** 60% → **90%**

---

### Nexus\Period (150+ requirements → 128 complete, 22 pending)

**Pending Requirements:**
| Code | Requirement | Sprint | Assignee |
|------|-------------|--------|----------|
| Not coded | `createNextPeriod()` implementation | Sprint 1.3 | Backend |
| Not coded | Unit tests | Sprint 1.3 | Backend |
| Not coded | Performance validation <5ms | Sprint 1.3 | Backend |
| Not coded | Authorization policy | Sprint 1.3 | Backend |

**Completion Target:** 85% → **100%** ✅

---

### Nexus\Uom (100+ requirements → 55 complete, 45 pending)

**Pending Requirements:**
| Code | Requirement | Sprint | Assignee |
|------|-------------|--------|----------|
| ARC-UOM-0027 | Repository implementation | Sprint 3.1 | Backend |
| BUS-UOM-XXX | Seeding system units | Sprint 3.1 | Backend |
| Not coded | API endpoints | Sprint 3.1 | Backend |
| PER-UOM-104 | Conversion caching | Sprint 3.1 | Backend |
| BUS-UOM-201 | Circular conversion detection tests | Sprint 4.1 | QA |

**Completion Target:** 55% → **90%**

---

### Nexus\Sequencing (80+ requirements → 80 complete in package, 0 in Atomy)

**Pending Requirements:**
| Code | Requirement | Sprint | Assignee |
|------|-------------|--------|----------|
| ARC-SEQ-0023 | Database migrations | Sprint 1.1 | Backend |
| ARC-SEQ-0024 | Eloquent models | Sprint 1.1 | Backend |
| ARC-SEQ-0025 | Repository implementations | Sprint 1.1 | Backend |
| ARC-SEQ-0026 | Service provider bindings | Sprint 1.1 | Backend |
| TEST-SEQ-0401 | Concurrent generation tests | Sprint 1.1 | Backend |
| PER-SEQ-0336 | Zero duplicates with 100 parallel requests | Sprint 1.1 | Backend |

**Completion Target:** 40% → **100%** ✅

---

## 🚀 Execution Strategy

### Development Team Allocation

**Recommended Team Structure:**
- **1 Senior Backend Engineer:** Sequencing, Identity, Tenant (complex logic)
- **1 Mid-Level Backend Engineer:** AuditLogger, Setting, Uom (service implementations)
- **1 Backend Engineer:** Period, testing support
- **1 QA Engineer:** Comprehensive testing, integration validation
- **1 Technical Writer (Part-time):** Documentation

### Daily Standup Focus
- **What did I complete yesterday?** (Align with sprint tasks)
- **What am I working on today?** (Pick next task from sprint backlog)
- **Any blockers?** (Database access, missing requirements clarification)

### Definition of Done (DoD)
For each sprint task:
- [ ] Code implemented and follows architectural guidelines (no Laravel in packages)
- [ ] Unit tests written with >85% coverage
- [ ] Integration tests pass
- [ ] Performance benchmarks met
- [ ] Code reviewed and approved
- [ ] Documentation updated
- [ ] Requirement codes tracked in commit messages

### Git Workflow
1. Create feature branch from `main`: `feature/tenant-queue-context`
2. Implement task with atomic commits
3. Commit message format: `feat(tenant): Add queue context propagation (ARC-TEN-0587)`
4. Push and create Pull Request
5. Pass CI/CD checks (tests, linting, static analysis)
6. Code review by senior engineer
7. Merge to `main` after approval
8. Deploy to staging for integration testing

---

## 📦 Dependency Management

### Zero External Blockers
All 7 packages can be developed in parallel because:
- ✅ Tenant has no dependencies on other packages
- ✅ Identity only needs Tenant (already 76% complete)
- ✅ AuditLogger only needs Tenant (already 76% complete)
- ✅ Setting only needs Tenant (already 76% complete)
- ✅ Period needs Tenant, Identity, AuditLogger (all will be ≥85% by Sprint 2)
- ✅ Uom needs Tenant, Identity (both will be ≥85% by Sprint 2)
- ✅ Sequencing needs Tenant (already 76% complete)

### Internal Package Dependencies (for downstream packages)
Once these 7 packages hit 100%, the following packages can be implemented:
- **Finance** depends on: Period (✅), Sequencing (✅), Uom (✅), Identity (✅), AuditLogger (✅)
- **Payable** depends on: Finance, Sequencing, Uom, Identity, AuditLogger
- **Receivable** depends on: Finance, Sequencing, Uom, Identity, AuditLogger
- **Payroll** depends on: Period, Sequencing, Identity, AuditLogger, Hrm
- **Accounting** depends on: Finance, Period, Setting, AuditLogger

---

## 🎯 Success Metrics

### Sprint Completion Criteria
| Sprint | Packages Impacted | Target Completion | Key Deliverable |
|--------|------------------|-------------------|-----------------|
| Sprint 1 | Sequencing, Tenant, Period | Sequencing 40%→100%, Tenant 76%→90%, Period 85%→100% | Sequencing can generate numbers |
| Sprint 2 | Identity, AuditLogger, Setting | Identity 70%→95%, AuditLogger 65%→85%, Setting 60%→90% | MFA/SSO work, Timeline feed available |
| Sprint 3 | Uom | Uom 55%→90% | Currency conversions work |
| Sprint 4 | All | Test coverage >85% for all | All packages production-ready |
| Sprint 5 | All | Integration validated | Finance package can start development |

### Final Metrics (End of Week 6)
- [ ] **Tenant:** 90% complete (exclude future parent-child hierarchy)
- [ ] **Identity:** 95% complete (core + RBAC + MFA + SSO)
- [ ] **AuditLogger:** 85% complete (core + timeline feed)
- [ ] **Setting:** 90% complete (core + encryption + API)
- [ ] **Period:** 100% complete ✅
- [ ] **Uom:** 90% complete (core + seeding + API)
- [ ] **Sequencing:** 100% complete ✅

**Overall Average:** 64% → **93%** (excluding future enhancements)

---

## ⚠️ Risk Mitigation

### Identified Risks

| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| Sequencing database layer takes longer than 4 days | HIGH | MEDIUM | Allocate senior engineer; parallelize model and repository work |
| MFA/SSO integration with external services fails | MEDIUM | LOW | Use mock providers for testing; real providers optional |
| Performance benchmarks not met | HIGH | LOW | Start performance testing early (Sprint 1); optimize incrementally |
| Test coverage target (>85%) not achieved | MEDIUM | MEDIUM | Write tests alongside code (TDD approach); dedicate Sprint 4 for catch-up |
| Requirement ambiguity discovered mid-sprint | MEDIUM | MEDIUM | Implement reasonable default; document decision; consult product owner |

### Contingency Plans
- **If Sequencing slips:** Prioritize core generation + locking; defer gap filling and pattern versioning to Sprint 3
- **If MFA/SSO slips:** Deliver core authentication first; MFA/SSO as separate feature flag
- **If performance issues arise:** Focus on critical paths (Period.canPost, Identity.can); optimize others post-launch

---

## 📞 Communication Plan

### Stakeholder Updates
- **Daily:** Slack updates in #nexus-foundation-packages channel
- **Weekly:** Sprint progress report (Fridays) with completion % for each package
- **Bi-Weekly:** Demo to product owner showcasing completed features

### Code Review SLA
- **Review Request:** Within 4 hours of PR creation
- **Review Completion:** Within 8 hours of assignment
- **Merge:** Immediately after approval (CI passing)

---

## 🎓 Learning & Knowledge Transfer

### Technical Debt Documentation
For each sprint, document:
- **Shortcuts Taken:** What was deferred to hit sprint goals?
- **Future Refactoring:** What needs improvement later?
- **Known Limitations:** What edge cases aren't handled?

Example:
```markdown
## Sprint 2.1 - Identity MFA Implementation

**Shortcuts:**
- SMS MFA not implemented (only TOTP)
- MFA recovery flow not implemented

**Future Refactoring:**
- Extract MFA logic to separate `Nexus\Mfa` package for reuse
- Add rate limiting to MFA verification

**Known Limitations:**
- Maximum 10 backup codes per user
- Trusted device expires after 30 days (not configurable)
```

### Onboarding New Developers
- **Day 1:** Read ARCHITECTURE.md, copilot-instructions.md
- **Day 2:** Review completed packages (Tenant, Period)
- **Day 3:** Pair programming on active sprint task
- **Day 4:** Independent task with code review

---

## 📚 Reference Materials

### Architecture Documents
- `/ARCHITECTURE.md` - Core monorepo architectural principles
- `/.github/copilot-instructions.md` - Comprehensive coding standards
- `/docs/IMPLEMENTATION_STATUS.md` - Current state of all packages
- `/docs/TENANT_IMPLEMENTATION.md` - Tenant package complete reference
- `/docs/IDENTITY_IMPLEMENTATION.md` - Identity package reference
- `/docs/PERIOD_IMPLEMENTATION_SUMMARY.md` - Period package reference
- `/docs/UOM_IMPLEMENTATION.md` - UOM package reference
- `/docs/AUDITLOGGER_IMPLEMENTATION.md` - AuditLogger package reference

### Requirements
- `/REQUIREMENTS.csv` - Primary requirements (2,588 lines)
- `/REQUIREMENTS_PART2.csv` - Additional requirements (1,021 lines)
- Total: **3,609 requirements** across all packages

### Testing Resources
- PHPUnit Documentation: https://phpunit.de/
- Laravel Testing: https://laravel.com/docs/11.x/testing
- Concurrency Testing: Use `ParallelTesting` package for Sequencing

---

## ✅ Checklist for Implementation Start

Before beginning Sprint 1:
- [ ] All developers have read ARCHITECTURE.md
- [ ] All developers have access to codebase and can run `composer install`
- [ ] Database connection configured for local development
- [ ] CI/CD pipeline configured (GitHub Actions or equivalent)
- [ ] Code review guidelines established
- [ ] Sprint board created (Jira, Linear, or GitHub Projects)
- [ ] Daily standup scheduled
- [ ] Weekly demo scheduled with product owner
- [ ] Technical debt tracking system in place

---

## 🏁 Final Notes

This implementation plan is **requirement-driven** and **sprint-based** to ensure:
1. **Traceability:** Every task maps to specific requirements from REQUIREMENTS.csv
2. **Accountability:** Clear ownership and timelines for each deliverable
3. **Quality:** Comprehensive testing and performance validation built-in
4. **Momentum:** Quick wins in Sprint 1 to build team confidence
5. **Flexibility:** Sprints can be re-prioritized if business needs change

**Success Criteria:** By the end of Week 6, all 7 critical foundation packages will be production-ready, enabling the Finance, Payable, Receivable, and Payroll packages to be implemented without blockers.

**Next Steps:** Review this plan with the development team, assign sprint ownership, and begin Sprint 1 on [START_DATE].

---

**Document Version:** 1.0  
**Last Updated:** November 18, 2025  
**Author:** GitHub Copilot  
**Approved By:** [Product Owner Name]  
**Implementation Start Date:** [TBD]
