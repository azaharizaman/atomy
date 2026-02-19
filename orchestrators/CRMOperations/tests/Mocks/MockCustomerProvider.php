<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\Tests\Mocks;

use Nexus\CRMOperations\Contracts\CustomerProviderInterface;

/**
 * Mock implementation of CustomerProviderInterface for testing
 * 
 * @package Nexus\CRMOperations\Tests\Mocks
 */
final class MockCustomerProvider implements CustomerProviderInterface
{
    private array $customers = [];
    private array $links = [];

    public function findById(string $id): ?array
    {
        return $this->customers[$id] ?? null;
    }

    public function findByEmail(string $email): ?array
    {
        foreach ($this->customers as $customer) {
            if (($customer['email'] ?? '') === $email) {
                return $customer;
            }
        }
        return null;
    }

    public function createFromLead(array $leadData): string
    {
        $id = 'customer_' . uniqid();
        $this->customers[$id] = array_merge($leadData, ['id' => $id]);
        return $id;
    }

    public function update(string $customerId, array $data): void
    {
        if (isset($this->customers[$customerId])) {
            $this->customers[$customerId] = array_merge($this->customers[$customerId], $data);
        }
    }

    public function exists(string $customerId): bool
    {
        return isset($this->customers[$customerId]);
    }

    public function linkToOpportunity(string $customerId, string $opportunityId): void
    {
        $this->links[$opportunityId] = $customerId;
    }

    public function getCommunicationPreferences(string $customerId): array
    {
        return [
            'email' => true,
            'sms' => false,
            'push' => true,
        ];
    }

    // Helper method for testing
    public function addCustomer(string $id, array $data): void
    {
        $this->customers[$id] = array_merge(['id' => $id], $data);
    }
}
