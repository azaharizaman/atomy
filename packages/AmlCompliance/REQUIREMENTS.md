# Nexus\AmlCompliance - Package Requirements

**Package**: azaharizaman/nexus-aml-compliance  
**Version**: 1.0.0  
**Status**: 🔵 In Development  
**Domain**: Anti-Money Laundering (AML) Risk Assessment

---

## 1. Package Identity

| Property | Value |
|----------|-------|
| **Single Responsibility** | Assess AML risk and detect suspicious financial activity |
| **Atomic Domain** | AML Risk Assessment (ONE domain) |
| **Framework Agnostic** | ✅ Pure PHP 8.3+ |
| **Target LOC** | ~900 lines |
| **Dependencies** | azaharizaman/nexus-party, azaharizaman/nexus-sanctions, psr/log |

---

## 2. Functional Requirements

| Code | Requirement | Priority | Status |
|------|-------------|----------|--------|
| **AML-001** | Calculate AML risk score (0-100) for party | ⚠️ Critical | 🔵 Planned |
| **AML-002** | Risk score formula: Jurisdiction (30%) + Business Type (20%) + Sanctions (25%) + Transactions (25%) | ⚠️ Critical | 🔵 Planned |
| **AML-003** | Classify risk level: LOW (0-39), MEDIUM (40-69), HIGH (70-100) | ⚠️ Critical | 🔵 Planned |
| **AML-004** | Return risk factor breakdown (jurisdiction, business type, sanctions, transactions) | 🔴 High | 🔵 Planned |
| **AML-005** | Jurisdiction risk assessment (high-risk countries list) | 🔴 High | 🔵 Planned |
| **AML-006** | Business type risk classification (MSB, crypto, gambling = high) | 🔴 High | 🔵 Planned |
| **AML-007** | Integrate sanctions screening results into risk score | 🔴 High | 🔵 Planned |
| **AML-008** | Transaction pattern analysis (velocity, structuring) | 🔴 High | 🔵 Planned |
| **AML-009** | Transaction monitoring - detect unusual patterns | ⚠️ Critical | 🔵 Planned |
| **AML-010** | Velocity anomaly detection (too many transactions in short period) | 🔴 High | 🔵 Planned |
| **AML-011** | Amount threshold detection (transactions just below $10,000) | 🔴 High | 🔵 Planned |
| **AML-012** | Geographic anomaly detection (transactions in high-risk countries) | 🔴 High | 🔵 Planned |
| **AML-013** | Return suspicion reasons for flagged transactions | 🔴 High | 🔵 Planned |
| **AML-014** | Generate Suspicious Activity Report (SAR) | ⚠️ Critical | 🔵 Planned |
| **AML-015** | SAR includes: party details, suspicious activities, amount, date | ⚠️ Critical | 🔵 Planned |
| **AML-016** | Assign SAR to compliance officer for review | 🔴 High | 🔵 Planned |
| **AML-017** | SAR status tracking (draft, submitted, closed) | 🔴 High | 🔵 Planned |
| **AML-018** | Reassess risk for all high-risk parties (score >= 70) | 🟡 Medium | 🔵 Planned |
| **AML-019** | Risk history tracking (score changes over time) | 🟡 Medium | 🔵 Planned |
| **AML-020** | Alert on risk level changes (LOW → MEDIUM → HIGH) | 🔴 High | 🔵 Planned |

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
| **AmlRiskAssessorInterface** | `assessParty()`, `reassessHighRiskParties()` | Risk score calculation |
| **TransactionMonitorInterface** | `monitorTransaction()` | Transaction pattern detection |
| **SarGeneratorInterface** | `generateSar()`, `getSar()`, `updateSarStatus()` | SAR lifecycle management |
| **AmlRepositoryInterface** | `saveRiskScore()`, `getRiskHistory()` | Data persistence abstraction |

---

## 5. Value Objects

| Value Object | Properties | Immutable |
|--------------|------------|-----------|
| **AmlRiskScore** | `score` (0-100), `riskLevel` (enum), `factors[]` | ✅ Yes |
| **RiskFactors** | `jurisdictionRisk`, `businessTypeRisk`, `sanctionsRisk`, `transactionRisk` | ✅ Yes |
| **TransactionMonitoringResult** | `isSuspicious`, `reasons[]`, `score` | ✅ Yes |
| **SuspiciousActivityReport** | `sarId`, `partyId`, `reason`, `activities[]`, `amount`, `status` | ✅ Yes |

