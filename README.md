Package Description:

## Nexus\Tenant: Context and Isolation Engine
This Composer package provides the pure business logic and contracts necessary to manage multi-tenancy context, session, and administrative lifecycle. It is the central engine for identifying and validating the current tenant across the entire ERP system.

### Core Architecture & Provisions
The package is framework-agnostic and provides the following core components:

### Tenant Context Manager (TenantContextManager.php):

Provides the primary service for setting and retrieving the current active tenant ID during a request, process, or queue job.

Enables global access to the current tenant ID via a contract (TenantContextInterface), ensuring other packages can read the context.

### Tenant Impersonation Service:

Contains the validation and session logic to securely enable support staff impersonation, including tracking the original user and the tenant being accessed.

### Tenant Lifecycle Service:

Manages the pure business logic for administrative operations (creation, suspension, activation). This service relies on external contracts for actual persistence.

### Tenant Events:

Defines and dispatches framework-agnostic events for all lifecycle state changes (TenantCreatedEvent, TenantSuspendedEvent).

### Contracts (src/Contracts/):

Defines interfaces for all external dependencies: TenantRepositoryInterface, TenantContextInterface, and CacheRepositoryInterface.

## ⚠️ Implementation Requirements for Consuming Application (Nexus\Atomy)
The following critical features are not implemented by this package and must be provided by the application layer to achieve full functionality:

Data Isolation (Query Scoping):

The package DOES NOT provide database query scoping (automatic WHERE tenant_id = X clauses).

Implementation Requirement: The consuming application (Nexus\Atomy) must implement the database-specific feature (e.g., a Laravel Global Scope applied to all tenant-isolated Eloquent Models) using the tenant ID provided by the package's TenantContextInterface.

Persistence:

The package DOES NOT contain migrations or database logic.

Implementation Requirement: Nexus\Atomy must create the necessary migrations and implement the TenantRepositoryInterface using Eloquent models.

Cache Integration:

The package DOES NOT use any specific cache facade or driver.

Implementation Requirement: Nexus\Atomy must create a concrete class that implements the package's CacheRepositoryInterface using the framework's native cache solution and bind it in the service provider.