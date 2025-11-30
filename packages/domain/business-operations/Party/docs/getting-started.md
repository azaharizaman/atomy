# Getting Started with Nexus Party

**Version:** 1.0.0  
**Package:** `nexus/party`

---

## Prerequisites

- **PHP 8.3 or higher**
- **Composer**
- Basic understanding of dependency injection and interfaces
- Familiarity with DDD (Domain-Driven Design) Party Pattern recommended

---

## Installation

```bash
composer require nexus/party:"*@dev"
```

---

## When to Use This Package

The Party package is designed for:

✅ **Managing individuals and organizations** as master data across your ERP system  
✅ **Eliminating duplicate contact information** (name, email, phone, address, tax ID)  
✅ **Tracking individual mobility** (employee becomes vendor, customer representative changes companies)  
✅ **Managing organizational hierarchies** with circular reference prevention  
✅ **Multi-role scenarios** (same person is employee + vendor contact + customer)

Do NOT use this package for:

❌ **Simple contact forms** (overkill - just store name/email directly)  
❌ **Non-relational data** (if you don't need relationships between entities)  
❌ **Single-purpose entities** (if vendor will NEVER become a customer)

---

## Core Concepts

### The Party Pattern Problem & Solution

**THE PROBLEM:**

Traditional ERP systems embed contact information directly into domain entities:

```
vendors table:          employees table:        customers table:
- name                  - first_name            - company_name
- email                 - last_name             - email
- phone                 - email                 - phone
- address               - phone                 - address
- tax_id                - address               - tax_id
```

**Issues:**
1. **Data Duplication:** Same person's info in 3 tables
2. **Update Anomalies:** Change email → update 3 places
3. **Lost History:** Employee becomes vendor → recreate entire record

**THE SOLUTION: Party Pattern**

Separate **WHO** (identity) from **WHAT ROLE** (business purpose):

```
parties table (master):     vendors table:          employees table:
- id                        - id                    - id
- legal_name                - party_id (FK)         - party_id (FK)
- trading_name              - payment_terms         - salary
- tax_identity              - credit_limit          - job_title
```

**Benefits:**
- ✅ Single source of truth for contact info
- ✅ Zero data duplication
- ✅ Complete history when roles change
- ✅ Multi-role individuals supported

---

### Party Types

1. **INDIVIDUAL** - A natural person (employee, customer contact, vendor representative)
2. **ORGANIZATION** - A legal entity (company, charity, government agency)

```php
use Nexus\Party\Enums\PartyType;

// Check party type
if ($party->getPartyType() === PartyType::INDIVIDUAL) {
    // Handle individual-specific logic
}

if ($party->getPartyType()->isOrganization()) {
    // Organizations require tax registration
    $taxId = $party->getTaxIdentity();
}
```

---

### Party Relationships

Track connections between parties with effective dates:

| Relationship Type | From | To | Example |
|-------------------|------|----|----|
| `EMPLOYMENT_AT` | Individual | Organization | John works at Acme Corp |
| `CONTACT_FOR` | Individual | Organization | Mary is contact person for ABC Suppliers |
| `PART_OF` | Organization | Organization | Sales Dept is part of Acme Corp |
| `CUSTOMER_OF` | Party | Organization | Individual XYZ is customer of our company |
| `VENDOR_OF` | Party | Organization | Supplier ABC is vendor to our company |

```php
use Nexus\Party\Enums\RelationshipType;

// Create employment relationship
$employment = $relationshipManager->createRelationship(
    fromPartyId: $individualParty->getId(),
    toPartyId: $companyParty->getId(),
    type: RelationshipType::EMPLOYMENT_AT,
    effectiveFrom: new \DateTimeImmutable('2024-01-15')
);
```

---

## Basic Configuration

### Step 1: Implement Required Interfaces

The Party package defines **interfaces only**. You must implement them in your application layer.

#### Example: Eloquent Repository Implementation (Laravel)

```php
<?php

namespace App\Repositories\Party;

use App\Models\Party as PartyModel;
use Nexus\Party\Contracts\PartyRepositoryInterface;
use Nexus\Party\Contracts\PartyInterface;
use Nexus\Party\ValueObjects\TaxIdentity;

final readonly class EloquentPartyRepository implements PartyRepositoryInterface
{
    public function findById(string $id): ?PartyInterface
    {
        return PartyModel::find($id);
    }
    
    public function findByLegalName(string $tenantId, string $legalName): ?PartyInterface
    {
        return PartyModel::where('tenant_id', $tenantId)
            ->where('legal_name', $legalName)
            ->first();
    }
    
    public function findByTaxIdentity(string $tenantId, string $country, string $number): ?PartyInterface
    {
        return PartyModel::where('tenant_id', $tenantId)
            ->whereJsonContains('tax_identity->country', $country)
            ->whereJsonContains('tax_identity->number', $number)
            ->first();
    }
    
    public function save(array $data): PartyInterface
    {
        return PartyModel::create($data);
    }
}
```

---

### Step 2: Bind Interfaces in Service Provider

#### Laravel Example

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Party\Contracts\PartyRepositoryInterface;
use Nexus\Party\Contracts\PartyManagerInterface;
use Nexus\Party\Services\PartyManager;
use App\Repositories\Party\EloquentPartyRepository;

class PartyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind repository
        $this->app->singleton(
            PartyRepositoryInterface::class,
            EloquentPartyRepository::class
        );
        
        // Bind manager
        $this->app->singleton(PartyManager::class);
    }
}
```

Register in `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\PartyServiceProvider::class,
],
```

#### Symfony Example (services.yaml)

```yaml
services:
    # Repository binding
    Nexus\Party\Contracts\PartyRepositoryInterface:
        class: App\Repository\Party\DoctrinePartyRepository
    
    # Manager binding (autowiring handles dependencies)
    Nexus\Party\Services\PartyManager:
        arguments:
            $partyRepository: '@Nexus\Party\Contracts\PartyRepositoryInterface'
            $addressRepository: '@Nexus\Party\Contracts\AddressRepositoryInterface'
            $contactMethodRepository: '@Nexus\Party\Contracts\ContactMethodRepositoryInterface'
            $logger: '@Psr\Log\LoggerInterface'
