<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Services;

/**
 * Interface for backup code generation.
 */
interface BackupCodeGeneratorInterface
{
    public function generate(string $userId): array;
}
