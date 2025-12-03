Absolutely — here is the **complete, consolidated, production-ready folder structure** for the **entire HR domain + orchestrator layer**, under the `Nexus\` namespace.

This structure reflects everything we’ve designed so far:

* Clean Architecture
* Domain-first modular design
* Atomic domain packages (individually publishable)
* Operations/Orchestrator package that integrates them
* Shared DTOs/VOs are intentionally excluded because they live in `Nexus\Common`
* Integration adapters live outside the atomic domain packages

---

# ✅ **Top-Level HR Package Map**

Below is the complete hierarchy:

```
packages/HRM/
├── HumanResourceOperations/
├── LeaveManagement/
├── AttendanceManagement/
├── PayrollCore/
├── EmployeeProfile/
├── ShiftManagement/
└── (…future HR atomic domain packages)
```

---

# 🧱 **1. HUMAN RESOURCE OPERATIONS (Orchestration Layer)**

Namespace: `Nexus\HumanResourceOperations`

This is the application/orchestration layer that coordinates all other atomic packages.

```
packages/HumanResourceOperations/
├── composer.json
├── README.md
├── docs/
│   ├── architecture-overview.md
│   ├── sequence-diagrams/
│   ├── api-reference.md
│   └── examples/
│
├── src/
│   ├── UseCases/
│   │   ├── Leave/
│   │   │   ├── ApplyLeaveHandler.php
│   │   │   ├── CancelLeaveHandler.php
│   │   │   ├── ApproveLeaveHandler.php
│   │   │   ├── RejectLeaveHandler.php
│   │   │   ├── CalculateAccrualHandler.php
│   │   │   └── GetLeaveBalanceHandler.php
│   │   │
│   │   ├── Attendance/
│   │   │   ├── SyncAttendanceLogsHandler.php
│   │   │   ├── ValidateAttendanceEntryHandler.php
│   │   │   ├── GenerateAttendanceSummaryHandler.php
│   │   │   └── ResolveShiftCodeHandler.php
│   │   │
│   │   ├── Payroll/
│   │   │   ├── ProcessPayrollHandler.php
│   │   │   ├── GeneratePayslipHandler.php
│   │   │   ├── RecalculatePayrollComponentHandler.php
│   │   │   └── PreviewPayrollHandler.php
│   │   │
│   │   ├── Employee/
│   │   │   ├── RegisterEmployeeHandler.php
│   │   │   ├── UpdateEmployeeContractHandler.php
│   │   │   ├── TerminateEmployeeHandler.php
│   │   │   └── GetEmployeeProfileHandler.php
│   │   │
│   │   ├── Disciplinary/
│   │   │   ├── FileDisciplinaryCaseHandler.php
│   │   │   ├── AddEvidenceToCaseHandler.php
│   │   │   ├── ClassifyCaseHandler.php
│   │   │   ├── CalculateSanctionHandler.php
│   │   │   └── CloseDisciplinaryCaseHandler.php
│   │   │
│   │   ├── PerformanceReview/
│   │   │   ├── StartAppraisalCycleHandler.php
│   │   │   ├── SubmitSelfReviewHandler.php
│   │   │   ├── SubmitManagerReviewHandler.php
│   │   │   ├── ComputeFinalRatingHandler.php
│   │   │   └── FinalizeAppraisalHandler.php
│   │   │
│   │   ├── Training/
│   │   │   ├── EnrollEmployeeHandler.php
│   │   │   ├── ApproveTrainingRequestHandler.php
│   │   │   ├── TrackTrainingAttendanceHandler.php
│   │   │   ├── GrantCertificationHandler.php
│   │   │   └── CompleteTrainingHandler.php
│   │   │
│   │   ├── Recruitment/
│   │   │   ├── CreateJobPostingHandler.php
│   │   │   ├── SubmitApplicationHandler.php
│   │   │   ├── ScheduleInterviewHandler.php
│   │   │   ├── EvaluateInterviewHandler.php
│   │   │   └── MakeHiringDecisionHandler.php
│   │   │
│   │   ├── Onboarding/
│   │   │   ├── StartOnboardingHandler.php
│   │   │   ├── CompleteTaskHandler.php
│   │   │   ├── TrackOnboardingProgressHandler.php
│   │   │   ├── SubmitDocumentHandler.php
│   │   │   └── FinalizeProbationReviewHandler.php
│   │
│   ├── Pipelines/
│   │   ├── Leave/
│   │   │   ├── ApplyLeavePipeline.php
│   │   │   ├── CancelLeavePipeline.php
│   │   │   └── AccrualCalculationPipeline.php
│   │   │
│   │   ├── Attendance/
│   │   │   ├── AttendanceSyncPipeline.php
│   │   │   └── AnomalyDetectionPipeline.php
│   │   │
│   │   ├── Payroll/
│   │   │   ├── PayrollProcessingPipeline.php
│   │   │   └── ContributionCalculationPipeline.php
│   │   │
│   │   ├── Disciplinary/
│   │   │   ├── CaseEvaluationPipeline.php
│   │   │   ├── EvidenceReviewPipeline.php
│   │   │   └── SanctionDecisionPipeline.php
│   │   │
│   │   ├── PerformanceReview/
│   │   │   ├── ReviewSubmissionPipeline.php
│   │   │   └── FinalRatingPipeline.php
│   │   │
│   │   ├── Training/
│   │   │   └── EnrollmentApprovalPipeline.php
│   │   │
│   │   ├── Recruitment/
│   │   │   ├── ApplicationIntakePipeline.php
│   │   │   ├── InterviewWorkflowPipeline.php
│   │   │   └── DecisionMakingPipeline.php
│   │   │
│   │   └── Onboarding/
│   │       ├── OnboardingChecklistPipeline.php
│   │       └── ProbationReviewPipeline.php
│   │
│   ├── Services/
│   │   ├── CountryLaw/
│   │   │   ├── LeaveAccrualStrategyResolver.php
│   │   │   └── PayrollContributionResolver.php
│   │   │
│   │   ├── Leave/LeaveNotificationService.php
│   │   ├── Attendance/ShiftResolutionService.php
│   │   ├── Payroll/PayslipExportService.php
│   │   ├── Disciplinary/CaseSanctionService.php
│   │   ├── PerformanceReview/RatingAggregationService.php
│   │   ├── Training/TrainerMatchingService.php
│   │   ├── Recruitment/ApplicantRankingService.php
│   │   └── Onboarding/OnboardingProgressService.php
│   │
│   ├── Adapters/
│   │   ├── Attendance/
│   │   │   └── DeviceAttendanceAdapter.php
│   │   │
│   │   ├── Payroll/
│   │   │   └── BankFileExportAdapter.php
│   │   │
│   │   ├── Recruitment/
│   │   │   └── ExternalJobPortalAdapter.php
│   │   │
│   │   ├── Training/
│   │   │   └── ExternalCourseProviderAdapter.php
│   │   │
│   │   ├── Employee/
│   │   │   └── DocumentStorageAdapter.php
│   │
│   ├── Contracts/
│   │   ├── AttendanceDeviceGatewayInterface.php
│   │   ├── NotificationGatewayInterface.php
│   │   ├── ExternalJobPortalGatewayInterface.php
│   │   ├── BankExportGatewayInterface.php
│   │   └── DocumentStorageGatewayInterface.php
│   │
│   └── Exceptions/
│       ├── InvalidOperationException.php
│       ├── WorkflowAbortException.php
│       ├── IntegrationFailedException.php
│       └── PipelineStepException.php
│
└── tests/

