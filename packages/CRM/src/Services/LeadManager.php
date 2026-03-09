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
    /** @var array<string, LeadInterface> */
    private array $leads = [];
    /** @var array<string, LeadInterface> */
    private array $deletedLeads = [];
    private ?string $boundTenantId = null;

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
        $this->assertTenantScope($tenantId);

        $id = $this->generateId('lead');
        $now = new \DateTimeImmutable();
        $lead = $this->createLeadEntity(
            id: $id,
            tenantId: $tenantId,
            title: $title,
            source: $source,
            description: $description,
            estimatedValue: $estimatedValue,
            currency: $currency,
            externalRef: $externalRef,
            status: LeadStatus::New,
            score: null,
            createdAt: $now,
            updatedAt: $now,
            convertedAt: null,
            convertedToOpportunityId: null,
        );

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
        $updatedLead = $this->rebuildLead($lead, [
            'title' => $title ?? $lead->getTitle(),
            'description' => $description ?? $lead->getDescription(),
            'estimatedValue' => $estimatedValue ?? $lead->getEstimatedValue(),
            'currency' => $currency ?? $lead->getCurrency(),
            'updatedAt' => new \DateTimeImmutable(),
        ]);
        $this->leads[$id] = $updatedLead;

        $this->logger?->info('Lead updated', ['lead_id' => $id]);

        return $updatedLead;
    }

    public function updateStatus(string $id, LeadStatus $status): LeadInterface
    {
        $lead = $this->findByIdOrFail($id);

        if ($lead->getStatus() !== $status && !$lead->getStatus()->canTransitionTo($status)) {
            throw InvalidLeadStatusTransitionException::fromStatuses(
                $lead->getStatus(),
                $status
            );
        }

        $updatedLead = $this->rebuildLead($lead, [
            'status' => $status,
            'updatedAt' => new \DateTimeImmutable(),
        ]);
        $this->leads[$id] = $updatedLead;

        $this->logger?->info('Lead status updated', [
            'lead_id' => $id,
            'old_status' => $lead->getStatus()->value,
            'new_status' => $status->value
        ]);

        return $updatedLead;
    }

    public function updateSource(string $id, LeadSource $source): LeadInterface
    {
        $lead = $this->findByIdOrFail($id);
        $updatedLead = $this->rebuildLead($lead, [
            'source' => $source,
            'updatedAt' => new \DateTimeImmutable(),
        ]);
        $this->leads[$id] = $updatedLead;

        $this->logger?->info('Lead source updated', ['lead_id' => $id, 'source' => $source->value]);

        return $updatedLead;
    }

    public function assignScore(string $id, int $score, array $factors = []): LeadInterface
    {
        $lead = $this->findByIdOrFail($id);
        $updatedLead = $this->rebuildLead($lead, [
            'score' => new LeadScore($score, $factors),
            'updatedAt' => new \DateTimeImmutable(),
        ]);
        $this->leads[$id] = $updatedLead;

        $this->logger?->info('Lead score assigned', ['lead_id' => $id, 'score' => $score]);

        return $updatedLead;
    }

    public function convertToOpportunity(string $id): string
    {
        $lead = $this->findByIdOrFail($id);

        if (!$lead->isConvertible()) {
            throw LeadNotConvertibleException::forLead($id, $lead->getStatus());
        }

        $opportunityId = $this->generateId('opp');
        $updatedLead = $this->rebuildLead($lead, [
            'status' => LeadStatus::Converted,
            'convertedAt' => new \DateTimeImmutable(),
            'convertedToOpportunityId' => $opportunityId,
            'updatedAt' => new \DateTimeImmutable(),
        ]);
        $this->leads[$id] = $updatedLead;

        $this->logger?->info('Lead converted to opportunity', [
            'lead_id' => $id,
            'opportunity_id' => $opportunityId
        ]);

        return $opportunityId;
    }

    public function disqualify(string $id, string $reason): LeadInterface
    {
        $lead = $this->updateStatus($id, LeadStatus::Disqualified);

        $this->logger?->info('Lead disqualified', ['lead_id' => $id, 'reason' => $reason]);

        return $lead;
    }

    public function delete(string $id): void
    {
        $lead = $this->findByIdOrFail($id);
        unset($this->leads[$id]);
        $this->deletedLeads[$id] = $lead;

        $this->logger?->info('Lead deleted', ['lead_id' => $id]);
    }

    public function restore(string $id): LeadInterface
    {
        if (isset($this->leads[$id])) {
            return $this->leads[$id];
        }

        $deletedLead = $this->deletedLeads[$id] ?? null;
        if ($deletedLead === null) {
            throw LeadNotFoundException::forId($id);
        }

        $restoredLead = $this->rebuildLead($deletedLead, [
            'updatedAt' => new \DateTimeImmutable(),
        ]);
        $this->leads[$id] = $restoredLead;
        unset($this->deletedLeads[$id]);

        $this->logger?->info('Lead restored', ['lead_id' => $id]);

        return $restoredLead;
    }

    /**
     * @param array{
     *   title?: string,
     *   description?: ?string,
     *   status?: LeadStatus,
     *   source?: LeadSource,
     *   score?: ?LeadScore,
     *   estimatedValue?: ?int,
     *   currency?: ?string,
     *   externalRef?: ?string,
     *   updatedAt?: \DateTimeImmutable,
     *   convertedAt?: ?\DateTimeImmutable,
     *   convertedToOpportunityId?: ?string
     * } $overrides
     */
    private function rebuildLead(LeadInterface $lead, array $overrides): LeadInterface
    {
        return $this->createLeadEntity(
            id: $lead->getId(),
            tenantId: $lead->getTenantId(),
            title: $overrides['title'] ?? $lead->getTitle(),
            source: $overrides['source'] ?? $lead->getSource(),
            description: $overrides['description'] ?? $lead->getDescription(),
            estimatedValue: $overrides['estimatedValue'] ?? $lead->getEstimatedValue(),
            currency: $overrides['currency'] ?? $lead->getCurrency(),
            externalRef: $overrides['externalRef'] ?? $lead->getExternalRef(),
            status: $overrides['status'] ?? $lead->getStatus(),
            score: $overrides['score'] ?? $lead->getScore(),
            createdAt: $lead->getCreatedAt(),
            updatedAt: $overrides['updatedAt'] ?? $lead->getUpdatedAt(),
            convertedAt: $overrides['convertedAt'] ?? $lead->getConvertedAt(),
            convertedToOpportunityId: $overrides['convertedToOpportunityId'] ?? $lead->getConvertedToOpportunityId(),
        );
    }

    private function generateId(string $prefix): string
    {
        try {
            return sprintf('%s_%s', $prefix, bin2hex(random_bytes(10)));
        } catch (\Throwable $exception) {
            throw new CRMException('Unable to generate secure CRM identifier.', 0, $exception);
        }
    }

    private function assertTenantScope(string $tenantId): void
    {
        if ($this->boundTenantId === null) {
            $this->boundTenantId = $tenantId;
            return;
        }

        if ($this->boundTenantId !== $tenantId) {
            throw new CRMException(sprintf(
                'LeadManager is scoped to tenant "%s" and cannot create records for tenant "%s".',
                $this->boundTenantId,
                $tenantId
            ));
        }
    }

    private function createLeadEntity(
        string $id,
        string $tenantId,
        string $title,
        LeadSource $source,
        ?string $description,
        ?int $estimatedValue,
        ?string $currency,
        ?string $externalRef,
        LeadStatus $status,
        ?LeadScore $score,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
        ?\DateTimeImmutable $convertedAt,
        ?string $convertedToOpportunityId
    ): LeadInterface {
        return new class(
            $id,
            $tenantId,
            $title,
            $source,
            $description,
            $estimatedValue,
            $currency,
            $externalRef,
            $status,
            $score,
            $createdAt,
            $updatedAt,
            $convertedAt,
            $convertedToOpportunityId
        ) implements LeadInterface {
            private string $id;
            private string $tenantId;
            private string $title;
            private LeadSource $source;
            private ?string $description;
            private ?int $estimatedValue;
            private ?string $currency;
            private ?string $externalRef;
            private LeadStatus $status;
            private ?LeadScore $score;
            private \DateTimeImmutable $createdAt;
            private \DateTimeImmutable $updatedAt;
            private ?\DateTimeImmutable $convertedAt;
            private ?string $convertedToOpportunityId;

            public function __construct(
                string $id,
                string $tenantId,
                string $title,
                LeadSource $source,
                ?string $description,
                ?int $estimatedValue,
                ?string $currency,
                ?string $externalRef,
                LeadStatus $status,
                ?LeadScore $score,
                \DateTimeImmutable $createdAt,
                \DateTimeImmutable $updatedAt,
                ?\DateTimeImmutable $convertedAt,
                ?string $convertedToOpportunityId
            ) {
                $this->id = $id;
                $this->tenantId = $tenantId;
                $this->title = $title;
                $this->source = $source;
                $this->description = $description;
                $this->estimatedValue = $estimatedValue;
                $this->currency = $currency;
                $this->externalRef = $externalRef;
                $this->status = $status;
                $this->score = $score;
                $this->createdAt = $createdAt;
                $this->updatedAt = $updatedAt;
                $this->convertedAt = $convertedAt;
                $this->convertedToOpportunityId = $convertedToOpportunityId;
            }

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
    }
}
