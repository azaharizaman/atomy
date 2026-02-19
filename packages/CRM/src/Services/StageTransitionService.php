<?php

declare(strict_types=1);

namespace Nexus\CRM\Services;

use Nexus\CRM\Contracts\OpportunityInterface;
use Nexus\CRM\Contracts\OpportunityPersistInterface;
use Nexus\CRM\Enums\OpportunityStage;
use Nexus\CRM\Exceptions\InvalidStageTransitionException;
use Psr\Log\LoggerInterface;

/**
 * Stage Transition Service
 * 
 * Manages opportunity stage transitions with validation.
 * Pure domain service for stage management.
 * 
 * @package Nexus\CRM\Services
 * @author Azahari Zaman <azaharizaman@gmail.com>
 */
final readonly class StageTransitionService
{
    /**
     * @param OpportunityPersistInterface $persist Persistence service
     * @param LoggerInterface|null $logger Optional logger
     */
    public function __construct(
        private OpportunityPersistInterface $persist,
        private ?LoggerInterface $logger = null
    ) {}

    /**
     * Advance opportunity to the next stage
     * 
     * @throws InvalidStageTransitionException If transition is not valid
     */
    public function advance(OpportunityInterface $opportunity): OpportunityInterface
    {
        $currentStage = $opportunity->getStage();
        $nextStage = $currentStage->getNextStage();

        if ($nextStage === null) {
            throw InvalidStageTransitionException::opportunityAlreadyClosed($currentStage);
        }

        $this->validateTransition($opportunity, $currentStage, $nextStage);

        $this->logger?->info('Advancing opportunity stage', [
            'opportunity_id' => $opportunity->getId(),
            'from_stage' => $currentStage->value,
            'to_stage' => $nextStage->value,
        ]);

        return $this->persist->moveToStage($opportunity->getId(), $nextStage);
    }

    /**
     * Move opportunity to a specific stage
     * 
     * @throws InvalidStageTransitionException If transition is not valid
     */
    public function moveToStage(
        OpportunityInterface $opportunity,
        OpportunityStage $targetStage
    ): OpportunityInterface {
        $currentStage = $opportunity->getStage();

        $this->validateTransition($opportunity, $currentStage, $targetStage);

        $this->logger?->info('Moving opportunity to stage', [
            'opportunity_id' => $opportunity->getId(),
            'from_stage' => $currentStage->value,
            'to_stage' => $targetStage->value,
        ]);

        return $this->persist->moveToStage($opportunity->getId(), $targetStage);
    }

    /**
     * Mark opportunity as won
     */
    public function markAsWon(
        OpportunityInterface $opportunity,
        ?int $actualValue = null
    ): OpportunityInterface {
        $currentStage = $opportunity->getStage();

        if ($currentStage->isLost()) {
            throw new InvalidStageTransitionException(
                $currentStage,
                OpportunityStage::ClosedWon
            );
        }

        $this->logger?->info('Marking opportunity as won', [
            'opportunity_id' => $opportunity->getId(),
            'previous_stage' => $currentStage->value,
            'actual_value' => $actualValue,
        ]);

        return $this->persist->markAsWon($opportunity->getId(), $actualValue);
    }

    /**
     * Mark opportunity as lost
     */
    public function markAsLost(
        OpportunityInterface $opportunity,
        string $reason
    ): OpportunityInterface {
        $currentStage = $opportunity->getStage();

        if ($currentStage->isWon()) {
            throw new InvalidStageTransitionException(
                $currentStage,
                OpportunityStage::ClosedLost
            );
        }

        $this->logger?->info('Marking opportunity as lost', [
            'opportunity_id' => $opportunity->getId(),
            'previous_stage' => $currentStage->value,
            'reason' => $reason,
        ]);

        return $this->persist->markAsLost($opportunity->getId(), $reason);
    }

    /**
     * Reopen a closed opportunity
     */
    public function reopen(
        OpportunityInterface $opportunity,
        OpportunityStage $stage
    ): OpportunityInterface {
        $currentStage = $opportunity->getStage();

        if (!$currentStage->isFinal()) {
            throw new \LogicException(
                sprintf('Opportunity %s is not closed and cannot be reopened', $opportunity->getId())
            );
        }

        if (!$stage->isOpen()) {
            throw new \InvalidArgumentException(
                'Cannot reopen to a closed stage. Specify an open stage.'
            );
        }

        $this->logger?->info('Reopening opportunity', [
            'opportunity_id' => $opportunity->getId(),
            'previous_stage' => $currentStage->value,
            'new_stage' => $stage->value,
        ]);

        return $this->persist->reopen($opportunity->getId(), $stage);
    }

    /**
     * Validate stage transition
     * 
     * @throws InvalidStageTransitionException
     */
    private function validateTransition(
        OpportunityInterface $opportunity,
        OpportunityStage $from,
        OpportunityStage $to
    ): void {
        // Cannot transition from closed won
        if ($from->isWon() && !$to->isLost()) {
            throw InvalidStageTransitionException::opportunityAlreadyClosed($from);
        }

        // Cannot transition from closed lost
        if ($from->isLost() && !$to->isWon()) {
            throw InvalidStageTransitionException::opportunityAlreadyClosed($from);
        }

        // Cannot skip stages forward (must go sequentially)
        if ($to->isOpen() && $to->getPosition() > $from->getPosition() + 1) {
            throw InvalidStageTransitionException::cannotSkipStages($from, $to);
        }

        // Cannot go backwards without reopening
        if ($from->isOpen() && $to->isOpen() && $to->getPosition() < $from->getPosition()) {
            throw InvalidStageTransitionException::cannotGoBackwards($from, $to);
        }
    }

    /**
     * Check if opportunity can be advanced
     */
    public function canAdvance(OpportunityInterface $opportunity): bool
    {
        $stage = $opportunity->getStage();
        return $stage->canAdvance();
    }

    /**
     * Get valid next stages for an opportunity
     * 
     * @return OpportunityStage[]
     */
    public function getValidNextStages(OpportunityInterface $opportunity): array
    {
        $currentStage = $opportunity->getStage();

        if ($currentStage->isFinal()) {
            return [];
        }

        $validStages = [];

        // Can advance to next stage
        if ($currentStage->getNextStage() !== null) {
            $validStages[] = $currentStage->getNextStage();
        }

        // Can always close as won or lost from any open stage
        $validStages[] = OpportunityStage::ClosedWon;
        $validStages[] = OpportunityStage::ClosedLost;

        return $validStages;
    }
}