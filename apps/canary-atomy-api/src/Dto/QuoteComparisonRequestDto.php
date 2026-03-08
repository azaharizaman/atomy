<?php

declare(strict_types=1);

namespace App\Dto;

final readonly class QuoteComparisonRequestDto
{
    /**
     * @param array<int, array<string, mixed>> $vendors
     */
    private function __construct(
        public string $rfqId,
        public array $vendors,
        public ?string $idempotencyKey = null,
        public bool $isPreview = false,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromPayload(array $payload, ?string $idempotencyKey): self
    {
        if (!isset($payload['rfq_id']) || !is_string($payload['rfq_id'])) {
            throw new \InvalidArgumentException('rfq_id must be a string.');
        }
        $rfqId = trim($payload['rfq_id']);
        if ($rfqId === '') {
            throw new \InvalidArgumentException('rfq_id is required.');
        }

        $vendors = $payload['vendors'] ?? null;
        if (!is_array($vendors) || $vendors === []) {
            throw new \InvalidArgumentException('vendors payload is required.');
        }

        foreach ($vendors as $index => $vendor) {
            if (!is_array($vendor)) {
                throw new \InvalidArgumentException(sprintf('vendors[%d] must be an object.', $index));
            }
            if (!isset($vendor['vendor_id']) || !is_string($vendor['vendor_id'])) {
                throw new \InvalidArgumentException(sprintf('vendors[%d].vendor_id must be a string.', $index));
            }
            $vendorId = trim($vendor['vendor_id']);
            if ($vendorId === '') {
                throw new \InvalidArgumentException(sprintf('vendors[%d].vendor_id is required.', $index));
            }
            $lines = $vendor['lines'] ?? null;
            if (!is_array($lines)) {
                throw new \InvalidArgumentException(sprintf('vendors[%d].lines must be an array.', $index));
            }
        }

        $key = $idempotencyKey !== null ? trim($idempotencyKey) : null;
        if ($key === '') {
            $key = null;
        }

        $isPreview = (bool)($payload['is_preview'] ?? false);

        return new self($rfqId, $vendors, $key, $isPreview);
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return [
            'rfq_id' => $this->rfqId,
            'vendors' => $this->vendors,
            'idempotency_key' => $this->idempotencyKey,
            'is_preview' => $this->isPreview,
        ];
    }
}

