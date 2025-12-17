<?php

declare(strict_types=1);

namespace Nexus\PDPA\Tests\Enums;

use Nexus\PDPA\Enums\PdpaSensitiveData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdpaSensitiveData::class)]
final class PdpaSensitiveDataTest extends TestCase
{
    #[Test]
    public function it_has_five_sensitive_data_categories(): void
    {
        $categories = PdpaSensitiveData::cases();

        $this->assertCount(5, $categories);
    }

    #[Test]
    public function it_has_all_required_pdpa_sensitive_categories(): void
    {
        $expectedCategories = [
            'HEALTH',
            'POLITICAL_OPINION',
            'RELIGIOUS_BELIEF',
            'CRIMINAL_OFFENCE',
            'OTHER_PERSONAL_DATA',
        ];

        foreach ($expectedCategories as $category) {
            $this->assertContains(
                PdpaSensitiveData::from(PdpaSensitiveData::{$category}->value),
                PdpaSensitiveData::cases(),
                "Missing category: {$category}"
            );
        }
    }

    #[Test]
    #[DataProvider('categoryValuesProvider')]
    public function it_has_correct_values(PdpaSensitiveData $category, string $expectedValue): void
    {
        $this->assertEquals($expectedValue, $category->value);
    }

    public static function categoryValuesProvider(): array
    {
        return [
            'health' => [PdpaSensitiveData::HEALTH, 'health'],
            'political_opinion' => [PdpaSensitiveData::POLITICAL_OPINION, 'political_opinion'],
            'religious_belief' => [PdpaSensitiveData::RELIGIOUS_BELIEF, 'religious_belief'],
            'criminal_offence' => [PdpaSensitiveData::CRIMINAL_OFFENCE, 'criminal_offence'],
            'other_personal_data' => [PdpaSensitiveData::OTHER_PERSONAL_DATA, 'other_personal_data'],
        ];
    }

    #[Test]
    #[DataProvider('categoryLabelsProvider')]
    public function it_has_descriptive_labels(PdpaSensitiveData $category, string $expectedLabel): void
    {
        $this->assertEquals($expectedLabel, $category->getLabel());
    }

    public static function categoryLabelsProvider(): array
    {
        return [
            'health' => [PdpaSensitiveData::HEALTH, 'Physical or Mental Health'],
            'political_opinion' => [PdpaSensitiveData::POLITICAL_OPINION, 'Political Opinions'],
            'religious_belief' => [PdpaSensitiveData::RELIGIOUS_BELIEF, 'Religious Beliefs or Other Similar Beliefs'],
            'criminal_offence' => [PdpaSensitiveData::CRIMINAL_OFFENCE, 'Commission or Alleged Commission of Offence'],
            'other_personal_data' => [PdpaSensitiveData::OTHER_PERSONAL_DATA, 'Other Personal Data as Prescribed by Minister'],
        ];
    }

    #[Test]
    public function all_categories_reference_section_40(): void
    {
        foreach (PdpaSensitiveData::cases() as $category) {
            $this->assertEquals(40, $category->getSection());
        }
    }

    #[Test]
    #[DataProvider('categoryDescriptionsProvider')]
    public function it_has_detailed_descriptions(PdpaSensitiveData $category): void
    {
        $description = $category->getDescription();

        $this->assertNotEmpty($description);
        $this->assertIsString($description);
        $this->assertGreaterThan(10, strlen($description), 'Description should be meaningful');
    }

    public static function categoryDescriptionsProvider(): array
    {
        return [
            'health' => [PdpaSensitiveData::HEALTH],
            'political_opinion' => [PdpaSensitiveData::POLITICAL_OPINION],
            'religious_belief' => [PdpaSensitiveData::RELIGIOUS_BELIEF],
            'criminal_offence' => [PdpaSensitiveData::CRIMINAL_OFFENCE],
            'other_personal_data' => [PdpaSensitiveData::OTHER_PERSONAL_DATA],
        ];
    }

    #[Test]
    public function it_can_create_from_string_value(): void
    {
        $category = PdpaSensitiveData::from('health');
        $this->assertEquals(PdpaSensitiveData::HEALTH, $category);

        $category = PdpaSensitiveData::from('religious_belief');
        $this->assertEquals(PdpaSensitiveData::RELIGIOUS_BELIEF, $category);
    }

    #[Test]
    public function it_can_try_from_invalid_value(): void
    {
        $category = PdpaSensitiveData::tryFrom('invalid');
        $this->assertNull($category);
    }

    #[Test]
    public function it_requires_explicit_consent_for_all_categories(): void
    {
        foreach (PdpaSensitiveData::cases() as $category) {
            $this->assertTrue(
                $category->requiresExplicitConsent(),
                "Category {$category->value} should require explicit consent"
            );
        }
    }

    #[Test]
    public function health_data_is_sensitive(): void
    {
        $this->assertTrue(PdpaSensitiveData::HEALTH->requiresExplicitConsent());
        $this->assertStringContainsStringIgnoringCase(
            'health',
            PdpaSensitiveData::HEALTH->getDescription()
        );
    }

    #[Test]
    public function criminal_offence_data_is_sensitive(): void
    {
        $this->assertTrue(PdpaSensitiveData::CRIMINAL_OFFENCE->requiresExplicitConsent());
        $this->assertStringContainsStringIgnoringCase(
            'offence',
            PdpaSensitiveData::CRIMINAL_OFFENCE->getDescription()
        );
    }

    #[Test]
    public function it_provides_examples_of_data_types(): void
    {
        $examples = PdpaSensitiveData::HEALTH->getExamples();

        $this->assertIsArray($examples);
        $this->assertNotEmpty($examples);
    }

    #[Test]
    #[DataProvider('categoryExamplesProvider')]
    public function all_categories_have_examples(PdpaSensitiveData $category): void
    {
        $examples = $category->getExamples();

        $this->assertIsArray($examples);
        $this->assertGreaterThan(0, count($examples), "Category {$category->value} should have examples");
    }

    public static function categoryExamplesProvider(): array
    {
        return [
            'health' => [PdpaSensitiveData::HEALTH],
            'political_opinion' => [PdpaSensitiveData::POLITICAL_OPINION],
            'religious_belief' => [PdpaSensitiveData::RELIGIOUS_BELIEF],
            'criminal_offence' => [PdpaSensitiveData::CRIMINAL_OFFENCE],
            'other_personal_data' => [PdpaSensitiveData::OTHER_PERSONAL_DATA],
        ];
    }
}
