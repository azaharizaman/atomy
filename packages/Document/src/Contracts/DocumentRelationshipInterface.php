<?php

declare(strict_types=1);

namespace Nexus\Document\Contracts;

use Nexus\Document\ValueObjects\RelationshipType;

/**
 * Document relationship interface.
 *
 * Represents a directional link between two documents,
 * supporting amendment, superseding, related, and attachment relationships.
 */
interface DocumentRelationshipInterface
{
    /**
     * Get the unique relationship identifier (ULID).
     */
    public function getId(): string;

    /**
     * Get the source document identifier.
     */
    public function getSourceDocumentId(): string;

    /**
     * Get the target document identifier.
     */
    public function getTargetDocumentId(): string;

    /**
     * Get the relationship type.
     */
    public function getRelationshipType(): RelationshipType;

    /**
     * Get the identifier of the user who created this relationship.
     */
    public function getCreatedBy(): string;

    /**
     * Get the relationship creation timestamp.
     */
    public function getCreatedAt(): \DateTimeInterface;

    /**
     * Convert the relationship to an array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
