<?php

declare(strict_types=1);

namespace Nexus\Crypto\Services;

use Nexus\Crypto\Contracts\SymmetricEncryptorInterface;
use Nexus\Crypto\Enums\SymmetricAlgorithm;
use Nexus\Crypto\Exceptions\DecryptionException;
use Nexus\Crypto\Exceptions\EncryptionException;
use Nexus\Crypto\ValueObjects\EncryptedData;
use Nexus\Crypto\ValueObjects\EncryptionKey;

/**
 * Sodium Encryptor
 *
 * Implementation of SymmetricEncryptorInterface using Sodium extension
 * for AES-256-GCM and ChaCha20-Poly1305.
 */
final readonly class SodiumEncryptor implements SymmetricEncryptorInterface
{
    /**
     * {@inheritdoc}
     */
    public function encrypt(
        string $plaintext,
        SymmetricAlgorithm $algorithm = SymmetricAlgorithm::AES256GCM,
        ?EncryptionKey $key = null
    ): EncryptedData {
        // Require explicit key
        if ($key === null) {
            throw EncryptionException::failed("Encryption key is required");
        }
        
        $keyBinary = $key->getKeyBinary();
        
        // Validate key length
        $expectedLength = $algorithm->getKeyLength();
        if (strlen($keyBinary) !== $expectedLength) {
            throw EncryptionException::failed("Invalid key length");
        }
        
        // Generate nonce/IV
        $nonceBinary = random_bytes($algorithm->getIVLength());
        
        try {
            match ($algorithm) {
                SymmetricAlgorithm::AES256GCM => $result = $this->encryptAesGcm($plaintext, $keyBinary, $nonceBinary),
                SymmetricAlgorithm::CHACHA20POLY1305 => $result = $this->encryptChaCha20($plaintext, $keyBinary, $nonceBinary),
                SymmetricAlgorithm::AES256CBC => $result = $this->encryptAesCbc($plaintext, $keyBinary, $nonceBinary),
            };
        } catch (\Throwable $e) {
            throw EncryptionException::failed($e->getMessage());
        }
        
        return new EncryptedData(
            ciphertext: base64_encode($result['ciphertext']),
            iv: base64_encode($nonceBinary),
            tag: base64_encode($result['tag']),
            algorithm: $algorithm,
            metadata: [],
        );
    }
    
    /**
     * {@inheritdoc}
     */
    public function decrypt(EncryptedData $encrypted, ?EncryptionKey $key = null): string
    {
        // Require explicit key
        if ($key === null) {
            throw DecryptionException::failed("Decryption key is required");
        }
        
        $keyBinary = $key->getKeyBinary();
        
        // Decode components
        $ciphertext = $encrypted->getCiphertextBinary();
        $nonce = $encrypted->getIVBinary();
        $tag = $encrypted->getTagBinary();
        
        if (empty($ciphertext)) {
            throw DecryptionException::invalidCiphertext();
        }
        
        try {
            $plaintext = match ($encrypted->algorithm) {
                SymmetricAlgorithm::AES256GCM => $this->decryptAesGcm($ciphertext, $tag, $keyBinary, $nonce),
                SymmetricAlgorithm::CHACHA20POLY1305 => $this->decryptChaCha20($ciphertext, $tag, $keyBinary, $nonce),
                SymmetricAlgorithm::AES256CBC => $this->decryptAesCbc($ciphertext, $keyBinary, $nonce),
            };
        } catch (\Throwable $e) {
            throw DecryptionException::failed($e->getMessage());
        }
        
        if ($plaintext === false) {
            throw DecryptionException::authenticationFailed();
        }
        
        return $plaintext;
    }
    
    /**
     * Encrypt with AES-256-GCM using Sodium
     *
     * @return array{ciphertext: string, tag: string}
     */
    private function encryptAesGcm(string $plaintext, string $key, string $nonce): array
    {
        $ciphertext = sodium_crypto_aead_aes256gcm_encrypt(
            $plaintext,
            '', // No additional data
            $nonce,
            $key
        );
        
        // Sodium combines ciphertext and tag
        // Extract tag (last 16 bytes)
        $tag = substr($ciphertext, -16);
        $ciphertext = substr($ciphertext, 0, -16);
        
        return ['ciphertext' => $ciphertext, 'tag' => $tag];
    }
    
    /**
     * Decrypt with AES-256-GCM using Sodium
     */
    private function decryptAesGcm(string $ciphertext, string $tag, string $key, string $nonce): string
    {
        // Sodium expects ciphertext + tag concatenated
        $combined = $ciphertext . $tag;
        
        $plaintext = sodium_crypto_aead_aes256gcm_decrypt(
            $combined,
            '', // No additional data
            $nonce,
            $key
        );
        
        return $plaintext ?: throw DecryptionException::authenticationFailed();
    }
    
    /**
     * Encrypt with ChaCha20-Poly1305 using Sodium
     *
     * @return array{ciphertext: string, tag: string}
     */
    private function encryptChaCha20(string $plaintext, string $key, string $nonce): array
    {
        $ciphertext = sodium_crypto_aead_chacha20poly1305_ietf_encrypt(
            $plaintext,
            '',
            $nonce,
            $key
        );
        
        // Extract tag (last 16 bytes)
        $tag = substr($ciphertext, -16);
        $ciphertext = substr($ciphertext, 0, -16);
        
        return ['ciphertext' => $ciphertext, 'tag' => $tag];
    }
    
    /**
     * Decrypt with ChaCha20-Poly1305 using Sodium
     */
    private function decryptChaCha20(string $ciphertext, string $tag, string $key, string $nonce): string
    {
        $combined = $ciphertext . $tag;
        
        $plaintext = sodium_crypto_aead_chacha20poly1305_ietf_decrypt(
            $combined,
            '',
            $nonce,
            $key
        );
        
        return $plaintext ?: throw DecryptionException::authenticationFailed();
    }
    
    /**
     * Encrypt with AES-256-CBC using OpenSSL
     *
     * @return array{ciphertext: string, tag: string}
     */
    private function encryptAesCbc(string $plaintext, string $key, string $iv): array
    {
        $ciphertext = openssl_encrypt(
            $plaintext,
            'aes-256-cbc',
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
        
        if ($ciphertext === false) {
            throw EncryptionException::failed("OpenSSL encryption failed");
        }
        
        // CBC doesn't have authentication tag
        return ['ciphertext' => $ciphertext, 'tag' => ''];
    }
    
    /**
     * Decrypt with AES-256-CBC using OpenSSL
     */
    private function decryptAesCbc(string $ciphertext, string $key, string $iv): string
    {
        $plaintext = openssl_decrypt(
            $ciphertext,
            'aes-256-cbc',
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
        
        if ($plaintext === false) {
            throw DecryptionException::failed("OpenSSL decryption failed");
        }
        
        return $plaintext;
    }
    
}
