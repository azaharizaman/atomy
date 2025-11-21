# Requirements: Projectmanagement

Total Requirements: 114

| Package Namespace | Requirements Type | Code | Requirement Statements | Files/Folders | Status | Notes on Status | Date Last Updated |
|-------------------|-------------------|------|------------------------|---------------|--------|-----------------|-------------------|
| `Nexus\ProjectManagement` | Business Requirements | BUS-PRO-0042 | A project MUST have a project manager assigned |  |  |  |  |
| `Nexus\ProjectManagement` | Business Requirements | BUS-PRO-0049 | A task MUST belong to a project |  |  |  |  |
| `Nexus\ProjectManagement` | Business Requirements | BUS-PRO-0056 | Timesheet hours cannot be negative or exceed 24 hours per day per user |  |  |  |  |
| `Nexus\ProjectManagement` | Business Requirements | BUS-PRO-0063 | Approved timesheets are immutable (cannot be edited or deleted) |  |  |  |  |
| `Nexus\ProjectManagement` | Business Requirements | BUS-PRO-0070 | A task's actual hours MUST equal the sum of all approved timesheet hours for that task |  |  |  |  |
| `Nexus\ProjectManagement` | Business Requirements | BUS-PRO-0077 | Milestone billing amount cannot exceed remaining project budget (for fixed-price projects) |  |  |  |  |
| `Nexus\ProjectManagement` | Business Requirements | BUS-PRO-0084 | Resource allocation percentage cannot exceed 100% per user per day |  |  |  |  |
| `Nexus\ProjectManagement` | Business Requirements | BUS-PRO-0090 | Task dependencies must not create circular references |  |  |  |  |
| `Nexus\ProjectManagement` | Business Requirements | BUS-PRO-0096 | Project status cannot be "completed" if there are incomplete tasks |  |  |  |  |
| `Nexus\ProjectManagement` | Business Requirements | BUS-PRO-0101 | Timesheet billing rate defaults to resource allocation rate for the project |  |  |  |  |
| `Nexus\ProjectManagement` | Business Requirements | BUS-PRO-0106 | Client stakeholders can view only their own projects |  |  |  |  |
| `Nexus\ProjectManagement` | Business Requirements | BUS-PRO-0111 | Revenue recognition for fixed-price projects based on % completion or milestone approval |  |  |  |  |
| `Nexus\ProjectManagement` | Business Requirements | BUS-PRO-0116 | Earned value calculations require baseline (planned) values to be set |  |  |  |  |
| `Nexus\ProjectManagement` | Business Requirements | BUS-PRO-0121 | Lessons learned can only be created after project status = completed or cancelled |  |  |  |  |
| `Nexus\ProjectManagement` | Business Requirements | BUS-PRO-0125 | Timesheet approval requires user to have approve-timesheet permission for the project |  |  |  |  |
| `Nexus\ProjectManagement` | Functional Requirement | FUN-PRO-0236 | Create project with basic details |  |  |  |  |
| `Nexus\ProjectManagement` | Functional Requirement | FUN-PRO-0242 | Create and manage tasks |  |  |  |  |
| `Nexus\ProjectManagement` | Functional Requirement | FUN-PRO-0248 | Task assignment and notifications |  |  |  |  |
| `Nexus\ProjectManagement` | Functional Requirement | FUN-PRO-0254 | Time tracking and timesheet entry |  |  |  |  |
| `Nexus\ProjectManagement` | Functional Requirement | FUN-PRO-0260 | My Tasks view |  |  |  |  |
| `Nexus\ProjectManagement` | Functional Requirement | FUN-PRO-0266 | Project dashboard |  |  |  |  |
| `Nexus\ProjectManagement` | Functional Requirement | FUN-PRO-0272 | Time report by project |  |  |  |  |
| `Nexus\ProjectManagement` | Functional Requirement | FUN-PRO-0564 | Create project with basic details (name, client, start/end, budget) |  |  |  |  |
| `Nexus\ProjectManagement` | Functional Requirement | FUN-PRO-0565 | Create and manage tasks with assignees + priority |  |  |  |  |
| `Nexus\ProjectManagement` | Functional Requirement | FUN-PRO-0566 | Time tracking and timesheet entry |  |  |  |  |
| `Nexus\ProjectManagement` | Functional Requirement | FUN-PRO-0567 | My Tasks (view all tasks assigned to user) |  |  |  |  |
| `Nexus\ProjectManagement` | Functional Requirement | FUN-PRO-0568 | Project dashboard (overview, % complete) |  |  |  |  |
| `Nexus\ProjectManagement` | Functional Requirement | FUN-PRO-0569 | Milestones with approvals and deliverables |  |  |  |  |
| `Nexus\ProjectManagement` | Functional Requirement | FUN-PRO-0570 | Task dependencies (predecessor) & Gantt-support (calc) |  |  |  |  |
| `Nexus\ProjectManagement` | Functional Requirement | FUN-PRO-0571 | Resource allocation & overallocation checks |  |  |  |  |
| `Nexus\ProjectManagement` | Functional Requirement | FUN-PRO-0572 | Budget tracking (planned vs actual) |  |  |  |  |
| `Nexus\ProjectManagement` | Functional Requirement | FUN-PRO-0573 | Project invoicing (milestone/T&M) |  |  |  |  |
| `Nexus\ProjectManagement` | Functional Requirement | FUN-PRO-0574 | Expense tracking & approvals |  |  |  |  |
| `Nexus\ProjectManagement` | Functional Requirement | FUN-PRO-0575 | Timesheet approval workflow |  |  |  |  |
| `Nexus\ProjectManagement` | Performance Requirement | PER-PRO-0328 | Project creation and save |  |  |  |  |
| `Nexus\ProjectManagement` | Performance Requirement | PER-PRO-0335 | Task creation and assignment |  |  |  |  |
| `Nexus\ProjectManagement` | Performance Requirement | PER-PRO-0342 | Timesheet entry and save |  |  |  |  |
| `Nexus\ProjectManagement` | Performance Requirement | PER-PRO-0348 | Gantt chart rendering (100 tasks) |  |  |  |  |
| `Nexus\ProjectManagement` | Performance Requirement | PER-PRO-0354 | Portfolio dashboard loading |  |  |  |  |
| `Nexus\ProjectManagement` | Performance Requirement | PER-PRO-0357 | Resource allocation view |  |  |  |  |
| `Nexus\ProjectManagement` | Reliability Requirement | REL-PRO-0390 | All financial calculations MUST be ACID-compliant |  |  |  |  |
| `Nexus\ProjectManagement` | Reliability Requirement | REL-PRO-0396 | Timesheet approval MUST prevent double-billing |  |  |  |  |
| `Nexus\ProjectManagement` | Reliability Requirement | REL-PRO-0402 | Resource allocation MUST prevent double-booking |  |  |  |  |
| `Nexus\ProjectManagement` | Reliability Requirement | REL-PRO-0408 | Milestone approval workflow MUST be resumable after failure |  |  |  |  |
| `Nexus\ProjectManagement` | Security and Compliance Requirement | SEC-PRO-0442 | Tenant data isolation |  |  |  |  |
| `Nexus\ProjectManagement` | Security and Compliance Requirement | SEC-PRO-0448 | Role-based access control |  |  |  |  |
| `Nexus\ProjectManagement` | Security and Compliance Requirement | SEC-PRO-0454 | Client portal access |  |  |  |  |
| `Nexus\ProjectManagement` | Security and Compliance Requirement | SEC-PRO-0460 | Timesheet integrity |  |  |  |  |
| `Nexus\ProjectManagement` | Security and Compliance Requirement | SEC-PRO-0466 | Financial data protection |  |  |  |  |
| `Nexus\ProjectManagement` | Security and Compliance Requirement | SEC-PRO-0471 | Audit trail completeness |  |  |  |  |
| `Nexus\ProjectManagement` | User Story | USE-PRO-0509 | As a project manager, I want to create a project with basic details (name, client, start/end dates, budget) |  |  |  |  |
| `Nexus\ProjectManagement` | User Story | USE-PRO-0516 | As a project manager, I want to create tasks within a project with descriptions, assignees, and due dates |  |  |  |  |
| `Nexus\ProjectManagement` | User Story | USE-PRO-0523 | As a team member, I want to view all tasks assigned to me across all projects in one place |  |  |  |  |
| `Nexus\ProjectManagement` | User Story | USE-PRO-0530 | As a team member, I want to log time against tasks (hours worked, date, description) |  |  |  |  |
| `Nexus\ProjectManagement` | User Story | USE-PRO-0537 | As a project manager, I want to view time logged by team members to track project progress |  |  |  |  |
| `Nexus\ProjectManagement` | User Story | USE-PRO-0543 | As a project manager, I want to mark tasks as complete and track project completion percentage |  |  |  |  |
| `Nexus\ProjectManagement` | User Story | USE-PRO-0549 | As a team member, I want to receive notifications when tasks are assigned to me or deadlines are approaching |  |  |  |  |
| `Nexus\ProjectManagement` | Business Requirements | BUS-PRO-0042 | A project MUST have a project manager assigned |  |  |  |  |
| `Nexus\ProjectManagement` | Business Requirements | BUS-PRO-0049 | A task MUST belong to a project |  |  |  |  |
| `Nexus\ProjectManagement` | Business Requirements | BUS-PRO-0056 | Timesheet hours cannot be negative or exceed 24 hours per day per user |  |  |  |  |
| `Nexus\ProjectManagement` | Business Requirements | BUS-PRO-0063 | Approved timesheets are immutable (cannot be edited or deleted) |  |  |  |  |
| `Nexus\ProjectManagement` | Business Requirements | BUS-PRO-0070 | A task's actual hours MUST equal the sum of all approved timesheet hours for that task |  |  |  |  |
| `Nexus\ProjectManagement` | Business Requirements | BUS-PRO-0077 | Milestone billing amount cannot exceed remaining project budget (for fixed-price projects) |  |  |  |  |
| `Nexus\ProjectManagement` | Business Requirements | BUS-PRO-0084 | Resource allocation percentage cannot exceed 100% per user per day |  |  |  |  |
| `Nexus\ProjectManagement` | Business Requirements | BUS-PRO-0090 | Task dependencies must not create circular references |  |  |  |  |
| `Nexus\ProjectManagement` | Business Requirements | BUS-PRO-0096 | Project status cannot be "completed" if there are incomplete tasks |  |  |  |  |
| `Nexus\ProjectManagement` | Business Requirements | BUS-PRO-0101 | Timesheet billing rate defaults to resource allocation rate for the project |  |  |  |  |
| `Nexus\ProjectManagement` | Business Requirements | BUS-PRO-0106 | Client stakeholders can view only their own projects |  |  |  |  |
| `Nexus\ProjectManagement` | Business Requirements | BUS-PRO-0111 | Revenue recognition for fixed-price projects based on % completion or milestone approval |  |  |  |  |
| `Nexus\ProjectManagement` | Business Requirements | BUS-PRO-0116 | Earned value calculations require baseline (planned) values to be set |  |  |  |  |
| `Nexus\ProjectManagement` | Business Requirements | BUS-PRO-0121 | Lessons learned can only be created after project status = completed or cancelled |  |  |  |  |
| `Nexus\ProjectManagement` | Business Requirements | BUS-PRO-0125 | Timesheet approval requires user to have approve-timesheet permission for the project |  |  |  |  |
| `Nexus\ProjectManagement` | Functional Requirement | FUN-PRO-0236 | Create project with basic details |  |  |  |  |
| `Nexus\ProjectManagement` | Functional Requirement | FUN-PRO-0242 | Create and manage tasks |  |  |  |  |
| `Nexus\ProjectManagement` | Functional Requirement | FUN-PRO-0248 | Task assignment and notifications |  |  |  |  |
| `Nexus\ProjectManagement` | Functional Requirement | FUN-PRO-0254 | Time tracking and timesheet entry |  |  |  |  |
| `Nexus\ProjectManagement` | Functional Requirement | FUN-PRO-0260 | My Tasks view |  |  |  |  |
| `Nexus\ProjectManagement` | Functional Requirement | FUN-PRO-0266 | Project dashboard |  |  |  |  |
| `Nexus\ProjectManagement` | Functional Requirement | FUN-PRO-0272 | Time report by project |  |  |  |  |
| `Nexus\ProjectManagement` | Functional Requirement | FUN-PRO-0564 | Create project with basic details (name, client, start/end, budget) |  |  |  |  |
| `Nexus\ProjectManagement` | Functional Requirement | FUN-PRO-0565 | Create and manage tasks with assignees + priority |  |  |  |  |
| `Nexus\ProjectManagement` | Functional Requirement | FUN-PRO-0566 | Time tracking and timesheet entry |  |  |  |  |
| `Nexus\ProjectManagement` | Functional Requirement | FUN-PRO-0567 | My Tasks (view all tasks assigned to user) |  |  |  |  |
| `Nexus\ProjectManagement` | Functional Requirement | FUN-PRO-0568 | Project dashboard (overview, % complete) |  |  |  |  |
| `Nexus\ProjectManagement` | Functional Requirement | FUN-PRO-0569 | Milestones with approvals and deliverables |  |  |  |  |
| `Nexus\ProjectManagement` | Functional Requirement | FUN-PRO-0570 | Task dependencies (predecessor) & Gantt-support (calc) |  |  |  |  |
| `Nexus\ProjectManagement` | Functional Requirement | FUN-PRO-0571 | Resource allocation & overallocation checks |  |  |  |  |
| `Nexus\ProjectManagement` | Functional Requirement | FUN-PRO-0572 | Budget tracking (planned vs actual) |  |  |  |  |
| `Nexus\ProjectManagement` | Functional Requirement | FUN-PRO-0573 | Project invoicing (milestone/T&M) |  |  |  |  |
| `Nexus\ProjectManagement` | Functional Requirement | FUN-PRO-0574 | Expense tracking & approvals |  |  |  |  |
| `Nexus\ProjectManagement` | Functional Requirement | FUN-PRO-0575 | Timesheet approval workflow |  |  |  |  |
| `Nexus\ProjectManagement` | Performance Requirement | PER-PRO-0328 | Project creation and save |  |  |  |  |
| `Nexus\ProjectManagement` | Performance Requirement | PER-PRO-0335 | Task creation and assignment |  |  |  |  |
| `Nexus\ProjectManagement` | Performance Requirement | PER-PRO-0342 | Timesheet entry and save |  |  |  |  |
| `Nexus\ProjectManagement` | Performance Requirement | PER-PRO-0348 | Gantt chart rendering (100 tasks) |  |  |  |  |
| `Nexus\ProjectManagement` | Performance Requirement | PER-PRO-0354 | Portfolio dashboard loading |  |  |  |  |
| `Nexus\ProjectManagement` | Performance Requirement | PER-PRO-0357 | Resource allocation view |  |  |  |  |
| `Nexus\ProjectManagement` | Reliability Requirement | REL-PRO-0390 | All financial calculations MUST be ACID-compliant |  |  |  |  |
| `Nexus\ProjectManagement` | Reliability Requirement | REL-PRO-0396 | Timesheet approval MUST prevent double-billing |  |  |  |  |
| `Nexus\ProjectManagement` | Reliability Requirement | REL-PRO-0402 | Resource allocation MUST prevent double-booking |  |  |  |  |
| `Nexus\ProjectManagement` | Reliability Requirement | REL-PRO-0408 | Milestone approval workflow MUST be resumable after failure |  |  |  |  |
| `Nexus\ProjectManagement` | Security and Compliance Requirement | SEC-PRO-0442 | Tenant data isolation |  |  |  |  |
| `Nexus\ProjectManagement` | Security and Compliance Requirement | SEC-PRO-0448 | Role-based access control |  |  |  |  |
| `Nexus\ProjectManagement` | Security and Compliance Requirement | SEC-PRO-0454 | Client portal access |  |  |  |  |
| `Nexus\ProjectManagement` | Security and Compliance Requirement | SEC-PRO-0460 | Timesheet integrity |  |  |  |  |
| `Nexus\ProjectManagement` | Security and Compliance Requirement | SEC-PRO-0466 | Financial data protection |  |  |  |  |
| `Nexus\ProjectManagement` | Security and Compliance Requirement | SEC-PRO-0471 | Audit trail completeness |  |  |  |  |
| `Nexus\ProjectManagement` | User Story | USE-PRO-0509 | As a project manager, I want to create a project with basic details (name, client, start/end dates, budget) |  |  |  |  |
| `Nexus\ProjectManagement` | User Story | USE-PRO-0516 | As a project manager, I want to create tasks within a project with descriptions, assignees, and due dates |  |  |  |  |
| `Nexus\ProjectManagement` | User Story | USE-PRO-0523 | As a team member, I want to view all tasks assigned to me across all projects in one place |  |  |  |  |
| `Nexus\ProjectManagement` | User Story | USE-PRO-0530 | As a team member, I want to log time against tasks (hours worked, date, description) |  |  |  |  |
| `Nexus\ProjectManagement` | User Story | USE-PRO-0537 | As a project manager, I want to view time logged by team members to track project progress |  |  |  |  |
| `Nexus\ProjectManagement` | User Story | USE-PRO-0543 | As a project manager, I want to mark tasks as complete and track project completion percentage |  |  |  |  |
| `Nexus\ProjectManagement` | User Story | USE-PRO-0549 | As a team member, I want to receive notifications when tasks are assigned to me or deadlines are approaching |  |  |  |  |
