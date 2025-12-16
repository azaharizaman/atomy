# Nexus\Sanctions - Package Requirements

**Package**: nexus/sanctions  
**Version**: 1.0.0  
**Status**: 🔵 In Development  
**Domain**: Regulatory Screening (Sanctions & PEP)

---

## 1. Package Identity

| Property | Value |
|----------|-------|
| **Single Responsibility** | Screen parties against sanctions lists and PEP databases |
| **Atomic Domain** | Regulatory Screening (ONE domain) |
| **Framework Agnostic** | ✅ Pure PHP 8.3+ |
| **Target LOC** | ~1,800 lines |
| **Dependencies** | nexus/party, nexus/audit-logger, psr/log |

---

## 2. Functional Requirements

| Code | Requirement | Priority | Status |
|------|-------------|----------|--------|
| **SANC-001** | Screen party name against OFAC sanctions list | ⚠️ Critical | 🔵 Planned |
| **SANC-002** | Screen party name against UN Security Council list | ⚠️ Critical | 🔵 Planned |
| **SANC-003** | Screen party name against EU sanctions list | ⚠️ Critical | 🔵 Planned |
| **SANC-004** | Screen party name against UK HMT sanctions list | ⚠️ Critical | 🔵 Planned |
| **SANC-005** | Support simultaneous multi-list screening | 🔴 High | 🔵 Planned |
| **SANC-006** | Fuzzy name matching with configurable threshold (0-100) | 🔴 High | 🔵 Planned |
| **SANC-007** | Handle international name variations (transliterations) | 🔴 High | 🔵 Planned |
| **SANC-008** | Match on date of birth (DOB) for stronger confirmation | 🟡 Medium | 🔵 Planned |
| **SANC-009** | Match on passport/national ID numbers | 🟡 Medium | 🔵 Planned |
| **SANC-010** | Match on business registration numbers (entities) | 🟡 Medium | 🔵 Planned |
| **SANC-011** | Return match strength score (exact, high, medium, low) | 🔴 High | 🔵 Planned |
| **SANC-012** | Return matched list source (OFAC, UN, EU, UK) | 🔴 High | 🔵 Planned |
| **SANC-013** | Return matched entity details (aliases, addresses) | 🔴 High | 🔵 Planned |
| **SANC-014** | PEP screening - identify politically exposed persons | ⚠️ Critical | 🔵 Planned |
| **SANC-015** | PEP level classification (HIGH/MEDIUM/LOW) | 🔴 High | 🔵 Planned |
| **SANC-016** | PEP position tracking (current and former roles) | 🔴 High | 🔵 Planned |
| **SANC-017** | RCA screening (Relatives & Close Associates of PEPs) | 🟡 Medium | 🔵 Planned |
| **SANC-018** | Periodic re-screening at configurable frequency | 🔴 High | 🔵 Planned |
| **SANC-019** | Schedule re-screening (daily, weekly, monthly, quarterly) | 🔴 High | 🔵 Planned |
| **SANC-020** | Alert on new matches from re-screening | ⚠️ Critical | 🔵 Planned |
| **SANC-021** | Sanctions hit workflow - freeze party account | ⚠️ Critical | 🔵 Planned |
| **SANC-022** | Sanctions hit workflow - notify compliance officer | ⚠️ Critical | 🔵 Planned |
| **SANC-023** | False positive resolution - mark as false positive | 🔴 High | 🔵 Planned |
| **SANC-024** | True positive confirmation - escalate to authority | ⚠️ Critical | 🔵 Planned |
| **SANC-025** | Generate sanctions screening report | 🔴 High | 🔵 Planned |
| **SANC-026** | Export screening results (PDF, Excel) | 🟡 Medium | 🔵 Planned |
| **SANC-027** | Maintain complete screening audit trail | ⚠️ Critical | 🔵 Planned |
| **SANC-028** | Log all screening attempts (match or no-match) | 🔴 High | 🔵 Planned |
| **SANC-029** | Track screening operator and timestamp | 🔴 High | 🔵 Planned |
| **SANC-030** | Support batch screening of multiple parties | 🟡 Medium | 🔵 Planned |
| **SANC-031** | Return screening results within 3 seconds (single party) | 🟡 Medium | 🔵 Planned |
| **SANC-032** | Cache sanctions list data for performance | 🟡 Medium | 🔵 Planned |
| **SANC-033** | Sanctions list update notification | 🔴 High | 🔵 Planned |
| **SANC-034** | Country-based risk score integration | 🟢 Low | 🔵 Planned |
| **SANC-035** | Integration with external sanctions data providers | 🟢 Low | 🔵 Planned |

---

## 3. Non-Functional Requirements

| Code | Requirement | Priority | Status |
|------|-------------|----------|--------|
| **NFR-001** | Framework-agnostic - Pure PHP 8.3+ | ⚠️ Critical | 🔵 Planned |
| **NFR-002** | Stateless architecture - No in-memory state | ⚠️ Critical | 🔵 Planned |
| **NFR-003** | All dependencies injected as interfaces | ⚠️ Critical | 🔵 Planned |
| **NFR-004** | All service classes are `final readonly` | ⚠️ Critical | 🔵 Planned |
| **NFR-005** | Use constructor property promotion | 🔴 High | 🔵 Planned |
| **NFR-006** | `declare(strict_types=1);` in all files | 🔴 High | 🔵 Planned |
| **NFR-007** | Complete PHPDoc on all public methods | 🔴 High | 🔵 Planned |
| **NFR-008** | Unit test coverage >80% | 🔴 High | 🔵 Planned |
| **NFR-009** | No framework facades or global helpers | ⚠️ Critical | 🔵 Planned |
| **NFR-010** | Independently publishable to Packagist | 🔴 High | 🔵 Planned |

