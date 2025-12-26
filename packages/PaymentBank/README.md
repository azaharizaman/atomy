# Nexus PaymentBank

**Version:** 0.1.0
**Status:** In Progress (see IMPLEMENTATION_SUMMARY.md for details)
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

public function connect(BankConnectionManagerInterface $manager)
{
    // 1. Initiate connection flow
    $result = $manager->initiateConnection(
        providerName: 'plaid',
        tenantId: 'tenant-1',
        parameters: [
            'redirect_url' => 'https://app.example.com/callback',
            'scopes' => ['transactions', 'auth']
        ]
    );

    // Redirect user to the authorization URL from $result
    
    // 2. Complete connection after callback
    $connection = $manager->completeConnection(
        providerName: 'plaid',
        tenantId: 'tenant-1',
        callbackData: [
            'public_token' => 'public-token-from-callback',
            'institution_id' => 'ins_123'
        ]
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
    $verificationResult = $service->verifyOwnership($connectionId, $accountId, [
        'name' => 'John Doe',
        'email' => 'john@example.com'
    ]);
    
    // Micro-deposit verification
    $verificationId = $service->initiateMicroDeposits($connectionId, $accountId);
    
    // ... later ...
    $verified = $service->verifyMicroDeposits($connectionId, $verificationId, [0.12, 0.45]);
}
```

### 4. Initiating Payments

Use `PaymentInitiationServiceInterface` for PIS (Payment Initiation Services).

```php
use Nexus\PaymentBank\Contracts\PaymentInitiationServiceInterface;
use Nexus\PaymentBank\ValueObjects\Beneficiary;
use Nexus\Common\ValueObjects\Money;

public function pay(PaymentInitiationServiceInterface $service, string $connectionId, string $sourceAccountId)
{
    $beneficiary = new Beneficiary(
        name: 'Acme Corp',
        iban: 'GB82WEST12345698765432',
        bic: 'WESTGB21XXX',
        address: '123 Business St, London'
    );

    $result = $service->initiatePayment(
        connectionId: $connectionId,
        sourceAccountId: $sourceAccountId,
        beneficiary: $beneficiary,
        amount: Money::of(100, 'USD'),
        reference: 'INV-001'
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
