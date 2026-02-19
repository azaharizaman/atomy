<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\Coordinators;

use Nexus\CRMOperations\Contracts\OpportunitySplitInterface;
use Nexus\CRMOperations\Exceptions\SplitOperationException;
use Psr\Log\LoggerInterface;

final readonly class OpportunitySplitCoordinator
{
    public function __construct(
        private ?LoggerInterface $logger = null
    ) {}

    public function createSplit(string $opportunityId, array $splits): OpportunitySplitInterface
    {
        $this->validateSplits($splits);
        
        $id = uniqid('split_');
        
        $split = new class($id, $opportunityId, $splits) implements OpportunitySplitInterface {
            public function __construct(
                public string $id,
                public string $opportunityId,
                public array $splits,
                public bool $approved = false
            ) {}

            public function getId(): string { return $this->id; }
            public function getOpportunityId(): string { return $this->opportunityId; }
            public function getSplits(): array { return $this->splits; }
            public function getTotalPercentage(): int {
                return array_sum(array_column($this->splits, 'percentage'));
            }
            public function isApproved(): bool { return $this->approved; }
        };

        return $split;
    }

    public function calculateOverlay(int $opportunityValue, array $overlayRates): int
    {
        $totalOverlay = 0;
        
        foreach ($overlayRates as $rate) {
            $totalOverlay += (int) ($opportunityValue * $rate['percentage'] / 100);
        }
        
        return $totalOverlay;
    }

    public function calculateSplitAmounts(int $opportunityValue, array $splits): array
    {
        $amounts = [];
        
        foreach ($splits as $split) {
            $amounts[$split['user_id']] = (int) ($opportunityValue * $split['percentage'] / 100);
        }
        
        return $amounts;
    }

    public function submitForApproval(OpportunitySplitInterface $split): void
    {
        $this->logger?->info('Split submitted for approval', [
            'split_id' => $split->getId(),
            'opportunity_id' => $split->getOpportunityId()
        ]);
    }

    public function approveSplit(OpportunitySplitInterface $split): void
    {
        $this->logger?->info('Split approved', [
            'split_id' => $split->getId()
        ]);
    }

    public function findByOpportunity(string $opportunityId, array $splits): ?OpportunitySplitInterface
    {
        foreach ($splits as $split) {
            if ($split->getOpportunityId() === $opportunityId) {
                return $split;
            }
        }
        
        return null;
    }

    private function validateSplits(array $splits): void
    {
        $totalPercentage = array_sum(array_column($splits, 'percentage'));
        
        if ($totalPercentage !== 100) {
            throw SplitOperationException::invalidPercentage($totalPercentage);
        }
        
        foreach ($splits as $split) {
            if (!isset($split['user_id']) || !isset($split['percentage'])) {
                throw SplitOperationException::missingFields();
            }
        }
    }
}
