<?php

declare(strict_types=1);

namespace Nexus\AccountingOperations\Workflows\PeriodClose\Steps;

use Nexus\AccountPeriodClose\Contracts\CloseDataProviderInterface;
use Nexus\AccountPeriodClose\ValueObjects\ClosingEntrySpec;
use Nexus\AccountingOperations\Exceptions\WorkflowException;

/**
 * Step 6: Post closing entries to the general ledger
 */
final readonly class PostClosingEntriesStep
{
    public function __construct(
        private CloseDataProviderInterface $dataProvider
    ) {}

    /**
     * Execute the posting step
     *
     * @param string $tenantId Tenant identifier
     * @param string $periodId Period to post entries for
     * @param array<string, mixed> $context Workflow context
     * @return array{result: array{posted_count: int, journal_ids: array<string>}, context: array<string, mixed>}
     * @throws WorkflowException
     */
    public function execute(string $tenantId, string $periodId, array $context = []): array
    {
        try {
            $adjustingEntries = $context['adjusting_entries'] ?? [];
            $closingEntries = $context['closing_entries'] ?? [];
            $retainedEarningsEntry = $context['retained_earnings_entry'] ?? null;

            // Combine all entries to post
            $allEntries = array_merge(
                $adjustingEntries,
                $closingEntries,
                $retainedEarningsEntry !== null ? [$retainedEarningsEntry] : []
            );

            if (empty($allEntries)) {
                $context['posted_entries'] = [];
                $context['posted_count'] = 0;

                return [
                    'result' => [
                        'posted_count' => 0,
                        'journal_ids' => [],
                    ],
                    'context' => $context,
                ];
            }

            // Post entries via data provider (which connects to Finance package)
            $journalIds = [];
            foreach ($allEntries as $entry) {
                $journalId = $this->dataProvider->postClosingEntry($tenantId, $periodId, $entry);
                $journalIds[] = $journalId;
            }

            // Update context
            $context['posted_entries'] = $allEntries;
            $context['posted_count'] = count($allEntries);
            $context['journal_ids'] = $journalIds;
            $context['posted_at'] = new \DateTimeImmutable();

            return [
                'result' => [
                    'posted_count' => count($allEntries),
                    'journal_ids' => $journalIds,
                ],
                'context' => $context,
            ];
        } catch (\Throwable $e) {
            if ($e instanceof WorkflowException) {
                throw $e;
            }
            throw new WorkflowException(
                "Failed to post closing entries: {$e->getMessage()}",
                previous: $e
            );
        }
    }

    /**
     * Check if step can be skipped
     */
    public function canSkip(array $context): bool
    {
        // Cannot skip posting
        return false;
    }

    /**
     * Get step name
     */
    public function getName(): string
    {
        return 'post_closing_entries';
    }

    /**
     * Get step description
     */
    public function getDescription(): string
    {
        return 'Posts all adjusting and closing entries to the general ledger';
    }
}
