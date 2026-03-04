<?php

declare(strict_types=1);

namespace Nexus\Reporting\Contracts;

interface AnalyticsAuthorizerInterface
{
    public function can(string $userId, string $action, string $queryId): bool;
}
