<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\ValueObjects;

final readonly class EncryptedSecret
{
    public function __construct(public string $payload) {}

    public static function fromJson(string $json): self
    {
        $decoded = json_decode($json, true);
        if (!is_array($decoded) || !isset($decoded['payload']) || !is_string($decoded['payload'])) {
            return new self($json);
        }

        return new self($decoded['payload']);
    }

    public function toJson(): string
    {
        return json_encode(['payload' => $this->payload], JSON_THROW_ON_ERROR);
    }
}
