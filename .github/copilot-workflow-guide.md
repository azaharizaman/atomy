# GitHub Copilot Workflow Guide for Nexus Development

This guide provides step-by-step workflows for common development tasks in the Nexus monorepo, optimized for GitHub Copilot assistance.

---

## 🚀 Workflow 1: Implementing a New Business Domain

**When to use:** You need to add a completely new business domain (e.g., CRM, Accounting, Payroll)

### Step-by-Step Process

#### Phase 1: Planning (Human-Led)
1. **Understand the domain requirements**
   - Review user stories and acceptance criteria
   - Identify core entities and operations
   - Determine data relationships
   - Check if any existing packages can be reused

2. **Ask Copilot for architectural guidance:**
   ```
   Based on ARCHITECTURE.md, help me plan a new [DOMAIN] package for Nexus.
   The domain needs to handle: [LIST_REQUIREMENTS]
   What entities, contracts, and services should this package include?
   ```

#### Phase 2: Package Creation (Copilot-Assisted)
3. **Use Agent Prompt 1** to create the package structure:
   ```
   @workspace /agent Create a new atomic package named [DOMAIN] following the 
   template in .github/copilot-agent-prompts.md (Prompt 1). The package should 
   handle [BUSINESS_LOGIC_DESCRIPTION].
   ```

4. **Review generated code:**
   - ✓ Check Contracts are comprehensive
   - ✓ Verify Services contain only business logic
   - ✓ Ensure no Laravel/framework dependencies
   - ✓ Validate docblocks and type hints

#### Phase 3: Atomy Implementation (Copilot-Assisted)
5. **Use Agent Prompt 2** to implement in Atomy:
   ```
   @workspace /agent Implement the [DOMAIN] package in Atomy following Prompt 2 
   in .github/copilot-agent-prompts.md. Include migrations, models, repositories, 
   controllers, and routes.
   ```

6. **Review implementation:**
   - ✓ Migrations match Contract requirements
   - ✓ Models implement package interfaces
   - ✓ Repositories implement repository interfaces
   - ✓ Contracts are bound in AppServiceProvider
   - ✓ API follows RESTful conventions

#### Phase 4: Testing (Copilot-Assisted)
7. **Generate tests:**
   ```
   @workspace Create feature tests for the [DOMAIN] API endpoints in Atomy.
   Test all CRUD operations and error cases.
   ```

8. **Run tests and verify:**
   ```bash
   cd apps/Atomy
   php artisan test --filter=[Domain]
   ```

#### Phase 5: Documentation (Copilot-Assisted)
9. **Update documentation:**
   ```
   @workspace Update ARCHITECTURE.md to include the new [DOMAIN] package in 
   the packages list with a brief description.
   ```

---

## 🔧 Workflow 2: Adding a Feature to Existing Domain

**When to use:** Extending functionality within an existing package

### Step-by-Step Process

#### Phase 1: Analysis (Human-Led)
1. **Locate the relevant package**
   ```
   @workspace Where should the logic for [FEATURE_DESCRIPTION] be implemented?
   Which package handles [DOMAIN]?
   ```

2. **Review existing contracts:**
   ```
   @workspace Show me the Contracts in packages/[Package]/src/Contracts/
   Do I need to add new methods or create new contracts for [FEATURE]?
   ```

#### Phase 2: Update Package (Copilot-Assisted)
3. **Update Contracts if needed:**
   ```
   @workspace Add method(s) to [Interface] in packages/[Package]/src/Contracts/ 
   to support [FEATURE_DESCRIPTION]. Include proper docblocks.
   ```

4. **Update Service classes:**
   ```
   @workspace Implement [METHOD_NAME] in packages/[Package]/src/Services/[Service].php
   following the contract definition. Use dependency injection and throw appropriate exceptions.
   ```

#### Phase 3: Update Atomy Implementation (Copilot-Assisted)
5. **Update migrations if schema changes needed:**
   ```
   @workspace Create a migration in apps/Atomy/database/migrations/ to add 
   [SCHEMA_CHANGES] for the [FEATURE] feature.
   ```

6. **Update repository implementation:**
   ```
   @workspace Implement the new [METHOD_NAME] from [Interface] in 
   apps/Atomy/app/Repositories/[Repository].php using Eloquent.
   ```

