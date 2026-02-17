---
description: Discovery and mapping workflow for multi-agent coordination in Nexus
---

# Nexus Discovery Workflow

This workflow is for new agents entering the repository to quickly map dependencies and identify relevant codebases without scanning the entire 50+ package monorepo.

## 🕵️ Step 1: Mapping the Domain
Search `docs/NEXUS_PACKAGES_REFERENCE.md` for keywords related to the current task.
```bash
grep -i "[keyword]" docs/NEXUS_PACKAGES_REFERENCE.md
```

## 🔗 Step 2: Dependency Analysis
Check the `composer.json` of identified packages to see what else they depend on.
```bash
cat packages/[PackageName]/composer.json
```

## 🗺️ Step 3: Layer Identification
Check for corresponding Orchestrators or Adapters:
- Orchestrators: `ls orchestrators/ | grep [Domain]`
- Adapters: `ls adapters/Laravel/ | grep [Domain]`

## 📝 Step 4: Documentation Review
Check the "Source of Truth" for the specific package:
- `packages/[PackageName]/README.md`
- `packages/[PackageName]/IMPLEMENTATION_SUMMARY.md`

## 🎯 Step 5: Establish Session State
Create the active task file if it doesn't exist:
```bash
cp .agent/tasks/TEMPLATE.md .agent/tasks/active_task.md
```
Update it with the identified dependencies and planned approach.
