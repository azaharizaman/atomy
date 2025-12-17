<?php

declare(strict_types=1);

namespace Nexus\PDPA\Tests\Enums;

use Nexus\PDPA\Enums\PdpaDataPrinciple;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdpaDataPrinciple::class)]
final class PdpaDataPrincipleTest extends TestCase
{
    #[Test]
    public function it_has_seven_data_protection_principles(): void
    {
        $principles = PdpaDataPrinciple::cases();

        $this->assertCount(7, $principles);
    }

    #[Test]
    public function it_has_all_required_pdpa_principles(): void
    {
        $expectedPrinciples = [
            'GENERAL',
            'NOTICE_AND_CHOICE',
            'DISCLOSURE',
            'SECURITY',
            'RETENTION',
            'DATA_INTEGRITY',
            'ACCESS',
        ];

        foreach ($expectedPrinciples as $principle) {
            $this->assertContains(
                PdpaDataPrinciple::from(PdpaDataPrinciple::{$principle}->value),
                PdpaDataPrinciple::cases(),
                "Missing principle: {$principle}"
            );
        }
    }

    #[Test]
    #[DataProvider('principleValuesProvider')]
    public function it_has_correct_values(PdpaDataPrinciple $principle, string $expectedValue): void
    {
        $this->assertEquals($expectedValue, $principle->value);
    }

    public static function principleValuesProvider(): array
    {
        return [
            'general' => [PdpaDataPrinciple::GENERAL, 'general'],
            'notice_and_choice' => [PdpaDataPrinciple::NOTICE_AND_CHOICE, 'notice_and_choice'],
            'disclosure' => [PdpaDataPrinciple::DISCLOSURE, 'disclosure'],
            'security' => [PdpaDataPrinciple::SECURITY, 'security'],
            'retention' => [PdpaDataPrinciple::RETENTION, 'retention'],
            'data_integrity' => [PdpaDataPrinciple::DATA_INTEGRITY, 'data_integrity'],
            'access' => [PdpaDataPrinciple::ACCESS, 'access'],
        ];
    }

    #[Test]
    #[DataProvider('principleLabelsProvider')]
    public function it_has_descriptive_labels(PdpaDataPrinciple $principle, string $expectedLabel): void
    {
        $this->assertEquals($expectedLabel, $principle->getLabel());
    }

    public static function principleLabelsProvider(): array
    {
        return [
            'general' => [PdpaDataPrinciple::GENERAL, 'General Principle (Section 6)'],
            'notice_and_choice' => [PdpaDataPrinciple::NOTICE_AND_CHOICE, 'Notice and Choice Principle (Section 7)'],
            'disclosure' => [PdpaDataPrinciple::DISCLOSURE, 'Disclosure Principle (Section 8)'],
            'security' => [PdpaDataPrinciple::SECURITY, 'Security Principle (Section 9)'],
            'retention' => [PdpaDataPrinciple::RETENTION, 'Retention Principle (Section 10)'],
            'data_integrity' => [PdpaDataPrinciple::DATA_INTEGRITY, 'Data Integrity Principle (Section 11)'],
            'access' => [PdpaDataPrinciple::ACCESS, 'Access Principle (Section 12)'],
        ];
    }

    #[Test]
    #[DataProvider('principleSectionsProvider')]
    public function it_has_correct_pdpa_section_references(PdpaDataPrinciple $principle, int $expectedSection): void
    {
        $this->assertEquals($expectedSection, $principle->getSection());
    }

    public static function principleSectionsProvider(): array
    {
        return [
            'general' => [PdpaDataPrinciple::GENERAL, 6],
            'notice_and_choice' => [PdpaDataPrinciple::NOTICE_AND_CHOICE, 7],
            'disclosure' => [PdpaDataPrinciple::DISCLOSURE, 8],
            'security' => [PdpaDataPrinciple::SECURITY, 9],
            'retention' => [PdpaDataPrinciple::RETENTION, 10],
            'data_integrity' => [PdpaDataPrinciple::DATA_INTEGRITY, 11],
            'access' => [PdpaDataPrinciple::ACCESS, 12],
        ];
    }

    #[Test]
    #[DataProvider('principleDescriptionsProvider')]
    public function it_has_detailed_descriptions(PdpaDataPrinciple $principle): void
    {
        $description = $principle->getDescription();

        $this->assertNotEmpty($description);
        $this->assertIsString($description);
        $this->assertGreaterThan(20, strlen($description), 'Description should be detailed');
    }

    public static function principleDescriptionsProvider(): array
    {
        return [
            'general' => [PdpaDataPrinciple::GENERAL],
            'notice_and_choice' => [PdpaDataPrinciple::NOTICE_AND_CHOICE],
            'disclosure' => [PdpaDataPrinciple::DISCLOSURE],
            'security' => [PdpaDataPrinciple::SECURITY],
            'retention' => [PdpaDataPrinciple::RETENTION],
            'data_integrity' => [PdpaDataPrinciple::DATA_INTEGRITY],
            'access' => [PdpaDataPrinciple::ACCESS],
        ];
    }

    #[Test]
    public function it_can_create_from_string_value(): void
    {
        $principle = PdpaDataPrinciple::from('general');
        $this->assertEquals(PdpaDataPrinciple::GENERAL, $principle);

        $principle = PdpaDataPrinciple::from('security');
        $this->assertEquals(PdpaDataPrinciple::SECURITY, $principle);
    }

    #[Test]
    public function it_can_try_from_invalid_value(): void
    {
        $principle = PdpaDataPrinciple::tryFrom('invalid');
        $this->assertNull($principle);
    }

    #[Test]
    public function general_principle_relates_to_consent(): void
    {
        $description = PdpaDataPrinciple::GENERAL->getDescription();

        $this->assertStringContainsStringIgnoringCase('consent', $description);
    }

    #[Test]
    public function notice_principle_relates_to_privacy_notice(): void
    {
        $description = PdpaDataPrinciple::NOTICE_AND_CHOICE->getDescription();

        $this->assertStringContainsStringIgnoringCase('notice', $description);
        $this->assertStringContainsStringIgnoringCase('inform', $description);
    }

    #[Test]
    public function security_principle_requires_protective_measures(): void
    {
        $description = PdpaDataPrinciple::SECURITY->getDescription();

        $this->assertStringContainsStringIgnoringCase('security', $description);
    }
}
