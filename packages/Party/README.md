# Nexus\Party

**Master Data Management for Entities**

A framework-agnostic PHP package implementing the **Party Pattern** to provide a unified abstraction for individuals and organizations across the Nexus ERP monorepo.

## Purpose

The Party package solves the "God Object" anti-pattern by separating the universal concept of **WHO** (name, contact info, legal identity) from **WHAT ROLE** they play in your business (customer, vendor, employee).

### The Problem

Without Party abstraction:
- Vendor table has: `name`, `email`, `phone`, `tax_id`
- Employee table has: `first_name`, `last_name`, `email`, `phone`
- Customer table has: `name`, `email`, `phone`, `address`

**Result:** Duplicated data, synchronization nightmares, and no way to track when the same person/organization plays multiple roles.

### The Solution

With Party abstraction:
- **Party table:** Single source of truth for identity and contact information
- **Vendor table:** Links to Party + adds vendor-specific data (payment terms, tolerances)
- **Employee table:** Links to Party + adds employment data (salary, job title)
- **Customer table:** Links to Party + adds customer data (credit limit, price list)

**Result:** Zero duplication, single canonical source, complete relationship history.

---

## Core Concepts

### Party Types

1. **INDIVIDUAL** - A natural person (employee, customer contact, vendor representative)
2. **ORGANIZATION** - A legal entity (company, vendor, customer organization)

### Party Relationships

Track connections between parties with effective dates:
- `EMPLOYMENT_AT` - Individual works at Organization
- `CONTACT_FOR` - Individual is a contact person for Organization
- `SUBSIDIARY_OF` - Organization is owned by parent Organization

### Key Features

✅ **Individual Mobility** - When a person changes companies, their transaction history follows them  
✅ **Circular Reference Prevention** - Automatic validation for organization hierarchies  
✅ **Multi-Contact Support** - Organizations can have multiple contact persons with roles  
✅ **Address Versioning** - Track address changes with effective dates  
✅ **Tax Identity Management** - Store multiple tax IDs per party (VAT, GST, EIN, etc.)

---

## Architecture

```
packages/Party/
├── src/
│   ├── Contracts/          # Interfaces
│   │   ├── PartyInterface.php
│   │   ├── PartyRepositoryInterface.php
│   │   ├── AddressInterface.php
│   │   ├── ContactMethodInterface.php
│   │   └── PartyRelationshipInterface.php
│   ├── Services/           # Business logic
│   │   ├── PartyManager.php
│   │   └── PartyRelationshipManager.php
│   ├── ValueObjects/       # Immutable data structures
│   │   ├── TaxIdentity.php
│   │   └── PostalAddress.php
│   ├── Enums/              # Type definitions
│   │   ├── PartyType.php
│   │   ├── AddressType.php
│   │   ├── ContactMethodType.php
│   │   └── RelationshipType.php
│   └── Exceptions/         # Domain exceptions
│       ├── PartyNotFoundException.php
│       ├── DuplicatePartyException.php
│       └── CircularRelationshipException.php
```

---

## Usage Example

### Creating an Organization (Vendor)

```php
use Nexus\Party\Services\PartyManager;
use Nexus\Party\Enums\PartyType;

// Create the party first
$party = $partyManager->createOrganization(
    tenantId: 'tenant-123',
    legalName: 'Acme Corporation',
    tradingName: 'Acme',
    taxIdentity: new TaxIdentity(
        country: 'MYS',
        number: '201901012345',
        issueDate: new \DateTimeImmutable('2019-01-01')
    )
);

// Add address
$partyManager->addAddress(
    partyId: $party->getId(),
    type: AddressType::LEGAL,
    address: new PostalAddress(
        streetLine1: '123 Main Street',
        city: 'Kuala Lumpur',
        postalCode: '50000',
        country: 'MYS'
    ),
    isPrimary: true
);

// Add contact method
$partyManager->addContactMethod(
    partyId: $party->getId(),
    type: ContactMethodType::EMAIL,
    value: 'info@acme.com',
    isPrimary: true
);

// Now create the vendor using the party_id
$vendor = $vendorManager->createVendor(
    tenantId: 'tenant-123',
    partyId: $party->getId(),
    code: 'VEN-001',
    paymentTerms: 'net_30'
);
```

### Creating an Individual (Employee)

```php
// Create individual party
$party = $partyManager->createIndividual(
    tenantId: 'tenant-123',
    fullName: 'Jane Smith',
    dateOfBirth: new \DateTimeImmutable('1990-05-15')
);

// Create employee linking to party
$employee = $employeeManager->createEmployee(
    tenantId: 'tenant-123',
    partyId: $party->getId(),
    employeeCode: 'EMP-001',
    hireDate: new \DateTimeImmutable('2024-01-15')
);

// Create employment relationship
$partyRelationshipManager->createRelationship(
    tenantId: 'tenant-123',
    fromPartyId: $party->getId(), // Individual
    toPartyId: $companyPartyId,   // Organization
    type: RelationshipType::EMPLOYMENT_AT,
    effectiveFrom: new \DateTimeImmutable('2024-01-15')
);
```

### Tracking Individual Mobility

```php
// Jane moves to a new company
$partyRelationshipManager->endRelationship(
    relationshipId: $oldRelationship->getId(),
    effectiveTo: new \DateTimeImmutable('2025-06-30')
);

$partyRelationshipManager->createRelationship(
    tenantId: 'tenant-123',
    fromPartyId: $janePartyId,
    toPartyId: $newCompanyPartyId,
    type: RelationshipType::EMPLOYMENT_AT,
    effectiveFrom: new \DateTimeImmutable('2025-07-01')
);

// All historical transactions reference $janePartyId
// Her purchasing patterns are preserved across companies
```

---

## Integration with Domain Packages

### Nexus\Payable (Vendor)

**Before:**
```php
interface VendorInterface {
    public function getName(): string;
    public function getEmail(): ?string;
    public function getTaxId(): ?string;
}
```

**After:**
```php
interface VendorInterface {
    public function getPartyId(): string;
    public function getPaymentTerms(): string;
    // ... vendor-specific methods only
}

// To get name/email/tax:
$vendor->getParty()->getLegalName();
$vendor->getParty()->getPrimaryEmail();
$vendor->getParty()->getTaxIdentity()->getNumber();
```

### Nexus\Hrm (Employee)

**Before:**
```php
interface EmployeeInterface {
    public function getFirstName(): string;
    public function getLastName(): string;
    public function getEmail(): string;
}
```

**After:**
```php
interface EmployeeInterface {
    public function getPartyId(): string;
    public function getHireDate(): \DateTimeInterface;
    // ... employment-specific methods only
}

// To get name/email:
$employee->getParty()->getLegalName();
$employee->getParty()->getPrimaryEmail();
```

---

## Requirements

- PHP 8.3 or higher
- No framework dependencies (pure PHP)

## License

MIT License - See LICENSE file for details
