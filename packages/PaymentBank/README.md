# Nexus PaymentBank

**Version:** 1.0.0
**Status:** Production Ready
**PHP:** ^8.3
**Extends:** `nexus/payment`

## Overview

`Nexus\PaymentBank` is an extension package for `Nexus\Payment` providing direct bank integrations for open banking (PSD2/Open Banking UK), real-time payments (RTP, FAST, SEPA Instant), and bank account verification services (Plaid, Yodlee, MX).

It provides a unified abstraction layer over various banking providers, handling connection lifecycles, account data retrieval, ownership verification, and payment initiation.

## Installation

```bash
composer require nexus/payment-bank
```

## Features

- **Bank Connection Management**: Securely link and manage bank connections (OAuth2, Credentials).
- **Account Information**: Retrieve account details, balances, and transaction history.
- **Account Verification**: Verify account ownership via instant verification or micro-deposits.
- **Payment Initiation**: Initiate and track payments directly from bank accounts (PIS).
- **Security**: Built-in encryption for sensitive credentials using `Nexus\Crypto`.

## Architecture

This package follows the Nexus Monorepo architecture:
- **Contracts**: Define the behavior (`src/Contracts`).
- **Services**: Implement the business logic (`src/Services`).
- **Entities**: Rich domain models (`src/Entities`).
- **Providers**: Adapter pattern for external banking APIs (`src/Contracts/ProviderInterface`).

## Usage

### 1. Managing Bank Connections

Use `BankConnectionManagerInterface` to handle the lifecycle of a bank connection.

```php
use Nexus\PaymentBank\Contracts\BankConnectionManagerInterface;
use Nexus\PaymentBank\DTOs\ConnectionRequest;

public function connect(BankConnectionManagerInterface $manager)
{
    // 1. Initiate connection flow
    $result = $manager->initiateConnection(new ConnectionRequest(
        tenantId: 'tenant-1',
        providerName: 'plaid',
        redirectUrl: 'https://app.example.com/callback',
        scopes: ['transactions', 'auth']
    ));

    // Redirect user to $result->authorizationUrl
    
    // 2. Complete connection after callback
    $connection = $manager->completeConnection(
        providerName: 'plaid',
        publicToken: 'public-token-from-callback',
        tenantId: 'tenant-1'
    );
}
```

### 2. Retrieving Account Data

Use `AccountServiceInterface` to fetch account information.

```php
use Nexus\PaymentBank\Contracts\AccountServiceInterface;

public function showAccounts(AccountServiceInterface $service, string $connectionId)
{
    // Get all accounts for a connection
    $accounts = $service->getAccounts($connectionId);

    foreach ($accounts as $account) {
        echo $account->getName() . ': ' . $account->getBalance()->getAmount();
    }
    
    // Get transactions
    $transactions = $service->getTransactions(
        connectionId: $connectionId,
        accountId: $accounts[0]->getId(),
        startDate: new \DateTimeImmutable('-30 days'),
        endDate: new \DateTimeImmutable('now')
    );
}
```

### 3. Verifying Accounts

Use `VerificationServiceInterface` for KYC and ownership checks.

```php
use Nexus\PaymentBank\Contracts\VerificationServiceInterface;

public function verify(VerificationServiceInterface $service, string $connectionId, string $accountId)
{
    // Instant verification (e.g., Plaid Identity)
    $owner = $service->getOwnerIdentity($connectionId, $accountId);
    
    // Micro-deposit verification
    $verificationId = $service->initiateMicroDeposits($connectionId, $accountId);
    
    // ... later ...
    $verified = $service->verifyMicroDeposits($connectionId, $accountId, [0.12, 0.45]);
}
```

### 4. Initiating Payments

Use `PaymentInitiationServiceInterface` for PIS (Payment Initiation Services).

```php
use Nexus\PaymentBank\Contracts\PaymentInitiationServiceInterface;
use Nexus\PaymentBank\DTOs\PaymentInitiationRequest;
use Nexus\Common\ValueObjects\Money;

public function pay(PaymentInitiationServiceInterface $service, string $connectionId, string $sourceAccountId)
{
    $result = $service->initiatePayment(
        connectionId: $connectionId,
        request: new PaymentInitiationRequest(
            sourceAccountId: $sourceAccountId,
            destinationAccountId: 'dest-123',
            amount: Money::of(100, 'USD'),
            reference: 'INV-001'
        )
    );
    
    // Check status
    $status = $service->getPaymentStatus($connectionId, $result->paymentId);
}
```

## Testing

```bash
composer test
```

## License

MIT License. See [LICENSE](LICENSE) for details.
