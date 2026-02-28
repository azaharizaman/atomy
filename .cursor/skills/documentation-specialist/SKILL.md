---
name: documentation-specialist
description: Creates and maintains Nexus documentation following project conventions. Use when writing READMEs, architecture docs, API documentation, package docs, or when the user asks for documentation.
---

# Documentation Specialist

## Document Locations

| Location | Purpose |
|----------|---------|
| `docs/project/` | Architecture, system overview, standards |
| `packages/*/README.md` | Package usage, installation, examples |
| `packages/*/IMPLEMENTATION_SUMMARY.md` | Progress, checklist |
| `packages/*/VALUATION_MATRIX.md` | Complexity, coverage metrics |
| `packages/*/REQUIREMENTS.md` | Requirements tracking |
| `apps/*/README.md` | App setup, run instructions |

## Primary References

- `docs/project/ARCHITECTURE.md` – single source of truth for architecture
- `docs/project/ORCHESTRATOR_INTERFACE_SEGREGATION.md` – interface pattern
- `docs/project/NEXUS_SYSTEM_OVERVIEW.md` – AI/architect reference
- `docs/project/NEXUS_PACKAGES_REFERENCE.md` – package inventory

## Package README Structure

1. **Header**: Package name, badges (version, downloads, license)
2. **Features**: Bullet list of capabilities
3. **Installation**: Composer, framework-specific setup
4. **Quick Start**: Minimal working example
5. **Usage**: Key interfaces, common patterns
6. **Framework Integration**: Laravel, Symfony, vanilla examples
7. **Testing**: How to run tests
8. **Requirements**: PHP version, dependencies
9. **Links**: Package reference, architecture docs

## API Documentation

- **canary-atomy-api**: OpenAPI/Swagger at `/api/docs`, `/api/docs.json`
- API Platform + Nelmio generate docs from resources
- PHPDoc on public APIs for IDE support

## Format

- Markdown throughout
- Code blocks with language (php, bash, yaml)
- Tables for structured data (interfaces, packages)
- Clear headings and TOC for long docs

## Consistency

- Use "Nexus" for the system name
- Use "package" for atomic units, "orchestrator" for workflow coordinators
- Link to `docs/project/` from package READMEs
