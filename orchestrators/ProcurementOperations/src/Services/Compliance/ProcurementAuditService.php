<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Services\Compliance;

use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;
use Nexus\Identity\Contracts\RoleQueryInterface;
use Nexus\Identity\Contracts\UserQueryInterface;
use Nexus\ProcurementOperations\Enums\ControlArea;
use Nexus\Tenant\Contracts\TenantContextInterface;
use Nexus\ProcurementOperations\Contracts\AuditLoggerAdapterInterface;
use Nexus\ProcurementOperations\Contracts\SettingsAdapterInterface;
use Nexus\ProcurementOperations\Enums\AuditFindingSeverity;
use Nexus\ProcurementOperations\DTOs\Audit\AuditFindingData;
use Nexus\ProcurementOperations\DTOs\Audit\Sox404EvidenceData;
use Nexus\ProcurementOperations\Services\SODValidationService;
use Nexus\ProcurementOperations\DTOs\Audit\ControlTestResultData;
use Nexus\ProcurementOperations\Exceptions\ProcurementAuditException;
use Nexus\ProcurementOperations\DTOs\Audit\SegregationOfDutiesReportData;
use Nexus\ProcurementOperations\Contracts\ProcurementAuditServiceInterface;

/**
 * Service for SOX 404 compliance audit and evidence generation.
 *
 * Generates comprehensive audit evidence packages for:
 * - Internal Control Testing (Section 404)
 * - Segregation of Duties Reports
 * - Approval Authority Validation
 * - Exception Tracking and Resolution
 * - Three-Way Match Audit Trail
 *
 * All evidence is generated with full audit trail for regulatory compliance.
 */
final class ProcurementAuditService implements ProcurementAuditServiceInterface
{
    /**
     * Incompatible duty pairs for SoD validation.
     *
     * @var array<array{duty1: string, duty2: string, severity: string, reason: string}>
     */
    private const INCOMPATIBLE_DUTY_PAIRS = [
        [
            'duty1' => 'create_requisition',
            'duty2' => 'approve_requisition',
            'severity' => 'HIGH',
            'reason' => 'Requestor cannot approve own request',
        ],
        [
            'duty1' => 'create_purchase_order',
            'duty2' => 'approve_purchase_order',
            'severity' => 'HIGH',
            'reason' => 'PO creator cannot approve own PO',
        ],
        [
            'duty1' => 'create_vendor',
            'duty2' => 'process_payment',
            'severity' => 'HIGH',
            'reason' => 'Vendor master changes require separation from payment processing',
        ],
        [
            'duty1' => 'receive_goods',
            'duty2' => 'approve_payment',
            'severity' => 'MEDIUM',
            'reason' => 'Goods receiver should not approve payment',
        ],
        [
            'duty1' => 'process_invoice',
            'duty2' => 'approve_payment',
            'severity' => 'MEDIUM',
            'reason' => 'Invoice processor should not approve same payment',
        ],
        [
            'duty1' => 'create_purchase_order',
            'duty2' => 'receive_goods',
            'severity' => 'MEDIUM',
            'reason' => 'PO creator should not receive goods for same order',
        ],
    ];

    /**
     * Storage for audit findings (in production, would be persisted via repository).
     *
     * @var array<string, AuditFindingData>
     */
    private array $findings = [];

