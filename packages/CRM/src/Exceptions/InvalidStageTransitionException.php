<?php

declare(strict_types=1);

namespace Nexus\CRM\Exceptions;

use Nexus\CRM\Enums\OpportunityStage;

/**
 * Invalid Stage Transition Exception
 * 
 * Thrown when an invalid opportunity stage transition is attempted.
 * 
 * @package Nexus\CRM\Exceptions
 * @author Azahari Zaman <azaharizaman@gmail.com>
 */
class InvalidStageTransitionException extends CRMException
{
    /**
     * @param OpportunityStage $from Current stage
     * @param OpportunityStage $to Attempted target stage
     */
    public function __construct(
        public readonly OpportunityStage $from,
        public readonly OpportunityStage $to
    ) {
        parent::__construct(
            sprintf(
                'Invalid stage transition from %s to %s',
                $from->label(),
                $to->label()
            )
        );
    }

    /**
     * Create exception for forward-only violation
     */
    public static function cannotSkipStages(OpportunityStage $from, OpportunityStage $to): self
    {
        $exception = new self($from, $to);
        $exception->message = sprintf(
            'Cannot skip stages: transition from %s to %s is not allowed. Use sequential stage advancement.',
            $from->label(),
            $to->label()
        );
        return $exception;
    }

    /**
     * Create exception for closed opportunity
     */
    public static function opportunityAlreadyClosed(OpportunityStage $currentStage): self
    {
        $exception = new self($currentStage, $currentStage);
        $exception->message = sprintf(
            'Cannot transition: opportunity is already in final stage %s',
            $currentStage->label()
        );
        return $exception;
    }

    /**
     * Create exception for backward transition
     */
    public static function cannotGoBackwards(OpportunityStage $from, OpportunityStage $to): self
    {
        $exception = new self($from, $to);
        $exception->message = sprintf(
            'Cannot transition backwards from %s to %s. Use reopen functionality instead.',
            $from->label(),
            $to->label()
        );
        return $exception;
    }

    /**
     * Get the current stage
     */
    public function getFromStage(): OpportunityStage
    {
        return $this->from;
    }

    /**
     * Get the attempted target stage
     */
    public function getToStage(): OpportunityStage
    {
        return $this->to;
    }
}