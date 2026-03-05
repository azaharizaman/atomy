<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\QuoteDecisionTrailEntryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity(repositoryClass: QuoteDecisionTrailEntryRepository::class)]
#[ORM\Table(name: 'quote_decision_trail_entries')]
class QuoteDecisionTrailEntry
{
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: QuoteComparisonRun::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private QuoteComparisonRun $comparisonRun;

    #[ORM\Column(type: 'string', length: 36)]
    private string $tenantId;

    #[ORM\Column(type: 'string', length: 64)]
    private string $rfqId;

    #[ORM\Column(type: Types::INTEGER)]
    private int $sequence;

    #[ORM\Column(type: 'string', length: 64)]
    private string $eventType;

    #[ORM\Column(type: 'string', length: 64)]
    private string $payloadHash;

    #[ORM\Column(type: 'string', length: 64)]
    private string $previousHash;

    #[ORM\Column(type: 'string', length: 64)]
    private string $entryHash;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $occurredAt;

    public function __construct(
        QuoteComparisonRun $comparisonRun,
        string $tenantId,
        string $rfqId,
        int $sequence,
        string $eventType,
        string $payloadHash,
        string $previousHash,
        string $entryHash,
        \DateTimeImmutable $occurredAt
    ) {
        $this->id = (new Ulid())->toBase32();
        $this->comparisonRun = $comparisonRun;
        $this->tenantId = $tenantId;
        $this->rfqId = $rfqId;
        $this->sequence = $sequence;
        $this->eventType = $eventType;
        $this->payloadHash = $payloadHash;
        $this->previousHash = $previousHash;
        $this->entryHash = $entryHash;
        $this->occurredAt = $occurredAt;
    }

    public function getSequence(): int
    {
        return $this->sequence;
    }

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function getEntryHash(): string
    {
        return $this->entryHash;
    }
}
