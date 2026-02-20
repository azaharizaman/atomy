<?php

declare(strict_types=1);

namespace Nexus\CRM\Services;

use Nexus\CRM\Contracts\LeadQueryInterface;
use Nexus\CRM\Contracts\LeadPersistInterface;
use Nexus\CRM\Contracts\LeadInterface;
use Nexus\CRM\Enums\LeadStatus;
use Nexus\CRM\Enums\LeadSource;
use Nexus\CRM\ValueObjects\LeadScore;
use Nexus\CRM\Exceptions\LeadNotFoundException;
use Nexus\CRM\Exceptions\InvalidLeadStatusTransitionException;
use Nexus\CRM\Exceptions\LeadNotConvertibleException;
use Nexus\CRM\Exceptions\CRMException;
use Psr\Log\LoggerInterface;

final class LeadManager implements LeadQueryInterface, LeadPersistInterface
{
    private array $leads = [];

    public function __construct(
        private ?LoggerInterface $logger = null
    ) {}

    public function findById(string $id): ?LeadInterface
    {
        return $this->leads[$id] ?? null;
    }

    public function findByIdOrFail(string $id): LeadInterface
    {
        return $this->findById($id)
            ?? throw LeadNotFoundException::forId($id);
    }

    public function findByExternalRef(string $externalRef): ?LeadInterface
    {
        foreach ($this->leads as $lead) {
            if ($lead->getExternalRef() === $externalRef) {
                return $lead;
            }
        }
        return null;
    }

    public function findByStatus(LeadStatus $status): iterable
    {
        foreach ($this->leads as $lead) {
            if ($lead->getStatus() === $status) {
                yield $lead;
            }
        }
    }

    public function findBySource(LeadSource $source): iterable
    {
        foreach ($this->leads as $lead) {
            if ($lead->getSource() === $source) {
                yield $lead;
            }
        }
    }

    public function findByDateRange(\DateTimeImmutable $from, \DateTimeImmutable $to): iterable
    {
        foreach ($this->leads as $lead) {
            $createdAt = $lead->getCreatedAt();
            if ($createdAt >= $from && $createdAt <= $to) {
                yield $lead;
            }
        }
    }

    public function findHighScoring(int $minimumScore): iterable
    {
        foreach ($this->leads as $lead) {
            $score = $lead->getScore();
            if ($score !== null && $score->getValue() >= $minimumScore) {
                yield $lead;
            }
        }
    }

    public function findUnassigned(): iterable
    {
        foreach ($this->leads as $lead) {
            if ($lead->getStatus() === LeadStatus::New) {
                yield $lead;
            }
        }
    }

    public function findConvertible(): iterable
    {
        foreach ($this->leads as $lead) {
            if ($lead->isConvertible()) {
                yield $lead;
            }
        }
    }

    public function countByStatus(LeadStatus $status): int
    {
        $count = 0;
        foreach ($this->leads as $lead) {
            if ($lead->getStatus() === $status) {
                $count++;
            }
        }
        return $count;
    }

    public function countBySource(LeadSource $source): int
    {
        $count = 0;
        foreach ($this->leads as $lead) {
            if ($lead->getSource() === $source) {
                $count++;
            }
        }
        return $count;
    }

