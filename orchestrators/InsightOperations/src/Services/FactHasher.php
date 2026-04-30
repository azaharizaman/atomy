<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Services;

use Nexus\InsightOperations\Contracts\FactHasherInterface;

final class FactHasher implements FactHasherInterface
{
    /**
     * @param array<string, mixed> $facts
     */
    public function hash(array $facts): string
    {
        return hash(
            "sha256",
            json_encode(
                $this->normalize($facts),
                JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION,
            ),
        );
    }

    private function normalize(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        $normalized = [];
        foreach ($value as $key => $item) {
            $normalized[$key] = $this->normalize($item);
        }

        if ($this->isAssociative($normalized)) {
            ksort($normalized);
        }

        return $normalized;
    }

    /**
     * @param array<mixed> $value
     */
    private function isAssociative(array $value): bool
    {
        if ($value === []) {
            return false;
        }

        return array_keys($value) !== range(0, count($value) - 1);
    }
}
