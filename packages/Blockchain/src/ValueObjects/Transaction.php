<?php

declare(strict_types=1);

namespace Nexus\Blockchain\ValueObjects;

use Nexus\Common\Contracts\SerializableVO;
use Nexus\Blockchain\Enums\TransactionStatus;
use Nexus\Crypto\Contracts\AsymmetricSignerInterface;
use Nexus\Crypto\ValueObjects\SignedData;

/**
 * Represents a single transaction within a block.
 */
final readonly class Transaction implements SerializableVO
{
    public function __construct(
        public TransactionId $id,
        public string $sender,
        public string $recipient,
        public float $amount,
        public string $data,
        public \DateTimeImmutable $timestamp,
        public ?string $signature = null,
        public TransactionStatus $status = TransactionStatus::PENDING
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id->toString(),
            'sender' => $this->sender,
            'recipient' => $this->recipient,
            'amount' => $this->amount,
            'data' => $this->data,
            'timestamp' => $this->timestamp->format(\DateTimeInterface::ATOM),
            'signature' => $this->signature,
            'status' => $this->status->value,
        ];
    }

    public function toString(): string
    {
        return sprintf(
            '[%s] %s -> %s: %.8f (%s)',
            $this->id->toString(),
            $this->sender,
            $this->recipient,
            $this->amount,
            $this->status->value
        );
    }

    public static function fromArray(array $data): static
    {
        return new self(
            id: TransactionId::fromString($data['id']),
            sender: $data['sender'],
            recipient: $data['recipient'],
            amount: (float) $data['amount'],
            data: $data['data'],
            timestamp: new \DateTimeImmutable($data['timestamp']),
            signature: $data['signature'] ?? null,
            status: TransactionStatus::from($data['status'])
        );
    }

    /**
     * Get data to be signed.
     */
    public function getSigningData(): string
    {
        return json_encode([
            'sender' => $this->sender,
            'recipient' => $this->recipient,
            'amount' => $this->amount,
            'data' => $this->data,
            'timestamp' => $this->timestamp->format(\DateTimeInterface::ATOM),
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * Verify if the transaction signature is valid.
     */
    public function verify(AsymmetricSignerInterface $signer, string $publicKey): bool
    {
        if ($this->signature === null) {
            return false;
        }

        $signedData = new SignedData(
            data: $this->getSigningData(),
            signature: base64_decode($this->signature),
            algorithm: $signer->getAlgorithm(),
            publicKey: $publicKey
        );

        return $signer->verify($signedData);
    }
}
