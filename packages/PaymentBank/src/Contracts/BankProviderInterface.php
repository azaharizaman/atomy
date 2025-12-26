<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Contracts;

use Nexus\PaymentBank\DTOs\ConnectionResult;
use Nexus\PaymentBank\DTOs\Institution;
use Nexus\PaymentBank\Enums\ProviderType;

interface BankProviderInterface
{
    /**
     * Get the provider type identifier.
     */
    public function getProviderType(): ProviderType;

    /**
     * Generate a link token or authorization URL for the frontend widget.
     *
     * @param string $userId The user identifier in the application
     * @param array<string, mixed> $options Provider-specific options (e.g., webhook, language)
     * @return string The link token or URL
     */
    public function generateLinkToken(string $userId, array $options = []): string;

    /**
     * Exchange a public token (from frontend) for an access token.
     *
     * @param string $publicToken The public token received from the frontend
     * @return ConnectionResult The result containing access token and metadata
     */
    public function exchangePublicToken(string $publicToken): ConnectionResult;

    /**
     * Get a list of supported institutions.
     *
     * @param int $limit
     * @param int $offset
     * @param array<string, mixed> $filters
     * @return array<Institution>
     */
    public function listInstitutions(int $limit = 100, int $offset = 0, array $filters = []): array;

    /**
     * Get details for a specific institution.
     *
     * @param string $institutionId
     * @return Institution
     */
    public function getInstitution(string $institutionId): Institution;

    /**
     * Fetch bank statements.
     *
     * @param array $credentials
     * @param \DateTimeImmutable $start
     * @param \DateTimeImmutable $end
     * @return array
     */
    public function fetchStatements(array $credentials, \DateTimeImmutable $start, \DateTimeImmutable $end): array;

    /**
     * Fetch bank transactions.
     *
     * @param array $credentials
     * @param \DateTimeImmutable $start
     * @param \DateTimeImmutable $end
     * @return array
     */
    public function fetchTransactions(array $credentials, \DateTimeImmutable $start, \DateTimeImmutable $end): array;

    /**
     * Get the account data provider for this bank provider.
     *
     * @return AccountDataProviderInterface
     */
    public function getAccountDataProvider(): AccountDataProviderInterface;

    /**
     * Get the payment initiation service for this bank provider.
     *
     * @return PaymentInitiationInterface
     */
    public function getPaymentInitiation(): PaymentInitiationInterface;

    /**
     * Get the account verification service for this bank provider.
     *
     * @return AccountVerificationInterface
     */
    public function getAccountVerification(): AccountVerificationInterface;
}
