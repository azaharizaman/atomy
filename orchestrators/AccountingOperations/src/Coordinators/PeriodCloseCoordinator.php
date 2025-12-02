<?php

declare(strict_types=1);

namespace Nexus\AccountingOperations\Coordinators;

use Nexus\AccountingOperations\Contracts\AccountingCoordinatorInterface;
use Nexus\AccountingOperations\DTOs\PeriodCloseRequest;
use Nexus\AccountingOperations\Services\CloseRuleRegistry;
use Nexus\AccountPeriodClose\Contracts\CloseReadinessValidatorInterface;
use Nexus\AccountPeriodClose\Contracts\ClosingEntryGeneratorInterface;
use Nexus\AccountPeriodClose\ValueObjects\CloseReadinessResult;

/**
 * Coordinator for period close operations.
 */
final readonly class PeriodCloseCoordinator implements AccountingCoordinatorInterface
{
    public function __construct(
        private CloseReadinessValidatorInterface $readinessValidator,
        private ClosingEntryGeneratorInterface $entryGenerator,
        private CloseRuleRegistry $ruleRegistry,
    ) {}

    public function getName(): string
    {
        return 'period_close';
    }

    public function hasRequiredData(string $tenantId, string $periodId): bool
    {
        return true;
    }

    /**
     * @return array<string>
     */
    public function getSupportedOperations(): array
    {
        return ['validate_readiness', 'generate_entries', 'close', 'reopen'];
    }

    public function validateReadiness(PeriodCloseRequest $request): CloseReadinessResult
    {
        // Run all registered rules
        $issues = [];

        foreach ($this->ruleRegistry->all() as $rule) {
            $result = $rule->check($request->tenantId, $request->periodId);
            if (!$result->passed) {
                $issues[] = $result;
            }
        }

        return new CloseReadinessResult(
            isReady: empty($issues),
            issues: $issues,
            checkedAt: new \DateTimeImmutable(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function closePeriod(PeriodCloseRequest $request): array
    {
        // 1. Validate readiness
        $readiness = $this->validateReadiness($request);
        if (!$readiness->isReady) {
            return [
                'success' => false,
                'reason' => 'Period not ready for close',
                'issues' => $readiness->issues,
            ];
        }

        // 2. Generate closing entries if requested
        if ($request->generateClosingEntries) {
            $entries = $this->entryGenerator->generate($request->tenantId, $request->periodId);
        }

        // 3. Lock period if requested
        if ($request->lockPeriod) {
            // Implementation would lock the period
        }

        return [
            'success' => true,
            'periodId' => $request->periodId,
            'closedAt' => new \DateTimeImmutable(),
            'closedBy' => $request->closedBy,
        ];
    }
}
