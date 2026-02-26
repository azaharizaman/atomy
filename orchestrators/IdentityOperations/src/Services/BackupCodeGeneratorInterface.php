<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Services;

/**
 * Interface for backup code generation.
 */
interface BackupCodeGeneratorInterface
{
    /**
     * @return list<string>
     */
    public function generate(string $userId): array;
}
