---
name: package-implementation
description: End-to-end implementation of a Nexus package based on its REQUIREMENTS.md. Use when you need to transform a requirements document into a production-ready implementation, including task planning, coding, requirement tracking, and PR creation.
---

# Package Implementation Skill

This skill guides the end-to-end implementation of a Nexus package, ensuring 100% requirement coverage, high-quality production code, and a clean Git workflow.

## 🚀 Workflow

### 1. Preparation & Branching
Before modifying any files, ensure a clean working environment:
- **Branch Check**: Verify you are NOT on the `main` branch.
- **Clean Main**: If there are unstaged or staged changes on `main`, commit and push them first.
- **Sync & Branch**: Pull the latest `main`, then checkout a new descriptive branch (e.g., `feature/implement-loyalty-core`).

### 2. Requirement Analysis
- **Read Requirements**: Thoroughly analyze `packages/<PackageName>/REQUIREMENTS.md`.
- **Ask for Package**: If the package name wasn't provided, ASK the user immediately.
- **Scope**: Target 100% requirement coverage unless the user specifies a subset (e.g., "only FUN-LOY-100 series").

### 3. Task Planning
- **Generate Task List**: Create a detailed markdown task list representing all work needed to fulfill the requirements.
- **Sequence**: Group tasks logically (e.g., Contracts -> Models -> Services -> Tests).

### 4. Implementation (Iterative: Plan -> Act -> Validate)
For each task in the list:
- **Production-Ready Code**: No shortcuts, no empty stubs, no placeholders.
- **Work Items**: If a placeholder is UNVOIDABLE, include a `// To be done later:` comment explaining why and what is pending.
- **Architecture**: Adhere strictly to the Three-Layer Architecture and Layer 1 purity (dependencies only on `Nexus\Common`).
- **Documentation**: Use ample docblocks (`/** ... */`) and inline comments for complex logic.
- **Testing**: Every functional requirement MUST have a corresponding test as specified in `REQUIREMENTS.md`.

### 5. Requirement Tracking
- **Mark as Completed**: Once a task is finished and verified, update `packages/<PackageName>/REQUIREMENTS.md` and change the Status from `Pending` to `Completed` for the relevant requirements.
- **Precision**: Mark requirements line-by-line. This is critical for the test-engineer's verification.

### 6. Delivery & PR
Once all tasks are complete:
- **Final Commit**: Commit all remaining changes with a descriptive message.
- **Push & PR**: Push the branch to GitHub and create a Pull Request using the `gh` CLI.
- **PR Template**: Ensure the PR description summarizes the requirements addressed.

## 📐 Coding Standards
- **Strict Typing**: `declare(strict_types=1);` in every file.
- **Immutability**: `final readonly class` for services; `readonly` properties for VOs.
- **DI**: Constructor injection with interfaces ONLY.
- **Purity**: No framework dependencies (`Illuminate\*`, `Symfony\*`) in Layer 1.

## 📖 Reference
- Refer to `docs/project/ARCHITECTURE.md` for full architectural rules.
- Refer to `docs/project/GEMINI.md` for project mandates.
