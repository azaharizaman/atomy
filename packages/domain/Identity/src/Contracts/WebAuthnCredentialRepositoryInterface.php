<?php

declare(strict_types=1);

namespace Nexus\Domain\Identity\Contracts;

use Nexus\Domain\Identity\ValueObjects\WebAuthnCredential;

/**
 * WebAuthn credential repository interface (Combined Query + Persist for backward compatibility)
 *
 * Extends both WebAuthnCredentialQueryInterface and WebAuthnCredentialPersistInterface.
 * New code should prefer injecting the specific Query or Persist interface
 * following CQRS principles.
 *
 * @deprecated Use WebAuthnCredentialQueryInterface for reads and WebAuthnCredentialPersistInterface for writes
 */
interface WebAuthnCredentialRepositoryInterface extends WebAuthnCredentialQueryInterface, WebAuthnCredentialPersistInterface
{
    // All methods inherited from WebAuthnCredentialQueryInterface and WebAuthnCredentialPersistInterface
}

