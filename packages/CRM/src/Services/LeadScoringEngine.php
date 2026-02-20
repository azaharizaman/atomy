<?php

declare(strict_types=1);

namespace Nexus\CRM\Services;

use Nexus\CRM\Contracts\LeadInterface;
use Nexus\CRM\ValueObjects\LeadScore;
use Psr\Log\LoggerInterface;

/**
 * Lead Scoring Engine
 * 
 * Calculates lead quality scores based on configurable factors.
 * Pure domain service with no external dependencies.
 * 
 * @package Nexus\CRM\Services
 * @author Azahari Zaman <azaharizaman@gmail.com>
 */
final readonly class LeadScoringEngine
{
    /**
     * Default scoring weights
     */
    private const DEFAULT_WEIGHTS = [
        'source_quality' => 15,
        'engagement' => 20,
        'fit' => 25,
        'timing' => 15,
        'budget' => 25,
    ];

    /**
     * @param array<string, int> $weights Custom scoring weights
     * @param LoggerInterface|null $logger Optional logger for debugging
     */
    public function __construct(
        private array $weights = self::DEFAULT_WEIGHTS,
        private ?LoggerInterface $logger = null
    ) {}

    /**
     * Calculate lead score
     */
    public function calculateScore(LeadInterface $lead, array $context = []): LeadScore
    {
        $factors = [];

        // Calculate source quality score
        $factors['source_quality'] = $this->calculateSourceQualityScore($lead);

        // Calculate engagement score from context
        $factors['engagement'] = $this->calculateEngagementScore($context);

        // Calculate fit score from context
        $factors['fit'] = $this->calculateFitScore($context);

        // Calculate timing score from context
        $factors['timing'] = $this->calculateTimingScore($context);

        // Calculate budget score from context
        $factors['budget'] = $this->calculateBudgetScore($lead, $context);

        // Apply weights to factors
        $weightedScore = $this->applyWeights($factors);

        $this->logger?->debug('Lead score calculated', [
            'lead_id' => $lead->getId(),
            'factors' => $factors,
            'weighted_score' => $weightedScore,
        ]);

        return new LeadScore($weightedScore, $factors);
    }

    /**
     * Calculate source quality score (0-100)
     */
    private function calculateSourceQualityScore(LeadInterface $lead): int
    {
        return match ($lead->getSource()->getCategory()) {
            'Relationship' => 90, // Referrals and partners are highest quality
            'Organic' => 70,      // Organic leads are good quality
            'Social' => 50,       // Social media leads are medium quality
            'Outbound' => 40,     // Outbound leads need more qualification
            'Paid' => 30,         // Paid leads need careful qualification
            default => 20,        // Unknown sources
        };
    }

    /**
     * Calculate engagement score from context (0-100)
     */
    private function calculateEngagementScore(array $context): int
    {
        $engagement = $context['engagement'] ?? [];

        $score = 0;
        $score += ($engagement['email_opens'] ?? 0) * 5;
        $score += ($engagement['email_clicks'] ?? 0) * 10;
        $score += ($engagement['website_visits'] ?? 0) * 3;
        $score += ($engagement['content_downloads'] ?? 0) * 15;
        $score += ($engagement['form_submissions'] ?? 0) * 20;

        return min(100, $score);
    }

    /**
     * Calculate fit score from context (0-100)
     */
    private function calculateFitScore(array $context): int
    {
        $fit = $context['fit'] ?? [];

        $score = 0;
        $score += ($fit['industry_match'] ?? false) ? 25 : 0;
        $score += ($fit['company_size_match'] ?? false) ? 25 : 0;
        $score += ($fit['location_match'] ?? false) ? 15 : 0;
        $score += ($fit['role_match'] ?? false) ? 20 : 0;
        $score += ($fit['has_decision_maker'] ?? false) ? 15 : 0;

        return min(100, $score);
    }

    /**
     * Calculate timing score from context (0-100)
     */
    private function calculateTimingScore(array $context): int
    {
        $timing = $context['timing'] ?? [];

        $score = 50; // Base score

        if ($timing['has_deadline'] ?? false) {
            $score += 30;
        }

        if ($timing['is_urgent'] ?? false) {
            $score += 20;
        }

        if ($timing['has_budget_this_quarter'] ?? false) {
            $score += 15;
        }

        return min(100, $score);
    }

    /**
     * Calculate budget score from context (0-100)
     */
    private function calculateBudgetScore(LeadInterface $lead, array $context): int
    {
        $budget = $context['budget'] ?? [];

        $score = 0;

        // Check if estimated value is provided
        if ($lead->getEstimatedValue() !== null) {
            $value = $lead->getEstimatedValue();
            $score += match (true) {
                $value >= 100000 => 40,
                $value >= 50000 => 30,
                $value >= 10000 => 20,
                default => 10,
            };
        }

        // Check budget indicators from context
        if ($budget['has_budget'] ?? false) {
            $score += 30;
        }

        if ($budget['budget_approved'] ?? false) {
            $score += 30;
        }

        return min(100, $score);
    }

    /**
     * Apply weights to factors and calculate final score
     */
    private function applyWeights(array $factors): int
    {
        $totalWeight = array_sum($this->weights);
        $weightedSum = 0;

        foreach ($factors as $factor => $score) {
            $weight = $this->weights[$factor] ?? 0;
            $weightedSum += ($score * $weight);
        }

        return (int) round($weightedSum / $totalWeight);
    }

    /**
     * Get configured weights
     * 
     * @return array<string, int>
     */
    public function getWeights(): array
    {
        return $this->weights;
    }

    /**
     * Create engine with custom weights
     */
    public function withWeights(array $weights): self
    {
        return new self($weights, $this->logger);
    }
}