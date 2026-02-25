# Technology Stack: Nexus (Atomy)

## Backend
- **Core Language:** PHP 8.3+ (utilizing strict typing, native enums, and readonly classes).
- **Architecture:** Custom 3-Layer Monorepo (Atomic Packages, Orchestrators, Adapters).
- **Framework (Adapters):** Laravel 11.x (or latest compatible version).
- **Package Manager:** Composer 2.x.
- **Dependency Injection:** Laravel's Service Container (used in Adapters to bridge Orchestrator interfaces to concrete implementations).

## Frontend (Reference Apps)
- **Framework:** React.
- **Data Fetching/Routing:** Inertia.js (for a seamless SPA feel within Laravel).
- **Styling:** Vanilla CSS (preferred for flexibility) or Tailwind CSS (if specified in specific app contexts).
- **Component Library:** shadcn/ui (React).

## Database & Persistence
- **Driver:** MariaDB/MySQL (via Eloquent ORM in the Adapter layer).
- **Migrations:** Managed within framework-specific Adapter directories.
- **Caching:** Redis (standard for Laravel SaaS).

## Quality & Verification
- **Unit Testing:** PHPUnit ^11.0 (mandatory for all packages and orchestrators).
- **Static Analysis:** PHPStan ^2.1 (Level 8 or higher).
- **Code Style:** PHP-CS-Fixer (following PSR-12 or project-specific rules).
