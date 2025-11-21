# Requirements: Hrm

Total Requirements: 622

| Package Namespace | Requirements Type | Code | Requirement Statements | Files/Folders | Status | Notes on Status | Date Last Updated |
|-------------------|-------------------|------|------------------------|---------------|--------|-----------------|-------------------|
| `Nexus\Hrm` | Architechtural Requirement | ARC-HRM-0703 | Package MUST be framework-agnostic with no Laravel dependencies in core services |  |  |  |  |
| `Nexus\Hrm` | Architechtural Requirement | ARC-HRM-0704 | All data structures defined via interfaces (EmployeeInterface, ContractInterface, LeaveInterface, AttendanceInterface, PerformanceReviewInterface) |  |  |  |  |
| `Nexus\Hrm` | Architechtural Requirement | ARC-HRM-0705 | All persistence operations via repository interfaces |  |  |  |  |
| `Nexus\Hrm` | Architechtural Requirement | ARC-HRM-0706 | Business logic in service layer (EmployeeManager, LeaveManager, AttendanceManager, PerformanceManager, DisciplinaryManager, TrainingManager) |  |  |  |  |
| `Nexus\Hrm` | Architechtural Requirement | ARC-HRM-0707 | All database migrations in application layer (apps/Atomy) |  |  |  |  |
| `Nexus\Hrm` | Architechtural Requirement | ARC-HRM-0708 | All Eloquent models in application layer |  |  |  |  |
| `Nexus\Hrm` | Architechtural Requirement | ARC-HRM-0709 | Repository implementations in application layer |  |  |  |  |
| `Nexus\Hrm` | Architechtural Requirement | ARC-HRM-0710 | IoC container bindings in application service provider |  |  |  |  |
| `Nexus\Hrm` | Architechtural Requirement | ARC-HRM-0711 | Package composer.json MUST NOT depend on laravel/framework |  |  |  |  |
| `Nexus\Hrm` | Architechtural Requirement | ARC-HRM-0712 | Support integration with Nexus\Backoffice via OrganizationServiceContract |  |  |  |  |
| `Nexus\Hrm` | Architechtural Requirement | ARC-HRM-0713 | Support integration with Nexus\Workflow for leave approvals and disciplinary workflows |  |  |  |  |
| `Nexus\Hrm` | Architechtural Requirement | ARC-HRM-0714 | Support integration with Nexus\AuditLogger for comprehensive change tracking |  |  |  |  |
| `Nexus\Hrm` | Architechtural Requirement | ARC-HRM-0715 | Provide event-driven architecture for HR lifecycle events |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0162 | Employees MUST have active contract before leave accrual begins |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0163 | Leave requests CANNOT exceed available balance unless negative balance policy enabled |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0164 | Probation completion required before permanent leave entitlements activate |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0165 | Attendance records MUST NOT overlap for same employee (prevent duplicate clock-ins) |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0166 | Performance reviews MUST be conducted by employee's direct manager or authorized delegate |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0167 | Disciplinary actions require documented evidence and approval workflow completion |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0168 | Training certifications with expiry dates trigger automatic reminders 30 days before expiry |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0169 | Employee termination MUST trigger automatic leave balance calculation and final settlement |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0716 | Employee probation period completion triggers permanent status promotion |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0717 | Employee cannot have overlapping employment contracts |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0718 | Contract end date must be after start date |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0719 | Leave entitlement calculation based on length of service and contract type |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0720 | Leave balance cannot be negative unless carry-forward policy allows |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0721 | Leave requests require approval from direct manager or authorized delegate |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0722 | Emergency leave can be applied retroactively within 48 hours |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0723 | Medical leave requires supporting documentation for more than 2 consecutive days |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0724 | Annual leave requests require minimum 3 days advance notice (configurable) |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0725 | Clock-in must occur before clock-out for same day |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0726 | Overtime calculation applies only after minimum threshold hours (e.g., 8 hours per day) |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0727 | Break time deducted from total working hours based on company policy |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0728 | Late clock-in triggers automatic deduction or warning based on policy |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0729 | Performance review cycles must be defined per company with start/end dates |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0730 | Self-assessment must be completed before manager review |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0731 | 360-degree feedback requires minimum 3 peer reviewers |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0732 | Performance ratings must follow configured rating scale (e.g., 1-5, A-E) |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0733 | Disciplinary actions must follow progressive discipline policy |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0734 | Employee must be notified in writing of disciplinary action within 24 hours |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0735 | Employee has right to respond to disciplinary charge within configured timeframe |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0736 | Investigation must be completed before final disciplinary decision |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0737 | Training enrollment requires manager approval for external/paid training |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0738 | Training completion must be recorded within 7 days of course end |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0739 | Mandatory training compliance tracked per employee and department |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0740 | Certification expiry triggers automatic renewal reminder 30/60/90 days before |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0741 | Employee cannot be terminated while under disciplinary investigation |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0742 | Resignation requires minimum notice period based on contract terms |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0743 | Final settlement calculation includes unused leave encashment |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0744 | Employee personal information changes require document verification |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0745 | Salary information visible only to HR, manager, and employee themselves |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0746 | All HR state changes must be ACID-compliant transactions |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0225 | Manage employee master data with personal information, emergency contacts, and dependents |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0226 | Track employment contracts with start date, probation period, position, and employment type |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0227 | Implement employee lifecycle states (prospect â†’ active â†’ probation â†’ permanent â†’ notice â†’ terminated) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0228 | Support automatic org hierarchy integration via OrganizationServiceContract (manager, subordinates, department queries) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0229 | Track employment history with position changes, transfers, and promotions |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0230 | Manage employee documents with secure storage, version control, and expiry tracking |  |  |  |  |
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
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0764 | Automatic contract expiry notification to HR and manager |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0765 | Implement employee lifecycle states (applicant → hired → active → probation → confirmed → notice → resigned → terminated) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0766 | Track lifecycle state transitions with effective dates and reasons |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0767 | Automatic probation completion check based on contract probation period |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0768 | Support probation extension with reason and approval |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0769 | Support employment confirmation with confirmation date |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0770 | Track position change history with effective dates |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0771 | Track department transfer history with reasons |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0772 | Track promotion history with old and new positions |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0773 | Track demotion history with reasons and approvals |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0774 | Track salary change history (amount, effective date, reason) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0775 | Integrate with Nexus\Backoffice for organizational hierarchy via OrganizationServiceContract |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0776 | Query employee manager from organizational structure |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0777 | Query employee direct reports from organizational structure |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0778 | Query employee department and office from organizational structure |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0779 | Support document management with categories (contract, ID, certificate, form) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0780 | Support document version control for updated documents |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0781 | Track document expiry dates with automatic reminders |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0782 | Secure document storage with access control |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0783 | Document audit trail (who uploaded, when, who viewed) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0784 | Define leave types with entitlement rules (annual, sick, unpaid, maternity, paternity, compassionate) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0785 | Configure leave accrual rules (monthly, yearly, upon confirmation) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0786 | Configure leave carry-forward rules with maximum carry-forward days |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0787 | Configure leave proration for mid-year joiners |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0788 | Calculate leave entitlement based on service length and contract type |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0789 | Track leave balances (entitled, used, pending, remaining) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0790 | Support leave request creation with date range and reason |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0791 | Support half-day and hourly leave requests |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0792 | Validate leave balance before submission |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0793 | Integrate with Nexus\Workflow for multi-level leave approval |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0794 | Support leave approval/rejection with comments |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0795 | Support leave cancellation before start date |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0796 | Support retroactive leave application for emergency situations |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0797 | Automatic leave deduction upon approval |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0798 | Leave calendar view showing team availability |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0799 | Leave conflict detection (minimum staff coverage) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0800 | Annual leave carry-forward processing at year-end |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0801 | Leave encashment calculation for unused leave |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0802 | Medical leave tracking with medical certificate upload |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0803 | Support attendance clock-in/clock-out recording |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0804 | Track attendance location via GPS coordinates (for mobile clock-in) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0805 | Support multiple clock-in/out for shift workers |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0806 | Track break start and end times |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0807 | Calculate total working hours per day |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0808 | Calculate overtime hours based on configured threshold |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0809 | Support overtime types (regular, weekend, public holiday) with different rates |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0810 | Track late arrival with duration and reason |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0811 | Track early departure with duration and reason |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0812 | Support attendance regularization for missed clock-in/out |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0813 | Attendance approval workflow for regularization requests |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0814 | Generate monthly attendance summaries per employee |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0815 | Generate departmental attendance reports |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0816 | Track attendance anomalies (multiple clock-ins, no clock-out) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0817 | Support shift scheduling integration |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0818 | Support public holiday calendar configuration |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0819 | Automatic absent marking for no-show without leave |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0820 | Attendance dashboard showing real-time present/absent/on-leave status |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0821 | Define performance review cycles (annual, semi-annual, quarterly) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0822 | Define review templates with competencies and KPIs |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0823 | Support rating scales (numeric 1-5, letter A-E, custom labels) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0824 | Support goal setting and tracking during review cycle |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0825 | Support self-assessment submission by employee |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0826 | Support manager assessment with rating and comments |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0827 | Support 360-degree feedback from peers, subordinates, and manager |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0828 | Support calibration sessions for normalizing ratings across departments |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0829 | Generate performance distribution reports (bell curve analysis) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0830 | Track performance improvement plans (PIP) with milestones |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0831 | Generate performance trend reports for career development |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0832 | Link performance ratings to salary increment recommendations |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0833 | Link performance ratings to promotion eligibility |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0834 | Performance review notification workflow (reminder, overdue alerts) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0835 | Performance review approval workflow with HR validation |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0836 | Historical performance review access for employees and managers |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0837 | Support disciplinary case creation with case type and severity |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0838 | Define disciplinary case types (misconduct, policy violation, attendance, performance) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0839 | Define severity levels (minor, major, severe, gross misconduct) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0840 | Track case lifecycle (reported → investigation → hearing → decision → closed) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0841 | Record incident details with date, time, location, witnesses |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0842 | Assign investigator to case with timeline |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0843 | Record investigation findings and evidence |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0844 | Issue show-cause notice to employee |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0845 | Record employee response to charges |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0846 | Conduct disciplinary hearing with panel members |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0847 | Record disciplinary actions (verbal warning, written warning, suspension, termination) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0848 | Track warning expiry dates (e.g., warnings valid for 12 months) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0849 | Progressive discipline tracking (escalation from verbal to written to termination) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0850 | Generate disciplinary letters and notices |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0851 | Support appeal process for disciplinary decisions |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0852 | Disciplinary case confidentiality with restricted access |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0853 | Disciplinary history report for HR compliance |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0854 | Integration with Nexus\Workflow for disciplinary approval workflow |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0855 | Define training programs with objectives and target audience |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0856 | Define training categories (technical, soft skills, compliance, leadership) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0857 | Define training types (internal, external, online, workshop, seminar) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0858 | Track training providers and instructors |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0859 | Schedule training sessions with date, time, location, capacity |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0860 | Support training enrollment with manager approval workflow |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0861 | Track training enrollment status (pending, approved, rejected, enrolled, cancelled) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0862 | Automatic enrollment confirmation email to participants |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0863 | Track training attendance with clock-in/out |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0864 | Record training completion with completion date |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0865 | Record training feedback and evaluation scores |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0866 | Issue training certificates upon completion |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0867 | Track certification validity and expiry dates |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0868 | Automatic certification renewal reminders |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0869 | Track mandatory training compliance per employee |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0870 | Generate training reports (completion rates, costs, effectiveness) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0871 | Training needs analysis reports by department |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0872 | Training history per employee for career development |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0873 | Training budget tracking and cost allocation |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0874 | Training ROI calculation and effectiveness metrics |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0875 | Support resignation processing with resignation date and reason |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0876 | Calculate notice period based on contract terms |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0877 | Track last working day and exit date |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0878 | Support termination processing with termination type (voluntary, involuntary, retirement) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0879 | Exit clearance checklist with multiple departments |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0880 | Exit interview scheduling and recording |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0881 | Calculate final settlement (unpaid salary, leave encashment, deductions) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0882 | Generate final settlement statement |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0883 | Generate employment verification letters |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0884 | Generate service certificates |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0885 | Track re-hire eligibility status |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0886 | Employee self-service portal for viewing personal information |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0887 | Employee self-service for leave requests and balance inquiry |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0888 | Employee self-service for viewing attendance records |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0889 | Employee self-service for viewing payslips (integration with Nexus\Payroll) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0890 | Employee self-service for updating contact information |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0891 | Employee self-service for document download |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0892 | Manager dashboard showing team structure and direct reports |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0893 | Manager dashboard showing pending leave approvals |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0894 | Manager dashboard showing team attendance summary |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0895 | Manager dashboard showing team performance review status |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0896 | HR dashboard showing company-wide headcount and demographics |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0897 | HR dashboard showing leave utilization and trends |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0898 | HR dashboard showing attendance compliance rates |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0899 | HR dashboard showing upcoming contract expirations |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0900 | HR dashboard showing probation completion and confirmation due dates |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0901 | HR dashboard showing training compliance and certification expirations |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0902 | Generate employee directory with search and filters |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0903 | Generate headcount reports by department, position, employment type |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0904 | Generate turnover analysis reports with reasons |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0905 | Generate demographics reports (age, gender, tenure distribution) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0906 | Generate absence reports with patterns and trends |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0907 | Generate overtime reports with cost implications |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0908 | Generate performance rating distribution reports |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0909 | Generate training investment reports |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0910 | Export all reports to Excel, PDF, CSV formats |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0911 | Provide RESTful API endpoints for all CRUD operations |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0912 | Provide GraphQL API for complex employee queries |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0913 | Support webhooks for HR event notifications |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0914 | Support bulk employee import from CSV/Excel |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0915 | Support bulk leave balance adjustment |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0916 | Support data anonymization for GDPR compliance |  |  |  |  |
| `Nexus\Hrm` | Maintainability Requirement | MAINT-HRM-0939 | Framework-agnostic core with zero Laravel dependencies in packages/Hrm/src/ |  |  |  |  |
| `Nexus\Hrm` | Maintainability Requirement | MAINT-HRM-0940 | Clear separation between business logic (services) and persistence (repositories) |  |  |  |  |
| `Nexus\Hrm` | Maintainability Requirement | MAINT-HRM-0941 | Comprehensive test coverage (>80% code coverage, >90% for critical paths) |  |  |  |  |
| `Nexus\Hrm` | Maintainability Requirement | MAINT-HRM-0942 | Support multiple database backends (MySQL, PostgreSQL) |  |  |  |  |
| `Nexus\Hrm` | Maintainability Requirement | MAINT-HRM-0943 | Provide comprehensive API documentation with examples |  |  |  |  |
| `Nexus\Hrm` | Maintainability Requirement | MAINT-HRM-0944 | Support plugin architecture for custom leave policies |  |  |  |  |
| `Nexus\Hrm` | Maintainability Requirement | MAINT-HRM-0945 | Support plugin architecture for custom attendance rules |  |  |  |  |
| `Nexus\Hrm` | Performance Requirement | PERF-HRM-0917 | Employee directory search with filters < 200ms for 100K records |  |  |  |  |
| `Nexus\Hrm` | Performance Requirement | PERF-HRM-0918 | Leave balance calculation with accrual rules < 50ms per employee |  |  |  |  |
| `Nexus\Hrm` | Performance Requirement | PERF-HRM-0919 | Monthly attendance summary generation < 2 seconds for 1000 employees |  |  |  |  |
| `Nexus\Hrm` | Performance Requirement | PERF-HRM-0920 | Performance review aggregation < 1 second for department of 200 employees |  |  |  |  |
| `Nexus\Hrm` | Performance Requirement | PERF-HRM-0921 | Real-time leave balance check < 20ms for approval validation |  |  |  |  |
| `Nexus\Hrm` | Performance Requirement | PERF-HRM-0922 | Dashboard metrics calculation < 500ms for company of 10K employees |  |  |  |  |
| `Nexus\Hrm` | Performance Requirement | PER-HRM-0375 | Employee search across 100K records |  |  |  |  |
| `Nexus\Hrm` | Performance Requirement | PER-HRM-0376 | Leave balance calculation with complex rules |  |  |  |  |
| `Nexus\Hrm` | Performance Requirement | PER-HRM-0377 | Monthly attendance report generation (1000 employees) |  |  |  |  |
| `Nexus\Hrm` | Performance Requirement | PER-HRM-0378 | Performance review data aggregation (department-level) |  |  |  |  |
| `Nexus\Hrm` | Performance Requirement | PER-HRM-0379 | Real-time leave balance check during request submission |  |  |  |  |
| `Nexus\Hrm` | Reliability Requirement | REL-HRM-0933 | All leave balance transactions must be ACID-compliant |  |  |  |  |
| `Nexus\Hrm` | Reliability Requirement | REL-HRM-0934 | Attendance clock-in/out must handle concurrent requests without data loss |  |  |  |  |
| `Nexus\Hrm` | Reliability Requirement | REL-HRM-0935 | Leave accrual batch job must be idempotent (safe to re-run) |  |  |  |  |
| `Nexus\Hrm` | Reliability Requirement | REL-HRM-0936 | Contract expiry notifications must use reliable queue system |  |  |  |  |
| `Nexus\Hrm` | Reliability Requirement | REL-HRM-0937 | Performance review workflow must handle delegation during employee absence |  |  |  |  |
| `Nexus\Hrm` | Reliability Requirement | REL-HRM-0938 | System must recover gracefully from third-party integration failures |  |  |  |  |
| `Nexus\Hrm` | Security and Compliance Requirement | SEC-HRM-0492 | Implement audit logging for all employee data changes using ActivityLoggerContract |  |  |  |  |
| `Nexus\Hrm` | Security and Compliance Requirement | SEC-HRM-0493 | Enforce tenant isolation for all HR data via tenant scoping |  |  |  |  |
| `Nexus\Hrm` | Security and Compliance Requirement | SEC-HRM-0494 | Support authorization policies through contract-based permission system |  |  |  |  |
| `Nexus\Hrm` | Security and Compliance Requirement | SEC-HRM-0495 | Encrypt sensitive employee data (personal information, salary details) at rest |  |  |  |  |
| `Nexus\Hrm` | Security and Compliance Requirement | SEC-HRM-0496 | Implement field-level access control (HR managers see salary, line managers don't) |  |  |  |  |
| `Nexus\Hrm` | Security Requirement | SEC-HRM-0923 | Implement field-level encryption for sensitive data (IC number, passport, salary) |  |  |  |  |
| `Nexus\Hrm` | Security Requirement | SEC-HRM-0924 | Enforce data masking for non-authorized viewers (show only last 4 digits of IC) |  |  |  |  |
| `Nexus\Hrm` | Security Requirement | SEC-HRM-0925 | Implement row-level security (employees can only view own data unless authorized) |  |  |  |  |
| `Nexus\Hrm` | Security Requirement | SEC-HRM-0926 | Enforce manager-subordinate data access boundaries |  |  |  |  |
| `Nexus\Hrm` | Security Requirement | SEC-HRM-0927 | Require additional authentication for sensitive operations (salary change, termination) |  |  |  |  |
| `Nexus\Hrm` | Security Requirement | SEC-HRM-0928 | Implement document access control with role-based permissions |  |  |  |  |
| `Nexus\Hrm` | Security Requirement | SEC-HRM-0929 | Track document access audit trail (who viewed, when, from where) |  |  |  |  |
| `Nexus\Hrm` | Security Requirement | SEC-HRM-0930 | Support data retention policies with automated data purging |  |  |  |  |
| `Nexus\Hrm` | Security Requirement | SEC-HRM-0931 | Support right to be forgotten (GDPR Article 17) |  |  |  |  |
| `Nexus\Hrm` | Security Requirement | SEC-HRM-0932 | Implement secure file upload with virus scanning |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0946 | As an HR admin, I want to onboard new employee with complete profile in one workflow |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0947 | As an HR admin, I want to track employment contracts with automatic expiry alerts |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0948 | As an HR admin, I want to configure leave policies per employment type |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0949 | As an HR admin, I want to process annual leave carry-forward at year-end automatically |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0950 | As an employee, I want to apply for leave and see my balance instantly |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0951 | As an employee, I want to view my leave history and upcoming leave |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0952 | As an employee, I want to clock in/out using mobile app with GPS verification |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0953 | As an employee, I want to view my monthly attendance summary and overtime hours |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0954 | As an employee, I want to request attendance regularization for missed clock-in |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0955 | As a manager, I want to approve/reject leave requests from my team |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0956 | As a manager, I want to view team leave calendar to ensure coverage |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0957 | As a manager, I want to view real-time attendance of my team |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0958 | As a manager, I want to conduct performance reviews with guided workflow |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0959 | As a manager, I want to track probation completion for my team members |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0960 | As a manager, I want to nominate team members for training programs |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0961 | As an HR manager, I want to configure performance review cycles company-wide |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0962 | As an HR manager, I want to track performance review completion across departments |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0963 | As an HR manager, I want to analyze performance rating distribution for calibration |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0964 | As an HR manager, I want to initiate disciplinary cases with investigation workflow |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0965 | As an HR manager, I want to track progressive discipline for compliance |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0966 | As an HR manager, I want to schedule training programs with enrollment management |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0967 | As an HR manager, I want to track training completion and certification expiry |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0968 | As an HR manager, I want to track mandatory training compliance |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0969 | As an HR manager, I want to process employee resignations with exit clearance |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0970 | As an HR manager, I want to calculate final settlement for terminated employees |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0971 | As an HR manager, I want to generate employment verification letters automatically |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0972 | As an HR director, I want dashboard showing company-wide HR metrics |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0973 | As an HR director, I want turnover analysis with retention insights |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0974 | As an HR director, I want workforce planning reports (headcount trends, demographics) |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0975 | As an HR director, I want to analyze leave utilization patterns by department |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0976 | As an HR director, I want to track overtime costs and trends |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0977 | As an HR director, I want to measure training ROI and effectiveness |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0978 | As a developer, I want to integrate HRM with payroll for salary processing |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0979 | As a developer, I want to integrate HRM with workflow for approval processes |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0980 | As a developer, I want to integrate HRM with organizational structure automatically |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0981 | As a system admin, I want to configure leave policies without code changes |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0982 | As a system admin, I want to configure attendance rules and overtime calculations |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0983 | As a system admin, I want to configure performance review templates and rating scales |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0984 | As a system admin, I want to configure disciplinary action types and severity levels |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0985 | As a compliance officer, I want to audit all employee data changes |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0986 | As a compliance officer, I want to ensure data retention policies are enforced |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0987 | As a compliance officer, I want to generate compliance reports for labor authorities |  |  |  |  |
| `Nexus\Hrm` | Architechtural Requirement | ARC-HRM-0703 | Package MUST be framework-agnostic with no Laravel dependencies in core services |  |  |  |  |
| `Nexus\Hrm` | Architechtural Requirement | ARC-HRM-0704 | All data structures defined via interfaces (EmployeeInterface, ContractInterface, LeaveInterface, AttendanceInterface, PerformanceReviewInterface) |  |  |  |  |
| `Nexus\Hrm` | Architechtural Requirement | ARC-HRM-0705 | All persistence operations via repository interfaces |  |  |  |  |
| `Nexus\Hrm` | Architechtural Requirement | ARC-HRM-0706 | Business logic in service layer (EmployeeManager, LeaveManager, AttendanceManager, PerformanceManager, DisciplinaryManager, TrainingManager) |  |  |  |  |
| `Nexus\Hrm` | Architechtural Requirement | ARC-HRM-0707 | All database migrations in application layer (apps/Atomy) |  |  |  |  |
| `Nexus\Hrm` | Architechtural Requirement | ARC-HRM-0708 | All Eloquent models in application layer |  |  |  |  |
| `Nexus\Hrm` | Architechtural Requirement | ARC-HRM-0709 | Repository implementations in application layer |  |  |  |  |
| `Nexus\Hrm` | Architechtural Requirement | ARC-HRM-0710 | IoC container bindings in application service provider |  |  |  |  |
| `Nexus\Hrm` | Architechtural Requirement | ARC-HRM-0711 | Package composer.json MUST NOT depend on laravel/framework |  |  |  |  |
| `Nexus\Hrm` | Architechtural Requirement | ARC-HRM-0712 | Support integration with Nexus\Backoffice via OrganizationServiceContract |  |  |  |  |
| `Nexus\Hrm` | Architechtural Requirement | ARC-HRM-0713 | Support integration with Nexus\Workflow for leave approvals and disciplinary workflows |  |  |  |  |
| `Nexus\Hrm` | Architechtural Requirement | ARC-HRM-0714 | Support integration with Nexus\AuditLogger for comprehensive change tracking |  |  |  |  |
| `Nexus\Hrm` | Architechtural Requirement | ARC-HRM-0715 | Provide event-driven architecture for HR lifecycle events |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0162 | Employees MUST have active contract before leave accrual begins |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0163 | Leave requests CANNOT exceed available balance unless negative balance policy enabled |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0164 | Probation completion required before permanent leave entitlements activate |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0165 | Attendance records MUST NOT overlap for same employee (prevent duplicate clock-ins) |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0166 | Performance reviews MUST be conducted by employee's direct manager or authorized delegate |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0167 | Disciplinary actions require documented evidence and approval workflow completion |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0168 | Training certifications with expiry dates trigger automatic reminders 30 days before expiry |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0169 | Employee termination MUST trigger automatic leave balance calculation and final settlement |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0716 | Employee probation period completion triggers permanent status promotion |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0717 | Employee cannot have overlapping employment contracts |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0718 | Contract end date must be after start date |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0719 | Leave entitlement calculation based on length of service and contract type |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0720 | Leave balance cannot be negative unless carry-forward policy allows |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0721 | Leave requests require approval from direct manager or authorized delegate |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0722 | Emergency leave can be applied retroactively within 48 hours |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0723 | Medical leave requires supporting documentation for more than 2 consecutive days |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0724 | Annual leave requests require minimum 3 days advance notice (configurable) |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0725 | Clock-in must occur before clock-out for same day |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0726 | Overtime calculation applies only after minimum threshold hours (e.g., 8 hours per day) |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0727 | Break time deducted from total working hours based on company policy |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0728 | Late clock-in triggers automatic deduction or warning based on policy |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0729 | Performance review cycles must be defined per company with start/end dates |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0730 | Self-assessment must be completed before manager review |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0731 | 360-degree feedback requires minimum 3 peer reviewers |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0732 | Performance ratings must follow configured rating scale (e.g., 1-5, A-E) |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0733 | Disciplinary actions must follow progressive discipline policy |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0734 | Employee must be notified in writing of disciplinary action within 24 hours |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0735 | Employee has right to respond to disciplinary charge within configured timeframe |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0736 | Investigation must be completed before final disciplinary decision |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0737 | Training enrollment requires manager approval for external/paid training |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0738 | Training completion must be recorded within 7 days of course end |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0739 | Mandatory training compliance tracked per employee and department |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0740 | Certification expiry triggers automatic renewal reminder 30/60/90 days before |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0741 | Employee cannot be terminated while under disciplinary investigation |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0742 | Resignation requires minimum notice period based on contract terms |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0743 | Final settlement calculation includes unused leave encashment |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0744 | Employee personal information changes require document verification |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0745 | Salary information visible only to HR, manager, and employee themselves |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-0746 | All HR state changes must be ACID-compliant transactions |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0225 | Manage employee master data with personal information, emergency contacts, and dependents |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0226 | Track employment contracts with start date, probation period, position, and employment type |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0227 | Implement employee lifecycle states (prospect â†’ active â†’ probation â†’ permanent â†’ notice â†’ terminated) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0228 | Support automatic org hierarchy integration via OrganizationServiceContract (manager, subordinates, department queries) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0229 | Track employment history with position changes, transfers, and promotions |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0230 | Manage employee documents with secure storage, version control, and expiry tracking |  |  |  |  |
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
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0764 | Automatic contract expiry notification to HR and manager |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0765 | Implement employee lifecycle states (applicant → hired → active → probation → confirmed → notice → resigned → terminated) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0766 | Track lifecycle state transitions with effective dates and reasons |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0767 | Automatic probation completion check based on contract probation period |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0768 | Support probation extension with reason and approval |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0769 | Support employment confirmation with confirmation date |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0770 | Track position change history with effective dates |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0771 | Track department transfer history with reasons |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0772 | Track promotion history with old and new positions |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0773 | Track demotion history with reasons and approvals |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0774 | Track salary change history (amount, effective date, reason) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0775 | Integrate with Nexus\Backoffice for organizational hierarchy via OrganizationServiceContract |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0776 | Query employee manager from organizational structure |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0777 | Query employee direct reports from organizational structure |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0778 | Query employee department and office from organizational structure |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0779 | Support document management with categories (contract, ID, certificate, form) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0780 | Support document version control for updated documents |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0781 | Track document expiry dates with automatic reminders |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0782 | Secure document storage with access control |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0783 | Document audit trail (who uploaded, when, who viewed) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0784 | Define leave types with entitlement rules (annual, sick, unpaid, maternity, paternity, compassionate) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0785 | Configure leave accrual rules (monthly, yearly, upon confirmation) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0786 | Configure leave carry-forward rules with maximum carry-forward days |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0787 | Configure leave proration for mid-year joiners |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0788 | Calculate leave entitlement based on service length and contract type |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0789 | Track leave balances (entitled, used, pending, remaining) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0790 | Support leave request creation with date range and reason |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0791 | Support half-day and hourly leave requests |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0792 | Validate leave balance before submission |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0793 | Integrate with Nexus\Workflow for multi-level leave approval |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0794 | Support leave approval/rejection with comments |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0795 | Support leave cancellation before start date |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0796 | Support retroactive leave application for emergency situations |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0797 | Automatic leave deduction upon approval |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0798 | Leave calendar view showing team availability |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0799 | Leave conflict detection (minimum staff coverage) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0800 | Annual leave carry-forward processing at year-end |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0801 | Leave encashment calculation for unused leave |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0802 | Medical leave tracking with medical certificate upload |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0803 | Support attendance clock-in/clock-out recording |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0804 | Track attendance location via GPS coordinates (for mobile clock-in) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0805 | Support multiple clock-in/out for shift workers |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0806 | Track break start and end times |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0807 | Calculate total working hours per day |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0808 | Calculate overtime hours based on configured threshold |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0809 | Support overtime types (regular, weekend, public holiday) with different rates |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0810 | Track late arrival with duration and reason |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0811 | Track early departure with duration and reason |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0812 | Support attendance regularization for missed clock-in/out |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0813 | Attendance approval workflow for regularization requests |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0814 | Generate monthly attendance summaries per employee |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0815 | Generate departmental attendance reports |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0816 | Track attendance anomalies (multiple clock-ins, no clock-out) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0817 | Support shift scheduling integration |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0818 | Support public holiday calendar configuration |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0819 | Automatic absent marking for no-show without leave |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0820 | Attendance dashboard showing real-time present/absent/on-leave status |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0821 | Define performance review cycles (annual, semi-annual, quarterly) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0822 | Define review templates with competencies and KPIs |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0823 | Support rating scales (numeric 1-5, letter A-E, custom labels) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0824 | Support goal setting and tracking during review cycle |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0825 | Support self-assessment submission by employee |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0826 | Support manager assessment with rating and comments |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0827 | Support 360-degree feedback from peers, subordinates, and manager |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0828 | Support calibration sessions for normalizing ratings across departments |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0829 | Generate performance distribution reports (bell curve analysis) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0830 | Track performance improvement plans (PIP) with milestones |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0831 | Generate performance trend reports for career development |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0832 | Link performance ratings to salary increment recommendations |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0833 | Link performance ratings to promotion eligibility |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0834 | Performance review notification workflow (reminder, overdue alerts) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0835 | Performance review approval workflow with HR validation |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0836 | Historical performance review access for employees and managers |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0837 | Support disciplinary case creation with case type and severity |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0838 | Define disciplinary case types (misconduct, policy violation, attendance, performance) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0839 | Define severity levels (minor, major, severe, gross misconduct) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0840 | Track case lifecycle (reported → investigation → hearing → decision → closed) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0841 | Record incident details with date, time, location, witnesses |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0842 | Assign investigator to case with timeline |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0843 | Record investigation findings and evidence |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0844 | Issue show-cause notice to employee |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0845 | Record employee response to charges |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0846 | Conduct disciplinary hearing with panel members |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0847 | Record disciplinary actions (verbal warning, written warning, suspension, termination) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0848 | Track warning expiry dates (e.g., warnings valid for 12 months) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0849 | Progressive discipline tracking (escalation from verbal to written to termination) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0850 | Generate disciplinary letters and notices |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0851 | Support appeal process for disciplinary decisions |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0852 | Disciplinary case confidentiality with restricted access |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0853 | Disciplinary history report for HR compliance |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0854 | Integration with Nexus\Workflow for disciplinary approval workflow |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0855 | Define training programs with objectives and target audience |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0856 | Define training categories (technical, soft skills, compliance, leadership) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0857 | Define training types (internal, external, online, workshop, seminar) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0858 | Track training providers and instructors |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0859 | Schedule training sessions with date, time, location, capacity |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0860 | Support training enrollment with manager approval workflow |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0861 | Track training enrollment status (pending, approved, rejected, enrolled, cancelled) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0862 | Automatic enrollment confirmation email to participants |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0863 | Track training attendance with clock-in/out |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0864 | Record training completion with completion date |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0865 | Record training feedback and evaluation scores |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0866 | Issue training certificates upon completion |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0867 | Track certification validity and expiry dates |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0868 | Automatic certification renewal reminders |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0869 | Track mandatory training compliance per employee |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0870 | Generate training reports (completion rates, costs, effectiveness) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0871 | Training needs analysis reports by department |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0872 | Training history per employee for career development |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0873 | Training budget tracking and cost allocation |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0874 | Training ROI calculation and effectiveness metrics |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0875 | Support resignation processing with resignation date and reason |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0876 | Calculate notice period based on contract terms |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0877 | Track last working day and exit date |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0878 | Support termination processing with termination type (voluntary, involuntary, retirement) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0879 | Exit clearance checklist with multiple departments |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0880 | Exit interview scheduling and recording |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0881 | Calculate final settlement (unpaid salary, leave encashment, deductions) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0882 | Generate final settlement statement |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0883 | Generate employment verification letters |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0884 | Generate service certificates |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0885 | Track re-hire eligibility status |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0886 | Employee self-service portal for viewing personal information |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0887 | Employee self-service for leave requests and balance inquiry |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0888 | Employee self-service for viewing attendance records |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0889 | Employee self-service for viewing payslips (integration with Nexus\Payroll) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0890 | Employee self-service for updating contact information |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0891 | Employee self-service for document download |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0892 | Manager dashboard showing team structure and direct reports |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0893 | Manager dashboard showing pending leave approvals |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0894 | Manager dashboard showing team attendance summary |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0895 | Manager dashboard showing team performance review status |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0896 | HR dashboard showing company-wide headcount and demographics |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0897 | HR dashboard showing leave utilization and trends |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0898 | HR dashboard showing attendance compliance rates |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0899 | HR dashboard showing upcoming contract expirations |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0900 | HR dashboard showing probation completion and confirmation due dates |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0901 | HR dashboard showing training compliance and certification expirations |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0902 | Generate employee directory with search and filters |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0903 | Generate headcount reports by department, position, employment type |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0904 | Generate turnover analysis reports with reasons |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0905 | Generate demographics reports (age, gender, tenure distribution) |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0906 | Generate absence reports with patterns and trends |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0907 | Generate overtime reports with cost implications |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0908 | Generate performance rating distribution reports |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0909 | Generate training investment reports |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0910 | Export all reports to Excel, PDF, CSV formats |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0911 | Provide RESTful API endpoints for all CRUD operations |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0912 | Provide GraphQL API for complex employee queries |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0913 | Support webhooks for HR event notifications |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0914 | Support bulk employee import from CSV/Excel |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0915 | Support bulk leave balance adjustment |  |  |  |  |
| `Nexus\Hrm` | Functional Requirement | FUN-HRM-0916 | Support data anonymization for GDPR compliance |  |  |  |  |
| `Nexus\Hrm` | Maintainability Requirement | MAINT-HRM-0939 | Framework-agnostic core with zero Laravel dependencies in packages/Hrm/src/ |  |  |  |  |
| `Nexus\Hrm` | Maintainability Requirement | MAINT-HRM-0940 | Clear separation between business logic (services) and persistence (repositories) |  |  |  |  |
| `Nexus\Hrm` | Maintainability Requirement | MAINT-HRM-0941 | Comprehensive test coverage (>80% code coverage, >90% for critical paths) |  |  |  |  |
| `Nexus\Hrm` | Maintainability Requirement | MAINT-HRM-0942 | Support multiple database backends (MySQL, PostgreSQL) |  |  |  |  |
| `Nexus\Hrm` | Maintainability Requirement | MAINT-HRM-0943 | Provide comprehensive API documentation with examples |  |  |  |  |
| `Nexus\Hrm` | Maintainability Requirement | MAINT-HRM-0944 | Support plugin architecture for custom leave policies |  |  |  |  |
| `Nexus\Hrm` | Maintainability Requirement | MAINT-HRM-0945 | Support plugin architecture for custom attendance rules |  |  |  |  |
| `Nexus\Hrm` | Performance Requirement | PERF-HRM-0917 | Employee directory search with filters < 200ms for 100K records |  |  |  |  |
| `Nexus\Hrm` | Performance Requirement | PERF-HRM-0918 | Leave balance calculation with accrual rules < 50ms per employee |  |  |  |  |
| `Nexus\Hrm` | Performance Requirement | PERF-HRM-0919 | Monthly attendance summary generation < 2 seconds for 1000 employees |  |  |  |  |
| `Nexus\Hrm` | Performance Requirement | PERF-HRM-0920 | Performance review aggregation < 1 second for department of 200 employees |  |  |  |  |
| `Nexus\Hrm` | Performance Requirement | PERF-HRM-0921 | Real-time leave balance check < 20ms for approval validation |  |  |  |  |
| `Nexus\Hrm` | Performance Requirement | PERF-HRM-0922 | Dashboard metrics calculation < 500ms for company of 10K employees |  |  |  |  |
| `Nexus\Hrm` | Performance Requirement | PER-HRM-0375 | Employee search across 100K records |  |  |  |  |
| `Nexus\Hrm` | Performance Requirement | PER-HRM-0376 | Leave balance calculation with complex rules |  |  |  |  |
| `Nexus\Hrm` | Performance Requirement | PER-HRM-0377 | Monthly attendance report generation (1000 employees) |  |  |  |  |
| `Nexus\Hrm` | Performance Requirement | PER-HRM-0378 | Performance review data aggregation (department-level) |  |  |  |  |
| `Nexus\Hrm` | Performance Requirement | PER-HRM-0379 | Real-time leave balance check during request submission |  |  |  |  |
| `Nexus\Hrm` | Reliability Requirement | REL-HRM-0933 | All leave balance transactions must be ACID-compliant |  |  |  |  |
| `Nexus\Hrm` | Reliability Requirement | REL-HRM-0934 | Attendance clock-in/out must handle concurrent requests without data loss |  |  |  |  |
| `Nexus\Hrm` | Reliability Requirement | REL-HRM-0935 | Leave accrual batch job must be idempotent (safe to re-run) |  |  |  |  |
| `Nexus\Hrm` | Reliability Requirement | REL-HRM-0936 | Contract expiry notifications must use reliable queue system |  |  |  |  |
| `Nexus\Hrm` | Reliability Requirement | REL-HRM-0937 | Performance review workflow must handle delegation during employee absence |  |  |  |  |
| `Nexus\Hrm` | Reliability Requirement | REL-HRM-0938 | System must recover gracefully from third-party integration failures |  |  |  |  |
| `Nexus\Hrm` | Security and Compliance Requirement | SEC-HRM-0492 | Implement audit logging for all employee data changes using ActivityLoggerContract |  |  |  |  |
| `Nexus\Hrm` | Security and Compliance Requirement | SEC-HRM-0493 | Enforce tenant isolation for all HR data via tenant scoping |  |  |  |  |
| `Nexus\Hrm` | Security and Compliance Requirement | SEC-HRM-0494 | Support authorization policies through contract-based permission system |  |  |  |  |
| `Nexus\Hrm` | Security and Compliance Requirement | SEC-HRM-0495 | Encrypt sensitive employee data (personal information, salary details) at rest |  |  |  |  |
| `Nexus\Hrm` | Security and Compliance Requirement | SEC-HRM-0496 | Implement field-level access control (HR managers see salary, line managers don't) |  |  |  |  |
| `Nexus\Hrm` | Security Requirement | SEC-HRM-0923 | Implement field-level encryption for sensitive data (IC number, passport, salary) |  |  |  |  |
| `Nexus\Hrm` | Security Requirement | SEC-HRM-0924 | Enforce data masking for non-authorized viewers (show only last 4 digits of IC) |  |  |  |  |
| `Nexus\Hrm` | Security Requirement | SEC-HRM-0925 | Implement row-level security (employees can only view own data unless authorized) |  |  |  |  |
| `Nexus\Hrm` | Security Requirement | SEC-HRM-0926 | Enforce manager-subordinate data access boundaries |  |  |  |  |
| `Nexus\Hrm` | Security Requirement | SEC-HRM-0927 | Require additional authentication for sensitive operations (salary change, termination) |  |  |  |  |
| `Nexus\Hrm` | Security Requirement | SEC-HRM-0928 | Implement document access control with role-based permissions |  |  |  |  |
| `Nexus\Hrm` | Security Requirement | SEC-HRM-0929 | Track document access audit trail (who viewed, when, from where) |  |  |  |  |
| `Nexus\Hrm` | Security Requirement | SEC-HRM-0930 | Support data retention policies with automated data purging |  |  |  |  |
| `Nexus\Hrm` | Security Requirement | SEC-HRM-0931 | Support right to be forgotten (GDPR Article 17) |  |  |  |  |
| `Nexus\Hrm` | Security Requirement | SEC-HRM-0932 | Implement secure file upload with virus scanning |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0946 | As an HR admin, I want to onboard new employee with complete profile in one workflow |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0947 | As an HR admin, I want to track employment contracts with automatic expiry alerts |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0948 | As an HR admin, I want to configure leave policies per employment type |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0949 | As an HR admin, I want to process annual leave carry-forward at year-end automatically |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0950 | As an employee, I want to apply for leave and see my balance instantly |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0951 | As an employee, I want to view my leave history and upcoming leave |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0952 | As an employee, I want to clock in/out using mobile app with GPS verification |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0953 | As an employee, I want to view my monthly attendance summary and overtime hours |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0954 | As an employee, I want to request attendance regularization for missed clock-in |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0955 | As a manager, I want to approve/reject leave requests from my team |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0956 | As a manager, I want to view team leave calendar to ensure coverage |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0957 | As a manager, I want to view real-time attendance of my team |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0958 | As a manager, I want to conduct performance reviews with guided workflow |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0959 | As a manager, I want to track probation completion for my team members |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0960 | As a manager, I want to nominate team members for training programs |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0961 | As an HR manager, I want to configure performance review cycles company-wide |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0962 | As an HR manager, I want to track performance review completion across departments |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0963 | As an HR manager, I want to analyze performance rating distribution for calibration |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0964 | As an HR manager, I want to initiate disciplinary cases with investigation workflow |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0965 | As an HR manager, I want to track progressive discipline for compliance |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0966 | As an HR manager, I want to schedule training programs with enrollment management |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0967 | As an HR manager, I want to track training completion and certification expiry |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0968 | As an HR manager, I want to track mandatory training compliance |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0969 | As an HR manager, I want to process employee resignations with exit clearance |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0970 | As an HR manager, I want to calculate final settlement for terminated employees |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0971 | As an HR manager, I want to generate employment verification letters automatically |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0972 | As an HR director, I want dashboard showing company-wide HR metrics |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0973 | As an HR director, I want turnover analysis with retention insights |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0974 | As an HR director, I want workforce planning reports (headcount trends, demographics) |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0975 | As an HR director, I want to analyze leave utilization patterns by department |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0976 | As an HR director, I want to track overtime costs and trends |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0977 | As an HR director, I want to measure training ROI and effectiveness |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0978 | As a developer, I want to integrate HRM with payroll for salary processing |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0979 | As a developer, I want to integrate HRM with workflow for approval processes |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0980 | As a developer, I want to integrate HRM with organizational structure automatically |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0981 | As a system admin, I want to configure leave policies without code changes |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0982 | As a system admin, I want to configure attendance rules and overtime calculations |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0983 | As a system admin, I want to configure performance review templates and rating scales |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0984 | As a system admin, I want to configure disciplinary action types and severity levels |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0985 | As a compliance officer, I want to audit all employee data changes |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0986 | As a compliance officer, I want to ensure data retention policies are enforced |  |  |  |  |
| `Nexus\Hrm` | User Story | USE-HRM-0987 | As a compliance officer, I want to generate compliance reports for labor authorities |  |  |  |  |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-8129 | EPF Number field MUST be mandatory when Malaysia Payroll Statutory is enabled | apps/Atomy/database/migrations/ (epf_number field) | Deferred | To be implemented when Hrm package migration is enhanced | 2025-11-18 |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-8130 | SOCSO Number field MUST be mandatory when Malaysia Payroll Statutory is enabled | apps/Atomy/database/migrations/ (socso_number field) | Deferred | To be implemented when Hrm package migration is enhanced | 2025-11-18 |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-8131 | Income Tax Number field MUST be mandatory when Malaysia Payroll Statutory is enabled | apps/Atomy/database/migrations/ (income_tax_number field) | Deferred | To be implemented when Hrm package migration is enhanced | 2025-11-18 |
| `Nexus\Hrm` | Business Requirements | BUS-HRM-8132 | Compliance-driven validation rules MUST be injected by Nexus\Compliance | packages/Compliance/ (validation rule injection architecture) | Complete | Architecture supports compliance-driven validation rule injection | 2025-11-18 |
