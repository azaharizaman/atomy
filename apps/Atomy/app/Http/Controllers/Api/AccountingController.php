<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Nexus\Accounting\Services\AccountingManager;
use Nexus\Accounting\Core\ValueObjects\ReportingPeriod;
use Nexus\Accounting\Core\ValueObjects\ComplianceStandard;
use Nexus\Accounting\Core\ValueObjects\ExportFormat;
use Nexus\Accounting\Core\Enums\StatementType;
use Nexus\Accounting\Core\Enums\CashFlowMethod;
use Nexus\Accounting\Core\Enums\ConsolidationMethod;
use Nexus\Accounting\Core\Enums\PeriodCloseStatus;
use Nexus\Accounting\Exceptions\StatementNotFoundException;
use Nexus\Accounting\Exceptions\PeriodCloseException;
use Nexus\Accounting\Exceptions\ConsolidationException;
use Psr\Log\LoggerInterface;

/**
 * Accounting API Controller.
 *
 * Handles financial statement generation, period close operations,
 * multi-entity consolidation, and budget variance analysis.
 */
final class AccountingController extends Controller
{
    public function __construct(
        private readonly AccountingManager $accountingManager,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Generate Balance Sheet.
     *
     * POST /api/accounting/statements/balance-sheet
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function generateBalanceSheet(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'entity_id' => 'required|string',
            'period_id' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'compliance_standard' => 'nullable|string|in:gaap,ifrs,mfrs_malaysia',
            'comparative' => 'nullable|boolean',
            'prior_period_id' => 'nullable|string',
        ]);

