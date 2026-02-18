<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Contracts;

interface SecureIdGeneratorInterface
{
    public function randomHex(int $length): string;

    public function uuid(): string;
}
