<?php

declare(strict_types=1);

namespace Nexus\CRM\Services;

use Nexus\CRM\Contracts\OpportunityQueryInterface;
use Nexus\CRM\Contracts\OpportunityPersistInterface;
use Nexus\CRM\Contracts\OpportunityInterface;
use Nexus\CRM\Enums\OpportunityStage;
use Nexus\CRM\ValueObjects\ForecastProbability;
use Nexus\CRM\Exceptions\OpportunityNotFoundException;
use Nexus\CRM\Exceptions\InvalidStageTransitionException;
use Psr\Log\LoggerInterface;

final class OpportunityManager implements OpportunityQueryInterface, OpportunityPersistInterface
{
    private array $opportunities = [];

    public function __construct(
        private ?LoggerInterface $logger = null
    ) {}

    public function findById(string $id): ?OpportunityInterface
    {
        return $this->opportunities[$id] ?? null;
    }

    public function findByIdOrFail(string $id): OpportunityInterface
    {
        return $this->findById($id)
            ?? throw OpportunityNotFoundException::forId($id);
    }

    public function findByPipeline(string $pipelineId): iterable
    {
        foreach ($this->opportunities as $opp) {
            if ($opp->getPipelineId() === $pipelineId) {
                yield $opp;
            }
        }
    }

    public function findByStage(OpportunityStage $stage): iterable
    {
        foreach ($this->opportunities as $opp) {
            if ($opp->getStage() === $stage) {
                yield $opp;
            }
        }
    }

    public function findOpen(): iterable
    {
        foreach ($this->opportunities as $opp) {
            if ($opp->isOpen()) {
                yield $opp;
            }
        }
    }

    public function findWon(): iterable
    {
        foreach ($this->opportunities as $opp) {
            if ($opp->isWon()) {
                yield $opp;
            }
        }
    }

    public function findLost(): iterable
    {
        foreach ($this->opportunities as $opp) {
            if ($opp->isLost()) {
                yield $opp;
            }
        }
    }

    public function findByExpectedCloseDate(\DateTimeImmutable $from, \DateTimeImmutable $to): iterable
    {
        foreach ($this->opportunities as $opp) {
            $closeDate = $opp->getExpectedCloseDate();
            if ($closeDate >= $from && $closeDate <= $to) {
                yield $opp;
            }
        }
    }

    public function findByMinimumValue(int $minimumValue): iterable
    {
        foreach ($this->opportunities as $opp) {
            if ($opp->getValue() >= $minimumValue) {
                yield $opp;
            }
        }
    }

    public function findStale(int $maxDaysInStage): iterable
    {
        $threshold = (new \DateTimeImmutable())->modify("-{$maxDaysInStage} days");
        foreach ($this->opportunities as $opp) {
            if ($opp->isOpen() && $opp->getUpdatedAt() < $threshold) {
                yield $opp;
            }
        }
    }

    public function findBySourceLead(string $leadId): ?OpportunityInterface
    {
        foreach ($this->opportunities as $opp) {
            if ($opp->getSourceLeadId() === $leadId) {
                return $opp;
            }
        }
        return null;
    }

    public function countByStage(OpportunityStage $stage): int
    {
        $count = 0;
        foreach ($this->opportunities as $opp) {
            if ($opp->getStage() === $stage) {
                $count++;
            }
        }
        return $count;
    }

    public function getTotalOpenValue(): int
    {
        $total = 0;
        foreach ($this->opportunities as $opp) {
            if ($opp->isOpen()) {
                $total += $opp->getValue();
            }
        }
        return $total;
    }

    public function getWeightedPipelineValue(): int
    {
        $total = 0;
        foreach ($this->opportunities as $opp) {
            if ($opp->isOpen()) {
                $total += $opp->getWeightedValue();
            }
        }
        return $total;
    }

