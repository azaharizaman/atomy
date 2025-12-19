<?php

declare(strict_types=1);

namespace Nexus\Payment\Tests\Unit\Strategies;

use Nexus\Common\ValueObjects\Money;
use Nexus\Payment\Contracts\AllocatableDocumentInterface;

/**
 * Mock implementation of AllocatableDocumentInterface for testing allocation strategies.
 */
final class MockAllocatableDocument implements AllocatableDocumentInterface
{
    public function __construct(
        private readonly string $id,
        private readonly Money $amountDue,
        private readonly Money $outstandingBalance,
        private readonly \DateTimeImmutable $documentDate,
        private readonly ?\DateTimeImmutable $dueDate = null,
        private readonly string $currency = 'USD',
        private readonly string $documentType = 'invoice',
    ) {}

    public static function create(
        string $id,
        float $amountDue,
        ?float $outstanding = null,
        ?string $documentDate = null,
        ?string $dueDate = null,
        string $currency = 'USD',
    ): self {
        $outstanding ??= $amountDue;

        return new self(
            id: $id,
            amountDue: Money::of($amountDue, $currency),
            outstandingBalance: Money::of($outstanding, $currency),
            documentDate: new \DateTimeImmutable($documentDate ?? 'now'),
            dueDate: $dueDate ? new \DateTimeImmutable($dueDate) : null,
            currency: $currency,
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getDocumentType(): string
    {
        return $this->documentType;
    }

    public function getAmountDue(): Money
    {
        return $this->amountDue;
    }

    public function getOutstandingBalance(): Money
    {
        return $this->outstandingBalance;
    }

    public function getDocumentDate(): \DateTimeImmutable
    {
        return $this->documentDate;
    }

    public function getDueDate(): ?\DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function isFullyPaid(): bool
    {
        return $this->outstandingBalance->isZero();
    }

    public function isPartiallyPaid(): bool
    {
        return !$this->outstandingBalance->equals($this->amountDue)
            && !$this->outstandingBalance->isZero();
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }
}
