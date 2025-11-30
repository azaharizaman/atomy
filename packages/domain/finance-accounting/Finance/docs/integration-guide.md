# Integration Guide: Finance

Framework integration examples for Laravel and Symfony.

---

## Laravel Integration

### Service Provider

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Finance\Contracts\{FinanceManagerInterface, AccountRepositoryInterface};
use App\Repositories\Finance\EloquentAccountRepository;
use Nexus\Finance\Services\FinanceManager;

class FinanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            AccountRepositoryInterface::class,
            EloquentAccountRepository::class
        );
        
        $this->app->singleton(
            FinanceManagerInterface::class,
            FinanceManager::class
        );
    }
}
```

### Controller Example

```php
namespace App\Http\Controllers;

use Nexus\Finance\Contracts\FinanceManagerInterface;
use Nexus\Finance\ValueObjects\AccountCode;
use Nexus\Finance\Enums\AccountType;

class AccountController extends Controller
{
    public function __construct(
        private readonly FinanceManagerInterface $financeManager
    ) {}
    
    public function store(Request $request)
    {
        $account = $this->financeManager->createAccount(
            code: new AccountCode($request->code),
            name: $request->name,
            type: AccountType::from($request->type)
        );
        
        return response()->json($account);
    }
}
```

---

## Symfony Integration

### services.yaml

```yaml
services:
  Nexus\Finance\Contracts\AccountRepositoryInterface:
    class: App\Repository\Finance\DoctrineAccountRepository
    
  Nexus\Finance\Contracts\FinanceManagerInterface:
    class: Nexus\Finance\Services\FinanceManager
    arguments:
      - '@Nexus\Finance\Contracts\AccountRepositoryInterface'
```

---

## Testing

### Laravel Feature Test

```php
use Tests\TestCase;
use Nexus\Finance\Contracts\FinanceManagerInterface;

class JournalEntryTest extends TestCase
{
    public function test_can_post_journal_entry(): void
    {
        $manager = app(FinanceManagerInterface::class);
        
        $entry = $manager->postJournalEntry(/*...*/);
        
        $this->assertDatabaseHas('journal_entries', [
            'number' => $entry->getNumber()->value,
            'status' => 'posted',
        ]);
    }
}
```

---

**Last Updated:** 2025-11-25
