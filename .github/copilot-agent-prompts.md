# GitHub Copilot Agent Prompts for Nexus Monorepo

These prompts are designed for use with GitHub Copilot's agentic coding features. Reference these when asking Copilot to implement features autonomously.

---

## 🎯 Prompt 1: Create a New Atomic Package

```
Create a new atomic package named [PACKAGE_NAME] in the Nexus monorepo following these requirements:

ARCHITECTURE COMPLIANCE:
- Package location: packages/[PackageFolder]/
- Namespace: Nexus\[PackageFolder]
- Composer name: nexus/[package-name]
- Must be framework-agnostic (pure PHP)
- Must NOT contain any database migrations, models, or persistence logic
- Must define persistence needs via Contracts (Interfaces)

REQUIRED STRUCTURE:
1. packages/[PackageFolder]/composer.json
   - Set name: "nexus/[package-name]"
   - PSR-4 autoload: "Nexus\\[PackageFolder]\\": "src/"
   - Add appropriate description and license

2. packages/[PackageFolder]/README.md
   - Package purpose and features
   - Installation instructions
   - Basic usage examples
   - Contract implementations required

3. packages/[PackageFolder]/LICENSE
   - Add appropriate license file

4. packages/[PackageFolder]/src/Contracts/
   - [Entity]Interface.php - Define data structure contract
   - [Entity]RepositoryInterface.php - Define persistence contract
   - Add clear docblocks explaining each method

5. packages/[PackageFolder]/src/Services/
   - [Entity]Manager.php - Core business logic
   - Use constructor dependency injection
   - Depend only on Contracts, not concrete implementations
   - Add input validation and descriptive exceptions

6. packages/[PackageFolder]/src/Exceptions/
   - [Entity]NotFoundException.php
   - [Entity]InvalidException.php
   - Other domain-specific exceptions

7. packages/[PackageFolder]/src/[PackageFolder]ServiceProvider.php (optional)
   - Laravel integration helper
   - Register any needed bindings

VALIDATION CHECKS:
- ✓ No use of Eloquent, Request, or Laravel facades
- ✓ All business logic in Services
- ✓ All persistence needs defined as Contracts
- ✓ Proper PSR-12 formatting
- ✓ Strict types declared
- ✓ Comprehensive docblocks

FEATURE REQUIREMENTS:
[Describe the business logic this package should provide]

After creating the package, update the root composer.json repositories array and provide installation instructions for Atomy.
```

---

## 🎯 Prompt 2: Implement Package in Atomy

```
Implement the [PACKAGE_NAME] package in the Atomy application following these requirements:

PREREQUISITES:
- Package must already exist in packages/[PackageFolder]/
- Package must be installed via composer in apps/Atomy/

REQUIRED IMPLEMENTATION:
1. Create Database Migration(s) in apps/Atomy/database/migrations/
   - Filename: YYYY_MM_DD_HHMMSS_create_[table]_table.php
   - Schema must support all Contract requirements
   - Add proper indexes and foreign keys
   - Use appropriate column types

2. Create Eloquent Model(s) in apps/Atomy/app/Models/
   - Implement the [Entity]Interface from the package
   - Filename: [Entity].php
   - Add proper $fillable, $casts, and relationships
   - Add docblocks for IDE support

3. Create Repository Class(es) in apps/Atomy/app/Repositories/
   - Implement [Entity]RepositoryInterface from package
   - Filename: Db[Entity]Repository.php
   - Use Eloquent for database operations
   - Add proper error handling and exceptions

4. Bind Contracts in apps/Atomy/app/Providers/AppServiceProvider.php
   - In register() method, bind each interface to concrete implementation
   - Example: $this->app->bind(EntityRepositoryInterface::class, DbEntityRepository::class);

5. Create API Controller in apps/Atomy/app/Http/Controllers/Api/
   - Filename: [Entity]Controller.php
   - Inject package services via constructor
   - Implement RESTful methods (index, store, show, update, destroy)
   - Return JSON responses with proper HTTP status codes
   - Add validation using Form Requests

6. Add API Routes in apps/Atomy/routes/api.php
   - Use /v1/[resource] pattern
   - Register resource or explicit routes
   - Add appropriate middleware

VALIDATION CHECKS:
- ✓ Models implement package interfaces
- ✓ Repositories implement package repository interfaces
- ✓ All contracts are bound in service provider
- ✓ Migrations create all necessary tables
- ✓ API endpoints follow RESTful conventions
- ✓ Controllers use package services, not direct models

TESTING:
- Create feature tests for each API endpoint
- Test contract implementations work correctly
- Verify database relationships

Provide a summary of all files created and next steps for testing.
```

