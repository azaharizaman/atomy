# Requirements: CRM (Atomic Package Layer)

**Package:** `Nexus\CRM`  
**Version:** 1.0  
**Last Updated:** 2026-02-19  
**Total Requirements:** 142

---

## Package Boundary Definition

This requirements document defines the **atomic package layer** for `Nexus\CRM` - a stateless, framework-agnostic CRM domain logic engine.

### What Belongs in Package Layer (This Document)

- ✅ **Lead Management**: Lead entity, status state machine, scoring algorithms
- ✅ **Opportunity Management**: Opportunity entity, stage transitions, forecasting
- ✅ **Pipeline Management**: Pipeline configuration, stage definitions
- ✅ **Activity Tracking**: Activity entity, duration tracking, completion status
- ✅ **Lead Scoring Engine**: Configurable scoring factors and weights
- ✅ **Stage Transition Service**: Opportunity stage validation and transitions
- ✅ **Entity Contracts**: 12 interfaces for Lead, Opportunity, Pipeline, Activity (Query/Persist CQRS)
- ✅ **Value Objects**: LeadScore, PipelineStage, ForecastProbability, ActivityDuration
- ✅ **Enums**: LeadStatus, LeadSource, OpportunityStage, ActivityType, PipelineStatus
- ✅ **Business Rules**: Lead qualification, opportunity stage transitions, pipeline validation

### What Does NOT Belong in This Package

- ❌ **Database Schema**: Migrations, indexes, foreign keys (application layer)
- ❌ **API Endpoints**: REST routes, controllers (application layer)
- ❌ **Cross-Package Workflows**: Lead-to-opportunity conversion workflows (CRMOperations orchestrator)
- ❌ **Assignment Logic**: User assignment, delegation chains (CRMOperations orchestrator)
- ❌ **Notification Systems**: Email alerts, SLA notifications (CRMOperations orchestrator)
- ❌ **Dashboard Aggregation**: Cross-entity statistics (CRMOperations orchestrator)
- ❌ **External Integrations**: Marketing automation sync (CRMOperations orchestrator)
- ❌ **Customer/Contact Management**: Customer entity, party relationships (Party package)
- ❌ **Product Catalog**: Products, services, pricing (Product package)
- ❌ **Quotation Management**: Quotes, proposals (Sales package)
- ❌ **Document Generation**: Proposals, contracts, templates (Document package)

### Architectural References

- **Core Principles**: `.github/copilot-instructions.md`
- **Architecture Guidelines**: `ARCHITECTURE.md`
- **Package Reference**: `docs/NEXUS_PACKAGES_REFERENCE.md`

### Integration Points

This package is consumed by:
- `Nexus\CRMOperations` - Orchestrator for cross-package CRM workflows

---

## Requirements

