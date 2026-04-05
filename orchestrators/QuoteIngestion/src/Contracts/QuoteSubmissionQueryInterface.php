<?php

declare(strict_types=1);

namespace Nexus\QuoteIngestion\Contracts;

interface QuoteSubmissionQueryInterface
{
    public function find(string $tenantId, string $id): ?object;
}