        try {
            $period = new ReportingPeriod(
                id: $validated['period_id'],
                startDate: new \DateTimeImmutable($validated['start_date']),
                endDate: new \DateTimeImmutable($validated['end_date']),
                priorPeriodId: $validated['prior_period_id'] ?? null
            );

            $standard = isset($validated['compliance_standard'])
                ? ComplianceStandard::from($validated['compliance_standard'])
                : null;

            $balanceSheet = $this->accountingManager->generateBalanceSheet(
                $validated['entity_id'],
                $period,
                $standard
            );

            return response()->json([
                'success' => true,
                'data' => $balanceSheet->toArray(),
            ], 201);
        } catch (\Exception $e) {
            $this->logger->error('Failed to generate balance sheet', [
                'entity_id' => $validated['entity_id'],
                'period_id' => $validated['period_id'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate balance sheet',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate Income Statement.
     *
     * POST /api/accounting/statements/income-statement
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function generateIncomeStatement(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'entity_id' => 'required|string',
            'period_id' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'compliance_standard' => 'nullable|string|in:gaap,ifrs,mfrs_malaysia',
            'comparative' => 'nullable|boolean',
            'prior_period_id' => 'nullable|string',
        ]);

        try {
            $period = new ReportingPeriod(
                id: $validated['period_id'],
                startDate: new \DateTimeImmutable($validated['start_date']),
                endDate: new \DateTimeImmutable($validated['end_date']),
                priorPeriodId: $validated['prior_period_id'] ?? null
            );

            $standard = isset($validated['compliance_standard'])
                ? ComplianceStandard::from($validated['compliance_standard'])
                : null;

            $incomeStatement = $this->accountingManager->generateIncomeStatement(
                $validated['entity_id'],
                $period,
                $standard
            );

            return response()->json([
                'success' => true,
                'data' => $incomeStatement->toArray(),
            ], 201);
        } catch (\Exception $e) {
            $this->logger->error('Failed to generate income statement', [
                'entity_id' => $validated['entity_id'],
                'period_id' => $validated['period_id'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate income statement',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate Cash Flow Statement.
     *
     * POST /api/accounting/statements/cash-flow
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function generateCashFlowStatement(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'entity_id' => 'required|string',
            'period_id' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'method' => 'nullable|string|in:direct,indirect',
            'comparative' => 'nullable|boolean',
            'prior_period_id' => 'nullable|string',
        ]);

        try {
            $period = new ReportingPeriod(
                id: $validated['period_id'],
                startDate: new \DateTimeImmutable($validated['start_date']),
                endDate: new \DateTimeImmutable($validated['end_date']),
                priorPeriodId: $validated['prior_period_id'] ?? null
            );

            $method = isset($validated['method'])
                ? CashFlowMethod::from(ucfirst($validated['method']))
                : CashFlowMethod::Indirect;

            $cashFlowStatement = $this->accountingManager->generateCashFlowStatement(
                $validated['entity_id'],
                $period,
                $method
            );

            return response()->json([
                'success' => true,
                'data' => $cashFlowStatement->toArray(),
            ], 201);
        } catch (\Exception $e) {
            $this->logger->error('Failed to generate cash flow statement', [
                'entity_id' => $validated['entity_id'],
                'period_id' => $validated['period_id'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate cash flow statement',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a stored financial statement.
     *
     * GET /api/accounting/statements/{id}
     *
     * @param string $id
     * @return JsonResponse
     */
    public function getStatement(string $id): JsonResponse
    {
        try {
            $statement = $this->accountingManager->getStatement($id);

            return response()->json([
                'success' => true,
                'data' => $statement->toArray(),
            ]);
        } catch (StatementNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Statement not found',
                'error' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve statement', [
                'statement_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statement',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export a financial statement to a file.
     *
     * POST /api/accounting/statements/{id}/export
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function exportStatement(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'format' => 'required|string|in:pdf,excel,json,csv',
        ]);

        try {
            $format = ExportFormat::from(ucfirst($validated['format']));

            $filePath = $this->accountingManager->exportStatement($id, $format);

            return response()->json([
                'success' => true,
                'data' => [
                    'file_path' => $filePath,
                    'format' => $validated['format'],
                ],
            ]);
        } catch (StatementNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Statement not found',
                'error' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            $this->logger->error('Failed to export statement', [
                'statement_id' => $id,
                'format' => $validated['format'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to export statement',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lock a financial statement.
     *
     * POST /api/accounting/statements/{id}/lock
     *
     * @param string $id
     * @return JsonResponse
     */
    public function lockStatement(string $id): JsonResponse
    {
        try {
            $this->accountingManager->lockStatement($id);

            return response()->json([
                'success' => true,
                'message' => 'Statement locked successfully',
            ]);
        } catch (StatementNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Statement not found',
                'error' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            $this->logger->error('Failed to lock statement', [
                'statement_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to lock statement',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Unlock a financial statement.
     *
     * POST /api/accounting/statements/{id}/unlock
     *
     * @param string $id
     * @return JsonResponse
     */
    public function unlockStatement(string $id): JsonResponse
    {
        try {
            $this->accountingManager->unlockStatement($id);

            return response()->json([
                'success' => true,
                'message' => 'Statement unlocked successfully',
            ]);
        } catch (StatementNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Statement not found',
                'error' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            $this->logger->error('Failed to unlock statement', [
                'statement_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to unlock statement',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Close a fiscal period (month-end).
     *
     * POST /api/accounting/period-close/month
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function closeMonth(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period_id' => 'required|string',
            'closed_by' => 'required|string',
        ]);

        try {
            $this->accountingManager->closeMonth(
                $validated['period_id'],
                $validated['closed_by']
            );

            return response()->json([
                'success' => true,
                'message' => 'Period closed successfully',
            ]);
        } catch (PeriodCloseException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Period close validation failed',
                'error' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            $this->logger->error('Failed to close period', [
                'period_id' => $validated['period_id'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to close period',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Close a fiscal year (year-end).
     *
     * POST /api/accounting/period-close/year
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function closeYear(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fiscal_year_id' => 'required|string',
            'closed_by' => 'required|string',
        ]);

        try {
            $this->accountingManager->closeYear(
                $validated['fiscal_year_id'],
                $validated['closed_by']
            );

            return response()->json([
                'success' => true,
                'message' => 'Fiscal year closed successfully',
            ]);
        } catch (PeriodCloseException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Year-end close validation failed',
                'error' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            $this->logger->error('Failed to close fiscal year', [
                'fiscal_year_id' => $validated['fiscal_year_id'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to close fiscal year',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reopen a closed period.
     *
     * POST /api/accounting/period-close/{periodId}/reopen
     *
     * @param Request $request
     * @param string $periodId
     * @return JsonResponse
     */
    public function reopenPeriod(Request $request, string $periodId): JsonResponse
    {
        $validated = $request->validate([
            'reopened_by' => 'required|string',
            'reason' => 'required|string|min:10',
        ]);

        try {
            $this->accountingManager->reopenPeriod(
                $periodId,
                $validated['reopened_by'],
                $validated['reason']
            );

            return response()->json([
                'success' => true,
                'message' => 'Period reopened successfully',
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to reopen period', [
                'period_id' => $periodId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reopen period',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get period close status.
     *
     * GET /api/accounting/period-close/{periodId}/status
     *
     * @param string $periodId
     * @return JsonResponse
     */
    public function getPeriodCloseStatus(string $periodId): JsonResponse
    {
        try {
            $status = $this->accountingManager->getPeriodCloseStatus($periodId);

            return response()->json([
                'success' => true,
                'data' => [
                    'period_id' => $periodId,
                    'status' => $status->value,
                ],
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get period close status', [
                'period_id' => $periodId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get period close status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Consolidate financial statements for multiple entities.
     *
     * POST /api/accounting/consolidation/consolidate
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function consolidateStatements(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'parent_entity_id' => 'required|string',
            'child_entity_ids' => 'required|array|min:1',
            'child_entity_ids.*' => 'required|string',
            'period_id' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'method' => 'required|string|in:full,proportional,equity',
            'consolidation_rules' => 'nullable|array',
        ]);

        try {
            $period = new ReportingPeriod(
                id: $validated['period_id'],
                startDate: new \DateTimeImmutable($validated['start_date']),
                endDate: new \DateTimeImmutable($validated['end_date'])
            );

            $method = ConsolidationMethod::from(ucfirst($validated['method']));

            $consolidatedStatement = $this->accountingManager->consolidateStatements(
                $validated['parent_entity_id'],
                $validated['child_entity_ids'],
                $period,
                $method,
                $validated['consolidation_rules'] ?? []
            );

            return response()->json([
                'success' => true,
                'data' => $consolidatedStatement->toArray(),
            ], 201);
        } catch (ConsolidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Consolidation failed',
                'error' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            $this->logger->error('Failed to consolidate statements', [
                'parent_entity_id' => $validated['parent_entity_id'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to consolidate statements',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get consolidation entries for a consolidated statement.
     *
     * GET /api/accounting/consolidation/statements/{id}/entries
     *
     * @param string $id
     * @return JsonResponse
     */
    public function getConsolidationEntries(string $id): JsonResponse
    {
        try {
            $entries = $this->accountingManager->getConsolidationEntries($id);

            return response()->json([
                'success' => true,
                'data' => $entries,
            ]);
        } catch (StatementNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Consolidated statement not found',
                'error' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get consolidation entries', [
                'statement_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get consolidation entries',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Calculate budget variance analysis.
     *
     * POST /api/accounting/variance/budget
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function calculateBudgetVariance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'entity_id' => 'required|string',
            'period_id' => 'required|string',
            'account_ids' => 'nullable|array',
            'account_ids.*' => 'string',
        ]);

        try {
            $variances = $this->accountingManager->calculateBudgetVariance(
                $validated['entity_id'],
                $validated['period_id'],
                $validated['account_ids'] ?? null
            );

            return response()->json([
                'success' => true,
                'data' => $variances,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to calculate budget variance', [
                'entity_id' => $validated['entity_id'],
                'period_id' => $validated['period_id'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate budget variance',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Calculate period-over-period variance.
     *
     * POST /api/accounting/variance/period
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function calculatePeriodVariance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'entity_id' => 'required|string',
            'current_period_id' => 'required|string',
            'prior_period_id' => 'required|string',
        ]);

        try {
            $variance = $this->accountingManager->calculatePeriodVariance(
                $validated['entity_id'],
                $validated['current_period_id'],
                $validated['prior_period_id']
            );

            return response()->json([
                'success' => true,
                'data' => $variance->toArray(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to calculate period variance', [
                'entity_id' => $validated['entity_id'],
                'current_period_id' => $validated['current_period_id'],
                'prior_period_id' => $validated['prior_period_id'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate period variance',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
