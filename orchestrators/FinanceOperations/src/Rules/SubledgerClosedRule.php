<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Rules;

use Nexus\FinanceOperations\Contracts\SubledgerClosedRuleInterface;
use Nexus\FinanceOperations\Contracts\SubledgerPeriodStateInterface;
use Nexus\FinanceOperations\DTOs\RuleContexts\SubledgerClosedRuleContext;
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
final readonly class SubledgerClosedRule implements SubledgerClosedRuleInterface
{
    public function __construct(
        private SubledgerPeriodStateInterface $periodManager,
    ) {}

    /**
     * @inheritDoc
     */
    public function check(SubledgerClosedRuleContext $context): RuleResult
    {
        $tenantId = trim($context->tenantId);
        $periodId = trim($context->periodId);
        $subledgerType = $context->subledgerType->value;

        if ($tenantId === '') {
            return RuleResult::failed(
                $this->getName(),
                'Tenant ID is required for subledger closure validation',
                ['missing_field' => 'tenantId']
            );
        }

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
