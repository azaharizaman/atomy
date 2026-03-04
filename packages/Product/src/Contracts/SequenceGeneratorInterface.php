<?php

declare(strict_types=1);

namespace Nexus\Product\Contracts;

interface SequenceGeneratorInterface
{
    public function generateNext(string $scope, string $tenantId): string;
}
