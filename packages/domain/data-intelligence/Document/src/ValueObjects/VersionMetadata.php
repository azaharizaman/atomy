<?php

declare(strict_types=1);

namespace Nexus\Document\ValueObjects;

/**
 * Version metadata value object.
 *
 * Encapsulates version-specific metadata including version number,
 * change description, creator, and timestamp.
 * Immutable by design.
 */
final readonly class VersionMetadata
{
    /**
     * @param int $versionNumber Version number (1-based sequential)
     * @param string|null $changeDescription Description of changes in this version
     * @param string $createdBy User ULID who created this version
     * @param \DateTimeInterface $createdAt When the version was created
     */
    public function __construct(
        public int $versionNumber,
        public ?string $changeDescription,
        public string $createdBy,
        public \DateTimeInterface $createdAt
    ) {
        if ($versionNumber < 1) {
            throw new \InvalidArgumentException('Version number must be >= 1');
        }

        if (empty($createdBy)) {
            throw new \InvalidArgumentException('Created by user ID cannot be empty');
        }
    }

    /**
     * Create from an array representation.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            versionNumber: (int) ($data['version_number'] ?? 1),
            changeDescription: $data['change_description'] ?? null,
            createdBy: $data['created_by'] ?? '',
            createdAt: $data['created_at'] instanceof \DateTimeInterface
                ? $data['created_at']
                : new \DateTimeImmutable($data['created_at'] ?? 'now')
        );
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'version_number' => $this->versionNumber,
            'change_description' => $this->changeDescription,
            'created_by' => $this->createdBy,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Check if this version has a change description.
     */
    public function hasChangeDescription(): bool
    {
        return $this->changeDescription !== null && $this->changeDescription !== '';
    }
}
