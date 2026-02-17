# Nexus Agent Knowledge Hub

This file is a central directory for agents to find specialized information.

## 🏗️ Architecture & Philosophy
- **Core Rules**: [ARCHITECTURE.md](ARCHITECTURE.md)
- **Multi-Agent Coordination**: [AGENTIC_GUIDELINES.md](AGENTIC_GUIDELINES.md)
- **Advanced Orchestrator Pattern**: [ARCHITECTURE.md#3-the-advanced-orchestrator-pattern](ARCHITECTURE.md#3-the-advanced-orchestrator-pattern)

## 📦 Package Ecosystem
- **System Overview**: [docs/NEXUS_SYSTEM_OVERVIEW.md](docs/NEXUS_SYSTEM_OVERVIEW.md)
- **Package Reference**: [docs/NEXUS_PACKAGES_REFERENCE.md](docs/NEXUS_PACKAGES_REFERENCE.md)
- **Shared Value Objects**: [packages/Common/src/ValueObjects/](packages/Common/src/ValueObjects/)

## 🛠️ Tooling & Workflows
- **Discovery Workflow**: [.agent/workflows/discovery.md](.agent/workflows/discovery.md)
- **Architect Persona**: [.agent/workflows/nexus-architect.md](.agent/workflows/nexus-architect.md)
- **Developer Persona**: [.agent/workflows/nexus-developer.md](.agent/workflows/nexus-developer.md)
- **QA Persona**: [.agent/workflows/qa-engineer.md](.agent/workflows/qa-engineer.md)

## 📨 Coordination
- **Active Task**: [.agent/tasks/active_task.md](.agent/tasks/active_task.md)
- **Task Template**: [.agent/tasks/TEMPLATE.md](.agent/tasks/TEMPLATE.md)

## 🚨 Guardrails
- **No Frameworks in Packages**: Enforced by [QA Engineer workflow](.agent/workflows/qa-engineer.md).
- **Contract/Interface First**: Enforced by [Architect persona](.agent/workflows/nexus-architect.md).
- **Statelessness**: Enforced by [Developer persona](.agent/workflows/nexus-developer.md).
