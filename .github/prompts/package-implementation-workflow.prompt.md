# Package Implementation Workflow

## Objective
Systematically implement packages in the Nexus monorepo by analyzing requirements, creating skeleton structures, documenting file responsibilities, and producing comprehensive implementation guides.

## Workflow Steps

### Step 1: Examine Requirements for Given Package
**Input:** Package namespace (e.g., `Nexus\AuditLogger`, `Nexus\Sequencing`)

**Tasks:**
1. Open and read `REQUIREMENTS.csv`
2. Filter all rows where `Package Namespace` column matches the target package
3. Group requirements by type:
   - **Architectural Requirements** (ARC-*)
   - **Business Requirements** (BUS-*)
   - **Functional Requirements** (FUN-*)
   - **Other Requirements** (if any)
4. Analyze each requirement to understand:
   - What interfaces/contracts are needed
   - What services/business logic must be implemented
   - What data structures are required
   - What persistence mechanisms are needed
   - What application-layer components are required (models, migrations, repositories, etc.)

**Output:** Complete understanding of all requirements for the package

---

### Step 2: Create Skeleton Files and Folders
**Goal:** Create a complete, well-structured skeleton that satisfies all requirements while following the Nexus architecture.

#### 2.1 Package Layer (packages/{PackageName}/)

**Always Create:**
```
packages/{PackageName}/
├── composer.json              # Package definition (PSR-4, dependencies)
├── README.md                  # Package documentation
├── LICENSE                    # MIT License
└── src/
    ├── Contracts/             # REQUIRED: All interfaces
    ├── Exceptions/            # REQUIRED: Domain exceptions
    └── Services/              # REQUIRED: Business logic
```

**Conditionally Create Based on Complexity:**
```
└── src/
    ├── Core/                  # OPTIONAL: For complex packages with internal engine
    │   ├── Engine/           # Internal processing logic
    │   ├── ValueObjects/     # Internal immutable data structures
    │   ├── Entities/         # Internal domain entities
    │   └── Contracts/        # Internal interfaces (not exposed to consumers)
    └── ValueObjects/          # PUBLIC: Value objects exposed to consumers
```

