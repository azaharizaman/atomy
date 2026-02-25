# Nexus CustomerServiceOperations Orchestrator

**Post-Sales Support and Service Excellence Coordination**

[![PHP Version](https://img.shields.io/badge/php-%5E8.3-blue)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

## Overview

`Nexus\CustomerServiceOperations` orchestrates the complete **Customer Support Lifecycle**, connecting customer requests with internal knowledge and resources. It coordinates workflows across CRM, Messaging, Content, Document, and Workflow domains.

---

## Progressive Disclosure Philosophy

This orchestrator scales with your business needs:

### Tier 1: Essential (Small Business)
- Basic Ticket Creation & Lifecycle
- Simple Email Messaging
- Basic Knowledge Base Lookup
- Manual Agent Assignment

### Tier 2: Growth (Mid-Market)
- Ticket Prioritization & Categorization
- Multi-channel Messaging (Email, SMS)
- Basic SLA Management (Response Time tracking)
- Customer Interaction History Integration

### Tier 3: Enterprise (Large Corporation)
- Intelligent Ticket Routing (Skills-based)
- Advanced SLA Escalations (Multi-level, automatic reassignment)
- Knowledge-Infused Automated Suggestions for agents
- Ticket Attachments & Case Files management
- Complex Workflow State Machines

---

## Architecture

```
src/
├── Contracts/           # Orchestrator interfaces (dependency inversion)
│   ├── TicketInterface.php
│   ├── CustomerInterface.php
│   ├── AgentInterface.php
│   ├── MessageInterface.php
│   ├── KnowledgeBaseArticleInterface.php
│   ├── SlaInterface.php
│   ├── TicketRepositoryInterface.php
│   ├── TicketLifecycleWorkflowInterface.php
│   └── ...
├── Coordinators/        # Stateless traffic cops
│   ├── TicketRoutingCoordinator.php
│   ├── KnowledgeSuggestionCoordinator.php
│   └── ...
├── Workflows/           # Stateful long-running processes
│   ├── TicketLifecycleWorkflow.php
│   ├── SlaEscalationWorkflow.php
│   └── ...
├── Services/            # Pure business calculations
│   ├── SlaCalculator.php
│   ├── AgentSkillMatcher.php
│   └── ...
├── Listeners/           # Event reactors
│   ├── OnTicketCreatedRouteTicket.php
│   ├── OnSlaBreachedEscalate.php
│   └── ...
├── Rules/               # Validation constraints
│   ├── SlaComplianceRule.php
│   ├── AgentAvailabilityRule.php
│   └── ...
├── DataProviders/       # Cross-package data aggregation
│   ├── TicketContextProvider.php
│   └── ...
├── DTOs/                # Strict data contracts
│   ├── TicketDTO.php
│   ├── RoutingResultDTO.php
│   └── ...
├── Enums/               # Type-safe enumerations
│   ├── TicketStatus.php
│   ├── TicketPriority.php
│   ├── SlaStatus.php
│   └── ...
└── Exceptions/          # Domain-specific errors
    ├── TicketNotFoundException.php
    ├── SlaBreachException.php
    └── ...
```

---

## Event-Driven Integration

### Events Published

| Event | When Fired |
|-------|------------|
| `TicketCreatedEvent` | New support ticket submitted |
| `TicketAssignedEvent` | Ticket assigned to an agent |
| `TicketResolvedEvent` | Ticket marked as resolved |
| `SlaBreachedEvent` | SLA threshold exceeded |
| `MessageSentEvent` | Communication sent to customer |

### Event Listeners

| Listens To | Listener | Action |
|------------|----------|--------|
| `TicketCreatedEvent` | `RouteTicketListener` | Run intelligent routing |
| `TicketCreatedEvent` | `InitializeSlaListener` | Set SLA deadlines |
| `SlaBreachedEvent` | `EscalateTicketListener` | Trigger escalation workflow |

---

## Progressive Disclosure Matrix

| Feature | Tier 1 (SMB) | Tier 2 (Mid) | Tier 3 (Enterprise) |
|---------|--------------|--------------|---------------------|
| Ticket Lifecycle | ✅ | ✅ | ✅ |
| Email Messaging | ✅ | ✅ | ✅ |
| Manual Assignment | ✅ | ✅ | ✅ |
| KB Lookup | ✅ | ✅ | ✅ |
| Prioritization | - | ✅ | ✅ |
| SMS Messaging | - | ✅ | ✅ |
| Basic SLA Tracking | - | ✅ | ✅ |
| Intelligent Routing | - | - | ✅ |
| Advanced Escalations| - | - | ✅ |
| Agent Suggestions | - | - | ✅ |
| File Attachments | - | - | ✅ |

---

## Installation

```bash
composer require nexus/customer-service-operations
```

### Required Dependencies (via Adapters)

| Package | Purpose |
|---------|---------|
| `Nexus\CRM` | Customer profiles, contact history |
| `Nexus\Messaging` | Multi-channel communication |
| `Nexus\Content` | Knowledge base articles |
| `Nexus\Document` | Ticket attachments |
| `Nexus\Workflow` | SLA state machines |

---

## License

MIT License - See [LICENSE](LICENSE) file.
