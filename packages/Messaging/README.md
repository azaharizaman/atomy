# Nexus\Messaging

**Channel-agnostic, immutable communication record management for Email, SMS, Chat, WhatsApp, and more.**

[![PHP Version](https://img.shields.io/badge/PHP-8.3%2B-blue)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green)](LICENSE)
[![Test Coverage](https://img.shields.io/badge/Coverage-95%25-brightgreen)]()

---

## 📦 Overview

`Nexus\Messaging` is a **framework-agnostic PHP package** that provides atomic, stateless management of communication records across multiple channels (Email, SMS, WhatsApp, iMessage, Phone Calls, etc.). It separates the **WHAT** (immutable record of conversation) from the **HOW** (protocol-specific sending logic), making it a reusable, protocol-agnostic foundation for any communication system.

### Key Principles

- **📝 Immutable Records:** Once created, message records cannot be modified (append-only timeline)
- **🔌 Protocol Abstraction:** Supports any provider (Twilio, SendGrid, Meta) via connector interface
- **🏢 Multi-Tenant:** Native tenant isolation for SaaS applications
- **🔐 Compliance-Ready:** PII flags, archival policies, audit trails
- **⚡ Framework-Agnostic:** Pure PHP 8.3 - works with Laravel, Symfony, or vanilla PHP

---

## 🎯 Use Cases

- **Customer Support Systems:** Build conversation timelines on case/ticket screens
- **CRM Platforms:** Track all customer communications in one place
- **Marketing Automation:** Store campaign messages with delivery tracking
- **Multi-Channel Messaging:** Unify Email, SMS, WhatsApp under single API
- **Compliance & Audit:** Immutable records for SOX, GDPR, HIPAA
- **ERP Systems:** Communication history on orders, invoices, shipments

---

## 🚀 Installation

```bash
composer require nexus/messaging:"*@dev"
```

---

## ✨ Features

### Level 1 (MVP)
- ✅ Immutable `MessageRecord` value object
- ✅ Channel abstraction (Email, SMS, WhatsApp, etc.)
- ✅ Direction tracking (Inbound/Outbound)
- ✅ Repository interface for persistence
- ✅ Entity association (build conversation timelines)
- ✅ Audit event integration

### Level 2 (Professional)
- ✅ Delivery status tracking (Pending → Sent → Delivered → Failed)
- ✅ Provider reference IDs (Twilio SID, SendGrid ID, etc.)
- ✅ Connector interface for external providers
- ✅ Inbound webhook processing
- ✅ Tenant isolation (multi-tenant SaaS)
- ✅ Attachment metadata (no file I/O)
- ✅ Channel-specific metadata storage

### Level 3 (Enterprise)
- ✅ Rate limiting interface
- ✅ PII compliance flags
- ✅ Template engine abstraction
- ✅ Optimized timeline queries
- ✅ SSO user attribution
- ✅ Archival status (retention policies)
- ✅ Encryption neutrality

---

## 📖 Quick Start

### 1. Implement Repository Interface

```php
use Nexus\Messaging\Contracts\MessagingRepositoryInterface;
use Nexus\Messaging\ValueObjects\MessageRecord;

final class EloquentMessagingRepository implements MessagingRepositoryInterface
{
    public function saveRecord(MessageRecord $record): void
    {
        DB::table('messages')->insert($record->toArray());
    }

    public function findById(string $id): ?MessageRecord
    {
        $data = DB::table('messages')->where('id', $id)->first();
        // ... convert to MessageRecord VO
    }

    public function findByEntity(string $entityType, string $entityId, int $limit = 50, int $offset = 0): array
    {
        // ... query implementation
    }
    
    // ... other methods
}
```

### 2. Implement Connector Interface

```php
use Nexus\Messaging\Contracts\MessagingConnectorInterface;
use Nexus\Messaging\ValueObjects\MessageRecord;
use Nexus\Messaging\Enums\DeliveryStatus;

final class TwilioWhatsAppConnector implements MessagingConnectorInterface
{
    public function send(MessageRecord $draft): MessageRecord
    {
        // Call Twilio API
        $response = $this->twilioClient->messages->create(
            "whatsapp:{$draft->recipientPartyId}",
            [
                'from' => "whatsapp:{$this->twilioNumber}",
                'body' => $draft->body
            ]
        );

        // Return updated record with delivery status
        return $draft->withDeliveryStatus(
            DeliveryStatus::Sent,
            $response->sid
        );
    }

    public function processInboundWebhook(array $payload): MessageRecord
    {
        // Parse Twilio webhook payload
        return MessageRecord::createInbound(
            id: $this->generateId(),
            channel: Channel::WhatsApp,
            subject: null,
            body: $payload['Body'],
            receivedAt: new \DateTimeImmutable($payload['DateCreated']),
            senderPartyId: $this->parsePhoneNumber($payload['From']),
            recipientPartyId: $this->parsePhoneNumber($payload['To']),
            tenantId: $this->getCurrentTenantId(),
            providerReferenceId: $payload['MessageSid']
        );
    }

    public function getSupportedChannel(): string
    {
        return 'whatsapp';
    }

    public function isConfigured(): bool
    {
        return !empty($this->twilioAccountSid);
    }
}
```

### 3. Send Outbound Message

```php
use Nexus\Messaging\Services\MessageManager;
use Nexus\Messaging\Enums\Channel;

$messageManager = new MessageManager(
    repository: $messagingRepository,
    connector: $twilioConnector,
    logger: $logger
);

// Send WhatsApp message
$message = $messageManager->sendMessage(
    id: 'msg-' . Str::ulid(),
    channel: Channel::WhatsApp,
    subject: null,
    body: 'Hello! Your order has shipped.',
    senderPartyId: 'company-support',
    recipientPartyId: '+60123456789',
    tenantId: 'tenant-001',
    entityType: 'order',
    entityId: 'order-12345'
);

echo "Message sent: {$message->deliveryStatus->label()}";
```

### 4. Process Inbound Webhook

```php
// Webhook endpoint (e.g., Laravel controller)
public function handleTwilioWebhook(Request $request)
{
    $inboundMessage = $messageManager->processInboundWebhook(
        $request->all()
    );

    // Message automatically saved to database
    // Can trigger workflows, notifications, etc.

    return response('OK', 200);
}
```

### 5. Build Conversation Timeline

```php
// Get all messages for a support case
$timeline = $messageManager->getConversationTimeline(
    entityType: 'case',
    entityId: 'case-789',
    limit: 50
);

foreach ($timeline as $message) {
    echo "{$message->sentAt->format('Y-m-d H:i')} - ";
    echo "{$message->direction->label()} {$message->channel->label()}: ";
    echo "{$message->body}\n";
}
```

---

## 🏗️ Architecture

### Value Objects

- **`MessageRecord`** - Immutable aggregate root containing all message data
- **`AttachmentMetadata`** - Attachment references (no file I/O)

### Enums

- **`Channel`** - Communication channels (Email, SMS, WhatsApp, etc.)
- **`Direction`** - Message flow (Inbound/Outbound)
- **`DeliveryStatus`** - Delivery lifecycle (Pending, Sent, Delivered, Failed)
- **`ArchivalStatus`** - Retention policy status

### Contracts

- **`MessagingRepositoryInterface`** - Persistence abstraction
- **`MessagingConnectorInterface`** - External provider abstraction
- **`RateLimiterInterface`** - High-volume throttling
- **`MessageTemplateEngineInterface`** - Template rendering

### Services

- **`MessageManager`** - Core orchestrator (send, receive, query)

---

## 🔌 Supported Channels & Providers

The package is **protocol-agnostic**. The application layer implements connectors for specific providers:

| Channel | Example Providers | Implementation |
|---------|-------------------|----------------|
| **Email** | SendGrid, Postmark, AWS SES, SMTP | Via `MessagingConnectorInterface` |
| **SMS** | Twilio, Nexmo, AWS SNS | Via `MessagingConnectorInterface` |
| **WhatsApp** | Twilio WhatsApp API, Meta Business API | Via `MessagingConnectorInterface` |
| **iMessage** | Apple Business Chat | Via `MessagingConnectorInterface` |
| **Phone Call** | Twilio Voice, SIP | Log call notes as messages |
| **Chat** | Slack, Discord, MS Teams | Via `MessagingConnectorInterface` |
| **Webhook** | Custom HTTP webhooks | Generic webhook handler |
| **Internal Note** | Internal system notes | No external provider |

---

## 📊 Protocol Abstraction Pattern

```
┌─────────────────────────────────────────────────────────┐
│                   Application Layer                      │
│  ┌─────────────────────────────────────────────────┐   │
│  │  TwilioWhatsAppConnector                        │   │
│  │  implements MessagingConnectorInterface         │   │
│  │                                                  │   │
│  │  • Knows Twilio API specifics                   │   │
│  │  • Transforms Twilio webhook → MessageRecord    │   │
│  │  • Handles OAuth, retries, errors               │   │
│  └─────────────────────────────────────────────────┘   │
└────────────────────────┬────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────┐
│                Nexus\Messaging Package                   │
│  ┌─────────────────────────────────────────────────┐   │
│  │  MessagingConnectorInterface                    │   │
│  │  • send(MessageRecord): MessageRecord           │   │
│  │  • processInboundWebhook(array): MessageRecord  │   │
│  └─────────────────────────────────────────────────┘   │
│                                                          │
│  ┌─────────────────────────────────────────────────┐   │
│  │  MessageRecord (Value Object)                   │   │
│  │  • channel: Channel                             │   │
│  │  • body: string                                 │   │
│  │  • deliveryStatus: DeliveryStatus               │   │
│  │  • providerReferenceId: ?string                 │   │
│  └─────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────┘
```

**Key Insight:** The package defines `Channel::WhatsApp` without knowing what WhatsApp is. The connector implementation knows the Twilio-specific API details.

---

## 🔐 Compliance & Security

### PII Handling (L3.2)

```php
$message = MessageRecord::createOutbound(
    // ...
    body: 'Your SSN is 123-45-6789',
    containsPII: true // Flag for compliance
);
```

Application layer can:
- Encrypt body before persistence
- Restrict access logs
- Apply stricter retention policies

### Encryption Neutrality (L3.7)

The package **NEVER** encrypts data itself. Encryption is the application layer's responsibility:

```php
// Application layer implementation
public function saveRecord(MessageRecord $record): void
{
    $encryptedBody = $record->containsPII 
        ? $this->encryptor->encrypt($record->body)
        : $record->body;

    DB::table('messages')->insert([
        // ... other fields
        'body' => $encryptedBody,
        'encrypted' => $record->containsPII,
    ]);
}
```

### Archival Policies (L3.6)

```php
// Mark messages for archival
$archived = $message->withArchivalStatus(ArchivalStatus::PreArchived);

// Application layer batch job
$messagesToArchive = $repository->findByArchivalStatus(
    ArchivalStatus::PreArchived
);

foreach ($messagesToArchive as $msg) {
    $this->archiver->moveToArchive($msg);
}
```

---

## ⚡ High-Volume Optimization

### Rate Limiting (L3.1)

```php
$messageManager = new MessageManager(
    repository: $repository,
    connector: $connector,
    rateLimiter: $redisRateLimiter // Optional
);

// Automatically enforced before sending
$message = $messageManager->sendMessage(/* ... */);
// Throws RateLimitExceededException if quota exceeded
```

### Optimized Timeline Loading (L3.4)

```php
// Fast query for UI timeline (limit 20, optimized indexes)
$latestMessages = $messageManager->getLatestMessages(
    entityType: 'customer',
    entityId: 'cust-123',
    limit: 20
);
```

---

## 🧪 Testing

```bash
composer test
composer test:coverage
```

Example test:

```php
public function test_can_send_whatsapp_message(): void
{
    $connector = new TwilioWhatsAppConnector(/* ... */);
    $repository = new InMemoryMessageRepository();
    
    $manager = new MessageManager($repository, $connector);

    $message = $manager->sendMessage(
        id: 'msg-001',
        channel: Channel::WhatsApp,
        subject: null,
        body: 'Hello',
        senderPartyId: 'party-1',
        recipientPartyId: '+60123456789',
        tenantId: 'tenant-1'
    );

    $this->assertTrue($message->isOutbound());
    $this->assertSame(DeliveryStatus::Sent, $message->deliveryStatus);
}
```

---

## 📚 Available Interfaces

### MessagingRepositoryInterface

```php
saveRecord(MessageRecord): void
findById(string): ?MessageRecord
findByEntity(string, string, int, int): array<MessageRecord>
findLatestByEntity(string, string, int): array<MessageRecord>
findByTenant(string, int, int): array<MessageRecord>
findBySender(string, int, int): array<MessageRecord>
findByChannel(string, string, int, int): array<MessageRecord>
countByEntity(string, string): int
```

### MessagingConnectorInterface

```php
send(MessageRecord): MessageRecord
processInboundWebhook(array): MessageRecord
getSupportedChannel(): string
isConfigured(): bool
```

### RateLimiterInterface (Optional)

```php
allowAction(string, int, int): bool
remainingAttempts(string, int): int
availableIn(string): int
clear(string): void
```

### MessageTemplateEngineInterface (Optional)

```php
render(string, array): string
templateExists(string): bool
renderSubject(string, array): ?string
```

---

## 🔗 Integration with Other Nexus Packages

| Package | Integration Point | Usage |
|---------|-------------------|-------|
| **Nexus\Party** | `senderPartyId`, `recipientPartyId` | Link messages to Party entities |
| **Nexus\AuditLogger** | Optional `$auditLogger` callback | Log "message_sent", "message_received" events |
| **Nexus\Connector** | Application layer uses it | External API integration |
| **Nexus\Tenant** | `tenantId` field | Multi-tenant isolation |
| **Nexus\Monitoring** | Optional `TelemetryTrackerInterface` | Track message metrics |
| **Nexus\SSO** | User attribution | `senderPartyId` from authenticated user |

---

## 🎓 Best Practices

1. **Always use ULIDs for message IDs** - Sortable, globally unique
2. **Set `containsPII` flag** - When messages contain sensitive data
3. **Implement rate limiting** - For production environments
4. **Use optimized queries** - `findLatestByEntity()` for UI timelines
5. **Validate provider responses** - In connector implementations
6. **Log delivery failures** - For debugging and monitoring
7. **Test webhook parsers** - Validate all provider webhook formats

---

## 📖 Documentation

- [Getting Started](docs/getting-started.md) - Quick start guide
- [API Reference](docs/api-reference.md) - Complete API documentation
- [Integration Guide](docs/integration-guide.md) - Laravel/Symfony examples
- [Examples](docs/examples/) - Working code samples
- [Requirements](REQUIREMENTS.md) - All 20 requirements tracked
- [Implementation Summary](IMPLEMENTATION_SUMMARY.md) - Development metrics
- [Test Suite Summary](TEST_SUITE_SUMMARY.md) - Test coverage report
- [Valuation Matrix](VALUATION_MATRIX.md) - Package business value

---

## 🤝 Contributing

This package follows strict Nexus architectural guidelines:

- ✅ Framework-agnostic (no Laravel/Symfony dependencies)
- ✅ Immutable value objects with `readonly` properties
- ✅ Native PHP 8.3 enums
- ✅ Constructor property promotion
- ✅ Strict types (`declare(strict_types=1)`)
- ✅ PSR-12 coding standards
- ✅ 95%+ test coverage

---

## 📄 License

MIT License. See [LICENSE](LICENSE) for details.

---

## 🙏 Credits

Developed by the **Nexus Development Team** as part of the Nexus ERP ecosystem.

**Package Status:** ✅ Production Ready  
**Version:** 1.0.0  
**Last Updated:** November 24, 2025

## 📖 Documentation

### Package Documentation
- **[Getting Started Guide](docs/getting-started.md)** - Quick start guide with prerequisites and basic configuration
- **[API Reference](docs/api-reference.md)** - Complete documentation of all interfaces and components
- **[Integration Guide](docs/integration-guide.md)** - Laravel and Symfony integration examples
- **[Basic Usage Example](docs/examples/basic-usage.php)** - Simple usage patterns
- **[Advanced Usage Example](docs/examples/advanced-usage.php)** - Advanced scenarios

### Additional Resources
- `IMPLEMENTATION_SUMMARY.md` - Implementation progress and metrics
- `REQUIREMENTS.md` - Detailed requirements
- `TEST_SUITE_SUMMARY.md` - Test coverage and results
- `VALUATION_MATRIX.md` - Package valuation metrics
- See root `ARCHITECTURE.md` for overall system architecture
