<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\QuoteApprovalDecisionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity(repositoryClass: QuoteApprovalDecisionRepository::class)]
#[ORM\Table(name: 'quote_approval_decisions')]
class QuoteApprovalDecision
{
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

    #[ORM\Column(name: 'decision', type: 'string', length: 16)]
    private string $decision;

    #[ORM\Column(name: 'reason', type: Types::TEXT)]
    private string $reason;

    #[ORM\Column(name: 'decided_by', type: 'string', length: 128, nullable: true)]
    private ?string $decidedBy;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        QuoteComparisonRun $comparisonRun,
        string $tenantId,
        string $rfqId,
        string $decision,
        string $reason,
        ?string $decidedBy
    ) {
        $this->id = new Ulid();
        $this->comparisonRun = $comparisonRun;
        $this->tenantId = $tenantId;
        $this->rfqId = $rfqId;
        $this->decision = $decision;
        $this->reason = $reason;
        $this->decidedBy = $decidedBy;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): Ulid
    {
        return $this->id;
    }

    public function getDecision(): string
    {
        return $this->decision;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getDecidedBy(): ?string
    {
        return $this->decidedBy;
    }
}
