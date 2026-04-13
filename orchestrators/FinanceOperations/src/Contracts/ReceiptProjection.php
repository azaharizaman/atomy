<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

interface ReceiptProjection
{
    public function getId(): string;
    public function getAmount(): string;
    public function getCurrency(): string;
    public function getDueDate(): \DateTimeInterface;
    public function getStatus(): string;
    public function getCustomerId(): string;
}
