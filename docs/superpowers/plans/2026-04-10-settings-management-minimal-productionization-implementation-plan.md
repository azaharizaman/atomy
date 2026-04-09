# SettingsManagement Minimal Productionization Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make `orchestrators/SettingsManagement` presentable for alpha by aligning documented test commands, package scripts, and package-scoped CI.

**Architecture:** Keep runtime code untouched and focus on packaging quality surfaces: `composer.json` scripts, package-local `phpunit.xml`, README parity, and a path-scoped GitHub workflow. Validation is intentionally narrow and fast to preserve alpha delivery velocity.

**Tech Stack:** PHP 8.3, Composer, PHPUnit 10, GitHub Actions.

---

### Task 1: Add package test scripts and PHPUnit config

**Files:**
- Modify: `orchestrators/SettingsManagement/composer.json`
- Create: `orchestrators/SettingsManagement/phpunit.xml`
- Test: `orchestrators/SettingsManagement/tests/Unit/FiscalPeriodLockedRuleTest.php`

- [ ] **Step 1: Add failing expectation for missing script command (pre-check)**

Run:

```bash
cd orchestrators/SettingsManagement
composer test
```

Expected before change: `Command "test" is not defined.`

- [ ] **Step 2: Add Composer scripts in package composer.json**

Add this `scripts` block:

```json
"scripts": {
  "test": "vendor/bin/phpunit -c phpunit.xml",
  "test-coverage": "vendor/bin/phpunit -c phpunit.xml --coverage-text"
}
```

- [ ] **Step 3: Create phpunit.xml with stable package-local defaults**

Create `orchestrators/SettingsManagement/phpunit.xml`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="vendor/autoload.php" cacheDirectory=".phpunit.cache" colors="true">
  <testsuites>
    <testsuite name="SettingsManagement Unit">
      <directory suffix="Test.php">tests</directory>
    </testsuite>
  </testsuites>
  <coverage>
    <include>
      <directory suffix=".php">src</directory>
    </include>
  </coverage>
</phpunit>
```

- [ ] **Step 4: Run package tests through the new script**

Run:

```bash
cd orchestrators/SettingsManagement
composer test
```

Expected: PHPUnit passes and executes all package tests.

- [ ] **Step 5: Run coverage script**

Run:

```bash
cd orchestrators/SettingsManagement
composer test-coverage
```

Expected: PHPUnit runs tests and prints text coverage summary.

- [ ] **Step 6: Commit Task 1**

```bash
git add orchestrators/SettingsManagement/composer.json orchestrators/SettingsManagement/phpunit.xml
git commit -m "chore(settings-management): add package test and coverage scripts"
```

### Task 2: Align README with actual runnable commands

**Files:**
- Modify: `orchestrators/SettingsManagement/README.md`

- [ ] **Step 1: Update testing section to package-local command flow**

Ensure README includes:

```md
## Testing

~~~bash
cd orchestrators/SettingsManagement
composer install
composer test
composer test-coverage
~~~
```

- [ ] **Step 2: Verify README command parity with composer scripts**

Run:

```bash
cd orchestrators/SettingsManagement
composer run --list | rg "test|test-coverage"
```

Expected output includes both script names exactly as documented.

- [ ] **Step 3: Commit Task 2**

```bash
git add orchestrators/SettingsManagement/README.md
git commit -m "docs(settings-management): align testing docs with composer scripts"
```

### Task 3: Add package-scoped CI workflow

**Files:**
- Create: `.github/workflows/settings-management-ci.yml`

- [ ] **Step 1: Create package-scoped workflow**

Create `.github/workflows/settings-management-ci.yml`:

```yaml
name: SettingsManagement CI

on:
  pull_request:
    paths:
      - "orchestrators/SettingsManagement/**"
      - ".github/workflows/settings-management-ci.yml"
  push:
    branches: [main]
    paths:
      - "orchestrators/SettingsManagement/**"
      - ".github/workflows/settings-management-ci.yml"

jobs:
  test:
    runs-on: ubuntu-latest
    defaults:
      run:
        working-directory: orchestrators/SettingsManagement
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: "8.3"
          coverage: xdebug
      - uses: ramsey/composer-install@v3
      - name: Run tests
        run: composer test
      - name: Run coverage text
        run: composer test-coverage
```

- [ ] **Step 2: Validate workflow file is discoverable by GitHub CLI**

Run:

```bash
gh workflow list | rg "SettingsManagement CI"
```

Expected: workflow name appears in output.

- [ ] **Step 3: Commit Task 3**

```bash
git add .github/workflows/settings-management-ci.yml
git commit -m "ci(settings-management): add package-scoped test workflow"
```

### Task 4: Final verification and handoff

**Files:**
- Modify: `orchestrators/SettingsManagement/IMPLEMENTATION_SUMMARY.md`

- [ ] **Step 1: Run final local verification**

Run:

```bash
cd orchestrators/SettingsManagement
composer test
composer test-coverage
```

Expected: both commands succeed.

- [ ] **Step 2: Update implementation summary**

Append a short entry with:
- scripts added (`test`, `test-coverage`),
- `phpunit.xml` introduced,
- README test section aligned,
- CI workflow added.

- [ ] **Step 3: Commit Task 4**

```bash
git add orchestrators/SettingsManagement/IMPLEMENTATION_SUMMARY.md
git commit -m "docs(settings-management): record minimal productionization baseline"
```
