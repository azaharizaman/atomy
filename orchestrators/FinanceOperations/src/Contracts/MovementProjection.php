<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

interface MovementProjection
{
    public function getId(): string;
    public function getType(): string;
    public function getAmount(): string;
    public function getCurrency(): string;
    public function getPostingDate(): \DateTimeInterface;
    public function getStatus(): string;
}
