<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

interface CostCenterProjection
{
    public function getName(): string;
    public function getCode(): string;
    public function isActive(): bool;
    public function getResponsiblePerson(): ?string;
    public function getDepartment(): ?string;
}
