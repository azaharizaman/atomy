# Requirements: Hrm

Total Requirements: 159

| Package Namespace | Requirements Type | Code | Requirement Statements | Files/Folders | Status | Notes on Status | Date Last Updated |
|-------------------|-------------------|------|------------------------|---------------|--------|-----------------|-------------------|
| `Nexus\Hrm` | Architectural Requirement | ARC-HRM-0703 | Package MUST be framework-agnostic with no Laravel dependencies in core services |  |  |  |  |
| `Nexus\Hrm` | Architectural Requirement | ARC-HRM-0704 | All data structures defined via interfaces (EmployeeInterface, ContractInterface, LeaveInterface, AttendanceInterface, PerformanceReviewInterface) |  |  |  |  |
| `Nexus\Hrm` | Architectural Requirement | ARC-HRM-0705 | All persistence operations via repository interfaces |  |  |  |  |
| `Nexus\Hrm` | Architectural Requirement | ARC-HRM-0706 | Business logic in service layer (EmployeeManager, LeaveManager, AttendanceManager, PerformanceManager, DisciplinaryManager, TrainingManager) |  |  |  |  |
| `Nexus\Hrm` | Architectural Requirement | ARC-HRM-0711 | Package composer.json MUST NOT depend on laravel/framework |  |  |  |  |
| `Nexus\Hrm` | Architectural Requirement | ARC-HRM-0715 | Provide event-driven architecture for HR lifecycle events |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0162 | Employees MUST have active contract before leave accrual begins |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0163 | Leave requests CANNOT exceed available balance unless negative balance policy enabled |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0164 | Probation completion required before permanent leave entitlements activate |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0165 | Attendance records MUST NOT overlap for same employee (prevent duplicate clock-ins) |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0166 | Performance reviews MUST be conducted by employee's direct manager or authorized delegate |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0167 | Disciplinary actions require documented evidence |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0169 | Employee termination MUST trigger leave balance calculation and final settlement |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0716 | Employee probation period completion triggers permanent status promotion |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0717 | Employee cannot have overlapping employment contracts |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0718 | Contract end date must be after start date |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0719 | Leave entitlement calculation based on length of service and contract type |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0720 | Leave balance cannot be negative unless carry-forward policy allows |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0722 | Emergency leave can be applied retroactively within configurable timeframe |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0723 | Medical leave requires supporting documentation for more than configurable consecutive days |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0725 | Clock-in must occur before clock-out for same day |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0726 | Overtime calculation applies only after minimum threshold hours (configurable) |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0727 | Break time deducted from total working hours based on policy |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0730 | Self-assessment must be completed before manager review |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0731 | 360-degree feedback requires minimum configurable peer reviewers |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0732 | Performance ratings must follow configured rating scale |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0733 | Disciplinary actions must follow progressive discipline policy |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0741 | Employee cannot be terminated while under disciplinary investigation |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0742 | Resignation requires minimum notice period based on contract terms |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0743 | Final settlement calculation includes unused leave encashment |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0746 | All HR state changes must be ACID-compliant transactions |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0225 | Manage employee master data with personal information, emergency contacts, and dependents |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0226 | Track employment contracts with start date, probation period, position, and employment type |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0227 | Implement employee lifecycle states (prospect → active → probation → permanent → notice → terminated) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0229 | Track employment history with position changes, transfers, and promotions |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0230 | Manage employee documents with version control and expiry tracking |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0747 | Provide EmployeeManager as main orchestration service |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0748 | Support employee CRUD operations with personal information |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0749 | Track employee demographics (date of birth, gender, nationality, marital status) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0750 | Track employee contact information (personal email, phone, address) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0751 | Track emergency contacts with relationship and contact details |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0752 | Track employee dependents for benefits and tax purposes |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0753 | Track employee identification documents (passport, IC, work permit) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0754 | Track employee educational qualifications with institution and date |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0755 | Track employee professional certifications with expiry tracking |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0756 | Track employee work experience history before joining |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0757 | Support employee photo upload with file size and format validation |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0758 | Support employment contract creation with start date, end date, probation period |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0759 | Support contract types (permanent, fixed-term, probation, internship, consultant) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0760 | Track contract position, department, reporting manager |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0761 | Track contract work schedule (full-time, part-time, shift) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0762 | Support contract renewal with version tracking |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0763 | Support contract amendment for position/salary changes |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0765 | Implement employee lifecycle states (applicant → hired → active → probation → confirmed → notice → resigned → terminated) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0766 | Track lifecycle state transitions with effective dates and reasons |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0767 | Automatic probation completion check based on contract probation period |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0768 | Support probation extension with reason |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0769 | Support employment confirmation with confirmation date |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0770 | Track position change history with effective dates |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0771 | Track department transfer history with reasons |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0772 | Track promotion history with old and new positions |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0773 | Track demotion history with reasons |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0774 | Track salary change history (amount, effective date, reason) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0779 | Support document management with categories (contract, ID, certificate, form) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0780 | Support document version control for updated documents |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0781 | Track document expiry dates |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0784 | Define leave types with entitlement rules (annual, sick, unpaid, maternity, paternity, compassionate) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0785 | Configure leave accrual rules (monthly, yearly, upon confirmation) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0786 | Configure leave carry-forward rules with maximum carry-forward days |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0787 | Configure leave proration for mid-year joiners |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0788 | Calculate leave entitlement based on service length and contract type |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0789 | Track leave balances (entitled, used, pending, remaining) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0790 | Support leave request creation with date range and reason |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0791 | Support half-day and hourly leave requests |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0792 | Validate leave balance before submission |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0794 | Support leave approval/rejection with comments |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0795 | Support leave cancellation before start date |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0796 | Support retroactive leave application for emergency situations |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0797 | Automatic leave deduction upon approval |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0800 | Annual leave carry-forward processing at year-end |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0801 | Leave encashment calculation for unused leave |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0802 | Medical leave tracking with medical certificate metadata |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0803 | Support attendance clock-in/clock-out recording |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0804 | Track attendance location via GPS coordinates |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0805 | Support multiple clock-in/out for shift workers |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0806 | Track break start and end times |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0807 | Calculate total working hours per day |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0808 | Calculate overtime hours based on configured threshold |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0809 | Support overtime types (regular, weekend, public holiday) with different rates |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0810 | Track late arrival with duration and reason |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0811 | Track early departure with duration and reason |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0812 | Support attendance regularization for missed clock-in/out |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0816 | Track attendance anomalies (multiple clock-ins, no clock-out) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0819 | Automatic absent marking for no-show without leave |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0821 | Define performance review cycles (annual, semi-annual, quarterly) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0822 | Define review templates with competencies and KPIs |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0823 | Support rating scales (numeric 1-5, letter A-E, custom labels) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0824 | Support goal setting and tracking during review cycle |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0825 | Support self-assessment submission by employee |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0826 | Support manager assessment with rating and comments |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0827 | Support 360-degree feedback from peers, subordinates, and manager |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0828 | Support calibration sessions for normalizing ratings across departments |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0830 | Track performance improvement plans (PIP) with milestones |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0832 | Link performance ratings to salary increment recommendations |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0833 | Link performance ratings to promotion eligibility |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0836 | Provide historical performance review access |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0837 | Support disciplinary case creation with case type and severity |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0838 | Define disciplinary case types (misconduct, policy violation, attendance, performance) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0839 | Define severity levels (minor, major, severe, gross misconduct) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0840 | Track case lifecycle (reported → investigation → hearing → decision → closed) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0841 | Record incident details with date, time, location, witnesses |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0842 | Assign investigator to case with timeline |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0843 | Record investigation findings and evidence |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0845 | Record employee response to charges |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0846 | Conduct disciplinary hearing with panel members |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0847 | Record disciplinary actions (verbal warning, written warning, suspension, termination) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0848 | Track warning expiry dates |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0849 | Progressive discipline tracking (escalation from verbal to written to termination) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0851 | Support appeal process for disciplinary decisions |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0855 | Define training programs with objectives and target audience |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0856 | Define training categories (technical, soft skills, compliance, leadership) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0857 | Define training types (internal, external, online, workshop, seminar) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0858 | Track training providers and instructors |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0859 | Schedule training sessions with date, time, location, capacity |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0861 | Track training enrollment status (pending, approved, rejected, enrolled, cancelled) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0863 | Track training attendance with clock-in/out |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0864 | Record training completion with completion date |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0865 | Record training feedback and evaluation scores |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0867 | Track certification validity and expiry dates |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0869 | Track mandatory training compliance per employee |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0872 | Provide training history per employee |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0873 | Training budget tracking and cost allocation |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0875 | Support resignation processing with resignation date and reason |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0876 | Calculate notice period based on contract terms |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0877 | Track last working day and exit date |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0878 | Support termination processing with termination type (voluntary, involuntary, retirement) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0879 | Exit clearance checklist tracking |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0880 | Exit interview data recording |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0881 | Calculate final settlement (unpaid salary, leave encashment, deductions) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0885 | Track re-hire eligibility status |  |  |  |  |
| `Nexus\Hrm` | Maintainability Requirement | MAINT-HRM-0939 | Framework-agnostic core with zero Laravel dependencies in packages/Hrm/src/ |  |  |  |  |
| `Nexus\Hrm` | Maintainability Requirement | MAINT-HRM-0940 | Clear separation between business logic (services) and persistence (repositories) |  |  |  |  |
| `Nexus\Hrm` | Maintainability Requirement | MAINT-HRM-0941 | Comprehensive test coverage (>80% code coverage, >90% for critical paths) |  |  |  |  |
| `Nexus\Hrm` | Maintainability Requirement | MAINT-HRM-0944 | Support plugin architecture for custom leave policies |  |  |  |  |
| `Nexus\Hrm` | Maintainability Requirement | MAINT-HRM-0945 | Support plugin architecture for custom attendance rules |  |  |  |  |
| `Nexus\Hrm` | Performance Requirement | PERF-HRM-0918 | Leave balance calculation with accrual rules < 50ms per employee |  |  |  |  |
| `Nexus\Hrm` | Performance Requirement | PERF-HRM-0921 | Real-time leave balance check < 20ms for approval validation |  |  |  |  |
| `Nexus\Hrm` | Performance Requirement | PER-HRM-0376 | Leave balance calculation with complex rules |  |  |  |  |
| `Nexus\Hrm` | Performance Requirement | PER-HRM-0379 | Real-time leave balance check during request submission |  |  |  |  |
| `Nexus\Hrm` | Reliability Requirement | REL-HRM-0933 | All leave balance transactions must be ACID-compliant |  |  |  |  |
| `Nexus\Hrm` | Reliability Requirement | REL-HRM-0934 | Attendance clock-in/out must handle concurrent requests without data loss |  |  |  |  |
| `Nexus\Hrm` | Reliability Requirement | REL-HRM-0935 | Leave accrual batch job must be idempotent (safe to re-run) |  |  |  |  |