```

---

# 🧱 **2. LEAVE MANAGEMENT (Atomic Domain Package)**

Namespace: `Nexus\LeaveManagement`

This is your fully domain-pure package.

```
packages/HRM/LeaveManagement/
├── composer.json
├── README.md
├── docs/
│   ├── getting-started.md
│   ├── api-reference.md
│   └── examples/
├── src/
│   ├── Contracts/
│   │   ├── LeaveRepositoryInterface.php
│   │   ├── LeaveTypeRepositoryInterface.php
│   │   ├── LeaveBalanceRepositoryInterface.php
│   │   ├── LeaveCalculatorInterface.php
│   │   ├── LeaveAccrualEngineInterface.php
│   │   ├── LeavePolicyInterface.php
│   │   ├── AccrualStrategyInterface.php
│   │   ├── AccrualStrategyResolverInterface.php
│   │   └── CountryLawRepositoryInterface.php
│   │
│   ├── Entities/
│   │   ├── Leave.php
│   │   ├── LeaveType.php
│   │   ├── LeaveBalance.php
│   │   └── LeaveEntitlement.php
│   │
│   ├── ValueObjects/      (DTO/VO excluded if under Nexus\Common)
│   │   ├── LeavePeriod.php
│   │   ├── AccrualRule.php
│   │   ├── CarryForwardRule.php
│   │   └── ProrationRule.php
│   │
│   ├── Enums/
│   │   ├── LeaveStatus.php
│   │   ├── LeaveCategory.php
│   │   ├── AccrualFrequency.php
│   │   ├── ApprovalStatus.php
│   │   └── LeaveDuration.php
│   │
│   ├── Services/
│   │   ├── LeaveBalanceCalculator.php
│   │   ├── LeaveAccrualEngine.php
│   │   ├── LeavePolicyValidator.php
│   │   ├── LeaveOverlapDetector.php
│   │   ├── CarryForwardProcessor.php
│   │   ├── LeaveProrationCalculator.php
│   │   ├── LeaveEncashmentCalculator.php
│   │   └── AccrualStrategies/
│   │       ├── MonthlyAccrualStrategy.php
│   │       ├── FixedAllocationStrategy.php
│   │       ├── NoDeductionStrategy.php
│   │       └── CustomLawAdjustedStrategy.php
│   │
│   ├── Resolvers/
│   │   └── CountryLawAwareStrategyResolver.php
│   │
│   └── Exceptions/
│       ├── LeaveException.php
│       ├── LeaveNotFoundException.php
│       ├── LeaveOverlapException.php
│       ├── LeaveValidationException.php
│       ├── InsufficientBalanceException.php
│       ├── LeaveTypeNotFoundException.php
│       └── AccrualStrategyNotFoundException.php
│
└── tests/
```

---

# 🧱 **3. ATTENDANCE MANAGEMENT (Atomic Domain Package)**

Namespace: `Nexus\AttendanceManagement`

```
packages/HRM/AttendanceManagement/
├── composer.json
├── README.md
├── docs/
├── src/
│   ├── Contracts/
│   │   ├── AttendanceRepositoryInterface.php
│   │   ├── WorkScheduleRepositoryInterface.php
│   │   ├── AttendanceRuleInterface.php
│   │   └── AttendancePolicyInterface.php
│   │
│   ├── Entities/
│   │   ├── AttendanceRecord.php
│   │   ├── WorkSchedule.php
│   │   └── AttendanceAdjustment.php
│   │
│   ├── Enums/
│   │   ├── CheckType.php
│   │   ├── AttendanceStatus.php
│   │   └── AnomalyType.php
│   │
│   ├── Services/
│   │   ├── AttendanceValidator.php
│   │   ├── AnomalyDetector.php
│   │   └── ScheduleResolver.php
│   │
│   └── Exceptions/
└── tests/
```

---

# 🧱 **4. PAYROLL CORE (Atomic Domain Package)**

Namespace: `Nexus\PayrollCore`

```
packages/HRM/PayrollCore/
├── composer.json
├── README.md
├── docs/
├── src/
│   ├── Contracts/
│   │   ├── PayslipRepositoryInterface.php
│   │   ├── PayrollRuleInterface.php
│   │   ├── PayrollAdjustmentRepositoryInterface.php
│   │   └── PayrollFormulaInterface.php
│   │
│   ├── Entities/
│   │   ├── Payslip.php
│   │   ├── PayrollAdjustment.php
│   │   ├── PayrollPeriod.php
│   │   └── PayrollComponent.php
│   │
│   ├── Enums/
│   │   ├── ComponentType.php
│   │   ├── ContributionType.php
│   │   └── CalculationMode.php
│   │
│   ├── Services/
│   │   ├── PayrollCalculator.php
│   │   ├── OvertimeCalculator.php
│   │   ├── ContributionCalculator.php
│   │   └── NetPayCalculator.php
│   │
│   └── Exceptions/
└── tests/
```

---

# 🧱 **5. EMPLOYEE PROFILE (Atomic Domain Package)**

Namespace: `Nexus\EmployeeProfile`

```
packages/HRM/EmployeeProfile/
├── composer.json
├── README.md
├── src/
│   ├── Contracts/
│   │   ├── EmployeeRepositoryInterface.php
│   │   └── EmploymentContractRepositoryInterface.php
│   │
│   ├── Entities/
│   │   ├── Employee.php
│   │   ├── EmploymentContract.php
│   │   └── JobPosition.php
│   │
│   ├── Enums/
│   │   ├── EmploymentType.php
│   │   └── EmployeeStatus.php
│   │
│   ├── Services/
│   │   ├── EmploymentDurationCalculator.php
│   │   └── EmployeeEligibilityEvaluator.php
│   │
│   └── Exceptions/
└── tests/
```

---

# 🧱 **6. SHIFT MANAGEMENT (Atomic Domain Package)**

Namespace: `Nexus\ShiftManagement`

```
packages/HRM/ShiftManagement/
├── composer.json
├── README.md
├── src/
│   ├── Contracts/
│   │   ├── ShiftRepositoryInterface.php
│   │   ├── ShiftRuleInterface.php
│   │   └── ShiftGenerationPolicyInterface.php
│   │
│   ├── Entities/
│   │   ├── Shift.php
│   │   ├── ShiftTemplate.php
│   │   └── ShiftAssignment.php
│   │
│   ├── Enums/
│   │   ├── ShiftType.php
│   │   └── ShiftStatus.php
│   │
│   ├── Services/
│   │   ├── ShiftGenerator.php
│   │   ├── ShiftOverlapChecker.php
│   │   └── ShiftComplianceValidator.php
│   │
│   └── Exceptions/
└── tests/
```

---

Below is a **clean, standardized, Nexus-style directory structure** for the new HR domain packages you requested:

* **Disciplinary**
* **Performance Review**
* **Training & Development**
* **Recruitment**
* **Onboarding**

All structures follow the same *Atomic Package* format used across the Nexus ERP HR domain, consistent with your LeaveManagement, Attendance, Payroll, etc.

Each package is:

* **Pure domain only**
* **No orchestration**
* **No frameworks**
* **Publishable individually**
* **Contains Entities, Value Objects, Policies, Services, Exceptions, Contracts**

All orchestration (UseCases, Pipelines, Gateways) will be placed under **Nexus\HumanResourceOperations**.

---

# ✅ **1. Nexus\Disciplinary**

Handles employee misconduct reporting, case management, warnings, sanctions, and policy enforcement.

```
packages/HRM/Disciplinary/
├── composer.json
├── README.md
├── docs/
│   ├── workflow.md
│   ├── integration-guide.md
│   └── examples/
├── src/
│   ├── Contracts/
│   │   ├── DisciplinaryCaseRepositoryInterface.php
│   │   ├── EvidenceRepositoryInterface.php
│   │   ├── DisciplinaryPolicyInterface.php
│   │   └── SanctionDecisionEngineInterface.php
│   ├── Entities/
│   │   ├── DisciplinaryCase.php
│   │   ├── Evidence.php
│   │   └── Sanction.php
│   ├── ValueObjects/
│   │   ├── CaseNumber.php
│   │   ├── OffenceType.php
│   │   ├── SanctionLevel.php
│   │   └── CaseOutcome.php
│   ├── Policies/
│   │   ├── CodeOfConductPolicy.php
│   │   ├── FraudPolicy.php
│   │   ├── WorkplaceHarassmentPolicy.php
│   │   └── SafetyViolationPolicy.php
│   ├── Services/
│   │   ├── SanctionDecisionEngine.php
│   │   ├── CaseClassificationService.php
│   │   ├── SeverityScoringService.php
│   │   └── CaseValidationService.php
│   └── Exceptions/
│       ├── DisciplinaryCaseNotFoundException.php
│       ├── InvalidEvidenceException.php
│       ├── PolicyViolationException.php
│       └── SanctionCalculationException.php
└── tests/
```

---

# ✅ **2. Nexus\PerformanceReview**

For appraisal cycles, KPIs, competency scoring, rating calculations.

```
packages/HRM/PerformanceReview/
├── composer.json
├── README.md
├── docs/
│   ├── appraisal-cycle.md
│   ├── rating-formula.md
│   └── examples/
├── src/
│   ├── Contracts/
│   │   ├── AppraisalRepositoryInterface.php
│   │   ├── KpiRepositoryInterface.php
│   │   ├── ReviewTemplateRepositoryInterface.php
│   │   └── PerformanceCalculatorInterface.php
│   ├── Entities/
│   │   ├── Appraisal.php
│   │   ├── Kpi.php
│   │   └── ReviewTemplate.php
│   ├── ValueObjects/
│   │   ├── Rating.php
│   │   ├── CompetencyScore.php
│   │   └── AppraisalPeriod.php
│   ├── Services/
│   │   ├── PerformanceScoreCalculator.php
│   │   ├── WeightedRatingEngine.php
│   │   ├── AppraisalConsistencyChecker.php
│   │   └── KpiEvaluator.php
│   ├── Policies/
│   │   ├── RatingNormalizationPolicy.php
│   │   ├── CompetencyEvaluationPolicy.php
│   │   └── PromotionEligibilityPolicy.php
│   └── Exceptions/
│       ├── AppraisalNotFoundException.php
│       ├── InvalidRatingException.php
│       └── TemplateNotFoundException.php
└── tests/
```

---

# ✅ **3. Nexus\TrainingManagement**

Manages courses, enrollments, trainers, certification tracking.

```
packages/HRM/TrainingManagement/
├── composer.json
├── README.md
├── docs/
│   ├── training-lifecycle.md
│   ├── certification-guide.md
│   └── examples/
├── src/
│   ├── Contracts/
│   │   ├── CourseRepositoryInterface.php
│   │   ├── EnrollmentRepositoryInterface.php
│   │   ├── TrainerRepositoryInterface.php
│   │   └── TrainingPolicyInterface.php
│   ├── Entities/
│   │   ├── Course.php
│   │   ├── Enrollment.php
│   │   └── Trainer.php
│   ├── ValueObjects/
│   │   ├── CourseId.php
│   │   ├── EnrollmentStatus.php
│   │   └── Certification.php
│   ├── Services/
│   │   ├── CertificationGrantService.php
│   │   ├── AttendanceTrackingService.php
│   │   └── CourseEligibilityChecker.php
│   ├── Policies/
│   │   ├── TrainingApprovalPolicy.php
│   │   ├── CertificationPolicy.php
│   │   └── ReimbursementPolicy.php
│   └── Exceptions/
│       ├── CourseNotFoundException.php
│       ├── EnrollmentDeniedException.php
│       └── CertificationException.php
└── tests/
```

---

# ✅ **4. Nexus\Recruitment**

Covers job posting, applicant tracking, interviews, scoring.

```
packages/HRM/Recruitment/
├── composer.json
├── README.md
├── docs/
│   ├── interview-flow.md
│   ├── scoring-formula.md
│   └── examples/
├── src/
│   ├── Contracts/
│   │   ├── JobPostingRepositoryInterface.php
│   │   ├── ApplicantRepositoryInterface.php
│   │   ├── InterviewRepositoryInterface.php
│   │   └── HiringDecisionEngineInterface.php
│   ├── Entities/
│   │   ├── JobPosting.php
│   │   ├── Applicant.php
│   │   └── Interview.php
│   ├── ValueObjects/
│   │   ├── ApplicantScore.php
│   │   ├── InterviewResult.php
│   │   └── JobCode.php
│   ├── Services/
│   │   ├── ApplicantScoringEngine.php
│   │   ├── InterviewEvaluationService.php
│   │   └── HiringDecisionEngine.php
│   ├── Policies/
│   │   ├── EligibilityCheckPolicy.php
│   │   ├── DiversityCompliancePolicy.php
│   │   └── BackgroundCheckPolicy.php
│   └── Exceptions/
│       ├── ApplicantNotFoundException.php
│       ├── InvalidInterviewException.php
│       └── HiringDecisionException.php
└── tests/
```

---

# ✅ **5. Nexus\Onboarding**

Handles pre-hire → onboarding → probation milestones.

```
packages/HRM/Onboarding/
├── composer.json
├── README.md
├── docs/
│   ├── onboarding-flow.md
│   ├── digital-checklist.md
│   └── examples/
├── src/
│   ├── Contracts/
│   │   ├── OnboardingTaskRepositoryInterface.php
│   │   ├── OnboardingChecklistRepositoryInterface.php
│   │   └── ProbationReviewRepositoryInterface.php
│   ├── Entities/
│   │   ├── OnboardingTask.php
│   │   ├── OnboardingChecklist.php
│   │   └── ProbationReview.php
│   ├── ValueObjects/
│   │   ├── TaskStatus.php
│   │   ├── ChecklistId.php
│   │   └── ProbationStatus.php
│   ├── Services/
│   │   ├── OnboardingProgressTracker.php
│   │   ├── TaskAssignmentService.php
│   │   └── ProbationEvaluationService.php
│   ├── Policies/
│   │   ├── ProbationPolicy.php
│   │   ├── DocumentSubmissionPolicy.php
│   │   └── EquipmentIssuancePolicy.php
│   └── Exceptions/
│       ├── TaskNotFoundException.php
│       ├── ChecklistNotFoundException.php
│       └── ProbationReviewException.php
└── tests/
```

---

# ⭐ Final Notes

### ✔ All packages follow:

* Entity + VO + Policy + Service + Contracts layout
* No UseCases or Infrastructure
* No external dependencies
* No orchestration
* No DTOs (those are in `Nexus\Common`)

### ✔ HumanResourceOperations will orchestrate across:

* Leave
* Attendance
* Payroll
* Employee Profile
* **Disciplinary (NEW)**
* **PerformanceReview (NEW)**
* **Training (NEW)**
* **Recruitment (NEW)**
* **Onboarding (NEW)**


Each:

* Pure domain
* No framework
* No persistence implementation
* Cleanly boundaries
* Ready to publish individually on Packagist
