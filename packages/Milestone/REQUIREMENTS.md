# Requirements: Nexus\Milestone

Layer 1 atomic package: milestone entity, approvals, deliverables, billing/revenue rules.

| Package Namespace | Requirements Type | Code | Requirement Statements | Status |
|-------------------|-------------------|------|------------------------|--------|
| `Nexus\Milestone` | Business Requirements | BUS-PRO-0077 | Milestone billing amount cannot exceed remaining project budget (for fixed-price projects) | |
| `Nexus\Milestone` | Business Requirements | BUS-PRO-0111 | Revenue recognition for fixed-price projects based on % completion or milestone approval | |
| `Nexus\Milestone` | Functional Requirement | FUN-PRO-0569 | Milestones with approvals and deliverables | |
| `Nexus\Milestone` | Reliability Requirement | REL-PRO-0390 | All financial calculations MUST be ACID-compliant | |
| `Nexus\Milestone` | Reliability Requirement | REL-PRO-0408 | Milestone approval workflow MUST be resumable after failure | |
| `Nexus\Milestone` | Security and Compliance Requirement | SEC-PRO-0466 | Financial data protection | |
