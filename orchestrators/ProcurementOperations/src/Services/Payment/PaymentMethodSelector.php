<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Services\Payment;

use Nexus\ProcurementOperations\Contracts\PaymentMethodStrategyInterface;
use Nexus\ProcurementOperations\DTOs\PaymentExecutionResult;
use Nexus\ProcurementOperations\DTOs\PaymentRequest;
use Nexus\ProcurementOperations\Enums\PaymentMethod;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Service for selecting and executing payment methods.
 *
 * Auto-selects the optimal payment method based on:
 * - Vendor's available payment details (bank account, address)
 * - Urgency requirements
 * - International payment needs
 * - Cost optimization (lowest fee with suitable speed)
 */
final readonly class PaymentMethodSelector
{
    /**
     * @param array<PaymentMethodStrategyInterface> $strategies
     */
    public function __construct(
        private array $strategies,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Select the optimal payment method for a request.
     *
     * Selection criteria (in priority order):
     * 1. Must support the request (bank account, international, etc.)
     * 2. Must meet urgency requirements
     * 3. Lowest cost (selection priority)
     */
    public function selectMethod(PaymentRequest $request): ?PaymentMethodStrategyInterface
    {
        $this->logger->debug('Selecting payment method', [
            'vendor_id' => $request->vendorId,
            'amount' => $request->amount->getAmountInCents(),
            'preferred_method' => $request->preferredMethod->value,
            'urgent' => $request->urgent,
            'international' => $request->international,
        ]);

        // First, try the preferred method if it supports the request
        $preferredStrategy = $this->findStrategyByMethod($request->preferredMethod);
        if ($preferredStrategy !== null && $this->isStrategyViable($preferredStrategy, $request)) {
            $this->logger->info('Using preferred payment method', [
                'method' => $preferredStrategy->getMethod(),
            ]);
            return $preferredStrategy;
        }

        // Fall back to auto-selection
        $viableStrategies = $this->findViableStrategies($request);

        if (count($viableStrategies) === 0) {
            $this->logger->warning('No viable payment methods found', [
                'vendor_id' => $request->vendorId,
                'has_bank_account' => $request->hasBankAccount(),
                'has_mailing_address' => $request->hasMailingAddress(),
            ]);
            return null;
        }

        // Sort by selection priority and return best option
        usort($viableStrategies, fn($a, $b) => $a->getSelectionPriority() <=> $b->getSelectionPriority());

        $selected = $viableStrategies[0];

        $this->logger->info('Auto-selected payment method', [
            'method' => $selected->getMethod(),
            'priority' => $selected->getSelectionPriority(),
        ]);

        return $selected;
    }

    /**
     * Execute payment using the selected or auto-selected method.
     */
    public function executePayment(PaymentRequest $request): PaymentExecutionResult
    {
        $strategy = $this->selectMethod($request);

        if ($strategy === null) {
            return PaymentExecutionResult::failure(
                message: 'No suitable payment method available',
                errors: ['No payment strategy supports the given request'],
            );
        }

        $this->logger->info('Executing payment', [
            'method' => $strategy->getMethod(),
            'vendor_id' => $request->vendorId,
            'amount' => $request->amount->getAmountInCents(),
        ]);

        return $strategy->execute($request);
    }

    /**
     * Get all available payment methods for a request.
     *
     * @return array<PaymentMethodStrategyInterface>
     */
    public function getAvailableMethods(PaymentRequest $request): array
    {
        return $this->findViableStrategies($request);
    }

    /**
     * Check if a specific payment method is available for a request.
     */
    public function isMethodAvailable(PaymentMethod $method, PaymentRequest $request): bool
    {
        $strategy = $this->findStrategyByMethod($method);

        if ($strategy === null) {
            return false;
        }

        return $this->isStrategyViable($strategy, $request);
    }

    /**
     * Find strategy by payment method.
     */
    private function findStrategyByMethod(PaymentMethod $method): ?PaymentMethodStrategyInterface
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->getMethod() === $method->value) {
                return $strategy;
            }
        }

        return null;
    }

    /**
     * Find all viable strategies for a request.
     *
     * @return array<PaymentMethodStrategyInterface>
     */
    private function findViableStrategies(PaymentRequest $request): array
    {
        $viable = [];

        foreach ($this->strategies as $strategy) {
            if ($this->isStrategyViable($strategy, $request)) {
                $viable[] = $strategy;
            }
        }

        return $viable;
    }

    /**
     * Check if a strategy is viable for a request.
     */
    private function isStrategyViable(PaymentMethodStrategyInterface $strategy, PaymentRequest $request): bool
    {
        // Must support the request
        if (!$strategy->supports($request)) {
            return false;
        }

        // Must pass validation
        $errors = $strategy->validate($request);
        if (count($errors) > 0) {
            return false;
        }

        // Must support urgency if required
        if ($request->urgent && !$strategy->supportsSameDay()) {
            return false;
        }

        // Must support international if required
        if ($request->international && !$strategy->supportsInternational()) {
            return false;
        }

        return true;
    }
}
