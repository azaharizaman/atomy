# Nexus CustomerServiceOperations Orchestrator - TODO

**Status:** Scaffolding

---

## 1. Foundation
- [x] Directory Structure
- [x] `composer.json`
- [x] `README.md`
- [ ] `VALUATION_MATRIX.md`

## 2. Contracts (Priority: HIGH)
- [x] `TicketInterface.php`
- [x] `CustomerInterface.php`
- [x] `AgentInterface.php`
- [x] `MessageInterface.php`
- [x] `KnowledgeBaseArticleInterface.php`
- [x] `SLAInterface.php`
- [x] `TicketProviderInterface.php`

## 3. Enums & DTOs
- [x] `TicketStatus.php`
- [x] `TicketPriority.php`
- [x] `SlaStatus.php`
- [x] `TicketDTO.php`
- [x] `RoutingResultDTO.php`

## 4. Coordinators
- [/] `TicketRoutingCoordinator.php` (Scaffolded)
- [ ] `KnowledgeSuggestionCoordinator.php`

## 5. Workflows
- [/] `TicketLifecycleWorkflow.php` (Scaffolded)
- [ ] `SlaEscalationWorkflow.php`

## 6. Services
- [ ] `SlaCalculator.php`
- [ ] `AgentSkillMatcher.php`

---

## Notes
- Follow the Orchestrator Interface Segregation Pattern.
- Implement features according to tier levels (Progressive Disclosure).
- Coordinators are stateless; Workflows are stateful.
