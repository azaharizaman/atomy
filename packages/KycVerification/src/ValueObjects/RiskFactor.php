<?php

declare(strict_types=1);

namespace Nexus\KycVerification\ValueObjects;

/**
 * Represents an individual risk factor contributing to overall risk score.
 * 
 * @immutable
 */
final readonly class RiskFactor
{
    public const string CATEGORY_COUNTRY = 'country';
    public const string CATEGORY_INDUSTRY = 'industry';
    public const string CATEGORY_PRODUCT = 'product';
    public const string CATEGORY_CHANNEL = 'channel';
    public const string CATEGORY_PEP = 'pep';
    public const string CATEGORY_SANCTIONS = 'sanctions';
    public const string CATEGORY_ADVERSE_MEDIA = 'adverse_media';
    public const string CATEGORY_TRANSACTION = 'transaction';
    public const string CATEGORY_STRUCTURE = 'structure';
    public const string CATEGORY_BEHAVIOR = 'behavior';

    public function __construct(
        public string $code,
        public string $category,
        public string $description,
        public int $score,
        public ?string $details = null,
        public ?string $source = null,
        public ?\DateTimeImmutable $identifiedAt = null,
        public bool $canBeMitigated = true,
    ) {}

    /**
     * Check if this is a critical risk factor
     */
    public function isCritical(): bool
    {
        return $this->score >= 20;
    }

    /**
     * Check if factor relates to sanctions
     */
    public function isSanctionsRelated(): bool
    {
        return $this->category === self::CATEGORY_SANCTIONS;
    }

    /**
     * Check if factor relates to PEP status
     */
    public function isPepRelated(): bool
    {
        return $this->category === self::CATEGORY_PEP;
    }

    /**
     * Create a country risk factor
     */
    public static function countryRisk(string $countryCode, int $score, ?string $details = null): self
    {
        return new self(
            code: "COUNTRY_{$countryCode}",
            category: self::CATEGORY_COUNTRY,
            description: "High-risk country: {$countryCode}",
            score: $score,
            details: $details,
            identifiedAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Create a PEP risk factor
     */
    public static function pepStatus(string $position, int $tier = 1): self
    {
        $score = match ($tier) {
            1 => 25, // Domestic PEP
            2 => 20, // Foreign PEP
            3 => 15, // International Organization
            4 => 10, // Family member/close associate
            default => 15,
        };

        return new self(
            code: "PEP_TIER_{$tier}",
            category: self::CATEGORY_PEP,
            description: "Politically Exposed Person",
            score: $score,
            details: $position,
            identifiedAt: new \DateTimeImmutable(),
            canBeMitigated: true,
        );
    }

    /**
     * Create a sanctions risk factor
     */
    public static function sanctionsMatch(string $listName, float $matchScore): self
    {
        return new self(
            code: 'SANCTIONS_MATCH',
            category: self::CATEGORY_SANCTIONS,
            description: "Potential sanctions list match: {$listName}",
            score: (int) min(40, $matchScore * 40),
            details: "Match score: {$matchScore}",
            source: $listName,
            identifiedAt: new \DateTimeImmutable(),
            canBeMitigated: false,
        );
    }

    /**
     * Create an adverse media risk factor
     */
    public static function adverseMedia(string $headline, string $source): self
    {
        return new self(
            code: 'ADVERSE_MEDIA',
            category: self::CATEGORY_ADVERSE_MEDIA,
            description: 'Adverse media coverage identified',
            score: 15,
            details: $headline,
            source: $source,
            identifiedAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Create a complex structure risk factor
     */
    public static function complexStructure(int $layers): self
    {
        $score = min(25, $layers * 5);

        return new self(
            code: 'COMPLEX_STRUCTURE',
            category: self::CATEGORY_STRUCTURE,
            description: "Complex ownership structure with {$layers} layers",
            score: $score,
            details: "{$layers} layers of ownership identified",
            identifiedAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Create an industry risk factor
     */
    public static function industryRisk(string $industry, int $score): self
    {
        return new self(
            code: "INDUSTRY_" . strtoupper(str_replace(' ', '_', $industry)),
            category: self::CATEGORY_INDUSTRY,
            description: "High-risk industry: {$industry}",
            score: $score,
            identifiedAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Convert to array
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'category' => $this->category,
            'description' => $this->description,
            'score' => $this->score,
            'details' => $this->details,
            'source' => $this->source,
            'identified_at' => $this->identifiedAt?->format('c'),
            'is_critical' => $this->isCritical(),
            'can_be_mitigated' => $this->canBeMitigated,
        ];
    }
}