**Decision Matrix for Core/ Folder:**
- ✅ **USE Core/** if:
  - Package has complex internal processing (Analytics, Workflow, Manufacturing)
  - Manager class would exceed 300 lines
  - Internal contracts needed for dependency injection
  - Value Objects should be handled only by Manager (not directly by consumers)
  
- ❌ **SKIP Core/** if:
  - Package is simple with < 10 files
  - Manager class under 200 lines
  - No internal helper contracts needed

**Critical Rules:**
- ✅ **MUST**: Package must be framework-agnostic (no Laravel dependencies in composer.json)
- ✅ **MUST**: All persistence via interfaces (RepositoryInterface)
- ✅ **MUST**: All data structures via interfaces
- ✅ **MUST**: Use strict types: `declare(strict_types=1);`
- ✅ **MUST**: Comprehensive docblocks with `@param`, `@return`, `@throws`
- ❌ **NEVER**: Use Laravel classes (Model, Request, facades)
- ❌ **NEVER**: Include database migrations in package
- ❌ **NEVER**: Reference application code

#### 2.2 Atomy Application Layer (apps/Atomy/)

**Always Create:**
```
apps/Atomy/
├── database/
│   └── migrations/
│       └── YYYY_MM_DD_HHMMSS_create_{table_name}_table.php
├── app/
│   ├── Models/
│   │   └── {ModelName}.php              # Implements package interfaces
│   ├── Repositories/
│   │   └── Db{Entity}Repository.php     # Implements RepositoryInterface
│   └── Providers/
│       └── {PackageName}ServiceProvider.php  # IoC bindings
```

**Conditionally Create Based on Requirements:**
```
apps/Atomy/
├── app/
│   ├── Traits/
│   │   └── {TraitName}.php              # Laravel-specific behavior (e.g., Auditable)
│   ├── Services/
│   │   └── {ServiceName}.php            # Application-specific services
│   ├── Console/
│   │   └── Commands/
│   │       └── {CommandName}Command.php  # Artisan commands
│   └── Http/
│       └── Controllers/
│           └── Api/
│               └── {Entity}Controller.php  # RESTful API
├── config/
│   └── {package}.php                     # Configuration file
└── routes/
    └── api_{package}.php                 # API routes (if needed)
```

**Critical Rules:**
- ✅ **MUST**: Implement ALL package interfaces with concrete classes
- ✅ **MUST**: Bind interfaces to implementations in ServiceProvider
- ✅ **MUST**: Place ALL database schema in migrations
- ✅ **MUST**: Use Eloquent models that implement package interfaces
- ✅ **MUST**: Repository pattern for all database operations
- ❌ **NEVER**: Put business logic in controllers or models
- ❌ **NEVER**: Direct database queries in controllers

#### 2.3 File Preservation Rules

**CRITICAL: Before creating or moving files:**

1. **Check if file/folder already exists:**
   ```bash
   # Use list_dir or file_search to check existence
   ```

2. **If file exists and created by another package:**
   - ✅ **DO NOT DELETE** - It may be referenced in other documentation
   - ✅ **DO NOT OVERWRITE** - Check if it can be extended/reused
   - ✅ **ADD TO** - If it's a shared file (e.g., routes, config), append to it
   - ⚠️ **DOCUMENT** - Note in implementation doc that file is shared

3. **If file needs relocation or rename:**
   - ✅ Find all references in documentation files:
     ```
     grep -r "old/path/to/file.php" docs/
     grep -r "OldFileName" docs/
     ```
   - ✅ Update ALL documentation files that reference it
   - ✅ Use `git mv` or document the move clearly
   - ✅ Create a migration note in the implementation doc

4. **Shared files that may exist:**
   - `apps/Atomy/app/Providers/AppServiceProvider.php` - Central binding location
   - `apps/Atomy/config/app.php` - Service provider registration
   - `apps/Atomy/routes/api.php` - May contain routes from multiple packages
   - `apps/Atomy/app/Http/Kernel.php` - Middleware registration

---

### Step 3: Update REQUIREMENTS.csv

**Goal:** Document exactly which files/folders/classes/methods deliver each requirement.

**Format Rules:**
1. Update the column: `Files/Folders/ or Class/Methods Responible to deliver this requirements`
2. Use semicolon (`;`) to separate multiple files
3. Use `::` to specify class methods: `ClassName.php::methodName()`
4. Include full relative paths from workspace root
5. Be specific and comprehensive

**Examples:**

| Requirement Type | Format Example |
|-----------------|----------------|
| **Single File** | `packages/AuditLogger/src/Contracts/AuditLogInterface.php` |
| **Multiple Files** | `packages/AuditLogger/src/Services/AuditLogManager.php; apps/Atomy/app/Models/AuditLog.php` |
| **Specific Methods** | `packages/AuditLogger/src/Services/AuditLogManager.php::log(), logBatch()` |
| **Class Property** | `apps/Atomy/app/Models/AuditLog.php::$fillable` |
| **Folder** | `packages/AuditLogger/src/Contracts/` |
| **Config** | `apps/Atomy/config/audit.php (sensitive_fields)` |

**Update Process:**
1. Read current REQUIREMENTS.csv
2. For each requirement of the target package:
   - Map requirement to specific files created
   - Include both package and Atomy files if both contribute
   - Specify methods/properties when relevant
3. Use `multi_replace_string_in_file` for efficiency (update all requirements in one call)
4. Preserve all other CSV columns unchanged

**Critical Rules:**
- ✅ **DO**: Update only the target package's requirements
- ✅ **DO**: Include 3-5 lines of context before/after for replace operations
- ✅ **DO**: Be specific (file + method is better than just file)
- ❌ **DON'T**: Modify other packages' requirement rows
- ❌ **DON'T**: Change the CSV structure or other columns
- ❌ **DON'T**: Leave entries blank if files exist

---

### Step 4: Create Comprehensive Implementation Documentation

**Goal:** Create a complete guide at `docs/{PACKAGENAME}_IMPLEMENTATION.md` following the established format.

**Template Structure:**

```markdown
# {PackageName} Package Implementation

Complete skeleton for the Nexus {PackageName} package and Atomy implementation.

## 📦 Package Structure (packages/{PackageName}/)

[Tree structure with file descriptions and requirement codes]

## 🚀 Atomy Implementation Structure (apps/Atomy/)

[Tree structure with file descriptions and requirement codes]

## ✅ Requirements Satisfied

### Architectural Requirements
- **ARC-XXX-0001**: ✅ [Requirement text] - [How it's satisfied]
[List all architectural requirements]

### Business Requirements
- **BUS-XXX-0001**: ✅ [Requirement text] - [How it's satisfied]
[List all business requirements]

### Functional Requirements
- **FUN-XXX-0001**: ✅ [Requirement text] - [How it's satisfied]
[List all functional requirements]

## 📝 Usage Examples

### 1. Install Package in Atomy
[Installation steps]

### 2. Register Service Provider
[Configuration steps]

### 3. Run Migrations
[Migration steps]

### 4-8. [Practical usage examples]
[Code examples showing how to use the package]

## 🔧 Configuration

[Configuration file details and options]

## 📊 Database Schema

[Table structure and column descriptions]

## 🧪 Testing

[How to run tests for package and Atomy implementation]

## 📚 Next Steps

[Numbered list of implementation steps]

## 🔒 Security Considerations

[Security features and best practices]

## 📖 Documentation

[Links to related documentation]
```

**Critical Content Rules:**

1. **File Trees Must Include:**
   - Full path from package/app root
   - Brief description of each file's purpose
   - Related requirement codes (e.g., `(ARC-AUD-0002, ARC-AUD-0003)`)
   - Key methods or classes in comments

2. **Requirements Section Must:**
   - List EVERY requirement from REQUIREMENTS.csv
   - Show ✅ for completed/planned
   - Explain HOW the requirement is satisfied
   - Reference specific files/methods

3. **Usage Examples Must:**
   - Be practical and copy-paste ready
   - Cover common use cases (80% of usage)
   - Show both simple and advanced scenarios
   - Include code comments explaining each step

4. **Database Schema Must:**
   - List all columns with types
   - Explain purpose of each column
   - Note indexes, foreign keys, constraints
   - Link columns to requirements

5. **Next Steps Must:**
   - Be actionable and numbered
   - Follow logical implementation order
   - Include prerequisite steps (e.g., install Laravel first)
   - Reference other packages if dependencies exist

**File Preservation Rules:**
- ✅ If shared files were modified (appended to), document this clearly
- ✅ If files were relocated, include a "Migration Notes" section
- ✅ If files are shared with other packages, note this in the structure tree

---

## Complete Workflow Example

### Input
```
Package: Nexus\Sequencing
```

### Execution

1. **Examine Requirements:**
   ```
   - Read REQUIREMENTS.csv
   - Extract all rows with Package Namespace = `Nexus\Sequencing`
   - Found: 8 architectural + 7 business requirements
   - Analysis: Needs sequence generation, pattern parsing, counter management
   ```

2. **Create Skeleton:**
   ```
   packages/Sequencing/
   ├── composer.json
   ├── README.md
   ├── LICENSE
   └── src/
       ├── Contracts/
       │   ├── SequenceInterface.php
       │   ├── SequenceRepositoryInterface.php
       │   └── PatternParserInterface.php
       ├── Exceptions/
       │   ├── SequenceNotFoundException.php
       │   ├── InvalidPatternException.php
       │   └── CounterOverflowException.php
       ├── Services/
       │   ├── SequenceManager.php
       │   ├── PatternParser.php
       │   └── CounterService.php
       └── ValueObjects/
           └── SequencePattern.php

   apps/Atomy/
   ├── database/migrations/
   │   └── 2025_11_17_000002_create_sequences_table.php
   ├── app/Models/
   │   └── Sequence.php
   ├── app/Repositories/
   │   └── DbSequenceRepository.php
   └── app/Providers/
       └── SequencingServiceProvider.php
   ```

3. **Update REQUIREMENTS.csv:**
   ```csv
   ARC-SEQ-0019,...,packages/Sequencing/composer.json,...
   ARC-SEQ-0020,...,packages/Sequencing/src/Contracts/SequenceInterface.php,...
   [... all requirements mapped to files ...]
   ```

4. **Create Documentation:**
   ```
   docs/SEQUENCING_IMPLEMENTATION.md
   - Complete structure trees
   - All 15 requirements documented
   - 8 usage examples
   - Configuration guide
   - Next steps
   ```

---

## Quality Checklist

Before considering the workflow complete, verify:

### Package Layer
- [ ] `composer.json` has no Laravel dependencies
- [ ] All interfaces in `src/Contracts/` are comprehensive
- [ ] All services use dependency injection
- [ ] All exceptions extend base PHP exceptions
- [ ] Value objects are immutable
- [ ] Docblocks are complete with types
- [ ] `declare(strict_types=1);` in all PHP files
- [ ] README.md explains package purpose and usage
- [ ] No framework-specific code in package

### Application Layer
- [ ] Migrations create all necessary tables
- [ ] Models implement package interfaces
- [ ] Repositories implement repository interfaces
- [ ] Service provider binds all interfaces
- [ ] Config file has sensible defaults
- [ ] API routes follow RESTful conventions
- [ ] Controllers are thin (logic in services)
- [ ] Commands have clear descriptions

### Requirements Documentation
- [ ] All requirements have file mappings
- [ ] File paths are correct and complete
- [ ] Methods are specified where relevant
- [ ] CSV structure is preserved
- [ ] Only target package rows modified

### Implementation Documentation
- [ ] File structure trees are complete
- [ ] All requirements listed with checkmarks
- [ ] Usage examples are practical
- [ ] Database schema is documented
- [ ] Next steps are actionable
- [ ] Security considerations noted
- [ ] File relocations/sharing documented

### File Preservation
- [ ] Existing shared files not deleted
- [ ] Appended content documented
- [ ] All documentation references updated
- [ ] Migration notes included if needed

---

## Common Pitfalls to Avoid

1. ❌ **Creating package dependencies on Laravel**
   - Check `composer.json` - only allow `psr/log` or similar PSR interfaces
   
2. ❌ **Putting business logic in Atomy controllers/models**
   - Logic belongs in package services
   
3. ❌ **Incomplete interface definitions**
   - Interfaces must cover ALL operations needed by services
   
4. ❌ **Missing method specifications in REQUIREMENTS.csv**
   - Be specific: `Manager.php::methodName()` not just `Manager.php`
   
5. ❌ **Overwriting shared files**
   - Always check if file exists, append or document sharing
   
6. ❌ **Forgetting to update documentation references**
   - When moving files, update ALL docs that reference them
   
7. ❌ **Generic exception messages**
   - Create specific domain exceptions for each error case
   
8. ❌ **Missing configuration**
   - Create config files for values that vary per deployment
   
9. ❌ **Incomplete implementation docs**
   - Follow the template structure completely
   
10. ❌ **Not using multi_replace for CSV updates**
    - Update all requirements in one efficient operation

---

## Success Criteria

The workflow is complete when:

1. ✅ All skeleton files created and follow architecture rules
2. ✅ All requirements in CSV have complete file mappings
3. ✅ Implementation documentation is comprehensive and follows template
4. ✅ No existing files were broken or orphaned
5. ✅ Package is framework-agnostic (composer.json check)
6. ✅ All interfaces and contracts are complete
7. ✅ Usage examples are practical and copy-paste ready
8. ✅ Security considerations are documented
9. ✅ Next steps provide clear implementation path
10. ✅ All documentation cross-references are valid

---

## Tool Usage Notes

- Use `list_dir` and `file_search` before creating files to check existence
- Use `grep_search` to find documentation references when relocating files
- Use `multi_replace_string_in_file` for efficient CSV updates
- Use `read_file` to understand existing shared files before modifying
- Always include 3-5 lines of context in replace operations

---

## Final Note

This workflow ensures consistency across all package implementations in the Nexus monorepo. Every package should follow this exact process to maintain architectural integrity and comprehensive documentation. The result is a monorepo where every package is independently publishable, well-documented, and properly integrated with the Atomy application layer.
