<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\Contracts;

use Nexus\Crypto\Contracts\SecureIdGeneratorInterface as BaseSecureIdGeneratorInterface;

/**
 * Interface for generating secure, cryptographically random identifiers for HR operations.
 *
 * This interface extends the base Nexus\Crypto SecureIdGeneratorInterface,
 * providing a unified contract for secure ID generation across the HR domain.
 *
 * The base interface provides:
 * - generateId(string $prefix = '', int $length = 16): string
 * - generateUuid(): string
 * - randomBytes(int $length): string
 * - randomHex(int $length): string
 *
 * This extension exists to:
 * 1. Allow HR-specific ID generation methods to be added if needed
 * 2. Maintain backward compatibility with existing HR services
 * 3. Provide HR-domain-specific documentation and context
 *
 * @package Nexus\HumanResourceOperations
 * @since 1.0.0
 * @see BaseSecureIdGeneratorInterface
 */
interface SecureIdGeneratorInterface extends BaseSecureIdGeneratorInterface
{
    // Inherits all methods from Nexus\Crypto\Contracts\SecureIdGeneratorInterface:
    // - generateId(string $prefix = '', int $length = 16): string
    // - generateUuid(): string
    // - randomBytes(int $length): string
    // - randomHex(int $length): string
    //
    // No additional methods required for HR operations at this time.
    // Add HR-specific ID generation methods here if needed in the future.
}