| Package Namespace | Requirements Type | Code | Requirement Statements | Files/Folders | Status | Notes on Status | Date Last Updated |
|-------------------|-------------------|------|------------------------|---------------|--------|-----------------|-------------------|
| **ARCHITECTURAL REQUIREMENTS** |
| `Nexus\CRM` | Architectural | ARC-CRM-0001 | Package MUST be framework-agnostic with zero dependencies on Laravel, Symfony, or any web framework | composer.json, src/ | ✅ Complete | No Illuminate\* imports | 2026-02-20 |
| `Nexus\CRM` | Architectural | ARC-CRM-0002 | Package composer.json MUST require only: php:^8.3 and nexus/common | composer.json | ✅ Complete | Minimal dependencies | 2026-02-20 |
| `Nexus\CRM` | Architectural | ARC-CRM-0003 | All entity data structures MUST be defined via interfaces (LeadInterface, OpportunityInterface, PipelineInterface, ActivityInterface) | Contracts/ | ✅ Complete | - | 2026-02-20 |
| `Nexus\CRM` | Architectural | ARC-CRM-0004 | All persistence operations MUST use CQRS repository interfaces (Query/Persist separation) | Contracts/ | ✅ Complete | Separate read/write | 2026-02-20 |
| `Nexus\CRM` | Architectural | ARC-CRM-0005 | Business logic MUST be concentrated in service layer with readonly injected dependencies | Services/ | ✅ Complete | - | 2026-02-20 |
| `Nexus\CRM` | Architectural | ARC-CRM-0006 | All value objects MUST be immutable readonly classes with constructor validation | ValueObjects/ | ✅ Complete | - | 2026-02-20 |
| `Nexus\CRM` | Architectural | ARC-CRM-0007 | All enums MUST be native PHP enums with helper methods for business logic | Enums/ | ✅ Complete | - | 2026-02-20 |
| `Nexus\CRM` | Architectural | ARC-CRM-0008 | All files MUST use declare(strict_types=1) and constructor property promotion with readonly modifiers | src/ | ✅ Complete | - | 2026-02-20 |
| `Nexus\CRM` | Architectural | ARC-CRM-0009 | Package MUST be stateless - no session state, no class-level mutable properties, all state externalized via repository interfaces | src/ | ✅ Complete | - | 2026-02-20 |
| `Nexus\CRM` | Architectural | ARC-CRM-0010 | All domain exceptions MUST extend base CRMException with factory methods for context-rich error creation | Exceptions/ | ✅ Complete | - | 2026-02-20 |
| **BUSINESS RULES - LEAD** |
| `Nexus\CRM` | Business Rule | BUS-CRM-0011 | Lead status transitions MUST follow defined state machine: New → Contacted → Qualified → Converted/Disqualified | Enums/LeadStatus.php | ✅ Complete | getValidTransitions() | 2026-02-19 |
| `Nexus\CRM` | Business Rule | BUS-CRM-0012 | Only qualified leads (status = Qualified) can be converted to opportunities | Enums/LeadStatus.php | ✅ Complete | isConvertible() | 2026-02-19 |
| `Nexus\CRM` | Business Rule | BUS-CRM-0013 | Lead scores MUST be between 0 and 100 inclusive | ValueObjects/LeadScore.php | ✅ Complete | Constructor validation | 2026-02-19 |
| `Nexus\CRM` | Business Rule | BUS-CRM-0014 | Lead source MUST be categorized as Organic, Relationship, Outbound, Paid, Social, or Uncategorized | Enums/LeadSource.php | ✅ Complete | getCategory() | 2026-02-19 |
| `Nexus\CRM` | Business Rule | BUS-CRM-0015 | Lead scoring weights MUST sum to 100 for proper weighted average calculation | Services/LeadScoringEngine.php | ✅ Complete | applyWeights() | 2026-02-19 |
| `Nexus\CRM` | Business Rule | BUS-CRM-0016 | High-quality leads MUST have score >= 70, medium-quality 40-69, low-quality < 40 | ValueObjects/LeadScore.php | ✅ Complete | Quality tier methods | 2026-02-19 |
| `Nexus\CRM` | Business Rule | BUS-CRM-0017 | Lead scores older than configured hours MUST be flagged for recalculation | ValueObjects/LeadScore.php | ✅ Complete | needsRecalculation() | 2026-02-19 |
| **BUSINESS RULES - OPPORTUNITY** |
| `Nexus\CRM` | Business Rule | BUS-CRM-0018 | Opportunity stage transitions MUST be sequential - cannot skip stages forward | Services/StageTransitionService.php | ✅ Complete | validateTransition() | 2026-02-19 |
| `Nexus\CRM` | Business Rule | BUS-CRM-0019 | Opportunity cannot transition backwards without explicit reopen operation | Services/StageTransitionService.php | ✅ Complete | cannotGoBackwards() | 2026-02-19 |
| `Nexus\CRM` | Business Rule | BUS-CRM-0020 | Closed won opportunities cannot transition to any stage except closed lost (and vice versa requires reopen) | Services/StageTransitionService.php | ✅ Complete | isFinal() check | 2026-02-19 |
| `Nexus\CRM` | Business Rule | BUS-CRM-0021 | Each opportunity stage MUST have a default probability percentage for forecasting | Enums/OpportunityStage.php | ✅ Complete | getDefaultProbability() | 2026-02-19 |
| `Nexus\CRM` | Business Rule | BUS-CRM-0022 | Weighted value MUST be calculated as deal value multiplied by probability percentage | ValueObjects/ForecastProbability.php | ✅ Complete | calculateWeightedValue() | 2026-02-19 |
| `Nexus\CRM` | Business Rule | BUS-CRM-0023 | Opportunity can be closed as won or lost from any open stage | Services/StageTransitionService.php | ✅ Complete | getValidNextStages() | 2026-02-19 |
| `Nexus\CRM` | Business Rule | BUS-CRM-0024 | Forecast probability MUST be between 0 and 100 inclusive | ValueObjects/ForecastProbability.php | ✅ Complete | Constructor validation | 2026-02-19 |
| **BUSINESS RULES - PIPELINE** |
| `Nexus\CRM` | Business Rule | BUS-CRM-0025 | Pipeline stages MUST have position >= 1 | ValueObjects/PipelineStage.php | ✅ Complete | Constructor validation | 2026-02-19 |
| `Nexus\CRM` | Business Rule | BUS-CRM-0026 | Pipeline stage probability MUST be between 0 and 100 inclusive | ValueObjects/PipelineStage.php | ✅ Complete | Constructor validation | 2026-02-19 |
| `Nexus\CRM` | Business Rule | BUS-CRM-0027 | Pipeline with probability 100% is a win stage, 0% is a loss stage | ValueObjects/PipelineStage.php | ✅ Complete | isWinStage(), isLossStage() | 2026-02-19 |
| `Nexus\CRM` | Business Rule | BUS-CRM-0028 | Archived pipelines cannot transition to any other status | Enums/PipelineStatus.php | ✅ Complete | getValidTransitions() | 2026-02-19 |
| `Nexus\CRM` | Business Rule | BUS-CRM-0029 | Only active pipelines can be used for new opportunities | Enums/PipelineStatus.php | ✅ Complete | isUsable() | 2026-02-19 |
| `Nexus\CRM` | Business Rule | BUS-CRM-0030 | Each tenant MUST have exactly one default pipeline | Contracts/PipelineInterface.php | ✅ Complete | isDefault() | 2026-02-19 |
| **BUSINESS RULES - ACTIVITY** |
| `Nexus\CRM` | Business Rule | BUS-CRM-0031 | Activity duration MUST be non-negative | ValueObjects/ActivityDuration.php | ✅ Complete | Constructor validation | 2026-02-19 |
| `Nexus\CRM` | Business Rule | BUS-CRM-0032 | Call, Meeting, and Task activities require scheduling | Enums/ActivityType.php | ✅ Complete | requiresScheduling() | 2026-02-19 |
| `Nexus\CRM` | Business Rule | BUS-CRM-0033 | Call and Meeting activities have duration, Note activities do not | Enums/ActivityType.php | ✅ Complete | hasDuration() | 2026-02-19 |
| `Nexus\CRM` | Business Rule | BUS-CRM-0034 | Activity is overdue if scheduled date has passed and not completed | Contracts/ActivityInterface.php | ✅ Complete | isOverdue() | 2026-02-19 |
| `Nexus\CRM` | Business Rule | BUS-CRM-0035 | Short duration is < 15 minutes, medium is 15-60 minutes, long is > 60 minutes | ValueObjects/ActivityDuration.php | ✅ Complete | Category methods | 2026-02-19 |
| **FUNCTIONAL CAPABILITIES - LEAD** |
| `Nexus\CRM` | Functional | FUN-CRM-0036 | LeadInterface MUST provide getId() returning unique lead identifier | Contracts/LeadInterface.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Functional | FUN-CRM-0037 | LeadInterface MUST provide getTenantId() for multi-tenancy support | Contracts/LeadInterface.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Functional | FUN-CRM-0038 | LeadInterface MUST provide getStatus() returning LeadStatus enum | Contracts/LeadInterface.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Functional | FUN-CRM-0039 | LeadInterface MUST provide getSource() returning LeadSource enum | Contracts/LeadInterface.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Functional | FUN-CRM-0040 | LeadInterface MUST provide getScore() returning nullable LeadScore value object | Contracts/LeadInterface.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Functional | FUN-CRM-0041 | LeadInterface MUST provide isQualified() and isConvertible() boolean methods | Contracts/LeadInterface.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Functional | FUN-CRM-0042 | LeadQueryInterface MUST provide findById(), findByIdOrFail(), findByExternalRef() methods | Contracts/LeadQueryInterface.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Functional | FUN-CRM-0043 | LeadQueryInterface MUST provide findByStatus(), findBySource(), findByDateRange() methods | Contracts/LeadQueryInterface.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Functional | FUN-CRM-0044 | LeadQueryInterface MUST provide findHighScoring(), findUnassigned(), findConvertible() methods | Contracts/LeadQueryInterface.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Functional | FUN-CRM-0045 | LeadPersistInterface MUST provide create(), update(), updateStatus(), updateSource() methods | Contracts/LeadPersistInterface.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Functional | FUN-CRM-0046 | LeadPersistInterface MUST provide assignScore(), convertToOpportunity(), disqualify() methods | Contracts/LeadPersistInterface.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Functional | FUN-CRM-0047 | LeadPersistInterface MUST provide delete() and restore() for soft delete support | Contracts/LeadPersistInterface.php | ✅ Complete | - | 2026-02-19 |
| **FUNCTIONAL CAPABILITIES - OPPORTUNITY** |
| `Nexus\CRM` | Functional | FUN-CRM-0048 | OpportunityInterface MUST provide getId() and getTenantId() methods | Contracts/OpportunityInterface.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Functional | FUN-CRM-0049 | OpportunityInterface MUST provide getPipelineId() returning parent pipeline identifier | Contracts/OpportunityInterface.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Functional | FUN-CRM-0050 | OpportunityInterface MUST provide getStage() returning OpportunityStage enum | Contracts/OpportunityInterface.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Functional | FUN-CRM-0051 | OpportunityInterface MUST provide getValue() and getCurrency() for deal value | Contracts/OpportunityInterface.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Functional | FUN-CRM-0052 | OpportunityInterface MUST provide getForecastProbability() and getWeightedValue() methods | Contracts/OpportunityInterface.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Functional | FUN-CRM-0053 | OpportunityInterface MUST provide isOpen(), isWon(), isLost() boolean methods | Contracts/OpportunityInterface.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Functional | FUN-CRM-0054 | OpportunityInterface MUST provide getDaysInCurrentStage() and getAgeInDays() methods | Contracts/OpportunityInterface.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Functional | FUN-CRM-0055 | OpportunityQueryInterface MUST provide findById(), findByIdOrFail(), findByPipeline() methods | Contracts/OpportunityQueryInterface.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Functional | FUN-CRM-0056 | OpportunityQueryInterface MUST provide findByStage(), findOpen(), findWon(), findLost() methods | Contracts/OpportunityQueryInterface.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Functional | FUN-CRM-0057 | OpportunityQueryInterface MUST provide findStale(), findBySourceLead(), getTotalOpenValue() methods | Contracts/OpportunityQueryInterface.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Functional | FUN-CRM-0058 | OpportunityPersistInterface MUST provide create(), update(), advanceStage(), moveToStage() methods | Contracts/OpportunityPersistInterface.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Functional | FUN-CRM-0059 | OpportunityPersistInterface MUST provide markAsWon(), markAsLost(), reopen() methods | Contracts/OpportunityPersistInterface.php | ✅ Complete | - | 2026-02-19 |
| **FUNCTIONAL CAPABILITIES - PIPELINE** |
| `Nexus\CRM` | Functional | FUN-CRM-0060 | PipelineInterface MUST provide getId(), getTenantId(), getName(), getDescription() methods | Contracts/PipelineInterface.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Functional | FUN-CRM-0061 | PipelineInterface MUST provide getStages() returning ordered array of PipelineStage value objects | Contracts/PipelineInterface.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Functional | FUN-CRM-0062 | PipelineInterface MUST provide getStageAtPosition() for stage lookup by position | Contracts/PipelineInterface.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Functional | FUN-CRM-0063 | PipelineInterface MUST provide isActive() and isDefault() boolean methods | Contracts/PipelineInterface.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Functional | FUN-CRM-0064 | PipelineQueryInterface MUST provide findById(), findByIdOrFail(), findByName(), findAll() methods | Contracts/PipelineQueryInterface.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Functional | FUN-CRM-0065 | PipelineQueryInterface MUST provide findByStatus(), findActive(), findDefault() methods | Contracts/PipelineQueryInterface.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Functional | FUN-CRM-0066 | PipelinePersistInterface MUST provide create(), update(), updateStatus() methods | Contracts/PipelinePersistInterface.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Functional | FUN-CRM-0067 | PipelinePersistInterface MUST provide addStage(), removeStage(), reorderStages() methods | Contracts/PipelinePersistInterface.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Functional | FUN-CRM-0068 | PipelinePersistInterface MUST provide setAsDefault(), delete(), restore() methods | Contracts/PipelinePersistInterface.php | ✅ Complete | - | 2026-02-19 |
| **FUNCTIONAL CAPABILITIES - ACTIVITY** |
| `Nexus\CRM` | Functional | FUN-CRM-0069 | ActivityInterface MUST provide getId(), getTenantId(), getType(), getTitle() methods | Contracts/ActivityInterface.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Functional | FUN-CRM-0070 | ActivityInterface MUST provide getDuration() returning nullable ActivityDuration value object | Contracts/ActivityInterface.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Functional | FUN-CRM-0071 | ActivityInterface MUST provide getRelatedEntityType() and getRelatedEntityId() for entity linking | Contracts/ActivityInterface.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Functional | FUN-CRM-0072 | ActivityInterface MUST provide getScheduledAt(), getStartedAt(), getEndedAt() timestamp methods | Contracts/ActivityInterface.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Functional | FUN-CRM-0073 | ActivityInterface MUST provide isCompleted(), isOverdue(), isScheduled() boolean methods | Contracts/ActivityInterface.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Functional | FUN-CRM-0074 | ActivityQueryInterface MUST provide findById(), findByType(), findByRelatedEntity() methods | Contracts/ActivityQueryInterface.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Functional | FUN-CRM-0075 | ActivityQueryInterface MUST provide findByLead(), findByOpportunity(), findByDateRange() methods | Contracts/ActivityQueryInterface.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Functional | FUN-CRM-0076 | ActivityQueryInterface MUST provide findScheduled(), findOverdue(), findCompleted(), findPending() methods | Contracts/ActivityQueryInterface.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Functional | FUN-CRM-0077 | ActivityPersistInterface MUST provide create(), update(), start(), complete() methods | Contracts/ActivityPersistInterface.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Functional | FUN-CRM-0078 | ActivityPersistInterface MUST provide reschedule(), cancel(), delete(), restore() methods | Contracts/ActivityPersistInterface.php | ✅ Complete | - | 2026-02-19 |
| **INTERFACE REQUIREMENTS** |
| `Nexus\CRM` | Interface | IFC-CRM-0079 | LeadInterface MUST define 16 methods for lead entity access | Contracts/LeadInterface.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Interface | IFC-CRM-0080 | LeadQueryInterface MUST define 11 query methods for lead retrieval | Contracts/LeadQueryInterface.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Interface | IFC-CRM-0081 | LeadPersistInterface MUST define 9 persist methods for lead modification | Contracts/LeadPersistInterface.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Interface | IFC-CRM-0082 | OpportunityInterface MUST define 18 methods for opportunity entity access | Contracts/OpportunityInterface.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Interface | IFC-CRM-0083 | OpportunityQueryInterface MUST define 13 query methods for opportunity retrieval | Contracts/OpportunityQueryInterface.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Interface | IFC-CRM-0084 | OpportunityPersistInterface MUST define 9 persist methods for opportunity modification | Contracts/OpportunityPersistInterface.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Interface | IFC-CRM-0085 | PipelineInterface MUST define 12 methods for pipeline entity access | Contracts/PipelineInterface.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Interface | IFC-CRM-0086 | PipelineQueryInterface MUST define 8 query methods for pipeline retrieval | Contracts/PipelineQueryInterface.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Interface | IFC-CRM-0087 | PipelinePersistInterface MUST define 9 persist methods for pipeline modification | Contracts/PipelinePersistInterface.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Interface | IFC-CRM-0088 | ActivityInterface MUST define 15 methods for activity entity access | Contracts/ActivityInterface.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Interface | IFC-CRM-0089 | ActivityQueryInterface MUST define 12 query methods for activity retrieval | Contracts/ActivityQueryInterface.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Interface | IFC-CRM-0090 | ActivityPersistInterface MUST define 8 persist methods for activity modification | Contracts/ActivityPersistInterface.php | ✅ Complete | - | 2026-02-19 |
| **VALUE OBJECT REQUIREMENTS** |
| `Nexus\CRM` | Value Object | VO-CRM-0091 | LeadScore MUST be immutable readonly class with value, factors, and calculatedAt properties | ValueObjects/LeadScore.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Value Object | VO-CRM-0092 | LeadScore MUST validate value is between 0 and 100 inclusive | ValueObjects/LeadScore.php | ✅ Complete | Constructor validation | 2026-02-19 |
| `Nexus\CRM` | Value Object | VO-CRM-0093 | LeadScore MUST provide fromFactors() factory method for score calculation | ValueObjects/LeadScore.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Value Object | VO-CRM-0094 | LeadScore MUST provide isHighQuality(), isMediumQuality(), isLowQuality() methods | ValueObjects/LeadScore.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Value Object | VO-CRM-0095 | PipelineStage MUST be immutable readonly class with name, position, probability properties | ValueObjects/PipelineStage.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Value Object | VO-CRM-0096 | PipelineStage MUST validate position >= 1 and probability between 0 and 100 | ValueObjects/PipelineStage.php | ✅ Complete | Constructor validation | 2026-02-19 |
| `Nexus\CRM` | Value Object | VO-CRM-0097 | PipelineStage MUST provide fromEnum() factory method for creating from OpportunityStage | ValueObjects/PipelineStage.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Value Object | VO-CRM-0098 | PipelineStage MUST provide isFinal(), isWinStage(), isLossStage() methods | ValueObjects/PipelineStage.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Value Object | VO-CRM-0099 | ForecastProbability MUST be immutable readonly class with percentage and reason properties | ValueObjects/ForecastProbability.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Value Object | VO-CRM-0100 | ForecastProbability MUST validate percentage is between 0 and 100 inclusive | ValueObjects/ForecastProbability.php | ✅ Complete | Constructor validation | 2026-02-19 |
| `Nexus\CRM` | Value Object | VO-CRM-0101 | ForecastProbability MUST provide fromDecimal(), guaranteed(), lost() factory methods | ValueObjects/ForecastProbability.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Value Object | VO-CRM-0102 | ForecastProbability MUST provide calculateWeightedValue() method for weighted pipeline calculations | ValueObjects/ForecastProbability.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Value Object | VO-CRM-0103 | ActivityDuration MUST be immutable readonly class with minutes property | ValueObjects/ActivityDuration.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Value Object | VO-CRM-0104 | ActivityDuration MUST validate minutes is non-negative | ValueObjects/ActivityDuration.php | ✅ Complete | Constructor validation | 2026-02-19 |
| `Nexus\CRM` | Value Object | VO-CRM-0105 | ActivityDuration MUST provide fromMinutes(), fromHours(), fromSeconds() factory methods | ValueObjects/ActivityDuration.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Value Object | VO-CRM-0106 | ActivityDuration MUST provide format() and formatHHMM() methods for display | ValueObjects/ActivityDuration.php | ✅ Complete | - | 2026-02-19 |
| **ENUM REQUIREMENTS** |
| `Nexus\CRM` | Enum | ENUM-CRM-0107 | LeadStatus MUST be native PHP enum with cases: New, Contacted, Qualified, Disqualified, Converted | Enums/LeadStatus.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Enum | ENUM-CRM-0108 | LeadStatus MUST provide getValidTransitions() and canTransitionTo() methods for state machine | Enums/LeadStatus.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Enum | ENUM-CRM-0109 | LeadSource MUST be native PHP enum with 11 source types | Enums/LeadSource.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Enum | ENUM-CRM-0110 | LeadSource MUST provide isInbound(), isOutbound(), isPaid(), getCategory() methods | Enums/LeadSource.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Enum | ENUM-CRM-0111 | OpportunityStage MUST be native PHP enum with cases: Prospecting through ClosedWon/ClosedLost | Enums/OpportunityStage.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Enum | ENUM-CRM-0112 | OpportunityStage MUST provide getDefaultProbability() and getPosition() methods | Enums/OpportunityStage.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Enum | ENUM-CRM-0113 | OpportunityStage MUST provide getNextStage() and canAdvance() methods | Enums/OpportunityStage.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Enum | ENUM-CRM-0114 | ActivityType MUST be native PHP enum with cases: Call, Email, Meeting, Task, Note | Enums/ActivityType.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Enum | ENUM-CRM-0115 | ActivityType MUST provide requiresScheduling(), hasDuration(), isCommunication() methods | Enums/ActivityType.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Enum | ENUM-CRM-0116 | PipelineStatus MUST be native PHP enum with cases: Active, Inactive, Archived | Enums/PipelineStatus.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Enum | ENUM-CRM-0117 | PipelineStatus MUST provide getValidTransitions() and canTransitionTo() methods | Enums/PipelineStatus.php | ✅ Complete | - | 2026-02-19 |
| **SERVICE REQUIREMENTS** |
| `Nexus\CRM` | Service | SRV-CRM-0118 | LeadScoringEngine MUST provide calculateScore() method accepting LeadInterface and context array | Services/LeadScoringEngine.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Service | SRV-CRM-0119 | LeadScoringEngine MUST calculate scores from source_quality, engagement, fit, timing, budget factors | Services/LeadScoringEngine.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Service | SRV-CRM-0120 | LeadScoringEngine MUST support custom weights via constructor and withWeights() method | Services/LeadScoringEngine.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Service | SRV-CRM-0121 | StageTransitionService MUST provide advance(), moveToStage(), markAsWon(), markAsLost() methods | Services/StageTransitionService.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Service | SRV-CRM-0122 | StageTransitionService MUST validate all stage transitions before execution | Services/StageTransitionService.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Service | SRV-CRM-0123 | StageTransitionService MUST provide reopen() method for closed opportunities | Services/StageTransitionService.php | ✅ Complete | - | 2026-02-19 |
| **EXCEPTION REQUIREMENTS** |
| `Nexus\CRM` | Exception | EXC-CRM-0124 | CRMException MUST be base exception class extending Exception | Exceptions/CRMException.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Exception | EXC-CRM-0125 | InvalidStageTransitionException MUST include from and to stages with factory methods | Exceptions/InvalidStageTransitionException.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Exception | EXC-CRM-0126 | LeadNotFoundException MUST include searched identifier with forId(), forExternalRef() factories | Exceptions/LeadNotFoundException.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Exception | EXC-CRM-0127 | OpportunityNotFoundException MUST include searched identifier with forId(), forSourceLead() factories | Exceptions/OpportunityNotFoundException.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Exception | EXC-CRM-0128 | PipelineNotFoundException MUST include searched identifier with forId(), forName(), noDefaultPipeline() factories | Exceptions/PipelineNotFoundException.php | ✅ Complete | - | 2026-02-19 |
| **VALIDATION REQUIREMENTS** |
| `Nexus\CRM` | Validation | VAL-CRM-0129 | Lead title MUST be non-empty string | Contracts/LeadInterface.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Validation | VAL-CRM-0130 | Opportunity value MUST be positive integer (in smallest currency unit) | Contracts/OpportunityInterface.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Validation | VAL-CRM-0131 | Currency code MUST be valid ISO 4217 format | Contracts/OpportunityInterface.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Validation | VAL-CRM-0132 | Expected close date MUST be future date for open opportunities | Contracts/OpportunityInterface.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Validation | VAL-CRM-0133 | Pipeline name MUST be non-empty string | Contracts/PipelineInterface.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Validation | VAL-CRM-0134 | Activity title MUST be non-empty string | Contracts/ActivityInterface.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Validation | VAL-CRM-0135 | All entity IDs MUST be non-empty strings | Contracts/*.php | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Validation | VAL-CRM-0136 | Tenant IDs MUST be non-empty strings for multi-tenancy isolation | Contracts/*.php | ✅ Complete | - | 2026-02-19 |
| **TESTING REQUIREMENTS** |
| `Nexus\CRM` | Testing | TST-CRM-0137 | All public interface methods MUST have unit tests | tests/ | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Testing | TST-CRM-0138 | LeadScoringEngine MUST have tests for all scoring factors | tests/ | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Testing | TST-CRM-0139 | StageTransitionService MUST have tests for all transition validation rules | tests/ | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Testing | TST-CRM-0140 | All value objects MUST have tests for validation rules | tests/ | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Testing | TST-CRM-0141 | All enums MUST have tests for state machine transitions | tests/ | ✅ Complete | - | 2026-02-19 |
| `Nexus\CRM` | Testing | TST-CRM-0142 | All exceptions MUST have tests for factory methods | tests/ | ✅ Complete | - | 2026-02-19 |

---

## Requirements Summary

### By Type
- **Architectural Requirements:** 10 (100% complete)
- **Business Requirements:** 25 (100% complete)
- **Functional Requirements:** 43 (100% complete)
- **Interface Requirements:** 12 (100% complete)
- **Value Object Requirements:** 16 (100% complete)
- **Enum Requirements:** 11 (100% complete)
- **Service Requirements:** 6 (100% complete)
- **Exception Requirements:** 5 (100% complete)
- **Validation Requirements:** 8 (100% complete)
- **Testing Requirements:** 6 (100% complete)

### By Status
- ✅ **Complete:** 142 (100%)
- ⏳ **Pending:** 0 (0%)
- 🚧 **In Progress:** 0 (0%)
- ❌ **Blocked:** 0 (0%)

---

## Notes

### Package Structure

The CRM atomic package provides pure domain logic for Customer Relationship Management:

1. **Lead Management**: Lead entity with status state machine, scoring, and conversion tracking
2. **Opportunity Management**: Opportunity entity with stage transitions and forecasting
3. **Pipeline Management**: Pipeline configuration with customizable stages
4. **Activity Tracking**: Activity entity for calls, emails, meetings, tasks, and notes

### Consumer Packages

This package is consumed by:
- `Nexus\CRMOperations` - Orchestrator for cross-package CRM workflows including lead assignment, notifications, and dashboard aggregation

### Design Decisions

1. **CQRS Pattern**: All persistence operations use separate Query and Persist interfaces for read/write separation
2. **State Machines**: Lead status and opportunity stage transitions are validated through enum-based state machines
3. **Value Objects**: All measurable quantities (score, probability, duration) are encapsulated in immutable value objects
4. **Framework Agnostic**: No dependencies on Laravel, Symfony, or any web framework

---

**Document Version**: 1.0  
**Creation Date:** 2026-02-19  
**Maintained By:** Nexus Architecture Team  
**Compliance:** Atomic Package Layer Standards
