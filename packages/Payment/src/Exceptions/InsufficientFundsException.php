<?php

declare(strict_types=1);

namespace Nexus\Payment\Exceptions;

use Nexus\Common\ValueObjects\Money;

/**
 * Thrown when there are insufficient funds for a payment.
 */
final class InsufficientFundsException extends PaymentException
{
    public function __construct(
        private readonly Money $requiredAmount,
        private readonly Money $availableAmount,
        ?string $accountId = null,
        ?\Throwable $previous = null,
    ) {
        $message = sprintf(
            'Insufficient funds: required %s %s, available %s %s',
            $this->requiredAmount->getAmount(),
            $this->requiredAmount->getCurrency(),
            $this->availableAmount->getAmount(),
            $this->availableAmount->getCurrency(),
        );

        if ($accountId !== null) {
            $message = sprintf('[Account: %s] %s', $accountId, $message);
        }

        parent::__construct(
            $message,
            402,
            $previous,
            [
                'required_amount' => $this->requiredAmount->getAmount(),
                'available_amount' => $this->availableAmount->getAmount(),
                'currency' => $this->requiredAmount->getCurrency(),
                'account_id' => $accountId,
                'shortfall' => $this->requiredAmount->subtract($this->availableAmount)->getAmount(),
            ],
        );
    }

    /**
     * Get the required amount.
     */
    public function getRequiredAmount(): Money
    {
        return $this->requiredAmount;
    }

    /**
     * Get the available amount.
     */
    public function getAvailableAmount(): Money
    {
        return $this->availableAmount;
    }

    /**
     * Get the shortfall amount.
     */
    public function getShortfall(): Money
    {
        return $this->requiredAmount->subtract($this->availableAmount);
    }
}
