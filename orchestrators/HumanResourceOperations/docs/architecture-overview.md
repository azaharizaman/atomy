# HRM Architecture Overview

## Package Structure

The HRM domain is organized into:

### 1. **HumanResourceOperations** (Orchestrator)
- Coordinates all HR domain packages
- Contains use cases, pipelines, and workflow orchestration
- No domain logic - pure application layer

### 2. **Atomic Domain Packages**
- **Leave** - Leave operations and balance tracking
- **AttendanceManagement** - Check-in/out and schedule management
- **PayrollCore** - Payroll calculation and payslip generation
- **EmployeeProfile** - Employee lifecycle and contracts
- **Shift** - Shift scheduling and assignment

## Dependency Flow

```
HumanResourceOperations (Orchestrator)
    ├── depends on → Leave
    ├── depends on → AttendanceManagement
    ├── depends on → PayrollCore
    ├── depends on → EmployeeProfile
    └── depends on → Shift
```

## Key Principles

1. **Framework Agnostic** - All packages use pure PHP 8.3+
2. **Contract-Driven** - Dependencies via interfaces only
3. **Stateless** - No in-memory state persistence
4. **Publishable** - Each atomic package can be published independently

## Integration Points

The orchestrator provides gateways for:
- Leave approval workflows
- Attendance device integration
- Payroll processing
- Employee data sync
