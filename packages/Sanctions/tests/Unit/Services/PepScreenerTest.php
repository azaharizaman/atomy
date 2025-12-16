<?php

declare(strict_types=1);

namespace Nexus\Sanctions\Tests\Unit\Services;

use DateTimeImmutable;
use Nexus\Sanctions\Contracts\PartyInterface;
use Nexus\Sanctions\Contracts\SanctionsRepositoryInterface;
use Nexus\Sanctions\Enums\PepLevel;
use Nexus\Sanctions\Enums\ScreeningFrequency;
use Nexus\Sanctions\Exceptions\InvalidPartyException;
use Nexus\Sanctions\Services\PepScreener;
use Nexus\Sanctions\ValueObjects\PepProfile;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Unit tests for PepScreener service
 *
 * Tests PEP detection, risk assessment, and FATF compliance.
 *
 * @covers \Nexus\Sanctions\Services\PepScreener
 */
final class PepScreenerTest extends TestCase
{
    private SanctionsRepositoryInterface&MockObject $repository;
    private PepScreener $screener;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(SanctionsRepositoryInterface::class);
        $this->screener = new PepScreener($this->repository, new NullLogger());
    }

    /**
     * Test PEP detection with risk level classification
     *
     * @test
     */
    public function pep_detection_with_risk_level(): void
    {
        // Arrange
        $party = $this->createPartyMock('PARTY-001', 'Ahmed Al-Rahman');

        $this->repository
            ->expects($this->once())
            ->method('searchPep')
            ->with('Ahmed Al-Rahman', 0.85)
            ->willReturn([
                [
                    'pep_id' => 'PEP-12345',
                    'name' => 'Ahmed Al-Rahman',
                    'position' => 'Minister of Finance',  // HIGH level keyword
                    'country' => 'AE',
                    'start_date' => '2020-01-01',
                    'end_date' => null,  // Current PEP
                    'source' => 'World Bank PEP Database',
                    'identified_at' => '2023-06-15',
                ],
            ]);

        // Act
        $profiles = $this->screener->screenForPep($party);

        // Assert
        $this->assertCount(1, $profiles);
        $this->assertInstanceOf(PepProfile::class, $profiles[0]);

        $profile = $profiles[0];
        $this->assertSame('PEP-12345', $profile->pepId);
        $this->assertSame('Ahmed Al-Rahman', $profile->name);
        $this->assertSame(PepLevel::HIGH, $profile->pepLevel);  // Minister = HIGH
        $this->assertSame('Minister of Finance', $profile->position);
        $this->assertSame('AE', $profile->country);
        $this->assertFalse($profile->isFormerPep());  // Still active (no end_date)
    }

    /**
     * Test former PEP risk reduction (>12 months rule)
     *
     * @test
     */
    public function former_pep_risk_reduction(): void
    {
        // Arrange
        $party = $this->createPartyMock('PARTY-002', 'Maria Santos');

        // End date > 12 months ago
        $endDate = (new DateTimeImmutable())->modify('-18 months');

        $this->repository
            ->expects($this->once())
            ->method('searchPep')
            ->with('Maria Santos', 0.85)
            ->willReturn([
                [
                    'pep_id' => 'PEP-67890',
                    'name' => 'Maria Santos',
                    'position' => 'Deputy Minister',  // Would be MEDIUM if current
                    'country' => 'PH',
                    'start_date' => '2018-06-01',
                    'end_date' => $endDate->format('Y-m-d'),  // Ended >12 months ago
                    'source' => 'Government Registry',
                    'identified_at' => '2023-01-10',
                ],
            ]);

        // Act
        $profiles = $this->screener->screenForPep($party);

        // Assert
        $this->assertCount(1, $profiles);

        $profile = $profiles[0];
        $this->assertSame(PepLevel::LOW, $profile->pepLevel);  // Reduced to LOW (former PEP >12 months)
        $this->assertTrue($profile->isFormerPep());
        $this->assertSame($endDate->format('Y-m-d'), $profile->endDate?->format('Y-m-d'));

        // Verify risk adjustment
        $riskLevel = $this->screener->assessRiskLevel($party, $profiles);
        $this->assertSame(PepLevel::LOW, $riskLevel);
    }

    /**
     * Test related persons (family/associates) checking
     *
     * @test
     */
    public function related_persons_checking(): void
    {
        // Arrange
        $party = $this->createPartyMock('PARTY-003', 'David Chen');

        // First call: Search for party (returns 1 PEP)
        // Second call: Search for related persons (returns family member)
        $this->repository
            ->expects($this->exactly(2))
            ->method('searchPep')
            ->willReturnOnConsecutiveCalls(
                // Party is PEP
                [
                    [
                        'pep_id' => 'PEP-11111',
                        'name' => 'David Chen',
                        'position' => 'Governor of Central Bank',  // HIGH
                        'country' => 'SG',
                        'start_date' => '2021-03-15',
                        'end_date' => null,
                        'source' => 'Central Bank Registry',
                        'identified_at' => '2023-05-20',
                    ],
                ],
                // Related person (family)
                [
                    [
                        'pep_id' => 'PEP-11112',
                        'name' => 'Sarah Chen',
                        'position' => 'Spouse of Governor',  // Family member
                        'country' => 'SG',
                        'relationship' => 'spouse',
                        'related_to' => 'PEP-11111',
                        'start_date' => '2021-03-15',
                        'end_date' => null,
                        'source' => 'Family Registry',
                        'identified_at' => '2023-05-20',
                    ],
                ]
            );

        // Act
        $relatedProfiles = $this->screener->checkRelatedPersons($party);

        // Assert
        $this->assertGreaterThan(0, count($relatedProfiles));

        // Should return family member profile
        $familyProfile = $relatedProfiles[0];
        $this->assertSame('PEP-11112', $familyProfile->pepId);
        $this->assertSame('Sarah Chen', $familyProfile->name);
        $this->assertStringContainsString('Spouse', $familyProfile->position);
    }

    /**
     * Test Enhanced Due Diligence (EDD) requirement determination
     *
     * @test
     */
    public function edd_requirement_determination(): void
    {
        // Arrange - HIGH level PEP
        $party = $this->createPartyMock('PARTY-004', 'General Ahmad');

        $this->repository
            ->expects($this->once())
            ->method('searchPep')
            ->willReturn([
                [
                    'pep_id' => 'PEP-22222',
                    'name' => 'General Ahmad',
                    'position' => 'Chief of Defense Staff',  // HIGH level (General)
                    'country' => 'MY',
                    'start_date' => '2019-01-01',
                    'end_date' => null,
                    'source' => 'Military Registry',
                    'identified_at' => '2023-07-01',
                ],
            ]);

        // Act
        $profiles = $this->screener->screenForPep($party);
        $requiresEdd = $this->screener->requiresEdd($party, $profiles);

        // Assert
        $this->assertTrue($requiresEdd);  // HIGH and MEDIUM levels require EDD

        // Verify monitoring frequency
        $frequency = $this->screener->getMonitoringFrequency(PepLevel::HIGH);
        $this->assertSame(ScreeningFrequency::MONTHLY, $frequency);
    }

    /**
     * Test batch PEP screening with error handling
     *
     * @test
     */
    public function batch_screening_handles_errors(): void
    {
        // Arrange
        $validParty = $this->createPartyMock('PARTY-005', 'John Doe');
        $invalidParty = $this->createPartyMock('', '');  // Invalid

        $this->repository
            ->expects($this->once())
            ->method('searchPep')
            ->with('John Doe', 0.85)
            ->willReturn([]);

        // Act
        $results = $this->screener->screenMultiple([$validParty, $invalidParty]);

        // Assert
        $this->assertCount(1, $results);  // Only valid party processed
        $this->assertArrayHasKey('PARTY-005', $results);
        $this->assertIsArray($results['PARTY-005']);
    }

    /**
     * Test invalid party throws exception
     *
     * @test
     */
    public function invalid_party_throws_exception(): void
    {
        // Arrange
        $party = $this->createPartyMock('', '');

        // Assert
        $this->expectException(InvalidPartyException::class);

        // Act
        $this->screener->screenForPep($party);
    }

    /**
     * Test risk modifier for multiple PEP connections
     *
     * @test
     */
    public function multiple_pep_connections_elevate_risk(): void
    {
        // Arrange
        $party = $this->createPartyMock('PARTY-006', 'Business Partner');

        $this->repository
            ->expects($this->once())
            ->method('searchPep')
            ->willReturn([
                [
                    'pep_id' => 'PEP-33333',
                    'name' => 'Connection 1',
                    'position' => 'Director',  // MEDIUM level
                    'country' => 'US',
                    'start_date' => '2020-01-01',
                    'end_date' => null,
                    'source' => 'Corporate Registry',
                    'identified_at' => '2023-08-01',
                ],
                [
                    'pep_id' => 'PEP-44444',
                    'name' => 'Connection 2',
                    'position' => 'Deputy Commissioner',  // MEDIUM level
                    'country' => 'US',
                    'start_date' => '2020-06-01',
                    'end_date' => null,
                    'source' => 'Government Registry',
                    'identified_at' => '2023-08-01',
                ],
                [
                    'pep_id' => 'PEP-55555',
                    'name' => 'Connection 3',
                    'position' => 'Assistant Director',  // MEDIUM level
                    'country' => 'US',
                    'start_date' => '2021-01-01',
                    'end_date' => null,
                    'source' => 'Corporate Registry',
                    'identified_at' => '2023-08-01',
                ],
            ]);

        // Act
        $profiles = $this->screener->screenForPep($party);
        $riskLevel = $this->screener->assessRiskLevel($party, $profiles);

        // Assert
        $this->assertCount(3, $profiles);
        // >2 MEDIUM profiles should elevate to HIGH
        $this->assertSame(PepLevel::HIGH, $riskLevel);
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
