<?php

declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Nexus\Crypto\Contracts\KeyStorageInterface;
use Nexus\Crypto\Enums\SymmetricAlgorithm;
use Nexus\Crypto\Exceptions\InvalidKeyException;
use Nexus\Crypto\Services\KeyGenerator;
use Nexus\Crypto\ValueObjects\EncryptionKey;

/**
 * Laravel Key Storage
 *
 * Implementation of KeyStorageInterface using Laravel database backend.
 * Stores encryption keys using envelope encryption (master key encrypts DEKs).
 */
final class LaravelKeyStorage implements KeyStorageInterface
{
    private string $table;
    private string $historyTable;
    
    public function __construct(
        private readonly KeyGenerator $keyGenerator
    ) {
        $this->table = config('crypto.key_storage.table', 'encryption_keys');
        $this->historyTable = config('crypto.key_storage.rotation_history_table', 'key_rotation_history');
    }
    
    /**
     * {@inheritdoc}
     */
    public function store(string $keyId, EncryptionKey $key): void
    {
        // Encrypt key with master key (envelope encryption)
        $encryptedKey = $this->encryptWithMasterKey($key->key);
        
        DB::table($this->table)->updateOrInsert(
            ['key_id' => $keyId],
            [
                'key_id' => $keyId,
                'encrypted_key' => $encryptedKey,
                'algorithm' => $key->algorithm->value,
                'version' => $this->getNextVersion($keyId),
                'created_at' => $key->createdAt->format('Y-m-d H:i:s'),
                'expires_at' => $key->expiresAt?->format('Y-m-d H:i:s'),
                'updated_at' => now(),
            ]
        );
    }
    
    /**
     * {@inheritdoc}
     */
    public function retrieve(string $keyId): EncryptionKey
    {
        $record = DB::table($this->table)
            ->where('key_id', $keyId)
            ->orderBy('version', 'desc')
            ->first();
        
        if ($record === null) {
            throw InvalidKeyException::notFound($keyId);
        }
        
        // Check if expired
        $expiresAt = $record->expires_at ? new DateTimeImmutable($record->expires_at) : null;
        if ($expiresAt !== null && $expiresAt < new DateTimeImmutable()) {
            throw InvalidKeyException::expired($keyId);
        }
        
        // Decrypt key with master key
        $decryptedKey = $this->decryptWithMasterKey($record->encrypted_key);
        
        return new EncryptionKey(
            key: $decryptedKey,
            algorithm: SymmetricAlgorithm::from($record->algorithm),
            createdAt: new DateTimeImmutable($record->created_at),
            expiresAt: $expiresAt,
        );
    }
    
    /**
     * {@inheritdoc}
     */
    public function rotate(string $keyId): EncryptionKey
    {
        return DB::transaction(function () use ($keyId) {
            $oldKey = $this->retrieve($keyId);
            
            // Get current version with lock to prevent race conditions
            $currentVersion = $this->getCurrentVersion($keyId);
            
            // Generate new key with same algorithm
            $expirationDays = config('crypto.rotation.default_expiration_days', 90);
            $newKey = $this->keyGenerator->generateSymmetricKey(
                $oldKey->algorithm,
                $expirationDays
            );
            
            // Encrypt key with master key (envelope encryption)
            $encryptedKey = $this->encryptWithMasterKey($newKey->key);
            
            $newVersion = $currentVersion + 1;
            
            // Store new key version atomically
            DB::table($this->table)->insert([
                'key_id' => $keyId,
                'encrypted_key' => $encryptedKey,
                'algorithm' => $newKey->algorithm->value,
                'version' => $newVersion,
                'created_at' => $newKey->createdAt->format('Y-m-d H:i:s'),
                'expires_at' => $newKey->expiresAt?->format('Y-m-d H:i:s'),
                'updated_at' => now(),
            ]);
            
            // Log rotation in history table
            DB::table($this->historyTable)->insert([
                'key_id' => $keyId,
                'old_version' => $currentVersion,
                'new_version' => $newVersion,
                'rotated_at' => now(),
                'reason' => 'automated_rotation',
            ]);
            
            return $newKey;
        });
    }
    
    /**
     * {@inheritdoc}
     */
    public function delete(string $keyId): void
    {
        DB::table($this->table)->where('key_id', $keyId)->delete();
    }
    
    /**
     * {@inheritdoc}
     */
    public function findExpiringKeys(int $days = 7): array
    {
        $threshold = now()->addDays($days);
        
        $records = DB::table($this->table)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $threshold)
            ->where('expires_at', '>', now())
            ->distinct()
            ->pluck('key_id');
        
        return $records->toArray();
    }
    
    /**
     * Encrypt key material with master key (envelope encryption)
     */
    private function encryptWithMasterKey(string $key): string
    {
        return encrypt($key);
    }
    
    /**
     * Decrypt key material with master key
     */
    private function decryptWithMasterKey(string $encryptedKey): string
    {
        try {
            return decrypt($encryptedKey);
        } catch (\Throwable $e) {
            throw InvalidKeyException::invalidFormat('Failed to decrypt key with master key');
        }
    }
    
    /**
     * Get current version for key ID with pessimistic lock
     */
    private function getCurrentVersion(string $keyId): int
    {
        return (int) DB::table($this->table)
            ->where('key_id', $keyId)
            ->lockForUpdate()
            ->max('version') ?? 0;
    }
    
    /**
     * Get next version for key ID
     */
    private function getNextVersion(string $keyId): int
    {
        return $this->getCurrentVersion($keyId) + 1;
    }
}
