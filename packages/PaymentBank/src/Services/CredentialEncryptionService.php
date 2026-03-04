<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Services;

use Nexus\PaymentBank\Contracts\CredentialEncryptionInterface;
use Nexus\PaymentBank\Exceptions\DecryptionException;
use Nexus\PaymentBank\ValueObjects\EncryptedSecret;
use Psr\Log\LoggerInterface;

/**
 * Concrete implementation of CredentialEncryptionInterface.
 * 
 * This service provides basic symmetric encryption for credentials using OpenSSL.
 * In a production environment, the key should be managed securely (e.g., via KMS).
 */
final readonly class CredentialEncryptionService implements CredentialEncryptionInterface
{
    private const string VERSION_HEADER = 'v1:';
    private string $key;

    public function __construct(
        string $key,
        private LoggerInterface $logger
    ) {
        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7), true);
        }

        if ($key === false || strlen($key) !== 32) {
            throw new \InvalidArgumentException('Encryption key must be a 32-byte binary string (or base64-encoded string with base64: prefix).');
        }

        $this->key = $key;
    }

    /**
     * {@inheritdoc}
     */
    public function encrypt(string $plaintext): EncryptedSecret
    {
        $iv = random_bytes(16);
        $ciphertext = openssl_encrypt($plaintext, 'aes-256-cbc', $this->key, OPENSSL_RAW_DATA, $iv);
        
        if ($ciphertext === false) {
            throw new \RuntimeException('Encryption failed');
        }

        // Store as base64-encoded Version + IV + Ciphertext
        return new EncryptedSecret(self::VERSION_HEADER . base64_encode($iv . $ciphertext));
    }

    /**
     * {@inheritdoc}
     */
    public function decrypt(EncryptedSecret $ciphertext): string
    {
        if (!str_starts_with($ciphertext->payload, self::VERSION_HEADER)) {
            throw DecryptionException::invalidCiphertext();
        }

        $payload = substr($ciphertext->payload, strlen(self::VERSION_HEADER));
        $data = base64_decode($payload, true);
        
        if ($data === false || strlen($data) <= 16) {
             $this->logger->error('Failed to decode ciphertext or invalid data length', [
                 'payload_length' => strlen($ciphertext->payload)
             ]);
             throw DecryptionException::invalidCiphertext();
        }
        
        $iv = substr($data, 0, 16);
        $ciphertextRaw = substr($data, 16);
        
        $decrypted = openssl_decrypt($ciphertextRaw, 'aes-256-cbc', $this->key, OPENSSL_RAW_DATA, $iv);

        if ($decrypted === false) {
            $this->logger->error('OpenSSL decryption failed', [
                'error' => openssl_error_string()
            ]);
            throw DecryptionException::failed('OpenSSL error');
        }

        return $decrypted;
    }
}
