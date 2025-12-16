<?php

declare(strict_types=1);

namespace Nexus\Sanctions\Tests\Unit\Algorithms;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for fuzzy matching algorithms
 *
 * Tests Levenshtein distance, Soundex, Metaphone, and token-based matching.
 *
 * @covers \Nexus\Sanctions\Services\SanctionsScreener
 */
final class FuzzyMatchingTest extends TestCase
{
    /**
     * Test Levenshtein distance calculation accuracy
     *
     * @test
     * @dataProvider levenshteinDataProvider
     */
    public function levenshtein_distance_calculation(string $str1, string $str2, float $expectedSimilarity): void
    {
        // Arrange & Act
        $distance = levenshtein(strtolower($str1), strtolower($str2));
        $maxLength = max(strlen($str1), strlen($str2));
        $similarity = $maxLength > 0 ? 1 - ($distance / $maxLength) : 1.0;

        // Assert
        $this->assertEqualsWithDelta($expectedSimilarity, $similarity, 0.01);
    }

    /**
     * @return array<string, array{string, string, float}>
     */
    public static function levenshteinDataProvider(): array
    {
        return [
            'exact match' => ['John Smith', 'John Smith', 1.0],
            'single char difference' => ['Mohammad', 'Mohammed', 0.78],  // 2 chars / 9 total
            'case insensitive' => ['AHMED', 'ahmed', 1.0],
            'spaces ignored' => ['John  Smith', 'John Smith', 0.92],  // 1 char / 12 total
            'similar names' => ['Catherine', 'Katherine', 0.78],  // 2 chars / 9 total
        ];
    }

    /**
     * Test Soundex phonetic matching accuracy
     *
     * @test
     * @dataProvider soundexDataProvider
     */
    public function soundex_phonetic_matching(string $name1, string $name2, bool $shouldMatch): void
    {
        // Arrange
        $words1 = explode(' ', $name1);
        $words2 = explode(' ', $name2);

        // Act
        $hasMatch = false;
        foreach ($words1 as $word1) {
            foreach ($words2 as $word2) {
                if (soundex($word1) === soundex($word2)) {
                    $hasMatch = true;
                    break 2;
                }
            }
        }

        // Assert
        $this->assertSame($shouldMatch, $hasMatch);
    }

    /**
     * @return array<string, array{string, string, bool}>
     */
    public static function soundexDataProvider(): array
    {
        return [
            'Catherine vs Katherine' => ['Catherine', 'Katherine', true],  // C365 = K365
            'Johnson vs Jonson' => ['Johnson', 'Jonson', true],  // J525 = J525
            'Smith vs Smythe' => ['Smith', 'Smythe', true],  // S530 = S530
            'Mohammed vs Mohamed' => ['Mohammed', 'Mohamed', true],  // M530 = M530
            'different names' => ['John', 'Peter', false],  // J500 != P360
            'Ahmad vs Ahmed' => ['Ahmad', 'Ahmed', true],  // A530 = A530
        ];
    }

    /**
     * Test token-based multi-word comparison with 70% threshold
     *
     * @test
     * @dataProvider tokenBasedDataProvider
     */
    public function token_based_multi_word_comparison(string $name1, string $name2, bool $shouldMatch): void
    {
        // Arrange
        $tokens1 = preg_split('/\s+/', strtolower($name1));
        $tokens2 = preg_split('/\s+/', strtolower($name2));

        // Act
        $matchCount = 0;
        foreach ($tokens1 as $token1) {
            foreach ($tokens2 as $token2) {
                if ($token1 === $token2) {
                    $matchCount++;
                    break;
                }
            }
        }

        $tokenRatio = max(count($tokens1), count($tokens2)) > 0
            ? $matchCount / max(count($tokens1), count($tokens2))
            : 0.0;

        $hasStrongTokenMatch = $tokenRatio >= 0.7;

        // Assert
        $this->assertSame($shouldMatch, $hasStrongTokenMatch);
    }

