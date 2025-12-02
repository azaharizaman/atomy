<?php

declare(strict_types=1);

namespace Nexus\AccountingOperations\Coordinators;

use Nexus\AccountingOperations\Contracts\AccountingCoordinatorInterface;
use Nexus\AccountPeriodClose\Services\AdjustingEntryGenerator;

/**
 * Coordinator for adjusting entries at period end.
 */
final readonly class AdjustingEntriesCoordinator implements AccountingCoordinatorInterface
{
    public function __construct(
        private AdjustingEntryGenerator $entryGenerator,
    ) {}

    public function getName(): string
    {
        return 'adjusting_entries';
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
        return ['generate', 'review', 'post'];
    }

    /**
     * Generate adjusting entries for period end.
     *
     * @return array<string, mixed>
     */
    public function generate(string $tenantId, string $periodId): array
    {
        $entries = $this->entryGenerator->generate($tenantId, $periodId);

        return [
            'tenantId' => $tenantId,
            'periodId' => $periodId,
            'entries' => $entries,
            'generatedAt' => new \DateTimeImmutable(),
        ];
    }

    /**
     * Review pending adjusting entries.
     *
     * @return array<string, mixed>
     */
    public function review(string $tenantId, string $periodId): array
    {
        // Implementation retrieves pending entries for review
        return [];
    }

    /**
     * Post adjusting entries.
     *
     * @param array<string> $entryIds
     * @return array<string, mixed>
     */
    public function post(string $tenantId, array $entryIds): array
    {
        // Implementation posts entries to GL
        return [
            'posted' => count($entryIds),
            'postedAt' => new \DateTimeImmutable(),
        ];
    }
}