---

## 🎯 Prompt 3: Create Edward Terminal Command

```
Create a Terminal UI command in the Edward application for [FEATURE_DESCRIPTION] following these requirements:

PREREQUISITES:
- Atomy must have the API endpoint implemented at /v1/[resource]
- Edward must have AtomyApiClient set up

REQUIRED IMPLEMENTATION:
1. Update apps/Edward/app/Http/Clients/AtomyApiClient.php
   - Add method(s) for calling Atomy API endpoints
   - Use Guzzle HTTP client for requests
   - Handle response parsing and error cases
   - Add proper type hints and docblocks

2. Create Artisan Command in apps/Edward/app/Console/Commands/
   - Filename: [Feature]Command.php
   - Signature: edward:[feature-name]
   - Description: Clear description of what command does
   - Inject AtomyApiClient via constructor

3. Command Implementation:
   - Use Symfony Console components for UI
   - Add colorful output (info, comment, error, success)
   - Create tables for data display
   - Add progress bars for long operations
   - Implement interactive prompts for user input
   - Handle API errors gracefully with user-friendly messages

4. Register Command in apps/Edward/app/Console/Kernel.php (if needed)

VALIDATION CHECKS:
- ✓ No direct database access
- ✓ No package dependencies (only uses API)
- ✓ Proper error handling for API failures
- ✓ User-friendly terminal output
- ✓ Input validation before API calls

UI GUIDELINES:
- Use $this->info() for informational messages (green)
- Use $this->comment() for secondary info (yellow)
- Use $this->error() for errors (red)
- Use $this->table() for displaying data
- Use $this->ask() / $this->confirm() for user input
- Use $this->choice() for menu selections

Provide usage examples and testing instructions.
```

---

## 🎯 Prompt 4: Implement Complete Feature End-to-End

```
Implement the feature "[FEATURE_DESCRIPTION]" across the entire Nexus monorepo following the architectural workflow:

USER STORY:
[Provide clear user story with acceptance criteria]

STEP 1: ANALYZE REQUIREMENTS
- Determine if core logic exists in existing packages
- Identify which package(s) should handle this logic
- Determine if a new package is needed
- List all data entities involved

STEP 2: PACKAGE LAYER (if new logic needed)
Create or update package in packages/:
- Define/update Contracts for entities and repositories
- Implement/update business logic in Services
- Add any new domain exceptions
- Ensure pure PHP, no persistence code

STEP 3: ATOMY LAYER (persistence & API)
Implement in apps/Atomy/:
- Create database migrations for new tables/columns
- Create/update Eloquent models implementing contracts
- Create/update repository implementations
- Bind contracts in AppServiceProvider
- Create API controller with RESTful methods
- Add routes in routes/api.php
- Create Form Request validation classes
- Write feature tests

STEP 4: EDWARD LAYER (optional - user interface)
Implement in apps/Edward/:
- Add API client methods to AtomyApiClient
- Create Artisan command for terminal UI
- Implement interactive command logic
- Add user-friendly output formatting

VALIDATION CHECKLIST:
- ✓ Logic is in packages (not Atomy)
- ✓ Packages are framework-agnostic
- ✓ Persistence is in Atomy only
- ✓ All contracts are implemented
- ✓ API endpoints follow RESTful patterns
- ✓ Edward uses API only (no database/package access)
- ✓ Tests are written and passing

DELIVERABLES:
- List all files created/modified
- Show example API requests/responses
- Provide testing commands
- Document any setup steps needed

Follow the architectural guidelines strictly. Ask clarifying questions if the feature requirements are ambiguous.
```

---

## 🎯 Prompt 5: Refactor Code to Comply with Architecture

