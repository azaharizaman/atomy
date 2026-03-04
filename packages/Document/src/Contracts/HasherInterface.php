<?php

declare(strict_types=1);

namespace Nexus\Document\Contracts;

interface HasherInterface
{
    public function hash(string $value): string;
}
