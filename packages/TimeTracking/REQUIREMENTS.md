# Requirements: Nexus\TimeTracking

Layer 1 atomic package: time entry and timesheet rules. Reusable for projects, support, internal work.

| Package Namespace | Requirements Type | Code | Requirement Statements | Status |
|-------------------|-------------------|------|------------------------|--------|
| `Nexus\TimeTracking` | Business Requirements | BUS-PRO-0056 | Timesheet hours cannot be negative or exceed 24 hours per day per user | |
| `Nexus\TimeTracking` | Business Requirements | BUS-PRO-0063 | Approved timesheets are immutable (cannot be edited or deleted) | |
| `Nexus\TimeTracking` | Business Requirements | BUS-PRO-0070 | A task's actual hours MUST equal the sum of all approved timesheet hours for that task | |
| `Nexus\TimeTracking` | Business Requirements | BUS-PRO-0101 | Timesheet billing rate defaults to resource allocation rate for the project | |
| `Nexus\TimeTracking` | Business Requirements | BUS-PRO-0125 | Timesheet approval requires user to have approve-timesheet permission for the project | |
| `Nexus\TimeTracking` | Functional Requirement | FUN-PRO-0254 | Time tracking and timesheet entry | |
| `Nexus\TimeTracking` | Functional Requirement | FUN-PRO-0566 | Time tracking and timesheet entry | |
| `Nexus\TimeTracking` | Functional Requirement | FUN-PRO-0575 | Timesheet approval workflow | |
| `Nexus\TimeTracking` | Functional Requirement | FUN-PRO-0272 | Time report by project | |
| `Nexus\TimeTracking` | Performance Requirement | PER-PRO-0342 | Timesheet entry and save | |
| `Nexus\TimeTracking` | Reliability Requirement | REL-PRO-0396 | Timesheet approval MUST prevent double-billing | |
| `Nexus\TimeTracking` | Security and Compliance Requirement | SEC-PRO-0460 | Timesheet integrity | |
| `Nexus\TimeTracking` | User Story | USE-PRO-0530 | As a team member, I want to log time against tasks (hours worked, date, description) | |
| `Nexus\TimeTracking` | User Story | USE-PRO-0537 | As a project manager, I want to view time logged by team members to track project progress | |
