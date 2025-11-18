# Nexus\Notifier

**Framework-agnostic notification engine for multi-channel communication.**

## Purpose

`Nexus\Notifier` provides a unified, decoupled interface for sending notifications across multiple channels (Email, SMS, Push Notifications, In-App Messages) without coupling your business logic to specific vendors or delivery mechanisms.

## Core Philosophy

**"Define the message once, deliver everywhere."**

This package implements the **Channel Agnosticism** principle: your business logic triggers notifications without knowing *how* they're delivered. The consuming application (Atomy) provides the concrete implementations for each channel.

## Key Features

- **Multi-Channel Support**: Email, SMS, Push Notifications, In-App messaging
- **Channel Agnosticism**: Business logic remains decoupled from delivery mechanisms
- **Single Definition**: One notification event contains all channel variations
- **Asynchronous Delivery**: Queue-based delivery prevents blocking
- **Priority Handling**: Critical notifications bypass normal queues
- **Template Engine**: Variable substitution and multi-language support
- **Delivery Tracking**: Complete lifecycle tracking (Pending → Sent → Delivered → Failed)
- **Preference Management**: Per-recipient channel and category preferences
- **Audit Trail**: Full logging integration via `Nexus\AuditLogger`
- **Rate Limiting**: Prevents spam and abuse
- **Fallback Channels**: Automatic fallback if primary channel fails

## Architecture

This package follows Nexus's **"Logic in Packages, Implementation in Applications"** principle:

- **Package (`packages/Notifier/`)**: Defines contracts, business logic, and value objects
- **Application (`apps/Atomy/`)**: Implements repositories, models, and channel handlers

### Package Structure

```
packages/Notifier/
├── composer.json
├── LICENSE
├── README.md
└── src/
    ├── Contracts/              # Interfaces (REQUIRED)
    │   ├── NotificationManagerInterface.php
    │   ├── NotificationInterface.php
    │   ├── NotifiableInterface.php
    │   ├── NotificationChannelInterface.php
    │   ├── NotificationTemplateRepositoryInterface.php
    │   ├── NotificationHistoryRepositoryInterface.php
    │   ├── NotificationRendererInterface.php
    │   ├── NotificationQueueInterface.php
    │   ├── DeliveryStatusTrackerInterface.php
    │   └── NotificationPreferenceRepositoryInterface.php
    ├── Exceptions/             # Domain exceptions
    │   ├── NotificationException.php
    │   ├── NotificationNotFoundException.php
    │   ├── InvalidChannelException.php
    │   ├── InvalidRecipientException.php
    │   ├── DeliveryFailedException.php
    │   ├── RateLimitExceededException.php
    │   └── TemplateRenderException.php
    ├── Services/               # Business logic
    │   ├── NotificationManager.php
    │   └── ChannelRouter.php
    └── ValueObjects/           # Immutable data structures
        ├── Priority.php
        ├── Category.php
        ├── DeliveryStatus.php
        ├── NotificationContent.php
        └── ChannelType.php
```

## Requirements Addressed

This package addresses the following requirements from `REQUIREMENTS.csv`:

- **FR-NOT-101**: Channel agnosticism - business services trigger notifications without knowing delivery method
- **FR-NOT-102**: Single notification definition containing all channel variations
- **FR-NOT-103**: Recipient abstraction via `NotifiableInterface`
- **FR-NOT-104**: Asynchronous notification delivery via queue
- **BUS-NOT-0001 to BUS-NOT-0010**: Business requirements for multi-channel delivery, consistency, retry logic, etc.
- **FUN-NOT-0185 to FUN-NOT-0204**: Functional requirements for notification management

See `REQUIREMENTS.csv` for complete list.

## Installation

This package is part of the Nexus monorepo. To use it in the Atomy application:

```bash
composer require nexus/notifier:"*@dev"
```

## Usage

### 1. Define a Notification

Create a notification class implementing `NotificationInterface`:

