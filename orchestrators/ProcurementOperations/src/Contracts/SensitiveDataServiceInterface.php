<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

/**
 * Interface for handling sensitive data operations.
 *
 * Provides data masking, encryption, and anonymization capabilities
 * for sensitive vendor and financial data using Nexus\Crypto.
 */
interface SensitiveDataServiceInterface
{
    /**
     * Mask a tax identification number for display.
     *
     * @param string $taxId The tax ID to mask
     * @param string $country ISO 3166-1 alpha-2 country code (e.g., 'MY', 'US', 'GB')
     * @return string Masked tax ID (e.g., "****1234")
     */
    public function maskTaxId(string $taxId, string $country): string;

    /**
     * Mask bank account number for display.
     *
     * @param string $accountNumber The account number to mask
     * @return string Masked account (e.g., "****5678")
     */
    public function maskBankAccount(string $accountNumber): string;

    /**
     * Mask IBAN for display.
     *
     * @param string $iban The IBAN to mask
     * @return string Masked IBAN (e.g., "DE89****...****4567")
     */
    public function maskIban(string $iban): string;

    /**
     * Mask email address for display.
     *
     * @param string $email The email to mask
     * @return string Masked email (e.g., "j***@example.com")
     */
    public function maskEmail(string $email): string;

    /**
     * Mask phone number for display.
     *
     * @param string $phone The phone to mask
     * @return string Masked phone (e.g., "+1****1234")
     */
    public function maskPhone(string $phone): string;

    /**
     * Encrypt sensitive data for storage.
     *
     * Uses AES-256-GCM authenticated encryption.
     *
     * @param string $data The data to encrypt
     * @return string Encrypted data (JSON-encoded EncryptedData)
     */
    public function encrypt(string $data): string;

    /**
     * Decrypt sensitive data from storage.
     *
     * @param string $encryptedData The data to decrypt (JSON-encoded EncryptedData)
     * @return string Decrypted data
     */
    public function decrypt(string $encryptedData): string;

    /**
     * Hash sensitive data for comparison without revealing original.
     *
     * @param string $data The data to hash
     * @return string Hashed data
     */
    public function hash(string $data): string;

    /**
     * Verify data against a hash.
     *
     * @param string $data The data to verify
     * @param string $hash The hash to verify against
     * @return bool True if data matches hash
     */
    public function verifyHash(string $data, string $hash): bool;

    /**
     * Mask banking details array for display.
     *
     * @param array<string, mixed> $bankingDetails The banking details to mask
     * @return array<string, mixed> Masked banking details
     */
    public function maskBankingDetails(array $bankingDetails): array;

    /**
     * Encrypt banking details for storage.
     *
     * @param array<string, mixed> $bankingDetails The banking details to encrypt
     * @return string Encrypted JSON string
     */
    public function encryptBankingDetails(array $bankingDetails): string;

    /**
     * Decrypt banking details from storage.
     *
     * @param string $encryptedData The encrypted banking details
     * @return array<string, mixed> Decrypted banking details
     */
    public function decryptBankingDetails(string $encryptedData): array;
}
