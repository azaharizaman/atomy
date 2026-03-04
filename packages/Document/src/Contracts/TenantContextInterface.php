<?php

declare(strict_types=1);

namespace Nexus\Document\Contracts;

interface TenantContextInterface
{
    public function requireTenant(): string;
}
