# Requirements: CRMOperations (Orchestrator Package Layer)

**Package:** `Nexus\CRMOperations`  
**Version:** 1.1  
**Last Updated:** 2026-02-20  
**Total Requirements:** 189

---

## Package Boundary Definition

This requirements document defines the **orchestrator package layer** for `Nexus\CRMOperations` - a cross-package workflow coordination layer that orchestrates multiple atomic packages to deliver complex business processes.

### What Belongs in Orchestrator Layer (This Document)

- ✅ **Cross-Package Workflows**: Lead-to-opportunity conversion, deal approval, escalation workflows
- ✅ **Coordinators**: LeadConversionCoordinator, OpportunityCloseCoordinator, PipelineAnalyticsCoordinator
- ✅ **Provider Interfaces**: CustomerProvider, QuotationProvider, NotificationProvider, AnalyticsProvider
- ✅ **Business Rules Aggregation**: LeadQualificationRule, OpportunityApprovalRule, SLABreachRule
- ✅ **Context Data Providers**: LeadContextDataProvider, OpportunityContextDataProvider
- ✅ **Integration Services**: PartyIntegrationService, SalesIntegrationService, AnalyticsIntegrationService

### What Does NOT Belong in This Package

- ❌ **Pure Domain Logic**: Lead/Opportunity entities, status state machines (belongs in `Nexus\CRM`)
- ❌ **Database Schema**: Migrations, indexes, foreign keys (application layer)
- ❌ **API Endpoints**: REST routes, controllers (application layer)
- ❌ **Party Package Core**: Customer entity, party relationships (belongs in `Nexus\Party`)
- ❌ **Sales Package Core**: Quotation entity, pricing engine (belongs in `Nexus\Sales`)
- ❌ **Notifier Package Core**: Email templates, notification channels (belongs in `Nexus\Notifier`)

### Architectural References

- **Orchestrator Interface Segregation**: `docs/ORCHESTRATOR_INTERFACE_SEGREGATION.md`
- **Architecture Guidelines**: `ARCHITECTURE.md`
- **Package Reference**: `docs/NEXUS_PACKAGES_REFERENCE.md`

---

## Orchestrated Packages

The CRMOperations orchestrator coordinates the following atomic packages:

| Package | Namespace | Orchestration Role |
|---------|-----------|-------------------|
| CRM | `Nexus\CRM` | Lead, Opportunity, Pipeline, Activity management |
| Party | `Nexus\Party` | Customer data and party relationships |
| Sales | `Nexus\Sales` | Quotation management and pricing |
| Workflow | `Nexus\Workflow` | Workflow state machine and execution |
| Notifier | `Nexus\Notifier` | Notification delivery and preferences |

### Integration Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                    CRMOperations Orchestrator                     │
│                                                                  │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌───────┐ │
│  │ Coordinators│  │  Workflows  │  │    Rules    │  │  Data │ │
│  │             │  │             │  │             │  │   Prov│ │
│  └──────┬──────┘  └──────┬──────┘  └──────┬──────┘  └───┬───┘ │
│         │                │                │              │      │
│  ┌──────┴────────────────┴────────────────┴──────────────┴───┐ │
│  │                    Provider Interfaces                       │ │
│  │  (CustomerProvider, QuotationProvider, NotificationProvider, │ │
│  │   AnalyticsProvider)                                         │ │
│  └───────────────────────────┬─────────────────────────────────┘ │
└──────────────────────────────┼───────────────────────────────────┘
                               │ implements
                               ▼
