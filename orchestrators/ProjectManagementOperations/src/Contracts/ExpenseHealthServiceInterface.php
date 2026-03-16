<?php

declare(strict_types=1);

namespace Nexus\ProjectManagementOperations\Contracts;

use Nexus\ProjectManagementOperations\DTOs\ExpenseHealthDTO;

interface ExpenseHealthServiceInterface
{
    public function calculate(string $projectId): ExpenseHealthDTO;
}