7. **Update API controller:**
   ```
   @workspace Add endpoint for [FEATURE] in apps/Atomy/app/Http/Controllers/Api/[Controller].php
   and register route in routes/api.php
   ```

#### Phase 4: Testing (Copilot-Assisted)
8. **Generate tests for new functionality:**
   ```
   @workspace Create tests for the new [FEATURE] endpoint in 
   apps/Atomy/tests/Feature/[Domain]Test.php
   ```

---

## 🖥️ Workflow 3: Creating Edward Terminal Commands

**When to use:** Adding user-facing terminal UI for existing API features

### Step-by-Step Process

#### Phase 1: Verify API (Human-Led)
1. **Confirm API endpoint exists:**
   ```
   @workspace Show me the API routes in apps/Atomy/routes/api.php related to [DOMAIN]
   ```

2. **Test API manually:**
   ```bash
   curl -X GET http://localhost/api/v1/[resource]
   ```

#### Phase 2: Create API Client Methods (Copilot-Assisted)
3. **Update AtomyApiClient:**
   ```
   @workspace Add method(s) to apps/Edward/app/Http/Clients/AtomyApiClient.php 
   for calling the /v1/[resource] endpoint(s). Include error handling.
   ```

#### Phase 3: Create Command (Copilot-Assisted)
4. **Generate Artisan command:**
   ```
   @workspace Create a new Artisan command in apps/Edward/app/Console/Commands/
   named [Feature]Command.php that uses AtomyApiClient to [FEATURE_DESCRIPTION].
   Make the output colorful and user-friendly with tables and prompts.
   ```

5. **Review command implementation:**
   - ✓ Uses AtomyApiClient (no direct DB access)
   - ✓ Has clear signature and description
   - ✓ Provides colorful, formatted output
   - ✓ Handles errors gracefully
   - ✓ Has interactive prompts where appropriate

#### Phase 4: Testing (Manual)
6. **Test command:**
   ```bash
   cd apps/Edward
   php artisan edward:[command-name]
   ```

---

## 🔄 Workflow 4: Refactoring for Compliance

**When to use:** Fixing architectural violations in existing code

### Step-by-Step Process

#### Phase 1: Identify Violations (Human or Copilot)
1. **Scan for violations:**
   ```
   @workspace Analyze [FILE_OR_DIRECTORY] for violations of the Nexus 
   architecture rules defined in ARCHITECTURE.md. List all issues found.
   ```

#### Phase 2: Plan Refactoring (Copilot-Assisted)
2. **Create refactoring plan:**
   ```
   @workspace Based on the violations found, create a detailed refactoring plan 
   using Prompt 5 from .github/copilot-agent-prompts.md
   ```

#### Phase 3: Execute Refactoring (Copilot-Assisted)
3. **Move business logic to packages:**
   ```
   @workspace Extract the business logic from [ATOMY_FILE] into a new service 
   class in packages/[Package]/src/Services/. Define contracts for any 
   persistence needs.
   ```

4. **Create Atomy implementation:**
   ```
   @workspace Create repository implementation in apps/Atomy/app/Repositories/ 
   that implements the contracts we just created. Update the controller to use 
   the package service instead of direct model access.
   ```

5. **Fix Edward violations:**
   ```
   @workspace Refactor apps/Edward/app/Console/Commands/[Command].php to use 
   AtomyApiClient instead of direct database access. Remove any package dependencies.
   ```

#### Phase 4: Validation (Human-Led)
6. **Run tests:**
   ```bash
   php artisan test
   ```

7. **Verify architectural compliance:**
   ```
   @workspace Verify that [REFACTORED_COMPONENT] now complies with all 
   architectural rules in ARCHITECTURE.md
   ```

---

## 🔗 Workflow 5: Creating Package Dependencies

**When to use:** Package A needs functionality from Package B

### Step-by-Step Process

#### Phase 1: Validate Dependency (Human-Led)
1. **Check for circular dependencies:**
   ```
   @workspace Show me the dependency graph of packages. If I make [PACKAGE_A] 
   depend on [PACKAGE_B], will this create a circular dependency?
   ```

#### Phase 2: Add Dependency (Copilot-Assisted)
2. **Update composer.json:**
   ```
   @workspace Use Prompt 6 from .github/copilot-agent-prompts.md to add 
   [PACKAGE_B] as a dependency of [PACKAGE_A]
   ```

