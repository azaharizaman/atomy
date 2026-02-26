# How to Use NEXUS_PACKAGES_REFERENCE.md

**Quick Start Guide for Developers & Coding Agents**

---

## 🎯 Purpose

`NEXUS_PACKAGES_REFERENCE.md` is your **mandatory first stop** before implementing any new feature in `consuming application (e.g., Laravel app)`. It prevents architectural violations by showing what first-party packages already exist and how to use them.

---

## 📖 When to Consult This Guide

**ALWAYS before:**
- Writing a new service in `consuming application (e.g., Laravel app)app/Services/`
- Creating a new integration or adapter
- Implementing a feature that feels "generic" or "reusable"
- Adding monitoring, logging, notifications, or storage

**Example Scenarios:**
- "I need to track metrics" → Check the guide → Use `Nexus\Telemetry`
- "I need to send emails" → Check the guide → Use `Nexus\Notifier`
- "I need to store files" → Check the guide → Use `Nexus\Storage`
- "I need auto-numbering" → Check the guide → Use `Nexus\Sequencing`

---

## 🔍 How to Navigate the Guide

### 1. Use the "I Need To..." Decision Matrix (Fastest)

Located at the bottom of the guide. Just Ctrl+F and search for what you need:

```
I need to track metrics → Nexus\Telemetry → TelemetryTrackerInterface
I need to log actions → Nexus\AuditLogger → AuditLogManagerInterface
I need to send notifications → Nexus\Notifier → NotificationManagerInterface
```

### 2. Browse by Category

The guide organizes packages into 16 categories:

1. Security & Identity
2. Observability & Monitoring
3. Communication
4. Data Management
5. Multi-Tenancy & Context
6. Business Logic Utilities
7. Financial Management
8. Sales & Procurement
9. Inventory & Warehouse
10. Human Resources
11. Operations
12. Integration & Workflow
13. Reporting & Analytics
14. Geographic & Routing
15. Compliance & Statutory
16. System Utilities

### 3. Read Package Details

Each package entry includes:

- **Capabilities:** What the package can do
- **When to Use:** Specific scenarios
- **Key Interfaces:** What to inject in constructors
- **Example Code:** Correct implementation pattern

---

## ✅ Example Workflow

**Scenario:** You need to implement a feature that sends payment reminders.

### Step 1: Search the Decision Matrix

```
Ctrl+F → "send notifications"
Result: Use Nexus\Notifier → NotificationManagerInterface
```

### Step 2: Find the Package Section

Navigate to **"3. Communication → Nexus\Notifier"**

### Step 3: Read the Details

```markdown
**Nexus\Notifier**
Capabilities:
- Multi-channel notifications (email, SMS, push, in-app)
- Template management
- Delivery tracking and retry logic

When to Use:
✅ Send email notifications
✅ SMS alerts
```

### Step 4: Use the Example Code

```php
// ✅ CORRECT: Send invoice payment reminder
public function __construct(
    private readonly NotificationManagerInterface $notifier
) {}

public function sendPaymentReminder(string $invoiceId): void
{
    $this->notifier->send(
        recipient: $invoice->getCustomerId(),
        channel: 'email',
        template: 'invoice.payment_reminder',
        data: ['invoice_number' => $invoice->getNumber()]
    );
}
```

### Step 5: Implement in consuming application

1. Inject `NotificationManagerInterface` in your service
2. Use it (consuming application already has the binding configured)
3. Done! No need to build custom notification system

---

## 🚫 Common Mistakes to Avoid

### ❌ Mistake 1: Skipping the Guide

```php
// ❌ WRONG: Building custom metrics without checking
class CustomMetricsCollector {
    private array $counters = [];
    // ... reimplementing Nexus\Telemetry
}
```

**Correct:** Check guide → Find `Nexus\Telemetry` → Inject `TelemetryTrackerInterface`

### ❌ Mistake 2: Not Using the Decision Matrix

Developers often browse the code looking for similar implementations instead of checking the guide. This leads to:
- Duplicated code
- Inconsistent patterns
- Architectural violations

**Correct:** Use Ctrl+F in the decision matrix

### ❌ Mistake 3: Creating Package-Specific Adapters

```php
// ❌ WRONG: Creating EventStream-specific metrics collector
class EventStreamMetricsCollector {
    public function trackEvent() { /* ... */ }
}
```

**Correct:** Inject generic `TelemetryTrackerInterface` (works for ANY domain)

---

## 🎓 For Coding Agents

### Pre-Implementation Checklist

Before generating ANY code in `consuming application (e.g., Laravel app)`, ask yourself:

1. **Have I checked NEXUS_PACKAGES_REFERENCE.md?**
   - If NO → STOP and check it first

2. **Does a Nexus package provide this capability?**
   - If YES → Use the package's interface
   - If NO → Proceed with custom implementation

3. **Am I injecting interfaces, not concrete classes?**
   - Constructor parameters must be interfaces

4. **Am I avoiding Laravel facades in packages/?**
   - No `Log::`, `Cache::`, `DB::` in package code

### Self-Correction Protocol

If you find yourself writing code that:
- Tracks metrics
- Logs actions
- Sends notifications
- Stores files
- Manages sequences
- Handles multi-tenancy

**STOP** and verify you're using the correct Nexus package.

---

## 📚 Related Documentation

- **Architecture Overview:** [`ARCHITECTURE.md`](../ARCHITECTURE.md)
- **Coding Standards:** [`.github/copilot-instructions.md`](../.github/copilot-instructions.md)
- **Package Requirements:** [`docs/REQUIREMENTS_*.md`](.)
- **Implementation Summaries:** [`docs/*_IMPLEMENTATION_SUMMARY.md`](.)

---

## 🔄 Keeping the Guide Updated

When a new package is added:

1. Update `NEXUS_PACKAGES_REFERENCE.md`
2. Add entry to appropriate category
3. Update "I Need To..." decision matrix
4. Provide usage example
5. Update count in `.github/copilot-instructions.md`

---

**Remember:** This guide exists to prevent wasted effort. 5 minutes checking the guide saves hours of refactoring.
