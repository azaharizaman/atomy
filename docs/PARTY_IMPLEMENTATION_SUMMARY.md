# Nexus\Party Implementation Summary

**Status**: ✅ **COMPLETE** (Core Package & Laravel Integration)  
**Date**: November 20, 2025  
**Package**: `nexus/party` v1.0.0  
**Application**: `apps/Atomy` (Laravel 12 Integration Layer)

---

## 📋 Executive Summary

The **Nexus\Party** package successfully implements the **DDD Party Pattern** to eliminate the "God Object" anti-pattern found in legacy ERP systems (e.g., Odoo's `res.partner`). This package provides a **framework-agnostic, composable contact management system** that decouples party identity (individuals, organizations) from domain entities (vendors, employees, customers).

### Key Achievements

✅ **Zero Framework Coupling**: Pure PHP 8.3+ package with no Laravel dependencies  
✅ **Value Object Immutability**: `TaxIdentity` and `PostalAddress` with country-specific validation  
✅ **Circular Reference Prevention**: Iterative relationship validation (max depth: 50)  
✅ **Individual Mobility**: Separate `Party` entities preserve transaction history across organizational changes  
✅ **Primary Flag Management**: Atomic operations for primary addresses/contact methods  
✅ **Temporal Relationships**: Effective dating for employment/organizational hierarchies  

---

## 🏗️ Architecture Overview

### The Party Pattern Philosophy

**Problem**: Traditional ERP systems embed contact information (name, email, phone, address, tax ID) directly into domain entities (Vendor, Employee, Customer), creating:
- **Data Duplication**: Same person's contact info stored in multiple tables
- **Update Anomalies**: Changing a phone number requires updating 3+ tables
- **Loss of History**: When an employee becomes a vendor, transaction history is fragmented

**Solution**: The Party Pattern uses **composition over inheritance**:
- A **Party** is a standalone identity (individual or organization)
- Domain entities (Vendor, Employee) **reference** a Party via `party_id` foreign key
- Contact details (addresses, emails, phones) belong to the Party, not the domain entity
- An individual can have multiple simultaneous roles (employee + vendor + customer)

### Package Structure

```
packages/Party/
├── composer.json                    # Framework-agnostic package definition
├── README.md                        # Comprehensive usage documentation
├── LICENSE                          # MIT License
└── src/
    ├── Enums/                       # Native PHP 8.3 Enums
    │   ├── PartyType.php            # INDIVIDUAL, ORGANIZATION, GOVERNMENT, INTERNAL
    │   ├── AddressType.php          # BILLING, SHIPPING, REGISTERED, PHYSICAL, MAILING
    │   ├── ContactMethodType.php    # EMAIL, PHONE_MOBILE, PHONE_LANDLINE, FAX, WEBSITE
    │   └── RelationshipType.php     # EMPLOYMENT_AT, PART_OF, OWNS, CUSTOMER_OF, VENDOR_OF
    ├── ValueObjects/                # Immutable data structures
    │   ├── TaxIdentity.php          # Country, number, registration/expiry dates
    │   └── PostalAddress.php        # Multi-country address validation (9 countries)
    ├── Contracts/                   # Interface definitions
    │   ├── PartyInterface.php
    │   ├── AddressInterface.php
    │   ├── ContactMethodInterface.php
    │   ├── PartyRelationshipInterface.php
    │   ├── PartyRepositoryInterface.php
    │   ├── AddressRepositoryInterface.php
    │   ├── ContactMethodRepositoryInterface.php
    │   └── PartyRelationshipRepositoryInterface.php
    ├── Services/                    # Business logic orchestration
    │   ├── PartyManager.php         # CRUD, address/contact management, duplicate detection
    │   └── PartyRelationshipManager.php  # Relationship lifecycle, circular validation
    └── Exceptions/                  # Domain exceptions
        ├── PartyNotFoundException.php
        ├── AddressNotFoundException.php
        ├── ContactMethodNotFoundException.php
        ├── RelationshipNotFoundException.php
        ├── CircularRelationshipException.php
        └── InvalidRelationshipTypeException.php
```

---

## 🧱 Core Components

### 1. Enums (Type Safety)

#### `PartyType` - Party Classification
```php
enum PartyType: string
{
    case INDIVIDUAL = 'individual';       // Natural person
    case ORGANIZATION = 'organization';   // Legal entity (company, charity)
    case GOVERNMENT = 'government';       // Government agency
    case INTERNAL = 'internal';           // Internal department/project

    public function requiresTaxRegistration(): bool;  // Only ORGANIZATION & GOVERNMENT
    public function canBeEmployee(): bool;            // Only INDIVIDUAL
}
```

#### `AddressType` - Address Purpose
```php
enum AddressType: string
{
    case BILLING = 'billing';             // Invoice address
    case SHIPPING = 'shipping';           // Delivery address
    case REGISTERED = 'registered';       // Legal registration address
    case PHYSICAL = 'physical';           // Actual location
    case MAILING = 'mailing';             // Postal correspondence

    public function isOfficial(): bool;               // REGISTERED only
    public function requiresProofOfAddress(): bool;   // REGISTERED & BILLING
}
```

#### `ContactMethodType` - Communication Channels
```php
enum ContactMethodType: string
{
    case EMAIL = 'email';
    case PHONE_MOBILE = 'phone_mobile';
    case PHONE_LANDLINE = 'phone_landline';
    case FAX = 'fax';
    case WEBSITE = 'website';

    public function getValidationPattern(): string;  // Regex for each type
    public function requiresVerification(): bool;    // EMAIL & PHONE_MOBILE
}
```

#### `RelationshipType` - Party Linkages
```php
enum RelationshipType: string
{
    case EMPLOYMENT_AT = 'employment_at';     // Individual → Organization
    case PART_OF = 'part_of';                 // Organization → Organization (hierarchy)
    case OWNS = 'owns';                       // Individual/Org → Organization (ownership)
    case CUSTOMER_OF = 'customer_of';         // Party → Organization
    case VENDOR_OF = 'vendor_of';             // Party → Organization

    public function requiresCircularCheck(): bool;   // PART_OF only
    public function isHierarchical(): bool;          // PART_OF & EMPLOYMENT_AT
    public function allowsMultiple(): bool;          // VENDOR_OF & CUSTOMER_OF
}
```

---

### 2. Value Objects (Immutability)

#### `TaxIdentity` - Tax Registration Data
```php
final readonly class TaxIdentity
{
    public function __construct(
        public string $country,              // ISO 3166-1 alpha-2 (MY, SG, US, etc.)
        public string $number,               // Tax ID number (e.g., SSM number, EIN)
        public ?\DateTimeImmutable $registeredOn = null,
        public ?\DateTimeImmutable $expiresOn = null,
    ) {}

    public function isExpired(): bool;
    public function toArray(): array;
    public static function fromArray(array $data): self;
}
```

**Validation Rules**:
- `country`: Must be valid ISO country code
- `number`: Non-empty string, no format validation (country-specific adapters handle this)
- `expiresOn`: If provided, must be after `registeredOn`

#### `PostalAddress` - Multi-Country Address
```php
final readonly class PostalAddress
{
    public function __construct(
        public string $line1,
        public ?string $line2,
        public string $city,
        public ?string $state,
        public string $postalCode,
        public string $country,              // ISO 3166-1 alpha-2
    ) {
        $this->validatePostalCode();         // Country-specific regex
    }

    public function getFullAddress(): string;
    public function getPostalCodePattern(string $country): string;
    private function validatePostalCode(): void;
}
```

**Postal Code Validation** (9 Countries):
| Country | Format | Regex |
|---------|--------|-------|
| **Malaysia (MY)** | 5 digits | `\d{5}` |
| **Singapore (SG)** | 6 digits | `\d{6}` |
| **USA (US)** | 5 or 9 digits | `\d{5}(-\d{4})?` |
| **UK** | Complex alphanumeric | `[A-Z]{1,2}\d{1,2}[A-Z]?\s?\d[A-Z]{2}` |
| **Canada (CA)** | A1A 1A1 | `[A-Z]\d[A-Z]\s?\d[A-Z]\d` |
| **Australia (AU)** | 4 digits | `\d{4}` |
| **India (IN)** | 6 digits | `\d{6}` |
| **China (CN)** | 6 digits | `\d{6}` |
| **Japan (JP)** | 7 digits | `\d{3}-?\d{4}` |

---

### 3. Service Layer (Business Logic)

#### `PartyManager` - Core Party Operations

**Public Methods**:
```php
// Party Creation
public function createOrganization(
    string $tenantId,
    string $legalName,
    ?string $displayName = null,
    ?TaxIdentity $taxIdentity = null,
    array $metadata = []
): PartyInterface;

public function createIndividual(
    string $tenantId,
    string $legalName,
    ?string $displayName = null,
    ?TaxIdentity $taxIdentity = null,
    array $metadata = []
): PartyInterface;

// Address Management
public function addAddress(
    string $partyId,
    PostalAddress $postalAddress,
    AddressType $type = AddressType::PHYSICAL,
    bool $isPrimary = false
): AddressInterface;

public function setPrimaryAddress(string $partyId, string $addressId): void;

// Contact Methods
public function addContactMethod(
    string $partyId,
    ContactMethodType $type,
    string $value,
    bool $isPrimary = false
): ContactMethodInterface;

// Duplicate Detection
public function findPotentialDuplicates(
    string $tenantId,
    ?string $legalName = null,
    ?TaxIdentity $taxIdentity = null
): array;
```

**Key Algorithms**:
1. **Primary Flag Management**: Before setting `is_primary=true`, clears all existing primary flags
2. **Duplicate Detection**: Fuzzy search by legal name + exact match on tax identity
3. **Validation**: Ensures tax identity is provided for organizations/government entities

#### `PartyRelationshipManager` - Relationship Lifecycle

**Public Methods**:
```php
// Relationship Creation
public function createRelationship(
    string $fromPartyId,
    string $toPartyId,
    RelationshipType $type,
    \DateTimeImmutable $effectiveFrom,
    ?\DateTimeImmutable $effectiveTo = null,
    array $metadata = []
): PartyRelationshipInterface;

// Relationship Termination
public function endRelationship(string $relationshipId, \DateTimeImmutable $effectiveTo): void;

// Circular Reference Prevention
private function validateNoCircularReference(
    string $fromPartyId,
    string $toPartyId,
    RelationshipType $type
): void;
```

**Circular Reference Prevention Algorithm**:
```php
// Iterative parent traversal (max depth: 50)
// Prevents: A → B → C → A (circular hierarchy)
if ($type === RelationshipType::PART_OF) {
    $visited = [$fromPartyId];
    $current = $toPartyId;
    $depth = 0;

    while ($current !== null && $depth < 50) {
        if (in_array($current, $visited)) {
            throw CircularRelationshipException::detected($fromPartyId, $toPartyId);
        }
        $visited[] = $current;
        $current = $this->getParentPartyId($current);
        $depth++;
    }
}
```

---

## 🗄️ Database Schema (Laravel Integration)

### Table: `parties`
```sql
CREATE TABLE parties (
    id CHAR(26) PRIMARY KEY,              -- ULID
    tenant_id CHAR(26) NOT NULL,
    party_type VARCHAR(20) NOT NULL,      -- Enum: individual, organization, etc.
    legal_name VARCHAR(255) NOT NULL,     -- Official name
    display_name VARCHAR(255),            -- Friendly name
    tax_identity JSON,                    -- TaxIdentity value object
    metadata JSON,                        -- Flexible data
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX idx_tenant_legal_name (tenant_id, legal_name),
    INDEX idx_tenant_type (tenant_id, party_type)
);
```

### Table: `party_addresses`
```sql
CREATE TABLE party_addresses (
    id CHAR(26) PRIMARY KEY,
    party_id CHAR(26) NOT NULL,
    address_type VARCHAR(20) NOT NULL,    -- Enum: billing, shipping, etc.
    postal_address JSON NOT NULL,         -- PostalAddress value object
    is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (party_id) REFERENCES parties(id) ON DELETE CASCADE,
    INDEX idx_party_primary (party_id, is_primary),
    INDEX idx_party_type (party_id, address_type)
);
```

### Table: `party_contact_methods`
```sql
CREATE TABLE party_contact_methods (
    id CHAR(26) PRIMARY KEY,
    party_id CHAR(26) NOT NULL,
    contact_type VARCHAR(20) NOT NULL,    -- Enum: email, phone_mobile, etc.
    value VARCHAR(255) NOT NULL,          -- Contact value
    is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (party_id) REFERENCES parties(id) ON DELETE CASCADE,
    INDEX idx_party_primary (party_id, is_primary),
    INDEX idx_party_type (party_id, contact_type),
    INDEX idx_value (value)
);
```

### Table: `party_relationships`
```sql
CREATE TABLE party_relationships (
    id CHAR(26) PRIMARY KEY,
    from_party_id CHAR(26) NOT NULL,      -- Source party
    to_party_id CHAR(26) NOT NULL,        -- Target party
    relationship_type VARCHAR(30) NOT NULL,
    effective_from DATE NOT NULL,
    effective_to DATE,                     -- NULL = still active
    metadata JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (from_party_id) REFERENCES parties(id) ON DELETE CASCADE,
    FOREIGN KEY (to_party_id) REFERENCES parties(id) ON DELETE CASCADE,
    INDEX idx_from_party (from_party_id),
    INDEX idx_to_party (to_party_id),
    INDEX idx_type (relationship_type),
    INDEX idx_effective_from (effective_from),
    INDEX idx_effective_to (effective_to),
    INDEX idx_from_to_type (from_party_id, to_party_id, relationship_type)
);
```

---

## 🔌 Laravel Integration Layer

### Repository Implementations

#### `EloquentPartyRepository`
```php
namespace App\Repositories\Party;

final readonly class EloquentPartyRepository implements PartyRepositoryInterface
{
    public function findById(string $id): PartyInterface;
    public function findByLegalName(string $tenantId, string $legalName): ?PartyInterface;
    public function findByTaxIdentity(string $tenantId, TaxIdentity $taxIdentity): ?PartyInterface;
    public function searchByName(string $tenantId, string $query, int $limit = 50): array;
    public function findByType(string $tenantId, PartyType $type, int $limit = 100): array;
    public function save(PartyInterface $party): void;
    public function delete(string $id): void;
}
```

**Key Queries**:
- **Tax Identity Lookup**: `whereJsonContains('tax_identity->country', 'MY')`
- **Fuzzy Name Search**: `WHERE legal_name LIKE '%query%' OR display_name LIKE '%query%'`

#### `EloquentPartyRelationshipRepository`
```php
public function getOrganizationalChain(string $partyId, int $maxDepth = 10): array
{
    // Uses recursive CTE (Common Table Expression)
    DB::select("
        WITH RECURSIVE org_chain AS (
            SELECT id, from_party_id, to_party_id, 1 as depth
            FROM party_relationships
            WHERE from_party_id = :party_id AND relationship_type = 'part_of'
            
            UNION ALL
            
            SELECT pr.id, pr.from_party_id, pr.to_party_id, oc.depth + 1
            FROM party_relationships pr
            INNER JOIN org_chain oc ON pr.from_party_id = oc.to_party_id
            WHERE oc.depth < :max_depth
        )
        SELECT * FROM org_chain
    ");
}
```

### Service Provider Bindings

**File**: `apps/Atomy/app/Providers/PartyServiceProvider.php`

```php
final class PartyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repository Bindings
        $this->app->singleton(PartyRepositoryInterface::class, EloquentPartyRepository::class);
        $this->app->singleton(AddressRepositoryInterface::class, EloquentAddressRepository::class);
        $this->app->singleton(ContactMethodRepositoryInterface::class, EloquentContactMethodRepository::class);
        $this->app->singleton(PartyRelationshipRepositoryInterface::class, EloquentPartyRelationshipRepository::class);

        // Service Bindings
        $this->app->singleton(PartyManager::class, function ($app) {
            return new PartyManager(
                partyRepository: $app->make(PartyRepositoryInterface::class),
                addressRepository: $app->make(AddressRepositoryInterface::class),
                contactMethodRepository: $app->make(ContactMethodRepositoryInterface::class),
                logger: $app->make(LoggerInterface::class)
            );
        });

        $this->app->singleton(PartyRelationshipManager::class, function ($app) {
            return new PartyRelationshipManager(
                relationshipRepository: $app->make(PartyRelationshipRepositoryInterface::class),
                partyRepository: $app->make(PartyRepositoryInterface::class),
                logger: $app->make(LoggerInterface::class)
            );
        });
    }
}
```

**Registered in**: `apps/Atomy/bootstrap/app.php`

---

## 📝 Usage Examples

### Example 1: Create Organization with Contact Details

```php
use Nexus\Party\Services\PartyManager;
use Nexus\Party\ValueObjects\TaxIdentity;
use Nexus\Party\ValueObjects\PostalAddress;
use Nexus\Party\Enums\AddressType;
use Nexus\Party\Enums\ContactMethodType;

$partyManager = app(PartyManager::class);

// Create organization
$party = $partyManager->createOrganization(
    tenantId: $currentTenant->id,
    legalName: 'Acme Corporation Sdn Bhd',
    displayName: 'Acme Corp',
    taxIdentity: new TaxIdentity(
        country: 'MY',
        number: '202301012345',
        registeredOn: new \DateTimeImmutable('2023-01-01')
    ),
    metadata: ['industry' => 'Manufacturing']
);

// Add registered address
$address = $partyManager->addAddress(
    partyId: $party->getId(),
    postalAddress: new PostalAddress(
        line1: 'No 123, Jalan Raja Laut',
        line2: 'Level 5',
        city: 'Kuala Lumpur',
        state: 'Wilayah Persekutuan',
        postalCode: '50350',
        country: 'MY'
    ),
    type: AddressType::REGISTERED,
    isPrimary: true
);

// Add contact methods
$partyManager->addContactMethod(
    partyId: $party->getId(),
    type: ContactMethodType::EMAIL,
    value: 'info@acmecorp.com.my',
    isPrimary: true
);

$partyManager->addContactMethod(
    partyId: $party->getId(),
    type: ContactMethodType::PHONE_MOBILE,
    value: '+60123456789',
    isPrimary: true
);
```

### Example 2: Create Employee Relationship

```php
use Nexus\Party\Services\PartyRelationshipManager;
use Nexus\Party\Enums\RelationshipType;

$relationshipManager = app(PartyRelationshipManager::class);

// Create individual party for employee
$individualParty = $partyManager->createIndividual(
    tenantId: $currentTenant->id,
    legalName: 'Ahmad bin Abdullah',
    displayName: 'Ahmad Abdullah'
);

// Create employment relationship
$employment = $relationshipManager->createRelationship(
    fromPartyId: $individualParty->getId(),
    toPartyId: $companyParty->getId(),
    type: RelationshipType::EMPLOYMENT_AT,
    effectiveFrom: new \DateTimeImmutable('2024-01-15'),
    metadata: ['position' => 'Senior Engineer', 'department' => 'IT']
);

// When employee resigns
$relationshipManager->endRelationship(
    relationshipId: $employment->getId(),
    effectiveTo: new \DateTimeImmutable('2025-03-31')
);
```

### Example 3: Organizational Hierarchy

```php
// Create subsidiary relationship
$subsidiary = $relationshipManager->createRelationship(
    fromPartyId: $subsidiaryParty->getId(),
    toPartyId: $parentCompanyParty->getId(),
    type: RelationshipType::PART_OF,
    effectiveFrom: new \DateTimeImmutable('2024-01-01')
);

// Get full organizational chain (uses recursive CTE)
$relationshipRepository = app(PartyRelationshipRepositoryInterface::class);
$chain = $relationshipRepository->getOrganizationalChain(
    partyId: $departmentParty->getId(),
    maxDepth: 10
);
```

---

## 🚀 Next Steps: Domain Entity Refactoring

The Party package is now ready for integration. The following domain entities need refactoring:

### Priority 1: Vendor Refactoring (`Nexus\Payable`)

**Current Schema**:
```sql
CREATE TABLE vendors (
    id CHAR(26),
    tenant_id CHAR(26),
    name VARCHAR(255),          -- ❌ Remove (use party.legal_name)
    email VARCHAR(255),         -- ❌ Remove (use party_contact_methods)
    phone VARCHAR(50),          -- ❌ Remove (use party_contact_methods)
    address TEXT,               -- ❌ Remove (use party_addresses)
    tax_id VARCHAR(100),        -- ❌ Remove (use party.tax_identity)
    -- ... other vendor-specific fields
);
```

**Target Schema**:
```sql
CREATE TABLE vendors (
    id CHAR(26),
    tenant_id CHAR(26),
    party_id CHAR(26) NOT NULL,  -- ✅ Add FK to parties table
    payment_terms VARCHAR(50),
    credit_limit DECIMAL(15,2),
    -- ... other vendor-specific fields
    FOREIGN KEY (party_id) REFERENCES parties(id)
);
```

**Migration Strategy**:
```php
// Migration: 2025_11_20_add_party_id_to_vendors_table.php
Schema::table('vendors', function (Blueprint $table) {
    $table->char('party_id', 26)->after('tenant_id')->nullable();
    $table->foreign('party_id')->references('id')->on('parties')->onDelete('restrict');
});

// Data migration: Create Party for each existing Vendor
$vendors = Vendor::all();
foreach ($vendors as $vendor) {
    $party = $partyManager->createOrganization(
        tenantId: $vendor->tenant_id,
        legalName: $vendor->name,
        taxIdentity: $vendor->tax_id ? new TaxIdentity('MY', $vendor->tax_id) : null
    );
    
    if ($vendor->address) {
        $partyManager->addAddress($party->getId(), /* parse address */, AddressType::BILLING, true);
    }
    
    if ($vendor->email) {
        $partyManager->addContactMethod($party->getId(), ContactMethodType::EMAIL, $vendor->email, true);
    }
    
    $vendor->update(['party_id' => $party->getId()]);
}

// Drop old columns
Schema::table('vendors', function (Blueprint $table) {
    $table->dropColumn(['name', 'email', 'phone', 'address', 'tax_id']);
});
```

### Priority 2: Employee Refactoring (`Nexus\Hrm`)

**Changes Required**:
- Add `party_id` FK to `employees` table
- Remove `first_name`, `last_name`, `email`, `phone_number` columns
- Create `PartyRelationship` (EMPLOYMENT_AT) when employee joins
- Update `EmployeeManager::createEmployee()` to call `PartyManager::createIndividual()` first

### Priority 3: Company Refactoring (`Nexus\Backoffice`)

**Changes Required**:
- Add `party_id` FK to `companies` table
- Remove `name`, `registration_number`, `tax_id` columns (keep `code` for internal reference)
- Update statutory adapters to use `$company->getParty()->getTaxIdentity()->getNumber()`

---

## 🧪 Testing Recommendations

### Unit Tests (Package Level)
```php
// packages/Party/tests/Services/PartyManagerTest.php
test('creates organization with tax identity', function () {
    $partyManager = new PartyManager($mockRepo, $mockAddressRepo, $mockContactRepo, $mockLogger);
    
    $party = $partyManager->createOrganization(
        tenantId: 'tenant-123',
        legalName: 'Test Corp',
        taxIdentity: new TaxIdentity('MY', '202301012345')
    );
    
    expect($party->getPartyType())->toBe(PartyType::ORGANIZATION);
    expect($party->getTaxIdentity()->number)->toBe('202301012345');
});

test('prevents circular organizational relationships', function () {
    $relationshipManager = new PartyRelationshipManager($mockRepo, $mockPartyRepo, $mockLogger);
    
    // A → B → C, trying to add C → A should throw
    expect(fn() => $relationshipManager->createRelationship(
        fromPartyId: 'party-c',
        toPartyId: 'party-a',
        type: RelationshipType::PART_OF,
        effectiveFrom: new \DateTimeImmutable()
    ))->toThrow(CircularRelationshipException::class);
});
```

### Integration Tests (Atomy Level)
```php
// apps/Atomy/tests/Feature/PartyIntegrationTest.php
test('vendor creation flow creates party first', function () {
    $response = $this->postJson('/api/vendors', [
        'party' => [
            'legal_name' => 'ABC Suppliers Sdn Bhd',
            'tax_identity' => ['country' => 'MY', 'number' => '202301012345'],
        ],
        'payment_terms' => 'NET_30',
        'credit_limit' => 50000.00,
    ]);
    
    $response->assertCreated();
    $vendor = Vendor::find($response->json('data.id'));
    
    expect($vendor->party_id)->not->toBeNull();
    expect($vendor->getParty()->getLegalName())->toBe('ABC Suppliers Sdn Bhd');
});
```

---

## 📊 Performance Considerations

### Indexing Strategy
- **Composite Indexes**: `(tenant_id, legal_name)` for fast tenant-scoped searches
- **JSON Indexing**: `tax_identity->country` and `tax_identity->number` for tax lookups
- **Relationship Queries**: Index on `(from_party_id, to_party_id, relationship_type)` for traversal

### Query Optimization
- **Recursive CTEs**: `getOrganizationalChain()` uses database-native recursion instead of N+1 queries
- **Primary Flag Queries**: Index on `(party_id, is_primary)` for fast primary lookups
- **Eager Loading**: Always eager load addresses/contacts when displaying party details

### Caching Recommendations
```php
// Cache organizational chain (rarely changes)
$chain = Cache::remember("party.{$partyId}.org_chain", 3600, function() use ($partyId) {
    return $this->relationshipRepository->getOrganizationalChain($partyId);
});

// Cache primary contact methods (frequently accessed)
$primaryEmail = Cache::remember("party.{$partyId}.primary_email", 300, function() use ($partyId) {
    return $this->contactMethodRepository->getPrimaryContactMethod($partyId, ContactMethodType::EMAIL);
});
```

---

## 🔒 Security & Compliance

### Data Privacy (GDPR/PDPA)
- **Right to Erasure**: Deleting a party cascades to addresses/contacts via FK constraints
- **Data Portability**: `Party::toArray()` exports all data in JSON format
- **Audit Trail**: Use `Nexus\AuditLogger` to log all party modifications

### Access Control
- **Tenant Isolation**: All queries scope by `tenant_id`
- **Permission Checks**: Implement `PartyPolicy` to control who can view/edit parties
- **Sensitive Data**: `tax_identity` should be encrypted at rest (use Laravel's encrypted casting)

---

## 🎯 Success Metrics

| Metric | Target | Status |
|--------|--------|--------|
| **Framework Coupling** | Zero Laravel dependencies in package | ✅ Achieved |
| **Value Object Immutability** | All value objects readonly | ✅ Achieved |
| **Circular Reference Prevention** | Max depth 50, iterative algorithm | ✅ Implemented |
| **Database Normalization** | No contact data duplication | ✅ Schema designed |
| **Migration Coverage** | 4 tables with proper FKs/indexes | ✅ Complete |
| **Service Provider Bindings** | All interfaces bound | ✅ Registered |
| **Repository Pattern** | 4 repositories implemented | ✅ Complete |

---

## 📚 Related Documentation

- **Package README**: `packages/Party/README.md` - Usage guide and API reference
- **Architectural Decision**: `.github/copilot-instructions.md` - Party Pattern rationale
- **Migration Guide**: (To be created) - Step-by-step domain entity refactoring

---

## 🏁 Conclusion

The **Nexus\Party** package successfully decouples party identity from domain entities, providing a robust, scalable foundation for contact management across the ERP system. The implementation adheres to all monorepo architectural principles:

✅ **Pure PHP package** (framework-agnostic)  
✅ **Contract-driven design** (dependency inversion)  
✅ **Value object immutability** (defensive programming)  
✅ **Repository pattern** (abstracted persistence)  
✅ **Service layer orchestration** (business logic separation)  

**Next Phase**: Refactor `Vendor`, `Employee`, and `Company` entities to use `party_id` FK, removing all legacy contact fields.

---

**Package Version**: 1.0.0  
**Author**: Azahari Zaman  
**Last Updated**: November 20, 2025
