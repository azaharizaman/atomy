<?php

declare(strict_types=1);

namespace Nexus\Sanctions\Tests\Unit\Services;

use DateTimeImmutable;
use Psr\Log\NullLogger;
use PHPUnit\Framework\TestCase;
use Nexus\Sanctions\Enums\MatchStrength;
use Nexus\Sanctions\Enums\SanctionsList;
use PHPUnit\Framework\MockObject\MockObject;
use Nexus\Sanctions\Contracts\PartyInterface;
use Nexus\Sanctions\Services\SanctionsScreener;
use Nexus\Sanctions\ValueObjects\ScreeningResult;
use Nexus\Sanctions\Exceptions\InvalidPartyException;
use Nexus\Sanctions\Exceptions\ScreeningFailedException;
use Nexus\Sanctions\Contracts\SanctionsRepositoryInterface;

/**
 * Unit tests for SanctionsScreener service
 *
 * Tests fuzzy matching algorithms, multi-list screening, and validation logic.
 *
 * @covers \Nexus\Sanctions\Services\SanctionsScreener
 */
final class SanctionsScreenerTest extends TestCase
{
    private SanctionsRepositoryInterface&MockObject $repository;
    private SanctionsScreener $screener;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(SanctionsRepositoryInterface::class);
        $this->screener = new SanctionsScreener($this->repository, new NullLogger());
    }

    /**
     * Test exact name match returns EXACT strength
     *
     * @test
     */
    public function exact_match_returns_exact_strength(): void
    {
        // Arrange
        $party = $this->createPartyMock('PARTY-001', 'John Smith');

        $this->repository
            ->expects($this->once())
            ->method('searchByName')
            ->with('John Smith', SanctionsList::OFAC)
            ->willReturn([
                [
                    'entry_id' => 'SDN-12345',
                    'name' => 'John Smith',  // Exact match
                    'aliases' => [],
                    'list' => 'ofac',
                    'entity_type' => 'individual',
                    'programs' => ['SDGT'],
                    'listed_date' => '2020-01-15',
                    'remarks' => 'Terrorist financier',
                ],
            ]);

        // Act
        $result = $this->screener->screen($party, [SanctionsList::OFAC]);

        // Assert
        $this->assertInstanceOf(ScreeningResult::class, $result);
        $this->assertTrue($result->hasMatches());
        $this->assertCount(1, $result->getMatches());

        $match = $result->getMatches()[0];
        $this->assertSame('SDN-12345', $match->entryId);
        $this->assertSame(MatchStrength::EXACT, $match->matchStrength);
        $this->assertSame(1.0, $match->similarityScore);
        $this->assertSame('John Smith', $match->matchedName);
    }

    /**
     * Test fuzzy matching with Levenshtein distance
     *
     * @test
     */
    public function fuzzy_matching_with_levenshtein(): void
    {
        // Arrange
        $party = $this->createPartyMock('PARTY-002', 'Mohammad Ali');

        $this->repository
            ->expects($this->once())
            ->method('searchByName')
            ->with('Mohammad Ali', SanctionsList::UN)
            ->willReturn([
                [
                    'entry_id' => 'UN-67890',
                    'name' => 'Mohammed Ali',  // Close variant (edit distance = 2)
                    'aliases' => ['Muhammad Ali'],
                    'list' => 'un',
                    'entity_type' => 'individual',
                    'programs' => ['ISIL'],
                    'listed_date' => '2019-06-10',
                    'remarks' => 'Associated with terrorist organization',
                ],
            ]);

        // Act
        $result = $this->screener->screen($party, [SanctionsList::UN], [
            'similarity_threshold' => 0.85,
        ]);

        // Assert
        $this->assertTrue($result->hasMatches());
        $this->assertCount(1, $result->getMatches());

        $match = $result->getMatches()[0];
        $this->assertSame('UN-67890', $match->entryId);
        $this->assertSame(MatchStrength::STRONG, $match->matchStrength);
        $this->assertGreaterThanOrEqual(0.85, $match->similarityScore);
        $this->assertLessThan(1.0, $match->similarityScore);
    }

    /**
     * Test phonetic matching boosts similarity score
     *
     * @test
     */
    public function phonetic_matching_boosts_score(): void
    {
        // Arrange
        $party = $this->createPartyMock('PARTY-003', 'Catherine Johnson');

        $this->repository
            ->expects($this->once())
            ->method('searchByName')
            ->with('Catherine Johnson', SanctionsList::EU)
            ->willReturn([
                [
                    'entry_id' => 'EU-11111',
                    'name' => 'Katherine Jonson',  // Phonetically similar
                    'aliases' => [],
                    'list' => 'eu',
                    'entity_type' => 'individual',
                    'programs' => ['Belarus'],
                    'listed_date' => '2021-03-20',
                    'remarks' => 'Government official',
                ],
            ]);

        // Act
        $result = $this->screener->screen($party, [SanctionsList::EU]);

        // Assert
        $this->assertTrue($result->hasMatches());
        $matches = $result->getMatches();
        $this->assertGreaterThan(0, count($matches));

        // Phonetic boost should increase score
        $match = $matches[0];
        $this->assertGreaterThan(0.7, $match->similarityScore);

        // Catherine/Katherine are phonetically identical (Soundex: C365)
        // Johnson/Jonson are phonetically identical (Soundex: J525)
        // Should get phonetic boost of +0.10
    }

    /**
     * Test multi-list screening aggregation
     *
     * @test
     */
    public function multi_list_screening_aggregation(): void
    {
        // Arrange
        $party = $this->createPartyMock('PARTY-004', 'Vladimir Petrov');

        // OFAC returns 1 match
        $this->repository
            ->expects($this->exactly(3))
            ->method('searchByName')
            ->willReturnCallback(function (string $name, SanctionsList $list) {
                return match ($list) {
                    SanctionsList::OFAC => [
                        [
                            'entry_id' => 'SDN-99999',
                            'name' => 'Vladimir Petrov',
                            'aliases' => ['V. Petrov'],
                            'list' => 'ofac',
                            'entity_type' => 'individual',
                            'programs' => ['UKRAINE-EO13662'],
                            'listed_date' => '2022-02-25',
                            'remarks' => 'Russian oligarch',
                        ],
                    ],
                    SanctionsList::EU => [
                        [
                            'entry_id' => 'EU-88888',
                            'name' => 'Vladimir Petrov',
                            'aliases' => [],
                            'list' => 'eu',
                            'entity_type' => 'individual',
                            'programs' => ['Russia'],
                            'listed_date' => '2022-02-26',
                            'remarks' => 'Sanctioned by EU',
                        ],
                    ],
                    SanctionsList::UK => [],
                };
            });

        // Act
        $result = $this->screener->screen($party, [
            SanctionsList::OFAC,
            SanctionsList::EU,
            SanctionsList::UK,
        ]);

        // Assert
        $this->assertTrue($result->hasMatches());
        $this->assertCount(2, $result->getMatches());  // OFAC + EU

        // Verify both lists represented
        $lists = array_map(fn($m) => $m->sanctionsList, $result->getMatches());
        $this->assertContains(SanctionsList::OFAC, $lists);
        $this->assertContains(SanctionsList::EU, $lists);

        // Verify highest strength is EXACT
        $this->assertSame(MatchStrength::EXACT, $result->getHighestMatchStrength());
    }

    /**
     * Test invalid party throws exception
     *
     * @test
     */
    public function invalid_party_throws_exception(): void
    {
        // Arrange
        $party = $this->createPartyMock('', '');  // Empty ID and name

        // Assert
        $this->expectException(InvalidPartyException::class);
        $this->expectExceptionMessage('Party ID cannot be empty');

        // Act
        $this->screener->screen($party, [SanctionsList::OFAC]);
    }

    /**
     * Test screening with empty name throws exception
     *
     * @test
     */
    public function empty_name_throws_exception(): void
    {
        // Arrange
        $party = $this->createPartyMock('PARTY-005', '   ');  // Whitespace-only name

        // Assert
        $this->expectException(InvalidPartyException::class);
        $this->expectExceptionMessage('Party name cannot be empty');

        // Act
        $this->screener->screen($party, [SanctionsList::OFAC]);
    }

    /**
     * Test batch screening handles individual errors
     *
     * @test
     */
    public function batch_screening_handles_errors(): void
    {
        // Arrange
        $validParty = $this->createPartyMock('PARTY-006', 'John Doe');
        $invalidParty = $this->createPartyMock('', '');  // Invalid

        $this->repository
            ->expects($this->once())
            ->method('searchByName')
            ->with('John Doe', SanctionsList::OFAC)
            ->willReturn([]);

        // Act
        $results = $this->screener->screenMultiple(
            [$validParty, $invalidParty],
            [SanctionsList::OFAC]
        );

        // Assert
        $this->assertCount(1, $results);  // Only valid party processed
        $this->assertArrayHasKey('PARTY-006', $results);
        $this->assertInstanceOf(ScreeningResult::class, $results['PARTY-006']);
    }

    /**
     * Create mock party for testing
     */
    private function createPartyMock(string $id, string $name): PartyInterface&MockObject
    {
        $party = $this->createMock(PartyInterface::class);
        $party->method('getId')->willReturn($id);
        $party->method('getName')->willReturn($name);
        $party->method('getDateOfBirth')->willReturn(null);
        $party->method('getNationalities')->willReturn([]);
        $party->method('getIdentificationNumbers')->willReturn([]);
        $party->method('getAddresses')->willReturn([]);

        return $party;
    }
}