┌──────────────────────────────────────────────────────────────────┐
│                    Atomic Packages (Layer 1)                      │
│                                                                   │
│  ┌─────────┐   ┌─────────┐   ┌─────────┐   ┌─────────┐         │
│  │   CRM   │   │  Party  │   │  Sales  │   │ Notifier│         │
│  └─────────┘   └─────────┘   └─────────┘   └─────────┘         │
└──────────────────────────────────────────────────────────────────┘
```

---

## Requirements

| Package Namespace | Requirements Type | Code | Requirement Statements | Files/Folders | Status | Notes on Status | Date Last Updated |
|-------------------|-------------------|------|------------------------|---------------|--------|-----------------|-------------------|
| **ARCHITECTURAL REQUIREMENTS** |
| `Nexus\CRMOperations` | Architectural | ARC-CRMOP-0001 | Package MUST be framework-agnostic with zero dependencies on Laravel, Symfony, or any web framework | composer.json, src/ | ⏳ Pending | Validate no Illuminate\* imports | 2026-02-19 |
| `Nexus\CRMOperations` | Architectural | ARC-CRMOP-0002 | Package composer.json MUST require only: php:^8.3, psr/log:^3.0, psr/event-dispatcher:^1.0 | composer.json | ⏳ Pending | PSR-only dependencies | 2026-02-19 |
| `Nexus\CRMOperations` | Architectural | ARC-CRMOP-0003 | All provider interfaces MUST be defined in Contracts/ directory | src/Contracts/ | ⏳ Pending | 4 interfaces defined | 2026-02-19 |
| `Nexus\CRMOperations` | Architectural | ARC-CRMOP-0004 | Package MUST depend on at least 2 atomic packages for orchestration | composer.json | ⏳ Pending | Depends on CRM, Party, Sales, Notifier | 2026-02-19 |
| `Nexus\CRMOperations` | Architectural | ARC-CRMOP-0005 | All coordinators MUST use constructor injection with readonly properties | src/Coordinators/ | ⏳ Pending | 3 coordinators | 2026-02-19 |
| `Nexus\CRMOperations` | Architectural | ARC-CRMOP-0006 | All workflows MUST use constructor injection with readonly properties | src/Workflows/ | ⏳ Pending | 3 workflows | 2026-02-19 |
| `Nexus\CRMOperations` | Architectural | ARC-CRMOP-0007 | All rules MUST use constructor injection with readonly properties | src/Rules/ | ⏳ Pending | 3 rules | 2026-02-19 |
| `Nexus\CRMOperations` | Architectural | ARC-CRMOP-0008 | All files MUST use declare(strict_types=1) and constructor property promotion | src/ | ⏳ Pending | - | 2026-02-19 |
| `Nexus\CRMOperations` | Architectural | ARC-CRMOP-0009 | Package MUST be stateless - no session state, all state externalized via interfaces | src/ | ⏳ Pending | - | 2026-02-19 |
| `Nexus\CRMOperations` | Architectural | ARC-CRMOP-0010 | All exceptions MUST extend base Exception with context-rich factory methods | src/Exceptions/ | ⏳ Pending | Define exceptions | 2026-02-19 |
| **BUSINESS REQUIREMENTS - CROSS-PACKAGE** |
| `Nexus\CRMOperations` | Business Rule | BUS-CRMOP-0011 | Lead conversion MUST create customer in Party package before creating opportunity in CRM | Coordinators/LeadConversionCoordinator.php | ⏳ Pending | Order validation | 2026-02-19 |
| `Nexus\CRMOperations` | Business Rule | BUS-CRMOP-0012 | Opportunity close MUST create quotation in Sales package before marking as won | Coordinators/OpportunityCloseCoordinator.php | ⏳ Pending | Order validation | 2026-02-19 |
| `Nexus\CRMOperations` | Business Rule | BUS-CRMOP-0013 | All workflow state changes MUST be atomic - all-or-nothing | Workflows/*.php | ⏳ Pending | Use transactions | 2026-02-19 |
| `Nexus\CRMOperations` | Business Rule | BUS-CRMOP-0014 | Lead qualification MUST consider lead score, activity count, and age | Rules/LeadQualificationRule.php | ⏳ Pending | Multi-factor evaluation | 2026-02-19 |
| `Nexus\CRMOperations` | Business Rule | BUS-CRMOP-0015 | Deal approval MUST evaluate value thresholds and discount percentages | Rules/OpportunityApprovalRule.php | ⏳ Pending | Threshold-based | 2026-02-19 |
| `Nexus\CRMOperations` | Business Rule | BUS-CRMOP-0016 | SLA breach MUST trigger notifications via Notifier package | Workflows/EscalationWorkflow.php | ⏳ Pending | Cross-package notification | 2026-02-19 |
| `Nexus\CRMOperations` | Business Rule | BUS-CRMOP-0017 | Default lead qualification minimum score: 50 | Rules/LeadQualificationRule.php | ⏳ Pending | Configurable threshold | 2026-02-19 |
| `Nexus\CRMOperations` | Business Rule | BUS-CRMOP-0018 | Default approval required for deals >= $5,000 (500000 cents) | Rules/OpportunityApprovalRule.php | ⏳ Pending | Configurable threshold | 2026-02-19 |
| `Nexus\CRMOperations` | Business Rule | BUS-CRMOP-0019 | Default lead first contact SLA: 24 hours | Rules/SLABreachRule.php | ⏳ Pending | Configurable threshold | 2026-02-19 |
| `Nexus\CRMOperations` | Business Rule | BUS-CRMOP-0020 | Default lead qualification SLA: 72 hours | Rules/SLABreachRule.php | ⏳ Pending | Configurable threshold | 2026-02-19 |
| `Nexus\CRMOperations` | Business Rule | BUS-CRMOP-0021 | Default opportunity follow-up SLA: 48 hours | Rules/SLABreachRule.php | ⏳ Pending | Configurable threshold | 2026-02-19 |
| `Nexus\CRMOperations` | Business Rule | BUS-CRMOP-0022 | Default opportunity proposal SLA: 168 hours (7 days) | Rules/SLABreachRule.php | ⏳ Pending | Configurable threshold | 2026-02-19 |
| **FUNCTIONAL REQUIREMENTS - COORDINATION** |
| `Nexus\CRMOperations` | Functional | FUN-CRMOP-0023 | LeadConversionCoordinator MUST convert lead to opportunity with customer creation | src/Coordinators/LeadConversionCoordinator.php | ⏳ Pending | execute method | 2026-02-19 |
| `Nexus\CRMOperations` | Functional | FUN-CRMOP-0024 | LeadConversionCoordinator MUST link customer to opportunity after creation | src/Coordinators/LeadConversionCoordinator.php | ⏳ Pending | linkToOpportunity call | 2026-02-19 |
| `Nexus\CRMOperations` | Functional | FUN-CRMOP-0025 | OpportunityCloseCoordinator MUST close opportunity as won with quotation | src/Coordinators/OpportunityCloseCoordinator.php | ⏳ Pending | closeAsWon method | 2026-02-19 |
| `Nexus\CRMOperations` | Functional | FUN-CRMOP-0026 | OpportunityCloseCoordinator MUST close opportunity as lost with reason | src/Coordinators/OpportunityCloseCoordinator.php | ⏳ Pending | closeAsLost method | 2026-02-19 |
| `Nexus\CRMOperations` | Functional | FUN-CRMOP-0027 | PipelineAnalyticsCoordinator MUST aggregate pipeline metrics across pipelines | src/Coordinators/PipelineAnalyticsCoordinator.php | ⏳ Pending | getDashboardData method | 2026-02-19 |
| `Nexus\CRMOperations` | Functional | FUN-CRMOP-0028 | PipelineAnalyticsCoordinator MUST calculate weighted pipeline value | src/Coordinators/PipelineAnalyticsCoordinator.php | ⏳ Pending | getPipelineSummary method | 2026-02-19 |
| `Nexus\CRMOperations` | Functional | FUN-CRMOP-0029 | PipelineAnalyticsCoordinator MUST generate reports in multiple formats | src/Coordinators/PipelineAnalyticsCoordinator.php | ⏳ Pending | generateReport method | 2026-02-19 |
| `Nexus\CRMOperations` | Functional | FUN-CRMOP-0030 | LeadToOpportunityWorkflow MUST execute multi-step conversion process | src/Workflows/LeadToOpportunityWorkflow.php | ⏳ Pending | execute method | 2026-02-19 |
| `Nexus\CRMOperations` | Functional | FUN-CRMOP-0031 | DealApprovalWorkflow MUST request approval based on deal value | src/Workflows/DealApprovalWorkflow.php | ⏳ Pending | requestApproval method | 2026-02-19 |
| `Nexus\CRMOperations` | Functional | FUN-CRMOP-0032 | EscalationWorkflow MUST check SLA breaches for leads and opportunities | src/Workflows/EscalationWorkflow.php | ⏳ Pending | checkLeadSLABreaches, checkOpportunitySLABreaches | 2026-02-19 |
| `Nexus\CRMOperations` | Functional | FUN-CRMOP-0033 | EscalationWorkflow MUST escalate breaches based on severity level | src/Workflows/EscalationWorkflow.php | ⏳ Pending | escalate method | 2026-02-19 |
| **INTERFACE REQUIREMENTS - PROVIDERS** |
| `Nexus\CRMOperations` | Interface | IFC-CRMOP-0034 | CustomerProviderInterface MUST define 7 methods for customer data access | src/Contracts/CustomerProviderInterface.php | ⏳ Pending | findById, findByEmail, createFromLead, update, exists, linkToOpportunity, getCommunicationPreferences | 2026-02-19 |
| `Nexus\CRMOperations` | Interface | IFC-CRMOP-0035 | QuotationProviderInterface MUST define 11 methods for quotation management | src/Contracts/QuotationProviderInterface.php | ⏳ Pending | findById, findByOpportunityId, createFromOpportunity, update, getStatus, markAsSent, markAsAccepted, markAsRejected, getTotalValue, getCurrency, isExpired, getExpiryDate | 2026-02-19 |
| `Nexus\CRMOperations` | Interface | IFC-CRMOP-0036 | NotificationProviderInterface MUST define 8 methods for notification delivery | src/Contracts/NotificationProviderInterface.php | ⏳ Pending | notify, notifyMany, notifyRole, escalate, remind, hasUnread, getUnreadCount | 2026-02-19 |
| `Nexus\CRMOperations` | Interface | IFC-CRMOP-0037 | AnalyticsProviderInterface MUST define 9 methods for analytics tracking | src/Contracts/AnalyticsProviderInterface.php | ⏳ Pending | track, trackConversion, getPipelineMetrics, getConversionRates, getLeadSourcePerformance, getSalesPerformance, getForecastAccuracy, generateReport, exportData | 2026-02-19 |
| **INTERFACE REQUIREMENTS - DATA PROVIDERS** |
| `Nexus\CRMOperations` | Interface | IFC-CRMOP-0038 | LeadContextDataProvider MUST provide enriched lead context for workflows | src/DataProviders/LeadContextDataProvider.php | ⏳ Pending | getContext method | 2026-02-19 |
| `Nexus\CRMOperations` | Interface | IFC-CRMOP-0039 | LeadContextDataProvider MUST provide lead data for conversion | src/DataProviders/LeadContextDataProvider.php | ⏳ Pending | getLeadDataForConversion method | 2026-02-19 |
| `Nexus\CRMOperations` | Interface | IFC-CRMOP-0040 | OpportunityContextDataProvider MUST provide enriched opportunity context | src/DataProviders/OpportunityContextDataProvider.php | ⏳ Pending | getContext method | 2026-02-19 |
| `Nexus\CRMOperations` | Interface | IFC-CRMOP-0041 | OpportunityContextDataProvider MUST provide opportunity data for closing | src/DataProviders/OpportunityContextDataProvider.php | ⏳ Pending | getOpportunityDataForClosing method | 2026-02-19 |
| `Nexus\CRMOperations` | Interface | IFC-CRMOP-0042 | OpportunityContextDataProvider MUST provide attention indicators | src/DataProviders/OpportunityContextDataProvider.php | ⏳ Pending | getAttentionIndicators method | 2026-02-19 |
| **INTERFACE REQUIREMENTS - RULES** |
| `Nexus\CRMOperations` | Interface | IFC-CRMOP-0043 | LeadQualificationRule MUST evaluate lead qualification | src/Rules/LeadQualificationRule.php | ⏳ Pending | evaluate method | 2026-02-19 |
| `Nexus\CRMOperations` | Interface | IFC-CRMOP-0044 | LeadQualificationRule MUST provide QualificationResult DTO | src/Rules/LeadQualificationRule.php | ⏳ Pending | QualificationResult class | 2026-02-19 |
| `Nexus\CRMOperations` | Interface | IFC-CRMOP-0045 | OpportunityApprovalRule MUST evaluate approval requirements | src/Rules/OpportunityApprovalRule.php | ⏳ Pending | evaluate method | 2026-02-19 |
| `Nexus\CRMOperations` | Interface | IFC-CRMOP-0046 | OpportunityApprovalRule MUST provide ApprovalResult DTO | src/Rules/OpportunityApprovalRule.php | ⏳ Pending | ApprovalResult class | 2026-02-19 |
| `Nexus\CRMOperations` | Interface | IFC-CRMOP-0047 | SLABreachRule MUST evaluate lead SLA status | src/Rules/SLABreachRule.php | ⏳ Pending | evaluateLead method | 2026-02-19 |
| `Nexus\CRMOperations` | Interface | IFC-CRMOP-0048 | SLABreachRule MUST evaluate opportunity SLA status | src/Rules/SLABreachRule.php | ⏳ Pending | evaluateOpportunity method | 2026-02-19 |
| `Nexus\CRMOperations` | Interface | IFC-CRMOP-0049 | SLABreachRule MUST provide SLAResult DTO | src/Rules/SLABreachRule.php | ⏳ Pending | SLAResult class | 2026-02-19 |
| **WORKFLOW REQUIREMENTS** |
| `Nexus\CRMOperations` | Workflow | WFL-CRMOP-0050 | LeadToOpportunityWorkflow MUST have step: Load and validate lead | src/Workflows/LeadToOpportunityWorkflow.php | ⏳ Pending | stepLoadLead | 2026-02-19 |
| `Nexus\CRMOperations` | Workflow | WFL-CRMOP-0051 | LeadToOpportunityWorkflow MUST have step: Check qualification | src/Workflows/LeadToOpportunityWorkflow.php | ⏳ Pending | stepCheckQualification | 2026-02-19 |
| `Nexus\CRMOperations` | Workflow | WFL-CRMOP-0052 | LeadToOpportunityWorkflow MUST have step: Create or find customer | src/Workflows/LeadToOpportunityWorkflow.php | ⏳ Pending | stepCreateCustomer | 2026-02-19 |
| `Nexus\CRMOperations` | Workflow | WFL-CRMOP-0053 | LeadToOpportunityWorkflow MUST have step: Create opportunity | src/Workflows/LeadToOpportunityWorkflow.php | ⏳ Pending | stepCreateOpportunity | 2026-02-19 |
| `Nexus\CRMOperations` | Workflow | WFL-CRMOP-0054 | LeadToOpportunityWorkflow MUST track conversion analytics | src/Workflows/LeadToOpportunityWorkflow.php | ⏳ Pending | trackConversion call | 2026-02-19 |
| `Nexus\CRMOperations` | Workflow | WFL-CRMOP-0055 | LeadToOpportunityWorkflow MUST send notifications on completion | src/Workflows/LeadToOpportunityWorkflow.php | ⏳ Pending | sendNotifications call | 2026-02-19 |
| `Nexus\CRMOperations` | Workflow | WFL-CRMOP-0056 | DealApprovalWorkflow MUST request approval for high-value deals | src/Workflows/DealApprovalWorkflow.php | ⏳ Pending | requestApproval method | 2026-02-19 |
| `Nexus\CRMOperations` | Workflow | WFL-CRMOP-0057 | DealApprovalWorkflow MUST determine approval level (team_lead, manager, director) | src/Workflows/DealApprovalWorkflow.php | ⏳ Pending | ApprovalResult evaluation | 2026-02-19 |
| `Nexus\CRMOperations` | Workflow | WFL-CRMOP-0058 | DealApprovalWorkflow MUST notify approvers | src/Workflows/DealApprovalWorkflow.php | ⏳ Pending | notifyApprovers method | 2026-02-19 |
| `Nexus\CRMOperations` | Workflow | WFL-CRMOP-0059 | DealApprovalWorkflow MUST track approval request analytics | src/Workflows/DealApprovalWorkflow.php | ⏳ Pending | track call | 2026-02-19 |
| `Nexus\CRMOperations` | Workflow | WFL-CRMOP-0060 | EscalationWorkflow MUST check SLA breaches for leads | src/Workflows/EscalationWorkflow.php | ⏳ Pending | checkLeadSLABreaches method | 2026-02-19 |
| `Nexus\CRMOperations` | Workflow | WFL-CRMOP-0061 | EscalationWorkflow MUST check SLA breaches for opportunities | src/Workflows/EscalationWorkflow.php | ⏳ Pending | checkOpportunitySLABreaches method | 2026-02-19 |
| `Nexus\CRMOperations` | Workflow | WFL-CRMOP-0062 | EscalationWorkflow MUST escalate high severity breaches | src/Workflows/EscalationWorkflow.php | ⏳ Pending | escalate method | 2026-02-19 |
| `Nexus\CRMOperations` | Workflow | WFL-CRMOP-0063 | EscalationWorkflow MUST send reminders for warnings | src/Workflows/EscalationWorkflow.php | ⏳ Pending | sendReminders method | 2026-02-19 |
| `Nexus\CRMOperations` | Workflow | WFL-CRMOP-0064 | EscalationWorkflow MUST track SLA check analytics | src/Workflows/EscalationWorkflow.php | ⏳ Pending | track call | 2026-02-19 |
| **COORDINATOR REQUIREMENTS** |
| `Nexus\CRMOperations` | Coordinator | CRD-CRMOP-0065 | LeadConversionCoordinator MUST orchestrate lead-to-opportunity conversion | src/Coordinators/LeadConversionCoordinator.php | ⏳ Pending | convertLead method | 2026-02-19 |
| `Nexus\CRMOperations` | Coordinator | CRD-CRMOP-0066 | LeadConversionCoordinator MUST validate lead can be converted | src/Coordinators/LeadConversionCoordinator.php | ⏳ Pending | validateLeadForConversion | 2026-02-19 |
| `Nexus\CRMOperations` | Coordinator | CRD-CRMOP-0067 | LeadConversionCoordinator MUST create or find customer | src/Coordinators/LeadConversionCoordinator.php | ⏳ Pending | createOrFindCustomer | 2026-02-19 |
| `Nexus\CRMOperations` | Coordinator | CRD-CRMOP-0068 | LeadConversionCoordinator MUST create opportunity from lead | src/Coordinators/LeadConversionCoordinator.php | ⏳ Pending | createOpportunity | 2026-02-19 |
| `Nexus\CRMOperations` | Coordinator | CRD-CRMOP-0069 | LeadConversionCoordinator MUST update lead status to converted | src/Coordinators/LeadConversionCoordinator.php | ⏳ Pending | updateStatus call | 2026-02-19 |
| `Nexus\CRMOperations` | Coordinator | CRD-CRMOP-0070 | LeadConversionCoordinator MUST send conversion notifications | src/Coordinators/LeadConversionCoordinator.php | ⏳ Pending | sendConversionNotifications | 2026-02-19 |
| `Nexus\CRMOperations` | Coordinator | CRD-CRMOP-0071 | OpportunityCloseCoordinator MUST orchestrate opportunity closing | src/Coordinators/OpportunityCloseCoordinator.php | ⏳ Pending | closeAsWon, closeAsLost | 2026-02-19 |
| `Nexus\CRMOperations` | Coordinator | CRD-CRMOP-0072 | OpportunityCloseCoordinator MUST get or create quotation | src/Coordinators/OpportunityCloseCoordinator.php | ⏳ Pending | getOrCreateQuotation | 2026-02-19 |
| `Nexus\CRMOperations` | Coordinator | CRD-CRMOP-0073 | OpportunityCloseCoordinator MUST mark quotation as accepted/rejected | src/Coordinators/OpportunityCloseCoordinator.php | ⏳ Pending | markAsAccepted, markAsRejected | 2026-02-19 |
| `Nexus\CRMOperations` | Coordinator | CRD-CRMOP-0074 | OpportunityCloseCoordinator MUST close opportunity with actual value | src/Coordinators/OpportunityCloseCoordinator.php | ⏳ Pending | markAsWon, markAsLost | 2026-02-19 |
| `Nexus\CRMOperations` | Coordinator | CRD-CRMOP-0075 | OpportunityCloseCoordinator MUST send won/lost notifications | src/Coordinators/OpportunityCloseCoordinator.php | ⏳ Pending | sendWonNotifications, sendLostNotifications | 2026-02-19 |
| `Nexus\CRMOperations` | Coordinator | CRD-CRMOP-0076 | PipelineAnalyticsCoordinator MUST generate dashboard data | src/Coordinators/PipelineAnalyticsCoordinator.php | ⏳ Pending | getDashboardData | 2026-02-19 |
| `Nexus\CRMOperations` | Coordinator | CRD-CRMOP-0077 | PipelineAnalyticsCoordinator MUST calculate pipeline summary | src/Coordinators/PipelineAnalyticsCoordinator.php | ⏳ Pending | getPipelineSummary | 2026-02-19 |
| `Nexus\CRMOperations` | Coordinator | CRD-CRMOP-0078 | PipelineAnalyticsCoordinator MUST group opportunities by stage | src/Coordinators/PipelineAnalyticsCoordinator.php | ⏳ Pending | getOpportunitiesByStage | 2026-02-19 |
| `Nexus\CRMOperations` | Coordinator | CRD-CRMOP-0079 | PipelineAnalyticsCoordinator MUST calculate pipeline trends | src/Coordinators/PipelineAnalyticsCoordinator.php | ⏳ Pending | getPipelineTrends | 2026-02-19 |
| `Nexus\CRMOperations` | Coordinator | CRD-CRMOP-0080 | PipelineAnalyticsCoordinator MUST generate forecasts | src/Coordinators/PipelineAnalyticsCoordinator.php | ⏳ Pending | getForecasts | 2026-02-19 |
| `Nexus\CRMOperations` | Coordinator | CRD-CRMOP-0081 | PipelineAnalyticsCoordinator MUST export pipeline data | src/Coordinators/PipelineAnalyticsCoordinator.php | ⏳ Pending | exportData method | 2026-02-19 |
| **INTEGRATION REQUIREMENTS** |
| `Nexus\CRMOperations` | Integration | INT-CRMOP-0082 | PartyIntegrationService MUST implement CustomerProviderInterface | src/Services/PartyIntegrationService.php | ⏳ Pending | Interface implementation | 2026-02-19 |
| `Nexus\CRMOperations` | Integration | INT-CRMOP-0083 | PartyIntegrationService MUST call Party package for customer operations | src/Services/PartyIntegrationService.php | ⏳ Pending | Delegates to Party package | 2026-02-19 |
| `Nexus\CRMOperations` | Integration | INT-CRMOP-0084 | SalesIntegrationService MUST implement QuotationProviderInterface | src/Services/SalesIntegrationService.php | ⏳ Pending | Interface implementation | 2026-02-19 |
| `Nexus\CRMOperations` | Integration | INT-CRMOP-0085 | SalesIntegrationService MUST call Sales package for quotation operations | src/Services/SalesIntegrationService.php | ⏳ Pending | Delegates to Sales package | 2026-02-19 |
| `Nexus\CRMOperations` | Integration | INT-CRMOP-0086 | AnalyticsIntegrationService MUST implement AnalyticsProviderInterface | src/Services/AnalyticsIntegrationService.php | ⏳ Pending | Interface implementation | 2026-02-19 |
| `Nexus\CRMOperations` | Integration | INT-CRMOP-0087 | AnalyticsIntegrationService MUST track events and conversions | src/Services/AnalyticsIntegrationService.php | ⏳ Pending | track, trackConversion methods | 2026-02-19 |
| `Nexus\CRMOperations` | Integration | INT-CRMOP-0088 | AnalyticsIntegrationService MUST provide pipeline metrics | src/Services/AnalyticsIntegrationService.php | ⏳ Pending | getPipelineMetrics method | 2026-02-19 |
| `Nexus\CRMOperations` | Integration | INT-CRMOP-0089 | AnalyticsIntegrationService MUST calculate conversion rates | src/Services/AnalyticsIntegrationService.php | ⏳ Pending | getConversionRates method | 2026-02-19 |
| `Nexus\CRMOperations` | Integration | INT-CRMOP-0090 | Orchestrator MUST use CRM package query/persist interfaces | src/Coordinators/, src/Workflows/ | ⏳ Pending | LeadQueryInterface, LeadPersistInterface, OpportunityQueryInterface, OpportunityPersistInterface | 2026-02-19 |
| **TESTING REQUIREMENTS** |
| `Nexus\CRMOperations` | Testing | TST-CRMOP-0091 | All coordinators MUST have unit tests | tests/Unit/Coordinators/ | ⏳ Pending | Test each coordinator | 2026-02-19 |
| `Nexus\CRMOperations` | Testing | TST-CRMOP-0092 | All workflows MUST have unit tests | tests/Unit/Workflows/ | ⏳ Pending | Test each workflow | 2026-02-19 |
| `Nexus\CRMOperations` | Testing | TST-CRMOP-0093 | All rules MUST have unit tests | tests/Unit/Rules/ | ⏳ Pending | Test each rule | 2026-02-19 |
| `Nexus\CRMOperations` | Testing | TST-CRMOP-0094 | All data providers MUST have unit tests | tests/Unit/DataProviders/ | ⏳ Pending | Test each data provider | 2026-02-19 |
| `Nexus\CRMOperations` | Testing | TST-CRMOP-0095 | LeadQualificationRule MUST have tests for all qualification criteria | tests/Unit/Rules/LeadQualificationRuleTest.php | ⏳ Pending | Score, activity, age tests | 2026-02-19 |
| `Nexus\CRMOperations` | Testing | TST-CRMOP-0096 | OpportunityApprovalRule MUST have tests for all approval thresholds | tests/Unit/Rules/OpportunityApprovalRuleTest.php | ⏳ Pending | Value, discount, special terms tests | 2026-02-19 |
| `Nexus\CRMOperations` | Testing | TST-CRMOP-0097 | SLABreachRule MUST have tests for all SLA types | tests/Unit/Rules/SLABreachRuleTest.php | ⏳ Pending | Lead and opportunity SLA tests | 2026-02-19 |
| `Nexus\CRMOperations` | Testing | TST-CRMOP-0098 | All provider interfaces MUST have mock implementations for testing | tests/Mocks/ | ⏳ Pending | Mock providers | 2026-02-19 |
| `Nexus\CRMOperations` | Testing | TST-CRMOP-0099 | Integration tests MUST verify cross-package coordination | tests/Integration/ | ⏳ Pending | End-to-end workflow tests | 2026-02-19 |
| `Nexus\CRMOperations` | Testing | TST-CRMOP-0100 | All service implementations MUST have unit tests | tests/Unit/Services/ | ⏳ Pending | Test each integration service | 2026-02-19 |
| **LEAD ROUTING ENGINE REQUIREMENTS** |
| `Nexus\CRMOperations` | Routing | RTE-CRMOP-0101 | LeadRoutingCoordinator MUST orchestrate lead assignment workflows | src/Coordinators/LeadRoutingCoordinator.php | ⏳ Pending | execute method | 2026-02-20 |
| `Nexus\CRMOperations` | Routing | RTE-CRMOP-0102 | LeadRoutingCoordinator MUST support round-robin assignment strategy | src/Coordinators/LeadRoutingCoordinator.php | ⏳ Pending | RoundRobinStrategy | 2026-02-20 |
| `Nexus\CRMOperations` | Routing | RTE-CRMOP-0103 | LeadRoutingCoordinator MUST support territory-based assignment | src/Coordinators/LeadRoutingCoordinator.php | ⏳ Pending | TerritoryBasedStrategy | 2026-02-20 |
| `Nexus\CRMOperations` | Routing | RTE-CRMOP-0104 | LeadRoutingCoordinator MUST support skill-based assignment | src/Coordinators/LeadRoutingCoordinator.php | ⏳ Pending | SkillBasedStrategy | 2026-02-20 |
| `Nexus\CRMOperations` | Routing | RTE-CRMOP-0105 | LeadRoutingCoordinator MUST balance workload across assignees | src/Coordinators/LeadRoutingCoordinator.php | ⏳ Pending | Workload balancing | 2026-02-20 |
| `Nexus\CRMOperations` | Routing | RTE-CRMOP-0106 | RoutingRule interface MUST define assignment criteria | src/Contracts/RoutingRuleInterface.php | ⏳ Pending | evaluate method | 2026-02-20 |
| `Nexus\CRMOperations` | Routing | RTE-CRMOP-0107 | RoutingRule MUST support territory match criteria | src/Contracts/RoutingRuleInterface.php | ⏳ Pending | Territory matching | 2026-02-20 |
| `Nexus\CRMOperations` | Routing | RTE-CRMOP-0108 | RoutingRule MUST support lead score criteria | src/Contracts/RoutingRuleInterface.php | ⏳ Pending | Score threshold | 2026-02-20 |
| `Nexus\CRMOperations` | Routing | RTE-CRMOP-0109 | RoutingRule MUST support source criteria | src/Contracts/RoutingRuleInterface.php | ⏳ Pending | Lead source matching | 2026-02-20 |
| `Nexus\CRMOperations` | Routing | RTE-CRMOP-0110 | RoutingRule MUST support industry criteria | src/Contracts/RoutingRuleInterface.php | ⏳ Pending | Industry matching | 2026-02-20 |
| **OPPORTUNITY SPLIT MANAGEMENT REQUIREMENTS** |
| `Nexus\CRMOperations` | Split | SPL-CRMOP-0111 | OpportunitySplitCoordinator MUST manage revenue splits | src/Coordinators/OpportunitySplitCoordinator.php | ⏳ Pending | createSplit method | 2026-02-20 |
| `Nexus\CRMOperations` | Split | SPL-CRMOP-0112 | OpportunitySplitCoordinator MUST calculate overlay splits | src/Coordinators/OpportunitySplitCoordinator.php | ⏳ Pending | calculateOverlay | 2026-02-20 |
| `Nexus\CRMOperations` | Split | SPL-CRMOP-0113 | OpportunitySplitCoordinator MUST support team collaboration splits | src/Coordinators/OpportunitySplitCoordinator.php | ⏳ Pending | Team splits | 2026-02-20 |
| `Nexus\CRMOperations` | Split | SPL-CRMOP-0114 | OpportunitySplitCoordinator MUST validate split percentages total 100% | src/Coordinators/OpportunitySplitCoordinator.php | ⏳ Pending | Validation | 2026-02-20 |
| `Nexus\CRMOperations` | Split | SPL-CRMOP-0115 | OpportunitySplitCoordinator MUST submit splits for approval | src/Coordinators/OpportunitySplitCoordinator.php | ⏳ Pending | Approval workflow | 2026-02-20 |
| **QUOTE-TO-ORDER WORKFLOW REQUIREMENTS** |
| `Nexus\CRMOperations` | Quote | QOW-CRMOP-0116 | QuoteToOrderWorkflow MUST generate quote from opportunity | src/Workflows/QuoteToOrderWorkflow.php | ⏳ Pending | generateQuote method | 2026-02-20 |
| `Nexus\CRMOperations` | Quote | QOW-CRMOP-0117 | QuoteToOrderWorkflow MUST submit quote for approval | src/Workflows/QuoteToOrderWorkflow.php | ⏳ Pending | Approval workflow | 2026-02-20 |
| `Nexus\CRMOperations` | Quote | QOW-CRMOP-0118 | QuoteToOrderWorkflow MUST track quote status changes | src/Workflows/QuoteToOrderWorkflow.php | ⏳ Pending | Status tracking | 2026-02-20 |
| `Nexus\CRMOperations` | Quote | QOW-CRMOP-0119 | QuoteToOrderWorkflow MUST convert accepted quote to order | src/Workflows/QuoteToOrderWorkflow.php | ⏳ Pending | Convert to Sales order | 2026-02-20 |
| `Nexus\CRMOperations` | Quote | QOW-CRMOP-0120 | QuoteToOrderWorkflow MUST handle quote expiration | src/Workflows/QuoteToOrderWorkflow.php | ⏳ Pending | Expiration handling | 2026-02-20 |
| **SALES PERFORMANCE MANAGEMENT REQUIREMENTS** |
| `Nexus\CRMOperations` | Performance | SPM-CRMOP-0121 | SalesPerformanceCoordinator MUST assign quotas to sales reps | src/Coordinators/SalesPerformanceCoordinator.php | ⏳ Pending | assignQuota method | 2026-02-20 |
| `Nexus\CRMOperations` | Performance | SPM-CRMOP-0122 | SalesPerformanceCoordinator MUST track quota attainment | src/Coordinators/SalesPerformanceCoordinator.php | ⏳ Pending | trackAttainment method | 2026-02-20 |
| `Nexus\CRMOperations` | Performance | SPM-CRMOP-0123 | SalesPerformanceCoordinator MUST calculate performance metrics | src/Coordinators/SalesPerformanceCoordinator.php | ⏳ Pending | Calculate metrics | 2026-02-20 |
| `Nexus\CRMOperations` | Performance | SPM-CRMOP-0124 | SalesPerformanceCoordinator MUST identify underperformers | src/Coordinators/SalesPerformanceCoordinator.php | ⏳ Pending | Identification logic | 2026-02-20 |
| `Nexus\CRMOperations` | Performance | SPM-CRMOP-0125 | SalesPerformanceCoordinator MUST trigger coaching workflows | src/Coordinators/SalesPerformanceCoordinator.php | ⏳ Pending | Coaching triggers | 2026-02-20 |
| `Nexus\CRMOperations` | Performance | SPM-CRMOP-0126 | SalesPerformanceCoordinator MUST generate performance dashboards | src/Coordinators/SalesPerformanceCoordinator.php | ⏳ Pending | Dashboard data | 2026-02-20 |
| **COMMISSION WORKFLOW REQUIREMENTS** |
| `Nexus\CRMOperations` | Commission | COM-CRMOP-0127 | CommissionWorkflow MUST calculate commissions from opportunities | src/Workflows/CommissionWorkflow.php | ⏳ Pending | calculateCommission | 2026-02-20 |
| `Nexus\CRMOperations` | Commission | COM-CRMOP-0128 | CommissionWorkflow MUST support tiered commission rates | src/Workflows/CommissionWorkflow.php | ⏳ Pending | Tiered rates | 2026-02-20 |
| `Nexus\CRMOperations` | Commission | COM-CRMOP-0129 | CommissionWorkflow MUST handle split commissions | src/Workflows/CommissionWorkflow.php | ⏳ Pending | Split handling | 2026-02-20 |
| `Nexus\CRMOperations` | Commission | COM-CRMOP-0130 | CommissionWorkflow MUST submit commissions for approval | src/Workflows/CommissionWorkflow.php | ⏳ Pending | Approval workflow | 2026-02-20 |
| `Nexus\CRMOperations` | Commission | COM-CRMOP-0131 | CommissionWorkflow MUST allow commission adjustments | src/Workflows/CommissionWorkflow.php | ⏳ Pending | Adjustments | 2026-02-20 |
| `Nexus\CRMOperations` | Commission | COM-CRMOP-0132 | CommissionWorkflow MUST generate commission statements | src/Workflows/CommissionWorkflow.php | ⏳ Pending | Statement generation | 2026-02-20 |
| **CUSTOMER SUCCESS HANDOFF REQUIREMENTS** |
| `Nexus\CRMOperations` | Handoff | CSH-CRMOP-0133 | CustomerSuccessHandoffWorkflow MUST trigger on opportunity close | src/Workflows/CustomerSuccessHandoffWorkflow.php | ⏳ Pending | Close trigger | 2026-02-20 |
| `Nexus\CRMOperations` | Handoff | CSH-CRMOP-0134 | CustomerSuccessHandoffWorkflow MUST create onboarding tasks | src/Workflows/CustomerSuccessHandoffWorkflow.php | ⏳ Pending | Task creation | 2026-02-20 |
| `Nexus\CRMOperations` | Handoff | CSH-CRMOP-0135 | CustomerSuccessHandoffWorkflow MUST transfer account ownership | src/Workflows/CustomerSuccessHandoffWorkflow.php | ⏳ Pending | Ownership transfer | 2026-02-20 |
| `Nexus\CRMOperations` | Handoff | CSH-CRMOP-0136 | CustomerSuccessHandoffWorkflow MUST notify customer success team | src/Workflows/CustomerSuccessHandoffWorkflow.php | ⏳ Pending | Notifications | 2026-02-20 |
| `Nexus\CRMOperations` | Handoff | CSH-CRMOP-0137 | CustomerSuccessHandoffWorkflow MUST create success plan | src/Workflows/CustomerSuccessHandoffWorkflow.php | ⏳ Pending | Plan creation | 2026-02-20 |
| **RENEWAL MANAGEMENT REQUIREMENTS** |
| `Nexus\CRMOperations` | Renewal | RNW-CRMOP-0138 | RenewalManagementWorkflow MUST create renewal opportunities | src/Workflows/RenewalManagementWorkflow.php | ⏳ Pending | Create renewal opp | 2026-02-20 |
| `Nexus\CRMOperations` | Renewal | RNW-CRMOP-0139 | RenewalManagementWorkflow MUST send renewal reminders | src/Workflows/RenewalManagementWorkflow.php | ⏳ Pending | Reminder sending | 2026-02-20 |
| `Nexus\CRMOperations` | Renewal | RNW-CRMOP-0140 | RenewalManagementWorkflow MUST calculate churn risk | src/Workflows/RenewalManagementWorkflow.php | ⏳ Pending | Risk scoring | 2026-02-20 |
| `Nexus\CRMOperations` | Renewal | RNW-CRMOP-0141 | RenewalManagementWorkflow MUST identify upsell opportunities | src/Workflows/RenewalManagementWorkflow.php | ⏳ Pending | Upsell identification | 2026-02-20 |
| `Nexus\CRMOperations` | Renewal | RNW-CRMOP-0142 | RenewalManagementWorkflow MUST track renewal probability | src/Workflows/RenewalManagementWorkflow.php | ⏳ Pending | Probability tracking | 2026-02-20 |
| **PARTNER SALES MANAGEMENT REQUIREMENTS** |
| `Nexus\CRMOperations` | Partner | PRT-CRMOP-0143 | PartnerSalesCoordinator MUST distribute leads to partners | src/Coordinators/PartnerSalesCoordinator.php | ⏳ Pending | Lead distribution | 2026-02-20 |
| `Nexus\CRMOperations` | Partner | PRT-CRMOP-0144 | PartnerSalesCoordinator MUST register partner opportunities | src/Coordinators/PartnerSalesCoordinator.php | ⏳ Pending | Opp registration | 2026-02-20 |
| `Nexus\CRMOperations` | Partner | PRT-CRMOP-0145 | PartnerSalesCoordinator MUST calculate partner commissions | src/Coordinators/PartnerSalesCoordinator.php | ⏳ Pending | Commission calc | 2026-02-20 |
| `Nexus\CRMOperations` | Partner | PRT-CRMOP-0146 | PartnerSalesCoordinator MUST handle deal registration | src/Coordinators/PartnerSalesCoordinator.php | ⏳ Pending | Deal registration | 2026-02-20 |
| `Nexus\CRMOperations` | Partner | PRT-CRMOP-0147 | PartnerSalesCoordinator MUST validate partner exclusivity | src/Coordinators/PartnerSalesCoordinator.php | ⏳ Pending | Exclusivity check | 2026-02-20 |
| **REAL-TIME NOTIFICATION REQUIREMENTS** |
| `Nexus\CRMOperations` | Notification | RTN-CRMOP-0148 | RealTimeNotifier MUST coordinate WebSocket notifications | src/Services/RealTimeNotifier.php | ⏳ Pending | WebSocket integration | 2026-02-20 |
| `Nexus\CRMOperations` | Notification | RTN-CRMOP-0149 | RealTimeNotifier MUST send real-time alerts | src/Services/RealTimeNotifier.php | ⏳ Pending | Alert sending | 2026-02-20 |
| `Nexus\CRMOperations` | Notification | RTN-CRMOP-0150 | RealTimeNotifier MUST coordinate push notifications | src/Services/RealTimeNotifier.php | ⏳ Pending | Push coordination | 2026-02-20 |
| **DOCUMENT GENERATION REQUIREMENTS** |
| `Nexus\CRMOperations` | Document | DOC-CRMOP-0151 | DocumentGenerator MUST generate proposals from templates | src/Services/DocumentGenerator.php | ⏳ Pending | Proposal generation | 2026-02-20 |
| `Nexus\CRMOperations` | Document | DOC-CRMOP-0152 | DocumentGenerator MUST generate contracts | src/Services/DocumentGenerator.php | ⏳ Pending | Contract generation | 2026-02-20 |
| `Nexus\CRMOperations` | Document | DOC-CRMOP-0153 | DocumentGenerator MUST generate quote documents | src/Services/DocumentGenerator.php | ⏳ Pending | Quote documents | 2026-02-20 |
| `Nexus\CRMOperations` | Document | DOC-CRMOP-0154 | DocumentGenerator MUST render templates with data | src/Services/DocumentGenerator.php | ⏳ Pending | Template rendering | 2026-02-20 |
| **SALES PLAYBOOK REQUIREMENTS** |
| `Nexus\CRMOperations` | Playbook | PLB-CRMOP-0155 | SalesPlaybookWorkflow MUST provide guided selling steps | src/Workflows/SalesPlaybookWorkflow.php | ⏳ Pending | Guided steps | 2026-02-20 |
| `Nexus\CRMOperations` | Playbook | PLB-CRMOP-0156 | SalesPlaybookWorkflow MUST trigger playbooks on events | src/Workflows/SalesPlaybookWorkflow.php | ⏳ Pending | Event triggers | 2026-02-20 |
| `Nexus\CRMOperations` | Playbook | PLB-CRMOP-0157 | SalesPlaybookWorkflow MUST recommend next best actions | src/Workflows/SalesPlaybookWorkflow.php | ⏳ Pending | NBA recommendations | 2026-02-20 |
| `Nexus\CRMOperations` | Playbook | PLB-CRMOP-0158 | SalesPlaybookWorkflow MUST track playbook completion | src/Workflows/SalesPlaybookWorkflow.php | ⏳ Pending | Completion tracking | 2026-02-20 |

---

## Requirements Summary

### By Type
- **Architectural Requirements:** 10 (0% complete)
- **Business Requirements:** 12 (0% complete)
- **Functional Requirements:** 10 (0% complete)
- **Interface Requirements:** 16 (0% complete)
- **Workflow Requirements:** 15 (0% complete)
- **Coordinator Requirements:** 17 (0% complete)
- **Integration Requirements:** 9 (0% complete)
- **Testing Requirements:** 10 (0% complete)
- **Lead Routing Requirements:** 10 (0% complete)
- **Opportunity Split Requirements:** 5 (0% complete)
- **Quote-to-Order Requirements:** 5 (0% complete)
- **Sales Performance Requirements:** 6 (0% complete)
- **Commission Requirements:** 6 (0% complete)
- **Customer Success Handoff Requirements:** 5 (0% complete)
- **Renewal Management Requirements:** 5 (0% complete)
- **Partner Sales Requirements:** 5 (0% complete)
- **Real-time Notification Requirements:** 3 (0% complete)
- **Document Generation Requirements:** 4 (0% complete)
- **Sales Playbook Requirements:** 4 (0% complete)

### By Status
- ✅ **Complete:** 0 (0%)
- ⏳ **Pending:** 189 (100%)
- 🚧 **In Progress:** 0 (0%)
- ❌ **Blocked:** 0 (0%)

---

## Notes

### Package Structure

The CRMOperations orchestrator provides cross-package coordination for:

1. **Lead Conversion**: Converting leads to opportunities with customer creation in Party package
2. **Deal Closing**: Closing opportunities with quotation management in Sales package
3. **Pipeline Analytics**: Aggregating metrics across CRM and Analytics packages
4. **SLA Monitoring**: Detecting SLA breaches and triggering notifications via Notifier package
5. **Approval Workflows**: Managing deal approval based on value thresholds
6. **Lead Routing**: Automatic lead assignment using round-robin, territory, or skill-based strategies
7. **Opportunity Splits**: Managing revenue splits and team collaboration
8. **Quote-to-Order**: Converting quotes to orders with approval workflows
9. **Sales Performance**: Quota management and performance tracking
10. **Commission Workflows**: Commission calculation and approval
11. **Customer Success**: Handoff from sales to customer success
12. **Renewal Management**: Managing subscription renewals and churn risk
13. **Partner Sales**: Partner lead distribution and deal registration
14. **Document Generation**: Creating proposals, contracts, and quotes
15. **Sales Playbooks**: Guided selling workflows and next best actions

### Design Decisions

1. **Interface Segregation**: All provider interfaces are defined in the orchestrator, not importing from atomic packages
2. **CQRS Pattern**: Uses CRM package query/persist interfaces for read/write separation
3. **Workflow Pattern**: Multi-step workflows with state tracking and result DTOs
4. **Rule Pattern**: Configurable business rules with threshold-based evaluation
5. **Framework Agnostic**: No dependencies on Laravel, Symfony, or any web framework

### Consumer Packages

This package is consumed by:
- `Nexus\CRMOperationsAdapter` - Laravel adapter for dependency injection
- Application layer - API endpoints and commands

---

**Document Version**: 1.1  
**Creation Date:** 2026-02-19  
**Last Updated:** 2026-02-20  
**Maintained By:** Nexus Architecture Team  
**Compliance:** Orchestrator Package Layer Standards
