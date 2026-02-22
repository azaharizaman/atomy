<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Rules;

use Nexus\FinanceOperations\Contracts\RuleInterface;
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
     * @param object $periodManager PeriodManagerInterface for period operations
     */
    public function __construct(
        private object $periodManager,
    ) {}

    /**
     * @inheritDoc
     *
     * @param object $context Context containing tenantId, periodId, and subledgerType
     * @return RuleResult The rule check result
     */
    public function check(object $context): RuleResult
    {
        $tenantId = $this->extractTenantId($context);
        $periodId = $this->extractPeriodId($context);
        $subledgerType = $this->extractSubledgerType($context);

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

    /**
     * Extract tenant ID from context.
     *
     * @param object $context The context object
     * @return string The tenant ID
     */
    private function extractTenantId(object $context): string
    {
        if (method_exists($context, 'getTenantId')) {
            return $context->getTenantId();
        }

        if (property_exists($context, 'tenantId')) {
            return $context->tenantId ?? '';
        }

        if (property_exists($context, 'tenant_id')) {
            return $context->tenant_id ?? '';
        }

        return '';
    }

    /**
     * Extract period ID from context.
     *
     * @param object $context The context object
     * @return string The period ID
     */
    private function extractPeriodId(object $context): string
    {
        if (method_exists($context, 'getPeriodId')) {
            return $context->getPeriodId();
        }

        if (property_exists($context, 'periodId')) {
            return $context->periodId ?? '';
        }

        if (property_exists($context, 'period_id')) {
            return $context->period_id ?? '';
        }

        return '';
    }

    /**
     * Extract subledger type from context.
     *
     * @param object $context The context object
     * @return string The subledger type
     */
    private function extractSubledgerType(object $context): string
    {
        if (method_exists($context, 'getSubledgerType')) {
            return $context->getSubledgerType();
        }

        if (property_exists($context, 'subledgerType')) {
            return $context->subledgerType ?? '';
        }

        if (property_exists($context, 'subledger_type')) {
            return $context->subledger_type ?? '';
        }

        return '';
    }
}
