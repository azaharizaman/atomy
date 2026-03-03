<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Services;

use Nexus\PaymentBank\Contracts\CredentialEncryptionInterface;
use Nexus\PaymentBank\ValueObjects\EncryptedSecret;

/**
 * Helper for decrypting credentials stored as JSON-serialized EncryptedSecret.
 * 
 * This is a pure utility class for decrypting bank connection credentials.
 * Credentials are stored as JSON-serialized EncryptedSecret value objects and
 * need to be deserialized and decrypted before use with provider APIs.
 */
final readonly class CredentialDecryptionHelper
{
    public function __construct(
        private CredentialEncryptionInterface $crypto
    ) {}

    /**
     * Decrypt credentials array containing JSON-serialized EncryptedSecret values.
     *
     * @param array<string, mixed> $encryptedCredentials Credentials with encrypted access/refresh tokens
     * @return array<string, mixed> Credentials with decrypted tokens
     */
    public function decryptCredentials(array $encryptedCredentials): array
    {
        $decrypted = $encryptedCredentials;
        
        foreach (['access_token', 'refresh_token'] as $key) {
            if (isset($encryptedCredentials[$key]) && is_string($encryptedCredentials[$key])) {
                $decrypted[$key] = $this->crypto->decrypt(
                    EncryptedSecret::fromJson($encryptedCredentials[$key])
                );
            }
        }
        
        return $decrypted;
    }
}
