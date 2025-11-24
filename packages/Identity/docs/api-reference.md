# API Reference: Identity

Complete API documentation for all interfaces, services, value objects, and exceptions.

## Interfaces (28 total)

### Core Interfaces

#### UserInterface
**Location:** `src/Contracts/UserInterface.php`

Entity contract for User.

**Methods:**
- `getId(): string` - Get user ULID
- `getEmail(): string` - Get user email
- `getPasswordHash(): string` - Get hashed password
- `getStatus(): UserStatus` - Get user status enum

[Full documentation with all 28 interfaces would go here - see source code for details]

## Services (10 total)

### UserManager
**Location:** `src/Services/UserManager.php`

**Purpose:** User lifecycle management (create, update, delete, status changes)

[Full service documentation would go here]

## Value Objects (20 total)

### UserStatus (Enum)
**Location:** `src/ValueObjects/UserStatus.php`

**Cases:** ACTIVE, INACTIVE, SUSPENDED, LOCKED, PENDING_ACTIVATION

[Full value object documentation would go here]

## Exceptions (19 total)

### UserNotFoundException
**Location:** `src/Exceptions/UserNotFoundException.php`

[Full exception documentation would go here]

---

**Note:** For complete API documentation, refer to source code docblocks. All public methods have comprehensive PHPDoc annotations.

**Last Updated:** 2024-11-24
