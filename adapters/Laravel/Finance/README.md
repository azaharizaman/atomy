# Nexus Laravel Finance Adapter

Laravel adapter for the Nexus Finance package, providing Eloquent implementations of repository interfaces and service provider bindings.

## Overview

This package bridges the framework-agnostic `nexus/finance` package with Laravel by providing:

- Eloquent Models implementing Finance interfaces
- Eloquent Repository implementations
- Service Provider for dependency injection bindings
- Database migrations for Finance tables

## Installation

```bash
composer require nexus/laravel-finance
```

The package uses Laravel's auto-discovery, so the service provider will be registered automatically.

## Publishing Migrations

To publish the migrations to your application:

```bash
php artisan vendor:publish --tag=nexus-finance-migrations
```

Then run the migrations:

```bash
php artisan migrate
```

## Components

### Models

- `Account` - Eloquent model implementing `AccountInterface`
- `JournalEntry` - Eloquent model implementing `JournalEntryInterface`
- `JournalEntryLine` - Eloquent model implementing `JournalEntryLineInterface`

### Repositories

- `EloquentAccountRepository` - Implements `AccountRepositoryInterface`
- `EloquentJournalEntryRepository` - Implements `JournalEntryRepositoryInterface`
- `EloquentLedgerRepository` - Implements `LedgerRepositoryInterface`

### Service Provider

The `FinanceServiceProvider` automatically binds:

```php
AccountRepositoryInterface::class => EloquentAccountRepository::class
JournalEntryRepositoryInterface::class => EloquentJournalEntryRepository::class
LedgerRepositoryInterface::class => EloquentLedgerRepository::class
```

## Usage

Once installed, you can inject the Finance interfaces into your controllers or services:

```php
use Nexus\Finance\Domain\Contracts\AccountRepositoryInterface;

class AccountController extends Controller
{
    public function __construct(
        private readonly AccountRepositoryInterface $accountRepository
    ) {}

    public function index()
    {
        $accounts = $this->accountRepository->findAll(['is_active' => true]);
        return response()->json($accounts);
    }
}
```

## Database Schema

### gl_accounts

| Column      | Type         | Description                              |
|-------------|--------------|------------------------------------------|
| id          | ULID         | Primary key                              |
| code        | VARCHAR(50)  | Unique account code                      |
| name        | VARCHAR(255) | Account name                             |
| type        | VARCHAR(50)  | ASSET, LIABILITY, EQUITY, REVENUE, EXPENSE |
| currency    | CHAR(3)      | ISO currency code (default: MYR)         |
| parent_id   | ULID         | Parent account for hierarchies           |
| is_header   | BOOLEAN      | Whether account is a header (non-postable) |
| is_active   | BOOLEAN      | Whether account is active                |
| description | TEXT         | Optional description                     |
| created_at  | TIMESTAMP    | Creation timestamp                       |
| updated_at  | TIMESTAMP    | Last update timestamp                    |

### gl_journal_entries

| Column       | Type         | Description                    |
|--------------|--------------|--------------------------------|
| id           | ULID         | Primary key                    |
| entry_number | VARCHAR(50)  | Unique entry number            |
| date         | DATE         | Entry date                     |
| reference    | VARCHAR(100) | External reference             |
| description  | TEXT         | Entry description              |
| status       | VARCHAR(20)  | draft, posted, or reversed     |
| created_by   | ULID         | User who created the entry     |
| posted_at    | TIMESTAMP    | When the entry was posted      |
| created_at   | TIMESTAMP    | Creation timestamp             |
| updated_at   | TIMESTAMP    | Last update timestamp          |

### gl_journal_entry_lines

| Column           | Type          | Description              |
|------------------|---------------|--------------------------|
| id               | ULID          | Primary key              |
| journal_entry_id | ULID          | Parent journal entry     |
| account_id       | ULID          | Target account           |
| debit_amount     | DECIMAL(20,4) | Debit amount             |
| credit_amount    | DECIMAL(20,4) | Credit amount            |
| currency         | CHAR(3)       | ISO currency code        |
| description      | TEXT          | Line description         |
| created_at       | TIMESTAMP     | Creation timestamp       |
| updated_at       | TIMESTAMP     | Last update timestamp    |

## License

MIT License
