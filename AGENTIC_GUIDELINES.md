# Nexus Agentic Guidelines

These guidelines are designed to maximize the efficiency and accuracy of AI agents (Opus 4, Codex 5.3, etc.) working in the Nexus Monorepo.

## 1. Multi-Agent Coordination

Nexus is a large system. Agents should follow these coordination patterns:

- **Architect Agent**: Responsible for design, interface definitions, and enforcing boundaries. Operates primarily in `ARCHITECTURE.md` and `Contracts/`.
- **Developer Agent**: Responsible for implementation and logic. Follows Architect's contracts.
- **QA Agent**: Responsible for testing, verification, and regression analysis.
- **Maintenance Agent**: Handles dependency updates (Dependabot), documentation syncing, and refactoring.

## 2. Shared Multi-Agent Context

To ensure continuity across sessions and between different agents, we use the following standard artifacts:

- **`.agent/tasks/active_task.md`**: The source of truth for the current in-progress work.
- **`.agent/logs/session_history.txt`**: A brief log of major decisions made in the current session.
- **`PROJECT_STATE.md`**: A root-level file (or in `.agent/`) that tracks the high-level roadmap and completion status of all 51+ packages.

## 3. Tool Engagement Rules

- **Discovery First**: Before making any changes, use `ls -R` or `find` to map the relevant packages. Do not assume a package doesn't exist just because it wasn't in the initial prompt.
- **Interface First**: Never create a service without first defining its contract in `src/Contracts/`.
- **Validation**: Use `composer test` or equivalent within the specific package directory being modified.

## 4. Documentation as Code

Agents must treat documentation as part of the implementation:
1. Update `IMPLEMENTATION_SUMMARY.md` in individual packages after every change.
2. Update `ARCHITECTURE.md` if any new pattern is introduced.
3. Sync `docs/NEXUS_SYSTEM_OVERVIEW.md` if the project scope changes.

## 5. Frontier Model Optimizations (Opus 4 / Codex 5.3)

- **Context Packing**: When passing instructions to a sub-agent, provide the relevant `ARCHITECTURE.md` sections directly to avoid halluncinations about layer boundaries.
- **Atomic Commits**: Agents should commit after completing each atomic requirement to provide clear history for the next agent.
- **Verification Logs**: Always include the output of test runs in the `walkthrough.md` to prove completion to the human USER and future agents.