```

---

### Step 3: Use the Package

```php
<?php

use Nexus\Party\Services\PartyManager;
use Nexus\Party\ValueObjects\TaxIdentity;
use Nexus\Party\ValueObjects\PostalAddress;
use Nexus\Party\Enums\AddressType;

final readonly class VendorService
{
    public function __construct(
        private PartyManager $partyManager
    ) {}
    
    public function createVendor(array $data): Vendor
    {
        // Step 1: Create party (master data)
        $party = $this->partyManager->createOrganization(
            tenantId: $this->getCurrentTenantId(),
            legalName: $data['company_name'],
            tradingName: $data['dba'] ?? null,
            taxIdentity: new TaxIdentity(
                country: 'MYS',
                number: $data['tax_number']
            )
        );
        
        // Step 2: Add address
        $this->partyManager->addAddress(
            partyId: $party->getId(),
            postalAddress: new PostalAddress(
                streetLine1: $data['address_line1'],
                city: $data['city'],
                postalCode: $data['postal_code'],
                country: 'MYS',
                state: $data['state']
            ),
            type: AddressType::BILLING,
            isPrimary: true
        );
        
        // Step 3: Create vendor entity (references party)
        $vendor = Vendor::create([
            'party_id' => $party->getId(),
            'payment_terms' => 'NET_30',
            'credit_limit' => 50000.00,
        ]);
        
        return $vendor;
    }
}
```

---

## Your First Integration

### Complete Example: Create Organization Party

```php
<?php

use Nexus\Party\Services\PartyManager;
use Nexus\Party\ValueObjects\TaxIdentity;
use Nexus\Party\ValueObjects\PostalAddress;
use Nexus\Party\Enums\AddressType;
use Nexus\Party\Enums\ContactMethodType;

// Inject PartyManager via constructor
public function __construct(
    private readonly PartyManager $partyManager
) {}

