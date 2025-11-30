# Integration Guide: Nexus\Scheduler

This guide demonstrates how to integrate the Scheduler package into Laravel and Symfony applications while keeping the package framework-agnostic.

---

## Laravel Integration

### 1. Install Package & Publish Config (optional)

```bash
composer require nexus/scheduler:"*@dev"
```

### 2. Create Database Tables

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up(): void
	{
		Schema::create('scheduled_jobs', function (Blueprint $table) {
			$table->string('id', 26)->primary(); // ULID
			$table->string('tenant_id', 26)->index();
			$table->string('job_type');
			$table->string('target_id');
			$table->json('payload');
			$table->json('recurrence')->nullable();
			$table->timestampTz('run_at');
			$table->string('status');
			$table->unsignedSmallInteger('retry_count')->default(0);
			$table->timestampTz('last_run_at')->nullable();
			$table->timestampsTz();
		});
	}
};
```

### 3. Implement Infrastructure Contracts

```php
namespace App\Scheduler;

use Nexus\Scheduler\Contracts\ScheduleRepositoryInterface;
use Nexus\Scheduler\Contracts\JobQueueInterface;
use Nexus\Scheduler\Contracts\ClockInterface;
use Nexus\Scheduler\ValueObjects\ScheduledJobInterface;

final readonly class DbScheduleRepository implements ScheduleRepositoryInterface { /* ... */ }
final readonly class LaravelJobQueue implements JobQueueInterface { /* ... */ }
final readonly class SystemClock implements ClockInterface { /* ... */ }
```

### 4. Bind Interfaces in a Service Provider

```php
use Illuminate\Support\ServiceProvider;
use Nexus\Scheduler\Contracts\{ScheduleRepositoryInterface, JobQueueInterface, ClockInterface};
use Nexus\Scheduler\Services\ScheduleManager;

final class SchedulerServiceProvider extends ServiceProvider
{
	public function register(): void
	{
		$this->app->singleton(ScheduleRepositoryInterface::class, DbScheduleRepository::class);
		$this->app->singleton(JobQueueInterface::class, LaravelJobQueue::class);
		$this->app->singleton(ClockInterface::class, SystemClock::class);

		$this->app->tag([ExportReportHandler::class, ReminderHandler::class], 'scheduler.handlers');

		$this->app->singleton(ScheduleManager::class, function ($app) {
			return new ScheduleManager(
				repository: $app->make(ScheduleRepositoryInterface::class),
				queue: $app->make(JobQueueInterface::class),
				clock: $app->make(ClockInterface::class),
				handlers: $app->tagged('scheduler.handlers'),
				logger: $app->make(Psr\Log\LoggerInterface::class),
				calendarExporter: new NullCalendarExporter()
			);
		});
	}
}
```

Register provider in `config/app.php` and ensure tagged handlers are auto-resolvable.

### 5. Console Command & Cron

```php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Nexus\Scheduler\Services\ScheduleManager;

final class ProcessScheduledJobs extends Command
{
	protected $signature = 'scheduler:process';
	protected $description = 'Dispatch due scheduled jobs to the queue';

	public function handle(ScheduleManager $manager): int
	{
		foreach ($manager->getDueJobs() as $job) {
			$manager->executeJob($job->id());
		}

		return self::SUCCESS;
	}
}
```

Add cron entry:

```
* * * * * php /path/to/artisan scheduler:process >> /var/log/scheduler.log 2>&1
```

### 6. Queue Worker

Ensure Laravel Horizon or `php artisan queue:work` is running so dispatched jobs are executed promptly.

---

## Symfony Integration

### 1. Install Package

```bash
composer require nexus/scheduler:"*@dev"
```

### 2. Define Doctrine Entities or Tables

Use Doctrine migrations to match the schema described in the Laravel section.

### 3. Implement Contracts

```php
namespace App\Scheduler;

use Nexus\Scheduler\Contracts\{ScheduleRepositoryInterface, JobQueueInterface, ClockInterface};

final readonly class DoctrineScheduleRepository implements ScheduleRepositoryInterface { /* ... */ }
final readonly class MessengerJobQueue implements JobQueueInterface { /* ... */ }
final readonly class SystemClock implements ClockInterface { /* ... */ }
```

### 4. Service Configuration (`config/services.yaml`)

```yaml
services:
	App\Scheduler\DoctrineScheduleRepository: ~
	App\Scheduler\MessengerJobQueue: ~
	App\Scheduler\SystemClock: ~

	Nexus\Scheduler\Contracts\ScheduleRepositoryInterface: '@App\Scheduler\DoctrineScheduleRepository'
	Nexus\Scheduler\Contracts\JobQueueInterface: '@App\Scheduler\MessengerJobQueue'
	Nexus\Scheduler\Contracts\ClockInterface: '@App\Scheduler\SystemClock'

	Nexus\Scheduler\Services\ScheduleManager:
		arguments:
			$repository: '@Nexus\Scheduler\Contracts\ScheduleRepositoryInterface'
			$queue: '@Nexus\Scheduler\Contracts\JobQueueInterface'
			$clock: '@Nexus\Scheduler\Contracts\ClockInterface'
			$handlers: !tagged_iterator scheduler.handlers
			$logger: '@logger'
			$calendarExporter: '@Nexus\Scheduler\Contracts\CalendarExporterInterface'

	App\Handlers\ExportHandler:
		tags: ['scheduler.handlers']
```

### 5. Messenger Worker

Configure a Messenger transport for scheduled job execution and ensure `symfony console messenger:consume` runs continuously.

### 6. Console Command

```php
namespace App\Command;

use Nexus\Scheduler\Services\ScheduleManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'scheduler:process')]
final class ProcessScheduledJobsCommand extends Command
{
	public function __construct(private ScheduleManager $manager)
	{
		parent::__construct();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		foreach ($this->manager->getDueJobs() as $job) {
			$this->manager->executeJob($job->id());
		}

		return self::SUCCESS;
	}
}
```

Register cron entry similar to Laravel to run the command every minute.

---

## Common Patterns & Tips

- **Tenant Awareness:** Include `tenant_id` columns and scope all repository queries.
- **Locking:** When fetching due jobs, use `SELECT ... FOR UPDATE SKIP LOCKED` (PostgreSQL) or equivalent to prevent double execution.
- **Telemetry:** Inject `Psr\Log\LoggerInterface` into `ScheduleManager` to capture retries and failures.
- **Preview Mode:** Use `ScheduleManager::preview()` for UI experiences where users configure recurrences before saving.
- **Calendar Export:** Bind `CalendarExporterInterface` to a no-op until v2 features are needed.

## Troubleshooting Checklist

| Symptom | Likely Cause | Fix |
|---------|--------------|-----|
| Jobs stuck in `pending` | Cron/command not running | Schedule command every minute |
| `NoHandlerFoundException` | Handler not tagged or auto-wired | Register handler with `scheduler.handlers` tag |
| Infinite retries | Handler always returns `shouldRetry=true` | Apply maximum retry safeguards in handler |
| Timezone drift | Different DB vs app timezones | Normalize to UTC in repository and `ClockInterface` |

Refer back to [docs/api-reference.md](api-reference.md) for detailed contract explanations.
