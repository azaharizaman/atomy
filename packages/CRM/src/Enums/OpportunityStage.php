<?php

declare(strict_types=1);

namespace Nexus\CRM\Enums;

/**
 * Opportunity Stage Enum
 * 
 * Represents the stages an opportunity goes through in the sales pipeline.
 * 
 * @package Nexus\CRM\Enums
 * @author Azahari Zaman <azaharizaman@gmail.com>
 */
enum OpportunityStage: string
{
    case Prospecting = 'prospecting';
    case Qualification = 'qualification';
    case NeedsAnalysis = 'needs_analysis';
    case Proposal = 'proposal';
    case Negotiation = 'negotiation';
    case ClosedWon = 'closed_won';
    case ClosedLost = 'closed_lost';

    /**
     * Get human-readable label for the stage
     */
    public function label(): string
    {
        return match ($this) {
            self::Prospecting => 'Prospecting',
            self::Qualification => 'Qualification',
            self::NeedsAnalysis => 'Needs Analysis',
            self::Proposal => 'Proposal',
            self::Negotiation => 'Negotiation',
            self::ClosedWon => 'Closed Won',
            self::ClosedLost => 'Closed Lost',
        };
    }

    /**
     * Get default probability percentage for this stage
     */
    public function getDefaultProbability(): int
    {
        return match ($this) {
            self::Prospecting => 10,
            self::Qualification => 20,
            self::NeedsAnalysis => 40,
            self::Proposal => 60,
            self::Negotiation => 80,
            self::ClosedWon => 100,
            self::ClosedLost => 0,
        };
    }

    /**
     * Get position/order in the pipeline
     */
    public function getPosition(): int
    {
        return match ($this) {
            self::Prospecting => 1,
            self::Qualification => 2,
            self::NeedsAnalysis => 3,
            self::Proposal => 4,
            self::Negotiation => 5,
            self::ClosedWon => 6,
            self::ClosedLost => 7,
        };
    }

    /**
     * Check if opportunity is open (not closed)
     */
    public function isOpen(): bool
    {
        return !in_array($this, [self::ClosedWon, self::ClosedLost], true);
    }

    /**
     * Check if opportunity is closed won
     */
    public function isWon(): bool
    {
        return $this === self::ClosedWon;
    }

    /**
     * Check if opportunity is closed lost
     */
    public function isLost(): bool
    {
        return $this === self::ClosedLost;
    }

    /**
     * Check if this is a final stage
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::ClosedWon, self::ClosedLost], true);
    }

    /**
     * Get the next stage in the pipeline
     */
    public function getNextStage(): ?self
    {
        return match ($this) {
            self::Prospecting => self::Qualification,
            self::Qualification => self::NeedsAnalysis,
            self::NeedsAnalysis => self::Proposal,
            self::Proposal => self::Negotiation,
            self::Negotiation => self::ClosedWon,
            self::ClosedWon => null,
            self::ClosedLost => null,
        };
    }

    /**
     * Check if can advance to next stage
     */
    public function canAdvance(): bool
    {
        return $this->isOpen() && $this->getNextStage() !== null;
    }

    /**
     * Get all open stages in order
     * 
     * @return OpportunityStage[]
     */
    public static function openStages(): array
    {
        return [
            self::Prospecting,
            self::Qualification,
            self::NeedsAnalysis,
            self::Proposal,
            self::Negotiation,
        ];
    }
}