public function setupNewCustomer(): void
{
    // 1. Create organization party
    $party = $this->partyManager->createOrganization(
        tenantId: 'tenant-abc-123',
        legalName: 'ABC Manufacturing Sdn Bhd',
        tradingName: 'ABC Mfg',
        taxIdentity: new TaxIdentity(
            country: 'MYS',
            number: '202301012345',
            issueDate: new \DateTimeImmutable('2023-01-01')
        ),
        registrationDate: new \DateTimeImmutable('2023-01-01')
    );
    
    echo "Party created: {$party->getId()}\n";
    
    // 2. Add registered office address
    $address = $this->partyManager->addAddress(
        partyId: $party->getId(),
        postalAddress: new PostalAddress(
            streetLine1: 'No 123, Jalan Raja Laut',
            streetLine2: 'Level 5, Tower A',
            city: 'Kuala Lumpur',
            postalCode: '50350',
            country: 'MYS',
            state: 'Wilayah Persekutuan'
        ),
        type: AddressType::LEGAL,
        isPrimary: true
    );
    
    echo "Address added: {$address->getId()}\n";
    
    // 3. Add contact methods
    $email = $this->partyManager->addContactMethod(
        partyId: $party->getId(),
        type: ContactMethodType::EMAIL,
        value: 'info@abcmfg.com.my',
        isPrimary: true
    );
    
    $phone = $this->partyManager->addContactMethod(
        partyId: $party->getId(),
        type: ContactMethodType::PHONE,
        value: '+60123456789',
        isPrimary: true
    );
    
    echo "Contact methods added: Email: {$email->getId()}, Phone: {$phone->getId()}\n";
    
    // 4. Find potential duplicates (before creating domain entity)
    $duplicates = $this->partyManager->findPotentialDuplicates(
        tenantId: 'tenant-abc-123',
        legalName: 'ABC Manufacturing',
        taxIdentity: new TaxIdentity('MYS', '202301012345')
    );
    
    if (count($duplicates) > 0) {
        echo "Warning: Found " . count($duplicates) . " potential duplicates\n";
    }
}
```

---

## Next Steps

- **API Reference:** Read the [API Reference](api-reference.md) for detailed interface documentation
- **Integration Guide:** Check [Integration Guide](integration-guide.md) for Laravel and Symfony examples
- **Examples:** See [Examples](examples/) for more code samples:
  - [Basic Usage](examples/basic-usage.php)
  - [Advanced Usage](examples/advanced-usage.php)

---

## Troubleshooting

### Common Issues

**Issue 1: "Interface not instantiable" error**

```
Target interface [Nexus\Party\Contracts\PartyRepositoryInterface] is not instantiable.
```

**Cause:** Interface not bound in service container.

**Solution (Laravel):**
```php
// In App\Providers\PartyServiceProvider
$this->app->singleton(
    PartyRepositoryInterface::class,
    EloquentPartyRepository::class
);
```

**Solution (Symfony):**
```yaml
# In config/services.yaml
Nexus\Party\Contracts\PartyRepositoryInterface:
    class: App\Repository\DoctrinePartyRepository
```

---

**Issue 2: Duplicate party exception**

```
Nexus\Party\Exceptions\DuplicatePartyException: Party with legal name 'Acme Corp' already exists
```

**Cause:** Attempting to create party with same legal name or tax identity.

**Solution:** Use duplicate detection before creating:

```php
$duplicates = $this->partyManager->findPotentialDuplicates(
    tenantId: $tenantId,
    legalName: 'Acme Corp'
);

if (count($duplicates) > 0) {
    // Handle duplicate (merge, skip, or prompt user)
}
```

---

**Issue 3: Invalid postal code format**

```
Invalid postal code format for country MYS: 1234
```

**Cause:** Malaysian postal codes must be 5 digits.

**Solution:** Validate input before creating PostalAddress:

```php
// Malaysian postal code: 5 digits
$postalCode = str_pad($input['postal_code'], 5, '0', STR_PAD_LEFT);

$address = new PostalAddress(
    streetLine1: $input['street'],
    city: $input['city'],
    postalCode: $postalCode,
    country: 'MYS'
);
```

---

**Issue 4: Circular relationship detected**

```
Nexus\Party\Exceptions\CircularRelationshipException: Circular relationship detected
```

**Cause:** Attempting to create organizational hierarchy loop (A → B → C → A).

**Solution:** This is by design - circular references are prevented. Review your organizational structure.

---

## Performance Tips

### Database Indexes

Always index foreign keys and tenant scopes:

```sql
-- parties table
CREATE INDEX idx_parties_tenant_legal_name ON parties(tenant_id, legal_name);
CREATE INDEX idx_parties_tenant_tax ON parties(tenant_id, (tax_identity->>'country'), (tax_identity->>'number'));

-- party_addresses table
CREATE INDEX idx_addresses_party_primary ON party_addresses(party_id, is_primary);

-- party_contact_methods table
CREATE INDEX idx_contacts_party_type_primary ON party_contact_methods(party_id, type, is_primary);
```

---

### Eager Loading

When displaying party details, eager load addresses and contacts:

```php
// Laravel Eloquent example
$party = Party::with(['addresses', 'contactMethods'])->find($partyId);
```

---

### Caching

Cache frequently accessed data:

```php
// Cache primary email (5 minutes)
$primaryEmail = Cache::remember(
    "party.{$partyId}.primary_email",
    300,
    fn() => $this->contactMethodRepository->getPrimaryContactMethod(
        $partyId,
        ContactMethodType::EMAIL
    )
);
```

---

## What's Next?

Now that you understand the basics, explore:

1. **[API Reference](api-reference.md)** - Complete interface documentation
2. **[Integration Guide](integration-guide.md)** - Laravel and Symfony integration
3. **[Advanced Examples](examples/advanced-usage.php)** - Relationships, hierarchies, duplicate detection

---

**Package Maintainer:** Nexus Architecture Team  
**Last Updated:** 2025-11-25  
**Package Version:** 1.0.0
