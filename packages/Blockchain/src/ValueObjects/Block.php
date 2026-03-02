<?php

declare(strict_types=1);

namespace Nexus\Blockchain\ValueObjects;

use Nexus\Common\Contracts\SerializableVO;
use Nexus\Crypto\Contracts\HasherInterface;

/**
 * Represents a block in the blockchain.
 */
final readonly class Block implements SerializableVO
{
    /**
     * @param array<Transaction> $transactions
     */
    public function __construct(
        public int $index,
        public \DateTimeImmutable $timestamp,
        public array $transactions,
        public string $previousHash,
        public string $hash,
        public int $nonce = 0
    ) {}

    public function toArray(): array
    {
        return [
            'index' => $this->index,
            'timestamp' => $this->timestamp->format(\DateTimeInterface::ATOM),
            'transactions' => array_map(fn(Transaction $tx) => $tx->toArray(), $this->transactions),
            'previousHash' => $this->previousHash,
            'hash' => $this->hash,
            'nonce' => $this->nonce,
        ];
    }

    public function toString(): string
    {
        return sprintf(
            'Block #%d [%s] - Hash: %s',
            $this->index,
            $this->timestamp->format(\DateTimeInterface::ATOM),
            $this->hash
        );
    }

    public static function fromArray(array $data): static
    {
        return new self(
            index: (int) $data['index'],
            timestamp: new \DateTimeImmutable($data['timestamp']),
            transactions: array_map(fn(array $tx) => Transaction::fromArray($tx), $data['transactions']),
            previousHash: $data['previousHash'],
            hash: $data['hash'],
            nonce: (int) $data['nonce']
        );
    }

    /**
     * Get data used for hash calculation.
     */
    public function getHashableData(): string
    {
        $txData = array_map(fn(Transaction $tx) => $tx->getSigningData(), $this->transactions);
        
        return json_encode([
            'index' => $this->index,
            'timestamp' => $this->timestamp->format(\DateTimeInterface::ATOM),
            'transactions' => $txData,
            'previousHash' => $this->previousHash,
            'nonce' => $this->nonce,
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * Calculate hash of the block.
     */
    public function calculateHash(HasherInterface $hasher): string
    {
        return $hasher->hash($this->getHashableData())->hash;
    }
}
