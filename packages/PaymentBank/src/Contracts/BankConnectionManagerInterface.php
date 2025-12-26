<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Contracts;

use Nexus\PaymentBank\Entities\BankConnectionInterface;
use Nexus\PaymentBank\Enums\ConsentStatus;

interface BankConnectionManagerInterface
{
    /**
     * Initiate a new bank connection flow.
     * Returns the authorization URL or data needed to redirect the user.
     *
     * @param string $providerName
     * @param string $tenantId
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    public function initiateConnection(string $providerName, string $tenantId, array $config = []): array;

    /**
     * Complete the connection flow using callback data.
     *
     * @param string $providerName
     * @param string $tenantId
     * @param array<string, mixed> $callbackData
     * @return BankConnectionInterface
     */
    public function completeConnection(string $providerName, string $tenantId, array $callbackData): BankConnectionInterface;

    /**
     * Refresh the connection credentials if supported.
     *
     * @param string $connectionId
     * @return BankConnectionInterface
     */
    public function refreshConnection(string $connectionId): BankConnectionInterface;

    /**
     * Disconnect and revoke access.
     *
     * @param string $connectionId
     * @return void
     */
    public function disconnect(string $connectionId): void;

    /**
     * Update the status of a connection.
     *
     * @param string $connectionId
     * @param ConsentStatus $status
     * @param string|null $errorMessage
     * @return BankConnectionInterface
     */
    public function updateStatus(string $connectionId, ConsentStatus $status, ?string $errorMessage = null): BankConnectionInterface;
}
