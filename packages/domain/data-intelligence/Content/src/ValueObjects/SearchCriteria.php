<?php

declare(strict_types=1);

namespace Nexus\Content\ValueObjects;

/**
 * Search criteria value object for faceted search (L3.6)
 * 
 * @property-read string|null $query
 * @property-read array<string> $categoryIds
 * @property-read string|null $languageCode
 * @property-read bool $publicOnly
 * @property-read string|null $viewerPartyId
 * @property-read int $limit
 * @property-read int $offset
 */
final readonly class SearchCriteria
{
    /**
     * @param string|null $query
     * @param array<string> $categoryIds
     * @param string|null $languageCode
     * @param bool $publicOnly
     * @param string|null $viewerPartyId
     * @param int $limit
     * @param int $offset
     */
    public function __construct(
        public ?string $query = null,
        public array $categoryIds = [],
        public ?string $languageCode = null,
        public bool $publicOnly = true,
        public ?string $viewerPartyId = null,
        public int $limit = 20,
        public int $offset = 0,
    ) {
        if ($this->limit < 1) {
            throw new \InvalidArgumentException('Limit must be positive');
        }

        if ($this->offset < 0) {
            throw new \InvalidArgumentException('Offset cannot be negative');
        }
    }

    /**
     * Create criteria for public search
     */
    public static function forPublic(?string $query = null): self
    {
        return new self(
            query: $query,
            publicOnly: true,
        );
    }

    /**
     * Create criteria with party access control
     */
    public static function forParty(string $partyId, ?string $query = null): self
    {
        return new self(
            query: $query,
            publicOnly: false,
            viewerPartyId: $partyId,
        );
    }
}
