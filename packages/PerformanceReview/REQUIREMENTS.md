# Nexus\PerformanceReview Requirements

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| **REQ-PFM-001** | Define `Appraisal` entity to represent a performance review cycle for an employee. | P0 | 🔴 |
| **REQ-PFM-002** | Define `Kpi` entity for specific measurable goals within an appraisal. | P0 | 🔴 |
| **REQ-PFM-003** | Define `ReviewTemplate` entity to standardize review structures across departments. | P1 | 🔴 |
| **REQ-PFM-004** | Implement `PerformanceScoreCalculator` service to compute weighted scores based on KPIs and competencies. | P0 | 🔴 |
| **REQ-PFM-005** | Implement `Rating` value object to enforce valid rating scales (e.g., 1-5, 0-100). | P0 | 🔴 |
| **REQ-PFM-006** | Implement `CompetencyScore` value object for qualitative assessments. | P1 | 🔴 |
| **REQ-PFM-007** | Define `AppraisalPeriod` value object to manage review cycles (e.g., Q1 2024). | P1 | 🔴 |
| **REQ-PFM-008** | Create `AppraisalRepositoryInterface` contract for data persistence. | P0 | 🔴 |
| **REQ-PFM-009** | Create `KpiEvaluator` service to determine KPI achievement levels. | P1 | 🔴 |
| **REQ-PFM-010** | Implement `RatingNormalization` policy to adjust scores based on bell curve or calibration. | P2 | 🔴 |
| **REQ-PFM-011** | Develop `StartAppraisalCycle` command to initiate reviews for eligible employees. | P1 | 🔴 |
| **REQ-PFM-012** | Develop `SubmitSelfAssessment` command for employee input. | P1 | 🔴 |
| **REQ-PFM-013** | Develop `SubmitManagerReview` command for supervisor input. | P1 | 🔴 |
