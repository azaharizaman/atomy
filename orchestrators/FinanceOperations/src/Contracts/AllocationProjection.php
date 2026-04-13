<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

interface AllocationProjection
{
    public function getCostCenterId(): string;
    public function getCostCenterName(): string;
    public function getAmount(): string;
    public function getPercentage(): string;
    public function getAllocationMethod(): string;
}
