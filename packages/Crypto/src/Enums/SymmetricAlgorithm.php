<?php

declare(strict_types=1);

namespace Nexus\Crypto\Enums;

/**
 * Symmetric Encryption Algorithm Enum
 *
 * Supported symmetric encryption algorithms with security metadata.
 * Used for data-at-rest encryption and secure data transmission.
 */
enum SymmetricAlgorithm: string
{
    case AES256GCM = 'aes-256-gcm';
    case AES256CBC = 'aes-256-cbc';
    case CHACHA20POLY1305 = 'chacha20-poly1305';
    
    /**
     * Check if algorithm provides authenticated encryption
     *
     * Authenticated encryption provides both confidentiality and integrity.
     */
    public function isAuthenticated(): bool
    {
        return match ($this) {
            self::AES256GCM => true,         // AEAD (Authenticated Encryption with Associated Data)
            self::AES256CBC => false,        // Encryption only (requires separate MAC)
            self::CHACHA20POLY1305 => true,  // AEAD
        };
    }
    
    /**
     * Check if algorithm requires initialization vector (IV)
     */
    public function requiresIV(): bool
    {
        return match ($this) {
            self::AES256GCM => true,         // Requires unique nonce per message
            self::AES256CBC => true,         // Requires unique IV per message
            self::CHACHA20POLY1305 => true,  // Requires unique nonce per message
        };
    }
    
    /**
     * Get required key length in bytes
     */
    public function getKeyLength(): int
    {
        return match ($this) {
            self::AES256GCM => 32,         // 256 bits
            self::AES256CBC => 32,         // 256 bits
            self::CHACHA20POLY1305 => 32,  // 256 bits
        };
    }
    
    /**
     * Get IV/nonce length in bytes
     */
    public function getIVLength(): int
    {
        return match ($this) {
            self::AES256GCM => 12,         // 96 bits (recommended for GCM)
            self::AES256CBC => 16,         // 128 bits (AES block size)
            self::CHACHA20POLY1305 => 12,  // 96 bits
        };
    }
    
    /**
     * Get authentication tag length in bytes (for AEAD modes)
     *
     * @return int|null Tag length or null for non-AEAD modes
     */
    public function getTagLength(): ?int
    {
        return match ($this) {
            self::AES256GCM => 16,         // 128 bits
            self::AES256CBC => null,       // Not authenticated
            self::CHACHA20POLY1305 => 16,  // 128 bits
        };
    }
    
    /**
     * Get OpenSSL cipher method name
     *
     * Used with openssl_encrypt/decrypt functions.
     */
    public function getOpenSSLMethod(): string
    {
        return match ($this) {
            self::AES256GCM => 'aes-256-gcm',
            self::AES256CBC => 'aes-256-cbc',
            self::CHACHA20POLY1305 => 'chacha20-poly1305', // OpenSSL 1.1.0+
        };
    }
    
    /**
     * Check if quantum-resistant
     *
     * Note: Symmetric algorithms with 256-bit keys are considered quantum-resistant
     * as Grover's algorithm only provides quadratic speedup (256-bit → 128-bit effective).
     */
    public function isQuantumResistant(): bool
    {
        return true; // All 256-bit symmetric algorithms are quantum-resistant
    }
}
