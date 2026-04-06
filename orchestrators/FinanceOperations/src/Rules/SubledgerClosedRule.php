<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Rules;

use Nexus\FinanceOperations\Contracts\PeriodStatusQueryInterface;
use Nexus\FinanceOperations\Contracts\RuleInterface;
use Nexus\FinanceOperations\Contracts\RuleContextInterface;
use Nexus\FinanceOperations\DTOs\RuleResult;

/**
 * Rule to validate that a subledger is closed before posting to GL.
 *
 * This rule ensures that all transactions in the subledger have been
 * finalized before they can be posted to the General Ledger.
 *
 * Following Advanced Orchestrator Pattern:
 * - Single responsibility: Subledger closure validation
 * - Testable in isolation
 * - Reusable across coordinators
 *
 * @see ARCHITECTURE.md Section 4 for rule patterns
 * @since 1.0.0
 */
final readonly class SubledgerClosedRule implements RuleInterface
{
    /**
     * @param PeriodStatusQueryInterface $periodManager Period status query dependency
     */
    public function __construct(
        private PeriodStatusQueryInterface $periodManager,
    ) {}

    /**
     * @inheritDoc
     *
     * @param RuleContextInterface $context Context containing tenantId, periodId, and subledgerType
     * @return RuleResult The rule check result
     */
    public function check(RuleContextInterface $context): RuleResult
    {
        $tenantId = $context->getTenantId();
        $periodId = trim((string) $context->getPeriodId());
        $subledgerType = trim((string) $context->getSubledgerType());

        if (empty($periodId)) {
            return RuleResult::failed(
                $this->getName(),
                'Period ID is required for subledger closure validation',
                ['missing_field' => 'periodId']
            );
        }

        if (empty($subledgerType)) {
            return RuleResult::failed(
                $this->getName(),
                'Subledger type is required for closure validation',
                ['missing_field' => 'subledgerType']
            );
        }

        $isClosed = $this->periodManager->isSubledgerClosed(
            $tenantId,
            $periodId,
            $subledgerType
        );

        if (!$isClosed) {
            return RuleResult::failed(
                $this->getName(),
                sprintf('Subledger "%s" is not closed for period %s', $subledgerType, $periodId),
                [
                    'subledger_type' => $subledgerType,
                    'period_id' => $periodId,
                ]
            );
        }

        return RuleResult::passed($this->getName());
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'subledger_closed';
    }
}