```
Refactor the [COMPONENT/FEATURE] to comply with Nexus architectural guidelines:

CURRENT VIOLATIONS:
[List architectural violations found]

REFACTORING PLAN:
1. IDENTIFY MISPLACED CODE:
   - Business logic in Atomy → Move to package
   - Persistence logic in package → Move to Atomy
   - Edward accessing database → Use API instead
   - Laravel-specific code in package → Make framework-agnostic

2. CREATE/UPDATE PACKAGE (if logic is in wrong place):
   - Extract business logic to package Service classes
   - Define Contracts for persistence needs
   - Remove Laravel dependencies
   - Make code pure PHP

3. UPDATE ATOMY (if contracts missing):
   - Implement extracted contracts
   - Keep persistence logic here
   - Update controllers to use package services
   - Maintain API layer

4. UPDATE EDWARD (if coupling exists):
   - Replace direct database calls with API calls
   - Remove package dependencies
   - Use AtomyApiClient exclusively

VALIDATION AFTER REFACTORING:
- ✓ All architectural rules are followed
- ✓ Tests still pass (or are updated)
- ✓ No functionality is broken
- ✓ Code is more maintainable and reusable

Document all changes made and verify functionality is preserved.
```

---

## 🎯 Prompt 6: Add Package Dependency

```
Update [PACKAGE_A] to depend on [PACKAGE_B] following these requirements:

PREREQUISITES:
- Both packages must already exist
- Dependency must make architectural sense (no circular dependencies)

IMPLEMENTATION:
1. Update packages/[PackageA]/composer.json:
   - Add to "require" section: "nexus/[package-b]": "^1.0"
   - Update composer.lock

2. Update Package A Service Classes:
   - Import necessary interfaces from Package B
   - Inject Package B services via constructor
   - Use only public APIs from Package B

3. Update Package A README.md:
   - Document the dependency
   - Explain why dependency is needed
   - Update installation instructions

4. Test in Atomy:
   - Ensure both packages work together
   - Verify no circular dependencies exist
   - Test integrated functionality

VALIDATION:
- ✓ Package A only depends on Package B's public contracts
- ✓ No circular dependencies created
- ✓ Dependency is explicitly declared in composer.json
- ✓ Documentation is updated
- ✓ Tests pass in Atomy

Provide migration guide if this affects existing Atomy implementations.
```

---

## 🎯 Prompt 7: Create Package Tests

```
Create comprehensive tests for the [PACKAGE_NAME] package following these requirements:

TEST STRUCTURE:
1. Create packages/[PackageFolder]/tests/ directory
2. Create packages/[PackageFolder]/phpunit.xml configuration
3. Add PHPUnit to package's dev dependencies

TEST COVERAGE:
1. Unit Tests for Services (tests/Unit/Services/):
   - Test business logic in isolation
   - Mock all repository dependencies
   - Test validation rules
   - Test exception cases
   - Test edge cases

2. Contract Tests (tests/Contracts/):
   - Verify interface definitions
   - Document expected behaviors
   - Provide reference implementation tests

MOCKING STRATEGY:
- Create mock repositories for testing
- Use PHPUnit mocks for dependencies
- Test services without database
- Test all code paths

VALIDATION:
- ✓ Tests run independently (no database needed)
- ✓ 100% coverage of service logic
- ✓ All exception cases tested
- ✓ All edge cases covered
- ✓ Tests are fast (<1 second per test)

Provide commands to run tests and coverage report.
```

---

## Usage Instructions

1. **Copy the relevant prompt** for your task
2. **Fill in the bracketed placeholders** with specific details
3. **Submit to GitHub Copilot Chat** or use in Copilot coding agent
4. **Review the generated code** for compliance with architecture
5. **Run validation checks** before committing

## Quick Reference

- **New feature?** → Use Prompt 4 (End-to-End)
- **New package only?** → Use Prompt 1
- **Implement existing package?** → Use Prompt 2
- **Terminal UI only?** → Use Prompt 3
- **Code violations?** → Use Prompt 5 (Refactor)
- **Link packages?** → Use Prompt 6
- **Need tests?** → Use Prompt 7
