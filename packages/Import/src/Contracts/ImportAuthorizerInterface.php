<?php

declare(strict_types=1);

namespace Nexus\Import\Contracts;

use Nexus\Import\ValueObjects\ImportMode;

/**
 * Import authorization contract
 * 
 * Handles permission checks for import operations.
 */
interface ImportAuthorizerInterface
{
    /**
     * Check if user can perform import
     * 
     * @param ImportHandlerInterface $handler Import handler being used
     * @param ImportMode $mode Import mode
     * @param ImportContextInterface|null $context Execution context
     * @return bool True if authorized
     */
    public function canImport(
        ImportHandlerInterface $handler,
        ImportMode $mode,
        ?ImportContextInterface $context
    ): bool;

    /**
     * Assert user can perform import (throws exception if not authorized)
     * 
     * @param ImportHandlerInterface $handler Import handler being used
     * @param ImportMode $mode Import mode
     * @param ImportContextInterface|null $context Execution context
     * @throws \Nexus\Import\Exceptions\ImportAuthorizationException
     */
    public function assertCanImport(
        ImportHandlerInterface $handler,
        ImportMode $mode,
        ?ImportContextInterface $context
    ): void;
}
