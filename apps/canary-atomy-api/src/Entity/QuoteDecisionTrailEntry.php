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
#[ORM\UniqueConstraint(name: 'UNIQ_QDTE_RUN_SEQ', columns: ['comparison_run_id', 'sequence'])]
#[ORM\Index(name: 'IDX_QDTE_RUN', columns: ['comparison_run_id'])]
#[ORM\Index(name: 'IDX_QDTE_TENANT_RFQ', columns: ['tenant_id', 'rfq_id'])]
class QuoteDecisionTrailEntry
{
    public const EVENT_TYPE_MATRIX_BUILT = 'matrix_built';
    public const EVENT_TYPE_SCORING_COMPUTED = 'scoring_computed';
    public const EVENT_TYPE_APPROVAL_EVALUATED = 'approval_evaluated';
    public const EVENT_TYPE_APPROVAL_OVERRIDE = 'approval_override';

    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    private Ulid $id;

    #[ORM\ManyToOne(targetEntity: QuoteComparisonRun::class)]
    #[ORM\JoinColumn(name: 'comparison_run_id', nullable: false, onDelete: 'CASCADE')]
    private QuoteComparisonRun $comparisonRun;

    #[ORM\Column(name: 'tenant_id', type: 'string', length: 36)]
    private string $tenantId;

    #[ORM\Column(name: 'rfq_id', type: 'string', length: 64)]
    private string $rfqId;

    #[ORM\Column(name: 'sequence', type: Types::INTEGER)]
    private int $sequence;

    #[ORM\Column(name: 'event_type', type: 'string', length: 64)]
    private string $eventType;

    #[ORM\Column(name: 'payload_hash', type: 'string', length: 64)]
    private string $payloadHash;

    #[ORM\Column(name: 'previous_hash', type: 'string', length: 64)]
    private string $previousHash;

    #[ORM\Column(name: 'entry_hash', type: 'string', length: 64)]
    private string $entryHash;

    #[ORM\Column(name: 'occurred_at', type: Types::DATETIME_IMMUTABLE)]
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
        $this->id = new Ulid();
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

    public function getId(): Ulid
    {
        return $this->id;
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

    public function getPayloadHash(): string
    {
        return $this->payloadHash;
    }

    public function getPreviousHash(): string
    {
        return $this->previousHash;
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
