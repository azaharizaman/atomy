<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Entities;

interface BankTransactionInterface
{
    public function getId(): string;
    public function getDate(): \DateTimeImmutable;
    public function getAmount(): float;
    public function getDescription(): string;
}