---

## 6. Enums

| Enum | Values | Purpose |
|------|--------|---------|
| **RiskLevel** | `LOW`, `MEDIUM`, `HIGH` | Risk classification |
| **JurisdictionRisk** | `LOW`, `MEDIUM`, `HIGH`, `VERY_HIGH` | Country risk levels |
| **BusinessTypeRisk** | `LOW`, `MEDIUM`, `HIGH` | Industry risk classification |
| **SarStatus** | `DRAFT`, `SUBMITTED`, `UNDER_REVIEW`, `CLOSED` | SAR lifecycle status |

---

## 7. Exceptions

| Exception | When Thrown | HTTP Status Equivalent |
|-----------|-------------|----------------------|
| **AmlException** | Base exception for all AML errors | 500 |
| **RiskAssessmentFailedException** | Cannot calculate risk score | 500 |
| **InvalidTransactionException** | Transaction data invalid | 400 |
| **SarGenerationFailedException** | Cannot generate SAR | 500 |

---

## 8. Dependencies

| Package | Version | Purpose |
|---------|---------|---------|
| **php** | ^8.3 | Language requirement |
| **azaharizaman/nexus-party** | ^1.0 | Party identity management |
| **azaharizaman/nexus-sanctions** | ^1.0 | Sanctions screening results |
| **psr/log** | ^3.0 | PSR-3 logging |

---

## 9. Integration Points

### With Other Nexus Packages

| Package | Integration Type | Purpose |
|---------|-----------------|---------|
| **azaharizaman/nexus-party** | Query | Get party business type, jurisdiction |
| **azaharizaman/nexus-sanctions** | Query | Get sanctions screening results for risk scoring |
| **azaharizaman/nexus-party-compliance** | Called By | Orchestrates full compliance check including AML |

### Consumer Implementation

```php
// Consuming application provides concrete repository
namespace App\Repositories\Aml;

use Nexus\AmlCompliance\Contracts\AmlRepositoryInterface;

final readonly class EloquentAmlRepository implements AmlRepositoryInterface
{
    public function saveRiskScore(
        string $partyId,
        AmlRiskScore $riskScore
    ): void {
        // Eloquent save to aml_risk_scores table
    }
}
```

---

## 10. Atomicity Compliance

| Criterion | Compliance | Evidence |
|-----------|------------|----------|
| **Domain-Specific** | ✅ Pass | ONE domain: AML risk assessment |
| **Stateless** | ✅ Pass | No in-memory state, all data externalized |
| **Framework-Agnostic** | ✅ Pass | Pure PHP 8.3+, zero framework dependencies |
| **Logic-Focused** | ✅ Pass | Business rules only, no migrations |
| **Contract-Driven** | ✅ Pass | All dependencies are interfaces |
| **Independently Deployable** | ✅ Pass | Standalone Packagist package |
| **<5,000 LOC** | ✅ Pass | Target: 900 LOC (18% of threshold) |
| **<15 Service Classes** | ✅ Pass | 3 services (20% of threshold) |
| **<40 Interface Methods** | ✅ Pass | 6 methods (15% of threshold) |

---

## 11. Testing Strategy

### Unit Tests (Target: 12 tests)

| Test Category | Test Count | Coverage Target |
|---------------|------------|-----------------|
| Risk score calculation | 4 tests | 100% |
| Transaction monitoring | 4 tests | 100% |
| SAR generation | 4 tests | 100% |

---

## 12. Performance Targets

| Metric | Target | Measurement |
|--------|--------|-------------|
| **Risk Assessment Time** | <2 seconds | Single party assessment |
| **Transaction Monitoring** | <1 second | Single transaction check |
| **Memory Usage** | <30 MB | Peak during assessment |

---

## 13. Security Considerations

| Requirement | Implementation |
|-------------|----------------|
| **Data Privacy** | No PII stored in package, only party IDs |
| **Audit Trail** | All risk assessments logged |
| **Access Control** | Assessment action authorized via Identity package |
| **SAR Confidentiality** | SARs encrypted at rest (consumer responsibility) |

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
**Implementation Phase**: Phase 1 (Weeks 4-5)
