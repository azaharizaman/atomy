# Requirements: Nexus\Project

Layer 1 atomic package: core project entity and lifecycle rules.

| Package Namespace | Requirements Type | Code | Requirement Statements | Status |
|-------------------|-------------------|------|------------------------|--------|
| `Nexus\Project` | Business Requirements | BUS-PRO-0042 | A project MUST have a project manager assigned | |
| `Nexus\Project` | Business Requirements | BUS-PRO-0096 | Project status cannot be "completed" if there are incomplete tasks | |
| `Nexus\Project` | Business Requirements | BUS-PRO-0106 | Client stakeholders can view only their own projects | |
| `Nexus\Project` | Business Requirements | BUS-PRO-0121 | Lessons learned can only be created after project status = completed or cancelled | |
| `Nexus\Project` | Functional Requirement | FUN-PRO-0236 | Create project with basic details | |
| `Nexus\Project` | Functional Requirement | FUN-PRO-0564 | Create project with basic details (name, client, start/end, budget) | |
| `Nexus\Project` | Functional Requirement | FUN-PRO-0266 | Project dashboard | |
| `Nexus\Project` | Functional Requirement | FUN-PRO-0568 | Project dashboard (overview, % complete) | |
| `Nexus\Project` | Performance Requirement | PER-PRO-0328 | Project creation and save | |
| `Nexus\Project` | Performance Requirement | PER-PRO-0354 | Portfolio dashboard loading | |
| `Nexus\Project` | Security and Compliance Requirement | SEC-PRO-0442 | Tenant data isolation | |
| `Nexus\Project` | Security and Compliance Requirement | SEC-PRO-0448 | Role-based access control | |
| `Nexus\Project` | Security and Compliance Requirement | SEC-PRO-0454 | Client portal access | |
| `Nexus\Project` | User Story | USE-PRO-0509 | As a project manager, I want to create a project with basic details (name, client, start/end dates, budget) | |