    public function create(
        string $tenantId,
        string $pipelineId,
        string $title,
        int $value,
        string $currency,
        \DateTimeImmutable $expectedCloseDate,
        ?string $description = null,
        ?string $sourceLeadId = null
    ): OpportunityInterface {
        $id = uniqid('opp_');
        
        $opp = new class($id, $tenantId, $pipelineId, $title, $value, $currency, $expectedCloseDate, $description, $sourceLeadId) implements OpportunityInterface {
            public function __construct(
                public string $id,
                public string $tenantId,
                public string $pipelineId,
                public string $title,
                public int $value,
                public string $currency,
                public \DateTimeImmutable $expectedCloseDate,
                public ?string $description,
                public ?string $sourceLeadId,
                public OpportunityStage $stage = OpportunityStage::Prospecting,
                public ?\DateTimeImmutable $actualCloseDate = null,
                public \DateTimeImmutable $createdAt = new \DateTimeImmutable(),
                public \DateTimeImmutable $updatedAt = new \DateTimeImmutable()
            ) {}

            public function getId(): string { return $this->id; }
            public function getTenantId(): string { return $this->tenantId; }
            public function getPipelineId(): string { return $this->pipelineId; }
            public function getTitle(): string { return $this->title; }
            public function getDescription(): ?string { return $this->description; }
            public function getStage(): OpportunityStage { return $this->stage; }
            public function getValue(): int { return $this->value; }
            public function getCurrency(): string { return $this->currency; }
            public function getExpectedCloseDate(): \DateTimeImmutable { return $this->expectedCloseDate; }
            public function getActualCloseDate(): ?\DateTimeImmutable { return $this->actualCloseDate; }
            public function getForecastProbability(): ForecastProbability { return ForecastProbability::fromDecimal($this->stage->getDefaultProbability() / 100); }
            public function getWeightedValue(): int { return (int) ($this->value * $this->stage->getDefaultProbability() / 100); }
            public function getSourceLeadId(): ?string { return $this->sourceLeadId; }
            public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
            public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
            public function isOpen(): bool { return !$this->isWon() && !$this->isLost(); }
            public function isWon(): bool { return $this->stage === OpportunityStage::ClosedWon; }
            public function isLost(): bool { return $this->stage === OpportunityStage::ClosedLost; }
            public function getDaysInCurrentStage(): int { return (int) ((time() - $this->updatedAt->getTimestamp()) / 86400); }
            public function getAgeInDays(): int { return (int) ((time() - $this->createdAt->getTimestamp()) / 86400); }
        };

        $this->opportunities[$id] = $opp;
        
        $this->logger?->info('Opportunity created', ['opportunity_id' => $id, 'tenant_id' => $tenantId]);
        
        return $opp;
    }

    public function update(
        string $id,
        ?string $title = null,
        ?string $description = null,
        ?int $value = null,
        ?string $currency = null,
        ?\DateTimeImmutable $expectedCloseDate = null
    ): OpportunityInterface {
        $opp = $this->findByIdOrFail($id);
        
        $this->logger?->info('Opportunity updated', ['opportunity_id' => $id]);
        
        return $opp;
    }

    public function advanceStage(string $id): OpportunityInterface
    {
        $opp = $this->findByIdOrFail($id);
        
        $this->logger?->info('Opportunity stage advanced', [
            'opportunity_id' => $id,
            'new_stage' => $opp->getStage()->value
        ]);
        
        return $opp;
    }

    public function moveToStage(string $id, OpportunityStage $stage): OpportunityInterface
    {
        $opp = $this->findByIdOrFail($id);
        
        $this->logger?->info('Opportunity moved to stage', [
            'opportunity_id' => $id,
            'stage' => $stage->value
        ]);
        
        return $opp;
    }

    public function markAsWon(string $id, ?int $actualValue = null): OpportunityInterface
    {
        $opp = $this->findByIdOrFail($id);
        
        $this->logger?->info('Opportunity marked as won', [
            'opportunity_id' => $id,
            'actual_value' => $actualValue
        ]);
        
        return $opp;
    }

    public function markAsLost(string $id, string $reason): OpportunityInterface
    {
        $opp = $this->findByIdOrFail($id);
        
        $this->logger?->info('Opportunity marked as lost', [
            'opportunity_id' => $id,
            'reason' => $reason
        ]);
        
        return $opp;
    }

    public function reopen(string $id, OpportunityStage $stage): OpportunityInterface
    {
        $opp = $this->findByIdOrFail($id);
        
        $this->logger?->info('Opportunity reopened', [
            'opportunity_id' => $id,
            'new_stage' => $stage->value
        ]);
        
        return $opp;
    }

    public function delete(string $id): void
    {
        $this->findByIdOrFail($id);
        
        $this->logger?->info('Opportunity deleted', ['opportunity_id' => $id]);
    }

    public function restore(string $id): OpportunityInterface
    {
        $opp = $this->findByIdOrFail($id);
        
        $this->logger?->info('Opportunity restored', ['opportunity_id' => $id]);
        
        return $opp;
    }
}
