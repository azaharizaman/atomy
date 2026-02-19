<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\Contracts;

interface AssignmentStrategyInterface
{
    public function assign(array $leadData, array $assignees): ?string;

    public function getName(): string;
}
