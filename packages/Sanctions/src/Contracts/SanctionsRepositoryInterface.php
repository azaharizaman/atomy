<?php

declare(strict_types=1);

namespace Nexus\Sanctions\Contracts;

use Nexus\Sanctions\Enums\SanctionsList;

/**
 * Interface for sanctions and PEP data repository operations.
 * 
 * Provides contract for accessing sanctions list data and PEP databases.
 * Implementing classes handle data source integration (API, database, files).
 * 
 * Data Sources:
 * - OFAC: US Treasury API / SDN List files
 * - UN: UN Security Council Consolidated List
 * - EU: EU Sanctions Map / XML exports
 * - UK HMT: HM Treasury Consolidated List
 * - National lists: Country-specific APIs/files
 * - PEP databases: Commercial providers (WorldCheck, Dow Jones, ComplyAdvantage)
 * 
 * Implementing classes should provide:
 * - Efficient data access with caching
 * - Data refresh/synchronization
 * - Fuzzy search capabilities
 * - Metadata tracking (last updated, version)
 * 
 * @package Nexus\Sanctions\Contracts
 */
interface SanctionsRepositoryInterface
{
    /**
     * Find sanctions list entries by name.
     *
     * Returns all entries matching the name with fuzzy matching.
     * Used by screening services for initial matching.
     *
     * @param string $name Name to search for
     * @param SanctionsList $list Sanctions list to search
     * @param float $similarityThreshold Minimum similarity (0.0-1.0)
     * @return array<array> Array of sanctions list entries with metadata
     */
    public function findByName(
        string $name,
        SanctionsList $list,
        float $similarityThreshold = 0.85
    ): array;

    /**
     * Find sanctions list entry by ID.
     *
     * Returns specific entry with full details.
     * Used for fetching complete data after initial match.
     *
     * @param string $entryId Entry ID from sanctions list
     * @param SanctionsList $list Sanctions list
     * @return array|null Entry data or null if not found
     */
    public function findById(string $entryId, SanctionsList $list): ?array;

    /**
     * Find PEP profiles by name.
     *
     * Searches PEP databases for matching profiles.
     * Returns detailed PEP information including position, dates, etc.
     *
     * @param string $name Name to search for
     * @param float $similarityThreshold Minimum similarity (0.0-1.0)
     * @return array<array> Array of PEP profile data
     */
    public function findPepByName(
        string $name,
        float $similarityThreshold = 0.85
    ): array;

    /**
     * Find PEP profile by ID.
     *
     * Returns specific PEP profile with full details.
     *
     * @param string $pepId PEP profile ID
     * @return array|null PEP profile data or null if not found
     */
    public function findPepById(string $pepId): ?array;

    /**
     * Get related persons for a PEP.
     *
     * Finds family members and close associates of a PEP.
     * Important for identifying indirect PEP exposure.
     *
     * @param string $pepId PEP profile ID
     * @return array<array> Array of related person data
     */
    public function getRelatedPersons(string $pepId): array;

    /**
     * Get last updated timestamp for a sanctions list.
     *
     * Used to determine if list data is current.
     * Helps with data refresh scheduling.
     *
     * @param SanctionsList $list Sanctions list
     * @return \DateTimeImmutable Last update timestamp
     * @throws \Nexus\Sanctions\Exceptions\SanctionsListUnavailableException If list unavailable
     */
    public function getListLastUpdated(SanctionsList $list): \DateTimeImmutable;

    /**
     * Get list version/revision.
     *
     * Some lists provide version numbers for change tracking.
     *
     * @param SanctionsList $list Sanctions list
     * @return string|null Version identifier
     */
    public function getListVersion(SanctionsList $list): ?string;

    /**
     * Check if list is available.
     *
     * Verifies connectivity and data availability before screening.
     * Prevents screening failures due to unavailable data.
     *
     * @param SanctionsList $list Sanctions list to check
     * @return bool True if list is available
     */
    public function isListAvailable(SanctionsList $list): bool;

    /**
     * Get total entry count for a list.
     *
     * Useful for statistics and monitoring.
     *
     * @param SanctionsList $list Sanctions list
     * @return int Number of entries
     */
    public function getListEntryCount(SanctionsList $list): int;

    /**
     * Search across multiple lists.
     *
     * Efficient method for multi-list screening.
     * Returns results grouped by list.
     *
     * @param string $name Name to search for
     * @param array<SanctionsList> $lists Lists to search
     * @param float $similarityThreshold Minimum similarity (0.0-1.0)
     * @return array<string, array> Results keyed by list name
     */
    public function searchMultipleLists(
        string $name,
        array $lists,
        float $similarityThreshold = 0.85
    ): array;

    /**
     * Refresh list data from source.
     *
     * Triggers data synchronization with list provider.
     * Should be called periodically to keep data current.
     *
     * @param SanctionsList $list Sanctions list to refresh
     * @return bool True if refresh successful
     * @throws \Nexus\Sanctions\Exceptions\SanctionsListUnavailableException If source unavailable
     */
    public function refreshList(SanctionsList $list): bool;

    /**
     * Get statistics for all lists.
     *
     * Provides overview of data availability and freshness.
     * Useful for monitoring and reporting.
     *
     * @return array<string, array> Statistics keyed by list name
     */
    public function getListStatistics(): array;
}