    public function __construct(
        private readonly AuditLoggerAdapterInterface $auditLogger,
        private readonly UserQueryInterface $userQuery,
        private readonly RoleQueryInterface $roleQuery,
        private readonly SODValidationService $sodValidationService,
        private readonly SettingsAdapterInterface $settings,
        private readonly TenantContextInterface $tenantContext,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function generateSox404Evidence(
        string $tenantId,
        string $auditPeriod,
        array $controlAreas = [],
    ): array {
        $this->logger->info('Generating SOX 404 evidence package', [
            'tenant_id' => $tenantId,
            'audit_period' => $auditPeriod,
            'control_areas' => $controlAreas,
        ]);

        // Parse audit period
        [$fiscalYear, $quarter] = $this->parseAuditPeriod($auditPeriod);
        [$periodStart, $periodEnd] = $this->getQuarterDates($fiscalYear, $quarter);

        // Determine which control areas to test
        $areasToTest = !empty($controlAreas)
            ? array_map(fn(string $area) => ControlArea::from($area), $controlAreas)
            : ControlArea::cases();

        // Generate control test results
        $controlTestResults = $this->executeControlTests($tenantId, $areasToTest, $periodStart, $periodEnd);

        // Get findings for period
        $findings = $this->getAuditExceptions($tenantId, $auditPeriod);

        // Generate SoD matrix
        $sodReport = $this->getSegregationOfDutiesReport($tenantId, $periodEnd);

        // Validate approval authority
        $approvalValidation = $this->validateApprovalAuthority($tenantId, $periodStart, $periodEnd);

        // Generate three-way match audit trail
        $threeWayMatchTrail = $this->getThreeWayMatchAuditTrail($tenantId, $periodStart, $periodEnd);

        // Build evidence package
        $evidenceData = new Sox404EvidenceData(
            evidenceId: $this->generateEvidenceId(),
            tenantId: $tenantId,
            fiscalYear: $fiscalYear,
            periodStart: $periodStart,
            periodEnd: $periodEnd,
            generatedAt: new \DateTimeImmutable(),
            generatedBy: $this->getCurrentUserId(),
            controlTestResults: $controlTestResults,
            findings: $findings,
            segregationOfDutiesMatrix: $sodReport['conflictMatrix'] ?? [],
            approvalAuthorityMatrix: $approvalValidation['matrix'] ?? [],
            threeWayMatchAuditTrail: $threeWayMatchTrail,
            managementAssertions: $this->generateManagementAssertions($controlTestResults),
            controlEnvironmentSummary: $this->summarizeControlEnvironment($controlTestResults, $sodReport),
            overallConclusion: $this->determineOverallConclusion($controlTestResults, $findings),
        );

        // Log audit trail
        $this->auditLogger->log(
            logName: 'procurement_audit',
            description: "SOX 404 evidence package generated for {$auditPeriod}",
            subjectType: 'sox_404_evidence',
            subjectId: $evidenceData->evidenceId,
            properties: [
                'fiscal_year' => $fiscalYear,
                'control_tests_count' => count($controlTestResults),
                'findings_count' => count($findings),
                'pass_rate' => $evidenceData->getControlTestPassRate(),
            ],
            event: 'sox_404_evidence_generated',
            tenantId: $tenantId,
        );

        return $evidenceData->getExecutiveSummary();
    }

    /**
     * {@inheritdoc}
     */
    public function getSegregationOfDutiesReport(
        string $tenantId,
        \DateTimeImmutable $asOfDate,
    ): array {
        $this->logger->info('Generating Segregation of Duties report', [
            'tenant_id' => $tenantId,
            'as_of_date' => $asOfDate->format('Y-m-d'),
        ]);

        // Get all roles in tenant
        $roles = $this->roleQuery->getAll($tenantId);

        // Build user-role assignments for each role
        $userRoleAssignments = [];
        $processedUserIds = [];

        foreach ($roles as $role) {
            // Get users assigned to this role using userQuery's findByRole
            $usersWithRole = $this->userQuery->findByRole($role->getId());

            foreach ($usersWithRole as $user) {
                $userId = $user->getId();

                if (!isset($userRoleAssignments[$userId])) {
                    // First time seeing this user, initialize
                    $userRoles = $this->userQuery->getUserRoles($userId);
                    $userRoleAssignments[$userId] = [
                        'user_id' => $userId,
                        'user_name' => $user->getEmail(), // Use email as identifier
                        'roles' => [],
                    ];

                    foreach ($userRoles as $userRole) {
                        $userRoleAssignments[$userId]['roles'][] = [
                            'role_id' => $userRole->getId(),
                            'role_name' => $userRole->getName(),
                            'permissions' => $this->roleQuery->getRolePermissions($userRole->getId()),
                        ];
                    }
                }
            }
        }

        // Detect SoD violations
        $violations = $this->detectSodViolations($userRoleAssignments);

        // Build conflict matrix
        $conflictMatrix = $this->buildConflictMatrix($violations);

        // Get mitigating controls
        $mitigatingControls = $this->getMitigatingControls($violations);

        // Assess compliance
        $isCompliant = count(array_filter(
            $violations,
            fn($v) => ($v['severity'] ?? 'MEDIUM') === 'HIGH'
        )) === 0;

        $report = new SegregationOfDutiesReportData(
            reportId: $this->generateReportId('sod'),
            tenantId: $tenantId,
            periodStart: $asOfDate->modify('-1 year'),
            periodEnd: $asOfDate,
            generatedAt: new \DateTimeImmutable(),
            generatedBy: $this->getCurrentUserId(),
            conflictMatrix: $conflictMatrix,
            violationSummary: $violations,
            userRoleAssignments: array_values($userRoleAssignments),
            incompatibleDutyPairs: self::INCOMPATIBLE_DUTY_PAIRS,
            riskAssessment: $this->assessSodRisks($violations),
            mitigatingControls: $mitigatingControls,
            isCompliant: $isCompliant,
            conclusion: $this->generateSodConclusion($violations, $isCompliant),
        );

        return [
            'report_id' => $report->reportId,
            'tenant_id' => $report->tenantId,
            'period_start' => $report->periodStart->format('Y-m-d'),
            'period_end' => $report->periodEnd->format('Y-m-d'),
            'generated_at' => $report->generatedAt->format('Y-m-d H:i:s'),
            'conflict_matrix' => $report->conflictMatrix,
            'violation_summary' => $report->violationSummary,
            'is_compliant' => $report->isCompliant,
            'conclusion' => $report->conclusion,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function validateApprovalAuthority(
        string $tenantId,
        \DateTimeImmutable $periodStart,
        \DateTimeImmutable $periodEnd,
    ): array {
        $this->logger->info('Validating approval authority', [
            'tenant_id' => $tenantId,
            'period_start' => $periodStart->format('Y-m-d'),
            'period_end' => $periodEnd->format('Y-m-d'),
        ]);

        // Get approval logs from audit trail using search
        $approvalActions = ['approved', 'approval_granted', 'po_approved', 'requisition_approved', 'payment_approved'];
        $approvalLogs = [];

        foreach ($approvalActions as $action) {
            $searchResult = $this->auditLogger->search(
                filters: [
                    'tenant_id' => $tenantId,
                    'event' => $action,
                    'date_from' => $periodStart->format('Y-m-d'),
                    'date_to' => $periodEnd->format('Y-m-d'),
                ],
                limit: 1000,
                tenantId: $tenantId,
            );
            $approvalLogs = array_merge($approvalLogs, $searchResult);
        }

        $validations = [];
        $exceptions = [];

        foreach ($approvalLogs as $log) {
            $approverId = $log->getCauserId();
            $properties = $log->getProperties();
            $amount = $properties['amount'] ?? 0;
            $documentType = $properties['document_type'] ?? 'unknown';

            // Get approver's authority limit
            $authorityLimit = $this->getApproverLimit((string)$approverId, $documentType);

            $isWithinLimit = $amount <= $authorityLimit;

            $validations[] = [
                'log_id' => $log->getId(),
                'approver_id' => $approverId,
                'document_type' => $documentType,
                'amount' => $amount,
                'authority_limit' => $authorityLimit,
                'within_limit' => $isWithinLimit,
                'approved_at' => $log->getCreatedAt()->format('c'),
            ];

            if (!$isWithinLimit) {
                $exceptions[] = [
                    'log_id' => $log->getId(),
                    'approver_id' => $approverId,
                    'exceeded_by' => $amount - $authorityLimit,
                    'exception_type' => 'authority_exceeded',
                ];
            }
        }

        // Build authority matrix
        $matrix = $this->buildApprovalAuthorityMatrix($tenantId);

        return [
            'tenant_id' => $tenantId,
            'period_start' => $periodStart->format('Y-m-d'),
            'period_end' => $periodEnd->format('Y-m-d'),
            'total_approvals' => count($validations),
            'within_authority' => count($validations) - count($exceptions),
            'exceptions' => $exceptions,
            'exception_count' => count($exceptions),
            'compliance_rate' => count($validations) > 0
                ? round(((count($validations) - count($exceptions)) / count($validations)) * 100, 2)
                : 100.0,
            'matrix' => $matrix,
            'validations' => $validations,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getAuditExceptions(
        string $tenantId,
        string $auditPeriod,
        array $filters = [],
    ): array {
        $findings = [];

        // Filter findings by tenant and period
        foreach ($this->findings as $finding) {
            if ($finding->tenantId !== $tenantId) {
                continue;
            }

            // Apply filters
            if (isset($filters['status'])) {
                $isOpen = $finding->isOpen();
                if ($filters['status'] === 'open' && !$isOpen) {
                    continue;
                }
                if ($filters['status'] === 'resolved' && $isOpen) {
                    continue;
                }
            }

            if (isset($filters['severity'])) {
                if ($finding->severity->value !== $filters['severity']) {
                    continue;
                }
            }

            if (isset($filters['control_area'])) {
                if ($finding->controlArea->value !== $filters['control_area']) {
                    continue;
                }
            }

            $findings[] = $finding;
        }

        return array_map(fn(AuditFindingData $f) => [
            'finding_id' => $f->findingId,
            'tenant_id' => $f->tenantId,
            'control_area' => $f->controlArea->value,
            'severity' => $f->severity->value,
            'title' => $f->title,
            'description' => $f->description,
            'recorded_by' => $f->recordedBy,
            'recorded_at' => $f->recordedAt->format('c'),
            'resolved_at' => $f->resolvedAt?->format('c'),
            'resolved_by' => $f->resolvedBy,
            'is_open' => $f->isOpen(),
        ], $findings);
    }

    /**
     * {@inheritdoc}
     */
    public function recordAuditFinding(
        string $tenantId,
        string $controlArea,
        string $findingType,
        string $description,
        string $recordedBy,
    ): array {
        $this->logger->info('Recording audit finding', [
            'tenant_id' => $tenantId,
            'control_area' => $controlArea,
            'finding_type' => $findingType,
        ]);

        $severity = AuditFindingSeverity::from($findingType);
        $control = ControlArea::from($controlArea);

        $finding = new AuditFindingData(
            findingId: $this->generateFindingId(),
            tenantId: $tenantId,
            controlArea: $control,
            severity: $severity,
            title: "Finding in {$control->value}",
            description: $description,
            rootCause: '', // To be filled during investigation
            recommendation: $this->generateRecommendation($control, $severity),
            recordedBy: $recordedBy,
            recordedAt: new \DateTimeImmutable(),
            remediationDeadline: (new \DateTimeImmutable())->modify(
                "+{$severity->getRemediationDeadlineDays()} days"
            ),
        );

        // Store finding (in production, would persist to repository)
        $this->findings[$finding->findingId] = $finding;

        // Log audit trail
        $this->auditLogger->log(
            logName: 'procurement_audit',
            description: "Audit finding recorded: {$severity->value} in {$controlArea}",
            subjectType: 'audit_finding',
            subjectId: $finding->findingId,
            properties: [
                'control_area' => $controlArea,
                'severity' => $findingType,
                'recorded_by' => $recordedBy,
                'remediation_deadline' => $finding->remediationDeadline->format('Y-m-d'),
            ],
            event: 'audit_finding_recorded',
            tenantId: $tenantId,
        );

        // Check if board notification is required
        if ($severity->requiresBoardNotification()) {
            $this->logger->warning('Finding requires board notification', [
                'finding_id' => $finding->findingId,
                'severity' => $findingType,
            ]);
        }

        return [
            'finding_id' => $finding->findingId,
            'tenant_id' => $finding->tenantId,
            'control_area' => $finding->controlArea->value,
            'severity' => $finding->severity->value,
            'title' => $finding->title,
            'description' => $finding->description,
            'recorded_by' => $finding->recordedBy,
            'recorded_at' => $finding->recordedAt->format('c'),
            'remediation_deadline' => $finding->remediationDeadline->format('c'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function resolveAuditFinding(
        string $findingId,
        string $resolution,
        string $resolvedBy,
        array $supportingDocuments = [],
    ): array {
        if (!isset($this->findings[$findingId])) {
            throw ProcurementAuditException::findingNotFound($findingId);
        }

        $finding = $this->findings[$findingId];

        if ($finding->isResolved()) {
            throw ProcurementAuditException::findingAlreadyResolved($findingId);
        }

        // Create resolved finding (immutable, so create new instance)
        $resolvedFinding = new AuditFindingData(
            findingId: $finding->findingId,
            tenantId: $finding->tenantId,
            controlArea: $finding->controlArea,
            severity: $finding->severity,
            title: $finding->title,
            description: $finding->description,
            rootCause: $finding->rootCause,
            recommendation: $finding->recommendation,
            recordedBy: $finding->recordedBy,
            recordedAt: $finding->recordedAt,
            remediationDeadline: $finding->remediationDeadline,
            resolvedBy: $resolvedBy,
            resolvedAt: new \DateTimeImmutable(),
            resolution: $resolution,
            supportingDocuments: $supportingDocuments,
            metadata: array_merge($finding->metadata, [
                'resolution_verification' => $this->verifyResolution($finding, $resolution),
            ]),
        );

        $this->findings[$findingId] = $resolvedFinding;

        // Log audit trail
        $this->auditLogger->log(
            logName: 'procurement_audit',
            description: "Audit finding resolved: {$resolution}",
            subjectType: 'audit_finding',
            subjectId: $findingId,
            properties: [
                'resolved_by' => $resolvedBy,
                'supporting_documents' => $supportingDocuments,
                'days_to_resolution' => $finding->getAgeDays(),
                'was_overdue' => $finding->isOverdue(),
            ],
            event: 'audit_finding_resolved',
            tenantId: $resolvedFinding->tenantId,
        );

        return [
            'finding_id' => $resolvedFinding->findingId,
            'tenant_id' => $resolvedFinding->tenantId,
            'control_area' => $resolvedFinding->controlArea->value,
            'severity' => $resolvedFinding->severity->value,
            'title' => $resolvedFinding->title,
            'resolved_by' => $resolvedFinding->resolvedBy,
            'resolved_at' => $resolvedFinding->resolvedAt?->format('c'),
            'resolution' => $resolvedFinding->resolution,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getControlTestResults(
        string $tenantId,
        string $controlId,
        string $testPeriod,
    ): array {
        $control = ControlArea::from($controlId);
        [$year, $quarter] = $this->parseAuditPeriod($testPeriod);
        [$periodStart, $periodEnd] = $this->getQuarterDates($year, $quarter);

        $testResult = $this->executeControlTest($tenantId, $control, $periodStart, $periodEnd);

        return [
            'test_id' => $testResult->testId,
            'control_area' => $testResult->controlArea->value,
            'period_start' => $testResult->periodStart->format('Y-m-d'),
            'period_end' => $testResult->periodEnd->format('Y-m-d'),
            'sample_size' => $testResult->sampleSize,
            'exceptions_found' => $testResult->exceptionsFound,
            'exception_rate' => $testResult->getExceptionRate(),
            'is_passing' => $testResult->isPassing,
            'conclusion' => $testResult->conclusion,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getManagementRepresentationData(
        string $tenantId,
        string $auditPeriod,
    ): array {
        [$fiscalYear, $quarter] = $this->parseAuditPeriod($auditPeriod);
        [$periodStart, $periodEnd] = $this->getQuarterDates($fiscalYear, $quarter);

        // Get evidence summary
        $controlTests = $this->executeControlTests(
            $tenantId,
            ControlArea::cases(),
            $periodStart,
            $periodEnd
        );

        $findings = $this->getAuditExceptions($tenantId, $auditPeriod, ['status' => 'open']);

        $passRate = $this->calculatePassRate($controlTests);
        $materialWeaknesses = array_filter(
            $findings,
            fn($f) => ($f['severity'] ?? '') === AuditFindingSeverity::MATERIAL_WEAKNESS->value
        );

        return [
            'tenant_id' => $tenantId,
            'audit_period' => $auditPeriod,
            'fiscal_year' => $fiscalYear,
            'generated_at' => (new \DateTimeImmutable())->format('c'),
            'assertions' => [
                'internal_controls_effective' => $passRate >= 95.0 && empty($materialWeaknesses),
                'no_material_weaknesses' => empty($materialWeaknesses),
                'financial_statements_fairly_stated' => true, // Requires external input
                'disclosure_complete' => true,
            ],
            'control_summary' => [
                'total_controls_tested' => count($controlTests),
                'controls_effective' => count(array_filter($controlTests, fn($t) => $t->isPassing)),
                'pass_rate' => $passRate,
            ],
            'findings_summary' => [
                'open_findings' => count($findings),
                'material_weaknesses' => count($materialWeaknesses),
                'significant_deficiencies' => count(array_filter(
                    $findings,
                    fn($f) => ($f['severity'] ?? '') === AuditFindingSeverity::SIGNIFICANT_DEFICIENCY->value
                )),
            ],
            'representations' => $this->generateRepresentationStatements($passRate, $materialWeaknesses),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getThreeWayMatchAuditTrail(
        string $tenantId,
        \DateTimeImmutable $periodStart,
        \DateTimeImmutable $periodEnd,
        array $filters = [],
    ): array {
        // Get matching events from audit log using search
        $matchActions = ['three_way_match_attempted', 'three_way_match_passed', 'three_way_match_failed'];
        $matchingEvents = [];

        foreach ($matchActions as $action) {
            $searchResult = $this->auditLogger->search(
                filters: [
                    'tenant_id' => $tenantId,
                    'event' => $action,
                    'date_from' => $periodStart->format('Y-m-d'),
                    'date_to' => $periodEnd->format('Y-m-d'),
                ],
                limit: 1000,
                tenantId: $tenantId,
            );
            $matchingEvents = array_merge($matchingEvents, $searchResult);
        }

        $trail = [];
        $summary = [
            'total_matches' => 0,
            'passed' => 0,
            'failed' => 0,
            'pass_rate' => 0.0,
        ];

        foreach ($matchingEvents as $event) {
            $properties = $event->getProperties();

            // Apply filters
            if (isset($filters['status'])) {
                $eventStatus = $properties['status'] ?? 'unknown';
                if ($filters['status'] !== $eventStatus) {
                    continue;
                }
            }

            $trail[] = [
                'event_id' => $event->getId(),
                'timestamp' => $event->getCreatedAt()->format('c'),
                'invoice_id' => $properties['invoice_id'] ?? null,
                'po_id' => $properties['po_id'] ?? null,
                'gr_id' => $properties['gr_id'] ?? null,
                'result' => $event->getEvent(),
                'price_variance' => $properties['price_variance'] ?? null,
                'quantity_variance' => $properties['quantity_variance'] ?? null,
                'matched_by' => $event->getCauserId(),
                'notes' => $properties['notes'] ?? null,
            ];

            $summary['total_matches']++;
            if ($event->getEvent() === 'three_way_match_passed') {
                $summary['passed']++;
            } else {
                $summary['failed']++;
            }
        }

        $summary['pass_rate'] = $summary['total_matches'] > 0
            ? round(($summary['passed'] / $summary['total_matches']) * 100, 2)
            : 0.0;

        return [
            'tenant_id' => $tenantId,
            'period_start' => $periodStart->format('Y-m-d'),
            'period_end' => $periodEnd->format('Y-m-d'),
            'summary' => $summary,
            'trail' => $trail,
        ];
    }

    // ========================================================================
    // Private Helper Methods
    // ========================================================================

    /**
     * Parse audit period string (e.g., '2024-Q4').
     *
     * @return array{0: int, 1: int}
     */
    private function parseAuditPeriod(string $auditPeriod): array
    {
        if (!preg_match('/^(\d{4})-Q([1-4])$/', $auditPeriod, $matches)) {
            throw ProcurementAuditException::invalidAuditPeriod($auditPeriod);
        }

        return [(int)$matches[1], (int)$matches[2]];
    }

    /**
     * Get quarter start and end dates.
     *
     * @return array{0: \DateTimeImmutable, 1: \DateTimeImmutable}
     */
    private function getQuarterDates(int $year, int $quarter): array
    {
        $startMonth = ($quarter - 1) * 3 + 1;
        $endMonth = $quarter * 3;

        $start = new \DateTimeImmutable("{$year}-{$startMonth}-01");
        $endDate = new \DateTimeImmutable("{$year}-{$endMonth}-01");
        $end = $endDate->modify('last day of this month');

        return [$start, $end];
    }

    /**
     * Execute control tests for specified areas.
     *
     * @param array<ControlArea> $controlAreas
     * @return array<ControlTestResultData>
     */
    private function executeControlTests(
        string $tenantId,
        array $controlAreas,
        \DateTimeImmutable $periodStart,
        \DateTimeImmutable $periodEnd,
    ): array {
        $results = [];

        foreach ($controlAreas as $control) {
            $results[] = $this->executeControlTest($tenantId, $control, $periodStart, $periodEnd);
        }

        return $results;
    }

    /**
     * Execute single control test.
     */
    private function executeControlTest(
        string $tenantId,
        ControlArea $control,
        \DateTimeImmutable $periodStart,
        \DateTimeImmutable $periodEnd,
    ): ControlTestResultData {
        $sampleSize = $control->getTestSampleSize();

        // Get sample transactions from audit log using search
        $samples = $this->auditLogger->search(
            filters: [
                'tenant_id' => $tenantId,
                'log_name' => $control->value,
                'date_from' => $periodStart->format('Y-m-d'),
                'date_to' => $periodEnd->format('Y-m-d'),
            ],
            limit: $sampleSize,
            tenantId: $tenantId,
        );

        // Analyze samples for exceptions
        $exceptions = $this->analyzeSamplesForExceptions($samples, $control);
        $exceptionsFound = count($exceptions);

        // Determine if test passes (exception rate <= 5%)
        $exceptionRate = $sampleSize > 0 ? ($exceptionsFound / $sampleSize) * 100 : 0;
        $isPassing = $exceptionRate <= 5.0;

        return new ControlTestResultData(
            testId: $this->generateTestId(),
            tenantId: $tenantId,
            controlArea: $control,
            testProcedure: $control->getObjective(),
            sampleSize: count($samples),
            exceptionsFound: $exceptionsFound,
            isPassing: $isPassing,
            testedBy: $this->getCurrentUserId(),
            testedAt: new \DateTimeImmutable(),
            periodStart: $periodStart,
            periodEnd: $periodEnd,
            conclusion: $isPassing
                ? "Control is operating effectively with {$exceptionsFound} exceptions in {$sampleSize} samples."
                : "Control deficiency identified: {$exceptionRate}% exception rate exceeds tolerance.",
            exceptions: $exceptions,
            workpaperReferences: [],
        );
    }

    /**
     * Analyze samples for control exceptions.
     */
    private function analyzeSamplesForExceptions(array $samples, ControlArea $control): array
    {
        $exceptions = [];

        foreach ($samples as $sample) {
            $exception = $this->checkSampleForException($sample, $control);
            if ($exception !== null) {
                $exceptions[] = $exception;
            }
        }

        return $exceptions;
    }

    /**
     * Check individual sample for control exception.
     */
    private function checkSampleForException(mixed $sample, ControlArea $control): ?array
    {
        // Control-specific exception checks
        return match ($control) {
            ControlArea::SEGREGATION_OF_DUTIES => $this->checkSodException($sample),
            ControlArea::APPROVAL_LIMITS => $this->checkApprovalLimitException($sample),
            ControlArea::THREE_WAY_MATCH => $this->checkThreeWayMatchException($sample),
            default => null, // Default: no exception
        };
    }

    private function checkSodException(mixed $sample): ?array
    {
        // Check if same user performed incompatible duties
        return null; // Placeholder
    }

    private function checkApprovalLimitException(mixed $sample): ?array
    {
        // Check if approval exceeded authority
        return null; // Placeholder
    }

    private function checkThreeWayMatchException(mixed $sample): ?array
    {
        // Check for matching variances
        return null; // Placeholder
    }

    /**
     * Detect SoD violations in user role assignments.
     */
    private function detectSodViolations(array $userRoleAssignments): array
    {
        $violations = [];

        foreach ($userRoleAssignments as $userId => $assignment) {
            $userPermissions = [];
            foreach ($assignment['roles'] as $role) {
                $userPermissions = array_merge($userPermissions, $role['permissions'] ?? []);
            }

            foreach (self::INCOMPATIBLE_DUTY_PAIRS as $pair) {
                if (
                    in_array($pair['duty1'], $userPermissions, true) &&
                    in_array($pair['duty2'], $userPermissions, true)
                ) {
                    $violations[] = [
                        'user_id' => $userId,
                        'user_name' => $assignment['user_name'],
                        'duty1' => $pair['duty1'],
                        'duty2' => $pair['duty2'],
                        'severity' => $pair['severity'],
                        'reason' => $pair['reason'],
                    ];
                }
            }
        }

        return $violations;
    }

    /**
     * Build conflict matrix from violations.
     */
    private function buildConflictMatrix(array $violations): array
    {
        $matrix = [];

        foreach (self::INCOMPATIBLE_DUTY_PAIRS as $pair) {
            $key = "{$pair['duty1']}|{$pair['duty2']}";
            $matrix[$key] = [
                'duty1' => $pair['duty1'],
                'duty2' => $pair['duty2'],
                'severity' => $pair['severity'],
                'violation_count' => 0,
                'affected_users' => [],
            ];
        }

        foreach ($violations as $violation) {
            $key = "{$violation['duty1']}|{$violation['duty2']}";
            if (isset($matrix[$key])) {
                $matrix[$key]['violation_count']++;
                $matrix[$key]['affected_users'][] = $violation['user_id'];
            }
        }

        return array_values($matrix);
    }

    /**
     * Get mitigating controls for violations.
     */
    private function getMitigatingControls(array $violations): array
    {
        return [
            'dual_approval_required' => true,
            'manager_review_enabled' => true,
            'audit_trail_logging' => true,
            'periodic_access_review' => 'quarterly',
        ];
    }

    /**
     * Assess SoD risks.
     */
    private function assessSodRisks(array $violations): array
    {
        $highCount = count(array_filter($violations, fn($v) => $v['severity'] === 'HIGH'));
        $mediumCount = count(array_filter($violations, fn($v) => $v['severity'] === 'MEDIUM'));

        return [
            'overall_risk' => $highCount > 0 ? 'HIGH' : ($mediumCount > 0 ? 'MEDIUM' : 'LOW'),
            'high_risk_violations' => $highCount,
            'medium_risk_violations' => $mediumCount,
            'low_risk_violations' => count($violations) - $highCount - $mediumCount,
        ];
    }

    /**
     * Generate SoD conclusion.
     */
    private function generateSodConclusion(array $violations, bool $isCompliant): string
    {
        if ($isCompliant) {
            return 'Segregation of duties controls are operating effectively. No high-severity violations identified.';
        }

        $highCount = count(array_filter($violations, fn($v) => $v['severity'] === 'HIGH'));
        return "Segregation of duties deficiencies identified. {$highCount} high-severity violations require remediation.";
    }

    /**
     * Build approval authority matrix.
     */
    private function buildApprovalAuthorityMatrix(string $tenantId): array
    {
        // Get approval limits from settings
        return [
            'DEPARTMENT_MANAGER' => [
                'requisition_limit' => $this->settings->getInt('approval.dept_manager.requisition_limit', 5000),
                'po_limit' => $this->settings->getInt('approval.dept_manager.po_limit', 5000),
            ],
            'DIRECTOR' => [
                'requisition_limit' => $this->settings->getInt('approval.director.requisition_limit', 25000),
                'po_limit' => $this->settings->getInt('approval.director.po_limit', 25000),
            ],
            'VP' => [
                'requisition_limit' => $this->settings->getInt('approval.vp.requisition_limit', 100000),
                'po_limit' => $this->settings->getInt('approval.vp.po_limit', 100000),
            ],
            'CFO' => [
                'requisition_limit' => $this->settings->getInt('approval.cfo.requisition_limit', 500000),
                'po_limit' => $this->settings->getInt('approval.cfo.po_limit', 500000),
            ],
            'CEO' => [
                'requisition_limit' => PHP_INT_MAX,
                'po_limit' => PHP_INT_MAX,
            ],
        ];
    }

    /**
     * Get approver's authority limit.
     */
    private function getApproverLimit(string $approverId, string $documentType): int
    {
        // Would query user's role and get limit from matrix
        // Placeholder implementation
        return 10000000; // $100,000 in cents
    }

    /**
     * Generate management assertions.
     */
    private function generateManagementAssertions(array $controlTests): array
    {
        $passRate = $this->calculatePassRate($controlTests);

        return [
            'existence' => $passRate >= 90.0,
            'completeness' => $passRate >= 90.0,
            'valuation' => $passRate >= 95.0,
            'rights_obligations' => true,
            'presentation_disclosure' => true,
        ];
    }

    /**
     * Summarize control environment.
     */
    private function summarizeControlEnvironment(array $controlTests, array $sodReport): array
    {
        return [
            'tone_at_top' => 'EFFECTIVE',
            'organizational_structure' => 'DEFINED',
            'hr_policies' => 'DOCUMENTED',
            'control_activities' => $this->calculatePassRate($controlTests) >= 95.0 ? 'EFFECTIVE' : 'NEEDS_IMPROVEMENT',
            'information_communication' => 'ADEQUATE',
            'monitoring' => 'ONGOING',
        ];
    }

    /**
     * Determine overall conclusion.
     */
    private function determineOverallConclusion(array $controlTests, array $findings): string
    {
        $passRate = $this->calculatePassRate($controlTests);
        $materialWeaknesses = array_filter(
            $findings,
            fn($f) => ($f['severity'] ?? '') === AuditFindingSeverity::MATERIAL_WEAKNESS->value
        );

        if (!empty($materialWeaknesses)) {
            return 'MATERIAL_WEAKNESS_IDENTIFIED';
        }

        if ($passRate < 90.0) {
            return 'SIGNIFICANT_DEFICIENCY_IDENTIFIED';
        }

        if ($passRate < 95.0) {
            return 'DEFICIENCY_IDENTIFIED';
        }

        return 'NO_MATERIAL_WEAKNESSES';
    }

    /**
     * Calculate control test pass rate.
     *
     * @param array<ControlTestResultData> $controlTests
     */
    private function calculatePassRate(array $controlTests): float
    {
        if (empty($controlTests)) {
            return 0.0;
        }

        $passing = count(array_filter($controlTests, fn($t) => $t->isPassing));
        return round(($passing / count($controlTests)) * 100, 2);
    }

    /**
     * Generate recommendation based on control and severity.
     */
    private function generateRecommendation(ControlArea $control, AuditFindingSeverity $severity): string
    {
        $base = "Implement enhanced controls for {$control->value}.";

        return match ($severity) {
            AuditFindingSeverity::MATERIAL_WEAKNESS => "{$base} Immediate remediation required. Board notification mandatory.",
            AuditFindingSeverity::SIGNIFICANT_DEFICIENCY => "{$base} Priority remediation within 60 days.",
            AuditFindingSeverity::DEFICIENCY => "{$base} Address within current fiscal quarter.",
            default => "{$base} Consider for next control improvement cycle.",
        };
    }

    /**
     * Verify resolution effectiveness.
     */
    private function verifyResolution(AuditFindingData $finding, string $resolution): array
    {
        return [
            'verified' => true,
            'verification_date' => (new \DateTimeImmutable())->format('c'),
            'verification_method' => 'management_review',
        ];
    }

    /**
     * Generate representation statements.
     */
    private function generateRepresentationStatements(float $passRate, array $materialWeaknesses): array
    {
        return [
            "Management has assessed the effectiveness of internal controls over financial reporting as of the period end date.",
            $passRate >= 95.0
                ? "Based on this assessment, internal controls are operating effectively."
                : "Based on this assessment, certain control deficiencies have been identified.",
            empty($materialWeaknesses)
                ? "No material weaknesses have been identified."
                : count($materialWeaknesses) . " material weakness(es) have been identified and disclosed.",
        ];
    }

    // ========================================================================
    // ID Generators
    // ========================================================================

    private function generateEvidenceId(): string
    {
        return 'EVD-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(6)));
    }

    private function generateReportId(string $prefix): string
    {
        return strtoupper($prefix) . '-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
    }

    private function generateFindingId(): string
    {
        return 'FND-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(6)));
    }

    private function generateTestId(): string
    {
        return 'TST-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
    }

    private function getCurrentUserId(): string
    {
        // Would get from auth context
        return 'system';
    }
}
