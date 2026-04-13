<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

interface CostPoolProjection
{
    public function getId(): string;
    public function getName(): string;
    public function getType(): string;
    public function getTotalAmount(): string;
    public function getCurrency(): string;
    public function getStatus(): string;
    public function getCreatedAt(): \DateTimeInterface;
}