    public function create(
        string $tenantId,
        string $title,
        LeadSource $source,
        ?string $description = null,
        ?int $estimatedValue = null,
        ?string $currency = null,
        ?string $externalRef = null
    ): LeadInterface {
        $id = uniqid('lead_');
        
        $lead = new class($id, $tenantId, $title, $source, $description, $estimatedValue, $currency, $externalRef) implements LeadInterface {
            public function __construct(
                public string $id,
                public string $tenantId,
                public string $title,
                public LeadSource $source,
                public ?string $description,
                public ?int $estimatedValue,
                public ?string $currency,
                public ?string $externalRef,
                public LeadStatus $status = LeadStatus::New,
                public ?LeadScore $score = null,
                public \DateTimeImmutable $createdAt = new \DateTimeImmutable(),
                public \DateTimeImmutable $updatedAt = new \DateTimeImmutable(),
                public ?\DateTimeImmutable $convertedAt = null,
                public ?string $convertedToOpportunityId = null
            ) {}

            public function getId(): string { return $this->id; }
            public function getTenantId(): string { return $this->tenantId; }
            public function getTitle(): string { return $this->title; }
            public function getDescription(): ?string { return $this->description; }
            public function getStatus(): LeadStatus { return $this->status; }
            public function getSource(): LeadSource { return $this->source; }
            public function getScore(): ?LeadScore { return $this->score; }
            public function getEstimatedValue(): ?int { return $this->estimatedValue; }
            public function getCurrency(): ?string { return $this->currency; }
            public function getExternalRef(): ?string { return $this->externalRef; }
            public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
            public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
            public function getConvertedAt(): ?\DateTimeImmutable { return $this->convertedAt; }
            public function getConvertedToOpportunityId(): ?string { return $this->convertedToOpportunityId; }
            public function isQualified(): bool { return $this->status === LeadStatus::Qualified; }
            public function isConvertible(): bool { return $this->status->isConvertible(); }
        };

        $this->leads[$id] = $lead;
        
        $this->logger?->info('Lead created', ['lead_id' => $id, 'tenant_id' => $tenantId]);
        
        return $lead;
    }

    public function update(
        string $id,
        ?string $title = null,
        ?string $description = null,
        ?int $estimatedValue = null,
        ?string $currency = null
    ): LeadInterface {
        $lead = $this->findByIdOrFail($id);
        
        $this->logger?->info('Lead updated', ['lead_id' => $id]);
        
        return $lead;
    }

    public function updateStatus(string $id, LeadStatus $status): LeadInterface
    {
        $lead = $this->findByIdOrFail($id);
        
        if (!$lead->getStatus()->canTransitionTo($status)) {
            throw InvalidLeadStatusTransitionException::fromStatuses(
                $lead->getStatus(),
                $status
            );
        }
        
        $this->logger?->info('Lead status updated', [
            'lead_id' => $id,
            'old_status' => $lead->getStatus()->value,
            'new_status' => $status->value
        ]);
        
        return $lead;
    }

    public function updateSource(string $id, LeadSource $source): LeadInterface
    {
        $lead = $this->findByIdOrFail($id);
        
        $this->logger?->info('Lead source updated', ['lead_id' => $id, 'source' => $source->value]);
        
        return $lead;
    }

    public function assignScore(string $id, int $score, array $factors = []): LeadInterface
    {
        $lead = $this->findByIdOrFail($id);
        
        $leadScore = new LeadScore($score, $factors);
        
        $this->logger?->info('Lead score assigned', ['lead_id' => $id, 'score' => $score]);
        
        return $lead;
    }

    public function convertToOpportunity(string $id): string
    {
        $lead = $this->findByIdOrFail($id);
        
        if (!$lead->isConvertible()) {
            throw LeadNotConvertibleException::forLead($id, $lead->getStatus());
        }
        
        $opportunityId = uniqid('opp_');
        
        $this->logger?->info('Lead converted to opportunity', [
            'lead_id' => $id,
            'opportunity_id' => $opportunityId
        ]);
        
        return $opportunityId;
    }

    public function disqualify(string $id, string $reason): LeadInterface
    {
        $lead = $this->findByIdOrFail($id);
        
        $this->logger?->info('Lead disqualified', ['lead_id' => $id, 'reason' => $reason]);
        
        return $lead;
    }

    public function delete(string $id): void
    {
        $this->findByIdOrFail($id);
        
        $this->logger?->info('Lead deleted', ['lead_id' => $id]);
    }

    public function restore(string $id): LeadInterface
    {
        $lead = $this->findByIdOrFail($id);
        
        $this->logger?->info('Lead restored', ['lead_id' => $id]);
        
        return $lead;
    }
}
