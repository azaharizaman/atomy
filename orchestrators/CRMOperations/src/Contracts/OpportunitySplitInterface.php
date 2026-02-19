<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\Contracts;

interface OpportunitySplitInterface
{
    public function getId(): string;
    public function getOpportunityId(): string;
    public function getSplits(): array;
    public function getTotalPercentage(): int;
    public function isApproved(): bool;
}