3. **Update package code:**
   ```
   @workspace Update packages/[PackageA]/src/Services/[Service].php to use 
   [Interface] from packages/[PackageB]/src/Contracts/. Use constructor injection.
   ```

#### Phase 3: Update Atomy Bindings (Copilot-Assisted)
4. **Verify bindings:**
   ```
   @workspace Check apps/Atomy/app/Providers/AppServiceProvider.php to ensure 
   both [PACKAGE_A] and [PACKAGE_B] contracts are properly bound.
   ```

#### Phase 4: Testing (Copilot-Assisted)
5. **Test integration:**
   ```
   @workspace Create integration tests for [PACKAGE_A] using [PACKAGE_B] 
   functionality in apps/Atomy/tests/Feature/
   ```

---

## 🧪 Workflow 6: Test-Driven Development

**When to use:** Writing tests before or alongside implementation

### Step-by-Step Process

#### Phase 1: Write Package Tests (Copilot-Assisted)
1. **Generate test structure:**
   ```
   @workspace Use Prompt 7 from .github/copilot-agent-prompts.md to create 
   tests for packages/[Package]/
   ```

2. **Run tests (expect failures):**
   ```bash
   cd packages/[Package]
   vendor/bin/phpunit
   ```

#### Phase 2: Implement Package Logic (Copilot-Assisted)
3. **Implement services to pass tests:**
   ```
   @workspace Implement packages/[Package]/src/Services/[Service].php to make 
   all tests in tests/Unit/Services/[Service]Test.php pass
   ```

#### Phase 3: Write Atomy Tests (Copilot-Assisted)
4. **Generate feature tests:**
   ```
   @workspace Create feature tests for [DOMAIN] API in 
   apps/Atomy/tests/Feature/[Domain]Test.php covering all endpoints
   ```

#### Phase 4: Implement Atomy Features (Copilot-Assisted)
5. **Implement to pass tests:**
   ```
   @workspace Implement the API endpoints to make all feature tests pass
   ```

---

## 📋 Quick Command Reference

### Asking Copilot for Help

**Architecture questions:**
```
@workspace Based on ARCHITECTURE.md, where should I put [CODE_TYPE]?
```

**Find existing code:**
```
@workspace Find all implementations of [Interface] in the workspace
```

**Review for compliance:**
```
@workspace Check if [FILE] follows Nexus architectural guidelines
```

**Generate boilerplate:**
```
@workspace Create a new [COMPONENT] following the Nexus [Package|Atomy|Edward] guidelines
```

### Using Agent Mode

**Full feature implementation:**
```
@workspace /agent Implement [FEATURE_DESCRIPTION] end-to-end using Prompt 4 
from .github/copilot-agent-prompts.md
```

**Package creation:**
```
@workspace /agent Create [PACKAGE_NAME] package using Prompt 1
```

**Refactoring:**
```
@workspace /agent Refactor [COMPONENT] for compliance using Prompt 5
```

---

## 🎯 Best Practices

1. **Always reference ARCHITECTURE.md** when asking architectural questions
2. **Use specific prompt numbers** from copilot-agent-prompts.md for complex tasks
3. **Review generated code** before committing - Copilot is smart but not perfect
4. **Run tests frequently** to catch issues early
5. **Ask for clarification** if Copilot's suggestion doesn't feel right
6. **Break large tasks** into smaller workflows for better results
7. **Keep context focused** - mention specific files and components
8. **Use @workspace** to give Copilot full workspace context

---

## 🚨 Common Pitfalls to Avoid

❌ **DON'T:** Ask Copilot to put business logic in Atomy  
✅ **DO:** Ask Copilot to create package services first, then implement in Atomy

❌ **DON'T:** Let Copilot create Eloquent models in packages  
✅ **DO:** Ask Copilot to create Contracts in packages, models in Atomy

❌ **DON'T:** Accept Edward commands that access the database  
✅ **DO:** Ensure Edward only uses AtomyApiClient

❌ **DON'T:** Skip the validation checklists in prompts  
✅ **DO:** Review each checklist item before considering code complete

❌ **DON'T:** Create circular package dependencies  
✅ **DO:** Ask Copilot to check dependency graph before adding dependencies
