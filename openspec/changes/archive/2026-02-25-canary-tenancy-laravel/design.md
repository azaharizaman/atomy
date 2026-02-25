## Context

Following the Symfony canary verification, we are now implementing a Laravel-based canary to ensure the `TenancyOperations` orchestrator works correctly within Laravel's ecosystem. This is a critical step to validate the Layer 3 adapter patterns for our primary framework.

## Goals / Non-Goals

**Goals:**
- Scaffolding a fresh Laravel 11 application in `apps/laravel-canary-tenancy`.
- Implementing a `TenantServiceProvider` to handle all IoC bindings for orchestrators and adapters.
- Providing concrete `Logging*` adapters (Layer 3) within the Laravel app.
- Creating a simple web UI (Blade/Tailwind) for tenant onboarding and status visualization.
- Demonstrating the Three-Layer Architecture in a Laravel context.

**Non-Goals:**
- Implementing actual database persistence (mocks/logging will be used as in the Symfony canary).
- Full user authentication/authorization system (just open routes for testing).
- Setting up a full build pipeline for CSS/JS (CDN will be used for Tailwind).

## Decisions

### 1. Framework & Structure
We will use Laravel 11 with the skeleton located in `apps/laravel-canary-tenancy`.

### 2. Service Wiring (The "Nexus" Way)
We will implement a `NexusServiceProvider` (or `TenantServiceProvider`) that explicitly binds orchestrator interfaces to their implementations and maps required adapters. This will follow the patterns seen in `adapters/Laravel/Tenant` but tailored for this canary.

### 3. Adapters
We will replicate the `Logging*` adapters created for the Symfony canary but adapt them to Laravel's conventions (e.g., using Laravel's `Log` facade or injecting `Psr\Log\LoggerInterface`).

### 4. Frontend Strategy
We will use **Blade** with **Tailwind CSS (CDN)**. This allows for a visually appealing status dashboard and onboarding form without the overhead of Vite/Node.js setup during initial verification.

## Risks / Trade-offs

- **[Risk] Path complexity in local repositories** → **Mitigation**: Use `composer` path repositories with relative paths, similar to the Symfony setup.
- **[Risk] Duplicate adapter logic** → **Mitigation**: While some logic is duplicated from the Symfony canary, this is intentional to keep the canary applications independent and focused on their respective framework implementations.
