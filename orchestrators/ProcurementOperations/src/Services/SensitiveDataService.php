<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Services;

use Nexus\Crypto\Contracts\CryptoManagerInterface;
use Nexus\Crypto\ValueObjects\EncryptedData;
use Nexus\Crypto\ValueObjects\HashResult;
use Nexus\ProcurementOperations\Contracts\SensitiveDataServiceInterface;

/**
 * Sensitive data service implementation using Nexus\Crypto.
 *
 * Provides masking, encryption, and hashing for sensitive vendor
 * and financial data, including:
 * - Tax IDs
 * - Bank account numbers
 * - IBANs
 * - Banking details
 *
 * Uses AES-256-GCM encryption and SHA-256 hashing by default.
 */
final readonly class SensitiveDataService implements SensitiveDataServiceInterface
{
    public function __construct(
        private CryptoManagerInterface $crypto,
    ) {}

    /**
     * @inheritDoc
     */
    public function maskTaxId(string $taxId): string
    {
        return $this->crypto->maskNationalId($taxId);
    }

    /**
     * @inheritDoc
     */
    public function maskBankAccount(string $accountNumber): string
    {
        $length = strlen($accountNumber);

        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return str_repeat('*', $length - 4) . substr($accountNumber, -4);
    }

    /**
     * @inheritDoc
     */
    public function maskIban(string $iban): string
    {
        return $this->crypto->maskIban($iban);
    }

    /**
     * @inheritDoc
     */
    public function maskEmail(string $email): string
    {
        return $this->crypto->maskEmail($email);
    }

    /**
     * @inheritDoc
     */
    public function maskPhone(string $phone): string
    {
        return $this->crypto->maskPhone($phone);
    }

    /**
     * @inheritDoc
     */
    public function encrypt(string $data, ?string $context = null): string
    {
        $encrypted = $this->crypto->encrypt($data);

        // Serialize EncryptedData to string for storage
        return json_encode($encrypted->toArray(), JSON_THROW_ON_ERROR);
    }

    /**
     * @inheritDoc
     */
    public function decrypt(string $encryptedData, ?string $context = null): string
    {
        // Deserialize from string back to EncryptedData
        $data = json_decode($encryptedData, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($data)) {
            throw new \RuntimeException('Invalid encrypted data format');
        }

        $encrypted = EncryptedData::fromArray($data);

        return $this->crypto->decrypt($encrypted);
    }

    /**
     * @inheritDoc
     */
    public function hash(string $data): string
    {
        $result = $this->crypto->hash($data);

        return $result->hash;
    }

    /**
     * @inheritDoc
     */
    public function verifyHash(string $data, string $hash): bool
    {
        // Reconstruct HashResult from stored hash
        $hashResult = $this->crypto->hash($data);

        return $hashResult->matches($hash);
    }

    /**
     * @inheritDoc
     */
    public function maskBankingDetails(array $bankingDetails): array
    {
        $masked = $bankingDetails;

        if (isset($masked['account_number']) && is_string($masked['account_number'])) {
            $masked['account_number'] = $this->maskBankAccount($masked['account_number']);
        }

        if (isset($masked['iban']) && is_string($masked['iban'])) {
            $masked['iban'] = $this->maskIban($masked['iban']);
        }

        if (isset($masked['swift_code']) && is_string($masked['swift_code'])) {
            // Mask middle part of SWIFT code
            $swift = $masked['swift_code'];
            $length = strlen($swift);
            if ($length >= 8) {
                $masked['swift_code'] = substr($swift, 0, 4) . '****' . substr($swift, -3);
            }
        }

        if (isset($masked['routing_number']) && is_string($masked['routing_number'])) {
            $routing = $masked['routing_number'];
            $masked['routing_number'] = str_repeat('*', strlen($routing) - 4) . substr($routing, -4);
        }

        return $masked;
    }

    /**
     * @inheritDoc
     */
    public function encryptBankingDetails(array $bankingDetails): string
    {
        $json = json_encode($bankingDetails, JSON_THROW_ON_ERROR);

        return $this->encrypt($json);
    }

    /**
     * @inheritDoc
     */
    public function decryptBankingDetails(string $encryptedData): array
    {
        $json = $this->decrypt($encryptedData);

        $result = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($result)) {
            throw new \RuntimeException('Decrypted banking details must be an array');
        }

        return $result;
    }
}
