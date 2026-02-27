<?php

declare(strict_types=1);

namespace Nexus\ProjectManagementOperations\Contracts;

use Nexus\ProjectManagementOperations\DTOs\LaborHealthDTO;

interface LaborHealthServiceInterface
{
    public function calculate(string $projectId): LaborHealthDTO;
}
