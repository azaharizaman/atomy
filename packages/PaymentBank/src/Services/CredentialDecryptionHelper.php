<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Services;

use Nexus\Crypto\Contracts\CryptoManagerInterface;
use Nexus\Crypto\ValueObjects\EncryptedData;

/**
 * Helper for decrypting credentials stored as JSON-serialized EncryptedData.
 * 
 * This is a pure utility class for decrypting bank connection credentials.
 * Credentials are stored as JSON-serialized EncryptedData value objects and
 * need to be deserialized and decrypted before use with provider APIs.
 */
final readonly class CredentialDecryptionHelper
{
    public function __construct(
        private CryptoManagerInterface $crypto
    ) {}

    /**
     * Decrypt credentials array containing JSON-serialized EncryptedData values.
     *
     * @param array<string, mixed> $encryptedCredentials Credentials with encrypted access/refresh tokens
     * @return array<string, mixed> Credentials with decrypted tokens
     */
    public function decryptCredentials(array $encryptedCredentials): array
    {
        $decrypted = $encryptedCredentials;
        
        if (isset($encryptedCredentials['access_token']) && is_string($encryptedCredentials['access_token'])) {
            $decrypted['access_token'] = $this->crypto->decrypt(
                EncryptedData::fromJson($encryptedCredentials['access_token'])
            );
        }
        
        if (isset($encryptedCredentials['refresh_token']) && $encryptedCredentials['refresh_token'] !== null) {
            $decrypted['refresh_token'] = $this->crypto->decrypt(
                EncryptedData::fromJson($encryptedCredentials['refresh_token'])
            );
        }
        
        return $decrypted;
    }
}
