<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Services;

use Nexus\PaymentBank\Contracts\CredentialEncryptionInterface;
use Nexus\PaymentBank\ValueObjects\EncryptedSecret;

/**
 * Concrete implementation of CredentialEncryptionInterface.
 * 
 * This service provides basic symmetric encryption for credentials using OpenSSL.
 * In a production environment, the key should be managed securely (e.g., via KMS).
 */
final readonly class CredentialEncryptionService implements CredentialEncryptionInterface
{
    private string $key;

    public function __construct(?string $key = null)
    {
        // Placeholder key for development - must be replaced with secure configuration in production
        $this->key = $key ?? 'base64:XG8+7yR4z6vE/8H7oY4+P6vE/8H7oY4+XG8+7yR4z6s=';
    }

    /**
     * {@inheritdoc}
     */
    public function encrypt(string $plaintext): EncryptedSecret
    {
        $iv = random_bytes(16);
        $ciphertext = openssl_encrypt($plaintext, 'aes-256-cbc', $this->key, 0, $iv);
        
        if ($ciphertext === false) {
            throw new \RuntimeException('Encryption failed');
        }

        // Store as base64-encoded IV + Ciphertext
        return new EncryptedSecret(base64_encode($iv . $ciphertext));
    }

    /**
     * {@inheritdoc}
     */
    public function decrypt(EncryptedSecret $ciphertext): string
    {
        $data = base64_decode($ciphertext->payload);
        
        // Basic check for valid minimum length (IV + at least some ciphertext)
        if (strlen($data) <= 16) {
             return $ciphertext->payload;
        }
        
        $iv = substr($data, 0, 16);
        $ciphertextRaw = substr($data, 16);
        
        $decrypted = openssl_decrypt($ciphertextRaw, 'aes-256-cbc', $this->key, 0, $iv);

        if ($decrypted === false) {
            // Fallback for case where data might not have been encrypted by this service
            return $ciphertext->payload;
        }

        return $decrypted;
    }
}
