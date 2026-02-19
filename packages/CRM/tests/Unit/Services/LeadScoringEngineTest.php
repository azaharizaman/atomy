<?php

declare(strict_types=1);

namespace Nexus\CRM\Tests\Unit\Services;

use Nexus\CRM\Contracts\LeadInterface;
use Nexus\CRM\Enums\LeadSource;
use Nexus\CRM\Services\LeadScoringEngine;
use Nexus\CRM\ValueObjects\LeadScore;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class LeadScoringEngineTest extends TestCase
{
    private LeadInterface&MockObject $leadMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->leadMock = $this->createMock(LeadInterface::class);
    }

    #[Test]
    public function it_creates_with_default_weights(): void
    {
        $engine = new LeadScoringEngine();

        $weights = $engine->getWeights();

        $this->assertSame([
            'source_quality' => 15,
            'engagement' => 20,
            'fit' => 25,
            'timing' => 15,
            'budget' => 25,
        ], $weights);
    }

    #[Test]
    public function it_creates_with_custom_weights(): void
    {
        $customWeights = [
            'source_quality' => 10,
            'engagement' => 30,
            'fit' => 20,
            'timing' => 20,
            'budget' => 20,
        ];

        $engine = new LeadScoringEngine($customWeights);

        $this->assertSame($customWeights, $engine->getWeights());
    }

    #[Test]
    public function it_calculates_score_for_lead(): void
    {
        $this->leadMock->method('getSource')->willReturn(LeadSource::Website);
        $this->leadMock->method('getId')->willReturn('lead-123');
        $this->leadMock->method('getEstimatedValue')->willReturn(null);

        $engine = new LeadScoringEngine();
        $score = $engine->calculateScore($this->leadMock);

        $this->assertInstanceOf(LeadScore::class, $score);
        $this->assertGreaterThanOrEqual(0, $score->getValue());
        $this->assertLessThanOrEqual(100, $score->getValue());
    }

    #[Test]
    public function it_calculates_source_quality_score_for_relationship_category(): void
    {
        // Referral and Partner are Relationship category
        $this->leadMock->method('getSource')->willReturn(LeadSource::Referral);
        $this->leadMock->method('getId')->willReturn('lead-123');
        $this->leadMock->method('getEstimatedValue')->willReturn(null);

        $engine = new LeadScoringEngine();
        $score = $engine->calculateScore($this->leadMock);

        // Relationship category should give 90 for source_quality factor
        $this->assertSame(90, $score->getFactor('source_quality'));
    }

    #[Test]
    #[DataProvider('sourceCategoryProvider')]
    public function it_calculates_correct_source_quality_scores(LeadSource $source, int $expectedScore): void
    {
        $this->leadMock->method('getSource')->willReturn($source);
        $this->leadMock->method('getId')->willReturn('lead-123');
        $this->leadMock->method('getEstimatedValue')->willReturn(null);

        $engine = new LeadScoringEngine();
        $score = $engine->calculateScore($this->leadMock);

        $this->assertSame($expectedScore, $score->getFactor('source_quality'));
    }

    public static function sourceCategoryProvider(): array
    {
        return [
            'relationship category - referral' => [LeadSource::Referral, 90],
            'relationship category - partner' => [LeadSource::Partner, 90],
            'organic category - website' => [LeadSource::Website, 70],
            'organic category - organic search' => [LeadSource::OrganicSearch, 70],
            'organic category - direct inquiry' => [LeadSource::DirectInquiry, 70],
            'social category' => [LeadSource::SocialMedia, 50],
            'outbound category - cold outreach' => [LeadSource::ColdOutreach, 40],
            'outbound category - email campaign' => [LeadSource::EmailCampaign, 40],
            'paid category - paid ads' => [LeadSource::PaidAds, 30],
            'paid category - trade show' => [LeadSource::TradeShow, 30],
            'unknown category - other' => [LeadSource::Other, 20],
        ];
    }

    #[Test]
    public function it_calculates_engagement_score_from_context(): void
    {
        $this->leadMock->method('getSource')->willReturn(LeadSource::Website);
        $this->leadMock->method('getId')->willReturn('lead-123');
        $this->leadMock->method('getEstimatedValue')->willReturn(null);

        $context = [
            'engagement' => [
                'email_opens' => 2,      // 2 * 5 = 10
                'email_clicks' => 3,     // 3 * 10 = 30
                'website_visits' => 5,   // 5 * 3 = 15
                'content_downloads' => 1, // 1 * 15 = 15
                'form_submissions' => 1,  // 1 * 20 = 20
            ],
        ];

        $engine = new LeadScoringEngine();
        $score = $engine->calculateScore($this->leadMock, $context);

        // Total: 10 + 30 + 15 + 15 + 20 = 90
        $this->assertSame(90, $score->getFactor('engagement'));
    }

    #[Test]
    public function it_caps_engagement_score_at_100(): void
    {
        $this->leadMock->method('getSource')->willReturn(LeadSource::Website);
        $this->leadMock->method('getId')->willReturn('lead-123');
        $this->leadMock->method('getEstimatedValue')->willReturn(null);

        $context = [
            'engagement' => [
                'email_opens' => 10,      // 10 * 5 = 50
                'email_clicks' => 10,     // 10 * 10 = 100
            ],
        ];

        $engine = new LeadScoringEngine();
        $score = $engine->calculateScore($this->leadMock, $context);

        // Would be 150, but capped at 100
        $this->assertSame(100, $score->getFactor('engagement'));
    }

    #[Test]
    public function it_calculates_fit_score_from_context(): void
    {
        $this->leadMock->method('getSource')->willReturn(LeadSource::Website);
        $this->leadMock->method('getId')->willReturn('lead-123');
        $this->leadMock->method('getEstimatedValue')->willReturn(null);

        $context = [
            'fit' => [
                'industry_match' => true,      // 25
                'company_size_match' => true,  // 25
                'location_match' => true,      // 15
                'role_match' => true,          // 20
                'has_decision_maker' => true,  // 15
            ],
        ];

        $engine = new LeadScoringEngine();
        $score = $engine->calculateScore($this->leadMock, $context);

        // Total: 25 + 25 + 15 + 20 + 15 = 100
        $this->assertSame(100, $score->getFactor('fit'));
    }

    #[Test]
    public function it_calculates_fit_score_with_partial_matches(): void
    {
        $this->leadMock->method('getSource')->willReturn(LeadSource::Website);
        $this->leadMock->method('getId')->willReturn('lead-123');
        $this->leadMock->method('getEstimatedValue')->willReturn(null);

        $context = [
            'fit' => [
                'industry_match' => true,      // 25
                'company_size_match' => false, // 0
                'location_match' => true,      // 15
                'role_match' => false,         // 0
                'has_decision_maker' => true,  // 15
            ],
        ];

        $engine = new LeadScoringEngine();
        $score = $engine->calculateScore($this->leadMock, $context);

        // Total: 25 + 0 + 15 + 0 + 15 = 55
        $this->assertSame(55, $score->getFactor('fit'));
    }

    #[Test]
    public function it_calculates_timing_score_from_context(): void
    {
        $this->leadMock->method('getSource')->willReturn(LeadSource::Website);
        $this->leadMock->method('getId')->willReturn('lead-123');
        $this->leadMock->method('getEstimatedValue')->willReturn(null);

        $context = [
            'timing' => [
                'has_deadline' => true,            // +30
                'is_urgent' => true,               // +20
                'has_budget_this_quarter' => true, // +15
            ],
        ];

        $engine = new LeadScoringEngine();
        $score = $engine->calculateScore($this->leadMock, $context);

        // Base 50 + 30 + 20 + 15 = 115, capped at 100
        $this->assertSame(100, $score->getFactor('timing'));
    }

    #[Test]
    public function it_calculates_timing_score_with_base_only(): void
    {
        $this->leadMock->method('getSource')->willReturn(LeadSource::Website);
        $this->leadMock->method('getId')->willReturn('lead-123');
        $this->leadMock->method('getEstimatedValue')->willReturn(null);

        $context = [
            'timing' => [],
        ];

        $engine = new LeadScoringEngine();
        $score = $engine->calculateScore($this->leadMock, $context);

        // Base score only: 50
        $this->assertSame(50, $score->getFactor('timing'));
    }

    #[Test]
    #[DataProvider('budgetValueProvider')]
    public function it_calculates_budget_score_from_estimated_value(?int $estimatedValue, int $expectedBaseScore): void
    {
        $this->leadMock->method('getSource')->willReturn(LeadSource::Website);
        $this->leadMock->method('getId')->willReturn('lead-123');
        $this->leadMock->method('getEstimatedValue')->willReturn($estimatedValue);

        $engine = new LeadScoringEngine();
        $score = $engine->calculateScore($this->leadMock);

        $this->assertSame($expectedBaseScore, $score->getFactor('budget'));
    }

    public static function budgetValueProvider(): array
    {
        return [
            'no estimated value' => [null, 0],
            'low value (< 10000)' => [5000, 10],
            'medium value (10000-49999)' => [25000, 20],
            'high value (50000-99999)' => [75000, 30],
            'enterprise value (>= 100000)' => [150000, 40],
        ];
    }

    #[Test]
    public function it_calculates_budget_score_with_budget_indicators(): void
    {
        $this->leadMock->method('getSource')->willReturn(LeadSource::Website);
        $this->leadMock->method('getId')->willReturn('lead-123');
        $this->leadMock->method('getEstimatedValue')->willReturn(50000);

        $context = [
            'budget' => [
                'has_budget' => true,       // +30
                'budget_approved' => true,  // +30
            ],
        ];

        $engine = new LeadScoringEngine();
        $score = $engine->calculateScore($this->leadMock, $context);

        // 30 (from value) + 30 + 30 = 90
        $this->assertSame(90, $score->getFactor('budget'));
    }

    #[Test]
    public function it_applies_weights_to_factors(): void
    {
        $this->leadMock->method('getSource')->willReturn(LeadSource::Referral); // Relationship = 90
        $this->leadMock->method('getId')->willReturn('lead-123');
        $this->leadMock->method('getEstimatedValue')->willReturn(100000); // 40

        $context = [
            'engagement' => [
                'email_clicks' => 10, // 100 (capped)
            ],
            'fit' => [
                'industry_match' => true,
                'company_size_match' => true,
                'location_match' => true,
                'role_match' => true,
                'has_decision_maker' => true,
            ], // 100
            'timing' => [
                'has_deadline' => true,
                'is_urgent' => true,
                'has_budget_this_quarter' => true,
            ], // 100
            'budget' => [
                'has_budget' => true,
                'budget_approved' => true,
            ], // 100 (40 + 30 + 30)
        ];

        $engine = new LeadScoringEngine();
        $score = $engine->calculateScore($this->leadMock, $context);

        // Weighted average:
        // (90*15 + 100*20 + 100*25 + 100*15 + 100*25) / 100
        // = (1350 + 2000 + 2500 + 1500 + 2500) / 100
        // = 9850 / 100 = 98.5 ≈ 99
        $this->assertSame(99, $score->getValue());
    }

    #[Test]
    public function it_logs_calculation_when_logger_provided(): void
    {
        $this->leadMock->method('getSource')->willReturn(LeadSource::Website);
        $this->leadMock->method('getId')->willReturn('lead-123');
        $this->leadMock->method('getEstimatedValue')->willReturn(null);

        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())
            ->method('debug')
            ->with(
                'Lead score calculated',
                $this->callback(function (array $context) {
                    return isset($context['lead_id'])
                        && $context['lead_id'] === 'lead-123'
                        && isset($context['factors'])
                        && isset($context['weighted_score']);
                })
            );

        $engine = new LeadScoringEngine(self::getDefaultWeights(), $loggerMock);
        $engine->calculateScore($this->leadMock);
    }

    #[Test]
    public function it_creates_new_engine_with_custom_weights(): void
    {
        $engine = new LeadScoringEngine();
        $newWeights = [
            'source_quality' => 50,
            'engagement' => 10,
            'fit' => 10,
            'timing' => 10,
            'budget' => 20,
        ];

        $newEngine = $engine->withWeights($newWeights);

        $this->assertNotSame($engine, $newEngine);
        $this->assertSame($newWeights, $newEngine->getWeights());
    }

    #[Test]
    public function it_handles_empty_context(): void
    {
        $this->leadMock->method('getSource')->willReturn(LeadSource::Website);
        $this->leadMock->method('getId')->willReturn('lead-123');
        $this->leadMock->method('getEstimatedValue')->willReturn(null);

        $engine = new LeadScoringEngine();
        $score = $engine->calculateScore($this->leadMock, []);

        $this->assertInstanceOf(LeadScore::class, $score);
        $this->assertGreaterThanOrEqual(0, $score->getValue());
    }

    #[Test]
    public function it_returns_all_factors_in_score(): void
    {
        $this->leadMock->method('getSource')->willReturn(LeadSource::Website);
        $this->leadMock->method('getId')->willReturn('lead-123');
        $this->leadMock->method('getEstimatedValue')->willReturn(null);

        $engine = new LeadScoringEngine();
        $score = $engine->calculateScore($this->leadMock);

        $factors = $score->getFactors();

        $this->assertArrayHasKey('source_quality', $factors);
        $this->assertArrayHasKey('engagement', $factors);
        $this->assertArrayHasKey('fit', $factors);
        $this->assertArrayHasKey('timing', $factors);
        $this->assertArrayHasKey('budget', $factors);
    }

    private static function getDefaultWeights(): array
    {
        return [
            'source_quality' => 15,
            'engagement' => 20,
            'fit' => 25,
            'timing' => 15,
            'budget' => 25,
        ];
    }
}