```php
use Nexus\Notifier\Contracts\NotificationInterface;
use Nexus\Notifier\ValueObjects\Priority;
use Nexus\Notifier\ValueObjects\Category;

final class PayslipAvailableNotification implements NotificationInterface
{
    public function __construct(
        private readonly string $employeeName,
        private readonly string $month,
        private readonly string $downloadUrl
    ) {}

    public function toEmail(): array
    {
        return [
            'subject' => "Your {$this->month} Payslip is Ready",
            'body' => "Hello {$this->employeeName}, your payslip for {$this->month} is now available. Download it here: {$this->downloadUrl}",
        ];
    }

    public function toSms(): string
    {
        return "Your {$this->month} payslip is ready. Download: {$this->downloadUrl}";
    }

    public function toPush(): array
    {
        return [
            'title' => 'Payslip Available',
            'body' => "Your {$this->month} payslip is ready",
            'action' => $this->downloadUrl,
        ];
    }

    public function toInApp(): array
    {
        return [
            'title' => 'Payslip Available',
            'message' => "Your {$this->month} payslip is now available for download.",
            'link' => $this->downloadUrl,
        ];
    }

    public function getPriority(): Priority
    {
        return Priority::Normal;
    }

    public function getCategory(): Category
    {
        return Category::Transactional;
    }
}
```

### 2. Send a Notification

```php
use Nexus\Notifier\Contracts\NotificationManagerInterface;

final class PayrollManager
{
    public function __construct(
        private readonly NotificationManagerInterface $notifier
    ) {}

    public function publishPayslip(string $employeeId): void
    {
        // Business logic to generate payslip...
        
        $notification = new PayslipAvailableNotification(
            employeeName: $employee->getName(),
            month: 'January 2025',
            downloadUrl: $payslipUrl
        );

        // Send via all preferred channels
        $this->notifier->send($employee, $notification);
    }
}
```

### 3. Implement Notifiable

Your recipient entity must implement `NotifiableInterface`:

```php
use Nexus\Notifier\Contracts\NotifiableInterface;

final class Employee implements NotifiableInterface
{
    public function getNotificationEmail(): ?string
    {
        return $this->email;
    }

    public function getNotificationPhone(): ?string
    {
        return $this->mobilePhone;
    }

    public function getNotificationDeviceTokens(): array
    {
        return $this->deviceTokens ?? [];
    }

    public function getNotificationLocale(): string
    {
        return $this->preferredLocale ?? 'en';
    }

    public function getNotificationTimezone(): string
    {
        return $this->timezone ?? 'UTC';
    }
}
```

## User Stories Solved

### 1. System Administrator Story
**Problem**: Switching SMS vendors requires code changes across multiple packages.

**Solution**: 
```php
// Only change binding in AppServiceProvider
$this->app->singleton(SmsChannelInterface::class, MessageBirdSmsChannel::class);
// No changes to Payroll, FieldService, or any other package!
```

### 2. Package Developer Story
**Problem**: Implementing notifications requires understanding SMTP, SMS APIs, etc.

**Solution**:
```php
// Simple, focused business logic
$this->notifier->send($user, new InvoiceReadyNotification($invoice));
```

### 3. Customer Relations Manager Story
**Problem**: Need multi-channel confirmation after booking.

**Solution**: The system automatically sends both email and SMS based on recipient preferences when you call `$notifier->send()`.

## Dependencies

This package depends on:
- **`nexus/audit-logger`**: For audit trail logging
- **`nexus/connector`**: For actual email/SMS delivery via external providers
- **`nexus/identity`**: For recipient user data
- **`nexus/setting`**: For notification preferences

## Integration Points

The consuming application (Atomy) must implement:

1. **Channel Handlers**: Email, SMS, Push, In-App channel implementations
2. **Repositories**: Template, History, Preference repositories with database persistence
3. **Queue System**: For asynchronous delivery
4. **Service Provider Bindings**: Wire all interfaces to concrete implementations

See `docs/NOTIFIER_IMPLEMENTATION.md` for complete implementation guide.

## Testing

Package tests are unit tests with mocked repositories. Atomy tests are feature tests with database.

## License

MIT License - see LICENSE file for details.

## Support

Part of the Nexus ERP Monorepo.