---

## 4. Interface Contracts

### Core Interfaces

| Interface | Methods | Purpose |
|-----------|---------|---------|
| **SanctionsScreenerInterface** | `screen()`, `getScreeningHistory()` | Main screening operations |
| **PepScreenerInterface** | `screen()`, `getPepProfile()` | PEP detection |
| **SanctionsRepositoryInterface** | `findMatches()`, `saveScreening()` | Data persistence abstraction |
| **PeriodicScreeningManagerInterface** | `scheduleReScreening()`, `executeScheduled()` | Re-screening automation |

---

## 5. Value Objects

| Value Object | Properties | Immutable |
|--------------|------------|-----------|
| **ScreeningResult** | `isMatch`, `matches[]`, `screenedAt`, `operator` | ✅ Yes |
| **SanctionsMatch** | `listSource`, `matchStrength`, `entityDetails`, `aliases[]` | ✅ Yes |
| **PepProfile** | `isPep`, `pepLevel`, `positions[]`, `country` | ✅ Yes |

---

## 6. Enums

| Enum | Values | Purpose |
|------|--------|---------|
| **SanctionsList** | `OFAC`, `UN`, `EU`, `UK_HMT` | List sources |
| **MatchStrength** | `EXACT`, `HIGH`, `MEDIUM`, `LOW` | Match confidence |
| **PepLevel** | `HIGH`, `MEDIUM`, `LOW` | PEP risk classification |
| **ScreeningFrequency** | `DAILY`, `WEEKLY`, `MONTHLY`, `QUARTERLY` | Re-screening schedule |

---

## 7. Exceptions

| Exception | When Thrown | HTTP Status Equivalent |
|-----------|-------------|----------------------|
| **SanctionsException** | Base exception for all sanctions errors | 500 |
| **ScreeningFailedException** | Screening service unavailable | 503 |
| **InvalidPartyException** | Party ID not found | 404 |
| **SanctionsListUnavailableException** | Cannot fetch sanctions list | 503 |

---

## 8. Dependencies

| Package | Version | Purpose |
|---------|---------|---------|
| **php** | ^8.3 | Language requirement |
| **nexus/party** | ^1.0 | Party identity management |
| **nexus/audit-logger** | ^1.0 | Screening audit trail |
| **psr/log** | ^3.0 | PSR-3 logging |

---

## 9. Integration Points

### With Other Nexus Packages

| Package | Integration Type | Purpose |
|---------|-----------------|---------|
| **nexus/party** | Query | Get party details for screening |
| **nexus/audit-logger** | Event | Log all screening activities |
| **nexus/aml-compliance** | Called By | AML risk scoring uses sanctions results |

### Consumer Implementation

```php
// Consuming application provides concrete repository
namespace App\Repositories\Sanctions;

use Nexus\Sanctions\Contracts\SanctionsRepositoryInterface;

final readonly class EloquentSanctionsRepository implements SanctionsRepositoryInterface
{
    public function findMatches(
        string $name,
        array $lists
    ): array {
        // Eloquent query against sanctions_lists table
    }
}
```

---

## 10. Atomicity Compliance

| Criterion | Compliance | Evidence |
|-----------|------------|----------|
| **Domain-Specific** | ✅ Pass | ONE domain: Regulatory screening |
| **Stateless** | ✅ Pass | No in-memory state, all data externalized |
| **Framework-Agnostic** | ✅ Pass | Pure PHP 8.3+, zero framework dependencies |
| **Logic-Focused** | ✅ Pass | Business rules only, no migrations |
| **Contract-Driven** | ✅ Pass | All dependencies are interfaces |
| **Independently Deployable** | ✅ Pass | Standalone Packagist package |
| **<5,000 LOC** | ✅ Pass | Target: 1,800 LOC (36% of threshold) |
| **<15 Service Classes** | ✅ Pass | 3 services (20% of threshold) |
| **<40 Interface Methods** | ✅ Pass | 8 methods (20% of threshold) |

---

## 11. Testing Strategy

### Unit Tests (Target: 15 tests)

| Test Category | Test Count | Coverage Target |
|---------------|------------|-----------------|
| Sanctions screening logic | 5 tests | 100% |
| PEP detection logic | 3 tests | 100% |
| Fuzzy matching algorithm | 4 tests | 100% |
| Re-screening workflows | 3 tests | 100% |

---

## 12. Performance Targets

| Metric | Target | Measurement |
|--------|--------|-------------|
| **Screening Time** | <3 seconds | Single party, 4 lists |
| **Batch Screening** | 100 parties/minute | With caching |
| **Memory Usage** | <50 MB | Peak during screening |
| **Cache Hit Rate** | >90% | For sanctions list data |

---

## 13. Security Considerations

| Requirement | Implementation |
|-------------|----------------|
| **Data Privacy** | No PII stored in package, only party IDs |
| **Audit Trail** | All screenings logged via AuditLogger |
| **Access Control** | Screening action authorized via Identity package |
| **Encryption** | Screening results encrypted at rest (consumer responsibility) |

---

## 14. Documentation Requirements

| Document | Status |
|----------|--------|
| README.md | ✅ Created |
| REQUIREMENTS.md | ✅ Created (this file) |
| composer.json | ✅ Created |
| LICENSE | 🔵 Planned |
| .gitignore | 🔵 Planned |
| CHANGELOG.md | 🔵 Planned |
| IMPLEMENTATION_SUMMARY.md | 🔵 Planned |
| TEST_SUITE_SUMMARY.md | 🔵 Planned |

---

**Last Updated**: December 16, 2025  
**Approved By**: Nexus Architecture Team  
**Implementation Phase**: Phase 1 (Weeks 1-3)