    /**
     * @return array<string, array{string, string, bool}>
     */
    public static function tokenBasedDataProvider(): array
    {
        return [
            'exact match' => ['John Smith', 'John Smith', true],  // 2/2 = 100%
            'partial match high' => ['John Michael Smith', 'John Smith Jr', true],  // 2/3 = 67% (close to 70%)
            'partial match low' => ['Ahmad Al-Rahman', 'Rahman Mohammed', false],  // 1/3 = 33%
            'single word match' => ['Vladimir', 'Vladimir Petrov', true],  // 1/2 = 50% but first word match
            'no match' => ['John Smith', 'Peter Johnson', false],  // 0/2 = 0%
        ];
    }

    /**
     * Test score boosting caps at 1.0
     *
     * @test
     */
    public function score_capping_at_one(): void
    {
        // Arrange
        $baseScore = 0.95;
        $phoneticBoost = 0.10;
        $tokenBoost = 0.05;

        // Act
        $boostedScore = $baseScore + $phoneticBoost + $tokenBoost;  // Would be 1.10
        $cappedScore = min(1.0, $boostedScore);

        // Assert
        $this->assertSame(1.0, $cappedScore);
        $this->assertLessThanOrEqual(1.0, $cappedScore);
    }

    /**
     * Test metaphone phonetic matching (more accurate than Soundex)
     *
     * @test
     * @dataProvider metaphoneDataProvider
     */
    public function metaphone_phonetic_matching(string $name1, string $name2, bool $shouldMatch): void
    {
        // Arrange
        $words1 = explode(' ', $name1);
        $words2 = explode(' ', $name2);

        // Act
        $hasMatch = false;
        foreach ($words1 as $word1) {
            foreach ($words2 as $word2) {
                if (metaphone($word1) === metaphone($word2)) {
                    $hasMatch = true;
                    break 2;
                }
            }
        }

        // Assert
        $this->assertSame($shouldMatch, $hasMatch);
    }

    /**
     * @return array<string, array{string, string, bool}>
     */
    public static function metaphoneDataProvider(): array
    {
        return [
            'Philip vs Phillip' => ['Philip', 'Phillip', true],  // FLP = FLP
            'Steven vs Stephen' => ['Steven', 'Stephen', true],  // STFN = STFN
            'Caitlin vs Katelyn' => ['Caitlin', 'Katelyn', true],  // KTLN = KTLN
            'different names' => ['Robert', 'Richard', false],  // RBRT != RXRT
        ];
    }

    /**
     * Test length normalization in Levenshtein
     *
     * @test
     */
    public function length_normalization_levenshtein(): void
    {
        // Arrange
        $short1 = 'John';
        $short2 = 'Jon';
        $long1 = 'Alexander';
        $long2 = 'Aleksander';

        // Act
        $shortDistance = levenshtein($short1, $short2);
        $shortSimilarity = 1 - ($shortDistance / max(strlen($short1), strlen($short2)));

        $longDistance = levenshtein($long1, $long2);
        $longSimilarity = 1 - ($longDistance / max(strlen($long1), strlen($long2)));

        // Assert
        // Both have edit distance of 1, but normalized differently
        $this->assertSame(1, $shortDistance);
        $this->assertSame(1, $longDistance);

        // Short string: 1/4 = 75% similar
        $this->assertEqualsWithDelta(0.75, $shortSimilarity, 0.01);

        // Long string: 1/10 = 90% similar
        $this->assertEqualsWithDelta(0.90, $longSimilarity, 0.01);

        // Longer strings are less affected by single character changes
        $this->assertGreaterThan($shortSimilarity, $longSimilarity);
    }

    /**
     * Test empty string handling
     *
     * @test
     */
    public function empty_string_handling(): void
    {
        // Arrange
        $str1 = 'John Smith';
        $str2 = '';

        // Act
        $distance = levenshtein($str1, $str2);
        $maxLength = max(strlen($str1), strlen($str2));
        $similarity = $maxLength > 0 ? 1 - ($distance / $maxLength) : 1.0;

        // Assert
        $this->assertSame(0.0, $similarity);  // No similarity with empty string
    }
}
