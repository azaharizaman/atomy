<?php

declare(strict_types=1);

namespace Nexus\AccountingOperations\Workflows\PeriodClose\Steps;

use Nexus\AccountPeriodClose\Services\ClosingEntryGenerator;
use Nexus\AccountPeriodClose\ValueObjects\ClosingEntrySpec;
use Nexus\AccountPeriodClose\Enums\CloseType;
use Nexus\AccountingOperations\Exceptions\WorkflowException;

/**
 * Step 4: Create closing entries for revenue and expense accounts
 */
final readonly class CreateClosingEntriesStep
{
    public function __construct(
        private ClosingEntryGenerator $closingEntryGenerator
    ) {}

    /**
     * Execute the closing entries creation step
     *
     * @param string $tenantId Tenant identifier
     * @param string $periodId Period to create closing entries for
     * @param array<string, mixed> $context Workflow context
     * @return array{result: array<ClosingEntrySpec>, context: array<string, mixed>}
     * @throws WorkflowException
     */
    public function execute(string $tenantId, string $periodId, array $context = []): array
    {
        try {
            $trialBalance = $context['trial_balance'] ?? null;
            $closeType = $context['close_type'] ?? CloseType::MONTHLY;

            if ($trialBalance === null) {
                throw new WorkflowException('Trial balance is required to create closing entries');
            }

            // Generate closing entries
            $closingEntries = $this->closingEntryGenerator->generate(
                tenantId: $tenantId,
                periodId: $periodId,
                trialBalance: $trialBalance,
                closeType: $closeType
            );

            // Update context
            $context['closing_entries'] = $closingEntries;
            $context['closing_entries_count'] = count($closingEntries);
            $context['closing_entries_created_at'] = new \DateTimeImmutable();

            return [
                'result' => $closingEntries,
                'context' => $context,
            ];
        } catch (\Throwable $e) {
            if ($e instanceof WorkflowException) {
                throw $e;
            }
            throw new WorkflowException(
                "Failed to create closing entries: {$e->getMessage()}",
                previous: $e
            );
        }
    }

    /**
     * Check if step can be skipped
     */
    public function canSkip(array $context): bool
    {
        // Cannot skip closing entries
        return false;
    }

    /**
     * Get step name
     */
    public function getName(): string
    {
        return 'create_closing_entries';
    }

    /**
     * Get step description
     */
    public function getDescription(): string
    {
        return 'Creates closing entries to transfer revenue and expense balances to retained earnings';
    }
}
