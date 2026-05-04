# Nexus Layer 1 Packagist Publishing Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to run this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Publish all first-party Layer 1 packages under `packages/` to public Packagist as `azaharizaman/nexus-*` packages.

**Architecture:** The monorepo remains the development source of truth. GitHub split repositories are generated distribution mirrors, and Packagist packages point to those split repositories.

**Tech Stack:** Composer, PHP CLI, GitHub CLI, Git subtree splitting, Packagist API, shell scripts, JSON manifest.

---

### Task 1: Build And Review The Package Manifest

**Files:**
- Create: `docs/package-publishing/nexus-layer1-packages.manifest.json`
- Create: `docs/package-publishing/nexus-layer1-packages.manifest.md`
- Use: `tools/package-publishing/build-layer1-manifest.php`

- [ ] **Step 1: Generate the manifest**

Run:

```bash
php tools/package-publishing/build-layer1-manifest.php
```

Expected: 102 entries are written. `packages/Projects` is ignored because it has no `composer.json`.

- [ ] **Step 2: Review target package names**

Run:

```bash
jq -r '.packages[] | "\(.path) -> \(.new_name) -> \(.github_repo)"' docs/package-publishing/nexus-layer1-packages.manifest.json
```

Expected: every target package uses the `azaharizaman/nexus-*` naming pattern.

### Task 2: Rewrite Composer Names Locally

**Files:**
- Modify: `packages/*/composer.json`
- Modify: any repo Composer file that depends on renamed Layer 1 packages
- Use: `tools/package-publishing/rewrite-composer-package-names.php`

- [ ] **Step 1: Dry-run the rewrite**

Run:

```bash
php tools/package-publishing/rewrite-composer-package-names.php
```

Expected: the script lists composer files that would change.

- [ ] **Step 2: Apply the rewrite**

Run:

```bash
php tools/package-publishing/rewrite-composer-package-names.php --write
```

Expected: Layer 1 package names and direct dependencies are rewritten from `nexus/*` to `azaharizaman/nexus-*`.

- [ ] **Step 3: Check remaining old direct package references**

Run:

```bash
rg -n '"nexus/' -g composer.json
```

Expected: only non-Layer-1 package names, non-Layer-1 dependencies, or intentionally unresolved historical references remain.

### Task 3: Create GitHub Distribution Repositories

**Files:**
- Use: `tools/package-publishing/create-github-repos.sh`

- [ ] **Step 1: Dry-run GitHub repo creation**

Run:

```bash
bash tools/package-publishing/create-github-repos.sh
```

Expected: 102 `gh repo create azaharizaman/nexus-*` commands are printed.

- [ ] **Step 2: Execute GitHub repo creation**

Run:

```bash
bash tools/package-publishing/create-github-repos.sh --execute
```

Expected: missing repos are created, existing repos are skipped.

### Task 4: Split And Push Package Repositories

**Files:**
- Use: `tools/package-publishing/split-push-layer1-packages.sh`

- [ ] **Step 1: Dry-run split pushes**

Run:

```bash
bash tools/package-publishing/split-push-layer1-packages.sh
```

Expected: each package prints a subtree split and push target.

- [ ] **Step 2: Push split repositories**

Run:

```bash
bash tools/package-publishing/split-push-layer1-packages.sh --execute
```

Expected: every split repository receives a `main` branch with that package at repo root.

- [ ] **Step 3: Push a first release tag**

Run:

```bash
RELEASE_TAG=v0.1.0-alpha1 bash tools/package-publishing/split-push-layer1-packages.sh --execute
```

Expected: every split repository receives a package-specific `v0.1.0-alpha1` tag.

### Task 5: Submit Packages To Packagist

**Files:**
- Use: `tools/package-publishing/submit-packagist-packages.sh`

- [ ] **Step 1: Dry-run Packagist package creation**

Run:

```bash
bash tools/package-publishing/submit-packagist-packages.sh
```

Expected: 102 `azaharizaman/nexus-*` Packagist package creation actions are printed.

- [ ] **Step 2: Execute Packagist package creation**

Run:

```bash
PACKAGIST_USERNAME=azaharizaman PACKAGIST_API_TOKEN=<main-token> bash tools/package-publishing/submit-packagist-packages.sh --execute
```

Expected: Packagist returns success for new packages. Existing packages may need manual review or the edit/update API.

### Task 6: Verify Public Installability

**Files:**
- Use: generated split repositories
- Use: Packagist package pages

- [ ] **Step 1: Verify Packagist sees the vendor packages**

Run:

```bash
curl -fsS 'https://packagist.org/packages/list.json?vendor=azaharizaman' | jq -r '.packageNames[]' | rg '^azaharizaman/nexus-'
```

Expected: the new Nexus packages are listed.

- [ ] **Step 2: Verify a sample install**

Run outside this monorepo:

```bash
composer create-project --no-install azaharizaman/nexus-common /tmp/nexus-common-smoke
```

Expected: Composer can resolve the package from Packagist.
