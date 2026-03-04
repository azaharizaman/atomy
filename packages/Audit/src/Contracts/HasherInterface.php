<?php

declare(strict_types=1);

namespace Nexus\Audit\Contracts;

use Nexus\Audit\Enums\HashAlgorithm;

interface HasherInterface
{
    public function hash(string $payload, HashAlgorithm $algorithm): string;
}
