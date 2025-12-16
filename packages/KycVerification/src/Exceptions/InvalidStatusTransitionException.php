<?php

declare(strict_types=1);

namespace Nexus\KycVerification\Exceptions;

use Nexus\KycVerification\Enums\VerificationStatus;

/**
 * Exception thrown when an invalid verification status transition is attempted.
 */
class InvalidStatusTransitionException extends KycVerificationException
{
    private VerificationStatus $fromStatus;

    private VerificationStatus $toStatus;

    private string $verificationId;

    public function __construct(
        string $verificationId,
        VerificationStatus $fromStatus,
        VerificationStatus $toStatus,
        ?\Throwable $previous = null
    ) {
        $this->verificationId = $verificationId;
        $this->fromStatus = $fromStatus;
        $this->toStatus = $toStatus;

        $allowedTransitions = $fromStatus->allowedTransitions();
        $allowedStr = empty($allowedTransitions)
            ? 'none (terminal state)'
            : implode(', ', array_map(fn(VerificationStatus $s) => $s->value, $allowedTransitions));

        parent::__construct(
            sprintf(
                'Cannot transition verification %s from %s to %s. Allowed transitions: %s',
                $verificationId,
                $fromStatus->value,
                $toStatus->value,
                $allowedStr
            ),
            0,
            $previous
        );
    }

    public function getVerificationId(): string
    {
        return $this->verificationId;
    }

    public function getFromStatus(): VerificationStatus
    {
        return $this->fromStatus;
    }

    public function getToStatus(): VerificationStatus
    {
        return $this->toStatus;
    }
}
