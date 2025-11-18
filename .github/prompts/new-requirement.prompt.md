Systematically execute the following task. The output is sets of requirements and user stories in CSV format that need to be appended to REQUIREMENTS_PART2.csv. Please also analyze REQUIREMENTS.csv as some requirments may already exist and could potentially be affected by these new requirements. If there are any redundant requirements already in the file, please note them to be removed in implementation if it has been implemented already. as this is a huge task, please break down the task into smaller steps and execute them one by one, ensuring each step is completed before moving to the next. checkout a new feature branch for this task.

## 📝 Comprehensive Requirements Generation 

**Objective:** Formulate detailed, concise, and atomic requirements and user stories for the entire Statutory and Compliance system, focusing on the separation between process enforcement and data reporting.

### I. Architectural Constraint & Scope

**The agent MUST adhere to the following architectural model:**
1.  **`Nexus\Compliance`**: Manages **Operational Compliance** (Process, Audits, Feature Composition, Configuration Checks, and SOD).
2.  **`Nexus\Statutory`**: Manages **Statutory Reporting Compliance** (Contracts, Metadata, Filing Formats, and Default Adapters).
3.  **`Nexus\Atomy`**: Contains all **Proprietary/Gated Implementations** and the **Conditional Binding/Feature Toggles**.

**Involved Packages (Include Refactoring Needs):**
* `Nexus\Compliance` (New)
* `Nexus\Statutory` (New)
* `Nexus\Statutory.Payroll.MYS` (New, Open Source)
* `Nexus\Statutory.Accounting.MYS.Prop` (New, Open Source)
* `Nexus\Payroll` (Refactor: Must only call generic `PayrollStatutoryInterface`)
* `Nexus\Accounting` (Refactor: Must only call generic `TaxonomyReportGeneratorInterface`)
* `Nexus\Finance` (Refactor: Must only ensure COA is mappable and respects period locks)
* `Nexus\Atomy` (Orchestration/Binding Logic)

---

### II. Required Output Sections

Generate requirements and stories for the following categories, ensuring each requirement explicitly references the affected package(s) and their role in the compliance process.

1.  **Functional Requirements (FR):** Core features (e.g., MBRS file generation, compliance scheme activation).
2.  **Business Rules Requirements (BR):** Enforcement logic (e.g., EPF deduction calculation rates, ISO 14001 mandatory fields).
3.  **Architectural Requirements (AR):** IoC binding, separation of concerns, and plug-in design.
4.  **Security Requirements (SR):** Data access, feature gating, and audit trails.
5.  **User Stories (US):** High-value stories for key stakeholders (System Admin, Accountant, HR Manager).

---

### III. Key Focus Areas (Mandatory Coverage)

* **Default Implementation:** Define the FR/AR for the `DefaultAccountingAdapter` (P&L/BS) and the `DefaultStatutoryAdapter` (Zero-Deductions) residing in `Nexus\Statutory`.
* **Feature Gating:** Define the AR for conditional binding in `Nexus\Atomy` (e.g., if the paid package is missing/disabled, bind the default).
* **Operational Enforcement:** Define the BR/US for `Nexus\Compliance` validating system configuration (e.g., ISO 14001 check on `Nexus\Settings`).
* **Refactoring:** Define the AR for **decoupling** `Nexus\Payroll`, `Nexus\Accounting`, and `Nexus\Finance` from any specific country logic.

---

*I recommend the agent starts with the Architectural Requirements first, as they govern all other requirements.*

The remaining section of this file are chat responses that has been copied from my chat with Gemini and is to be taken into consideration when formulating your output. THEY ARE NOT INSTRUCTIONS TO THIS PROMPT but to be understood as an added context to your thinking process. All requirement must be in REQUIREMENTS_PART2.csv file and to follow the existing formatting. Should there be redundant requirements already in the file, please note them to be removed in implementation if it has been implemented already.

---

That is an excellent strategy for achieving compliance while creating a **marketable, value-added feature** for the Malaysian market. You are correctly prioritizing business opportunity alongside technical compliance.

To answer your question directly: **Yes, you have enough *foundational* components, but you need one more strategic atomic package and a dedicated compliance implementation layer.**

The best way to structure this is to introduce a specific atomic package for **Statutory/Regulatory Reporting** and then build the **SSM Compliance module** directly into `Nexus\Atomy`.

-----

## 1\. 📦 Required New Atomic Package: `Nexus\Statutory`

Instead of letting the **`Nexus\Accounting`** package absorb all the complexity of global reporting laws, you should introduce a dedicated, atomic package to manage the *contracts* for governmental reporting.

| Package | Role | Rationale |
| :--- | :--- | :--- |
| **`Nexus\Statutory`** | **The Compliance Contract Hub.** Defines the contract for any required government report generation (e.g., `StatutoryReportGeneratorInterface`). | Keeps `Nexus\Accounting` generic (focused only on GAAP/IFRS reports) and abstracts the complexity of XML/XBRL schemas. |

### Required Contract

The core of this package is an interface that allows any government reporting module to be plugged in:

```php
// packages/Statutory/src/Contracts/TaxonomyReportGeneratorInterface.php
interface TaxonomyReportGeneratorInterface
{
    /**
     * Generates a fully tagged, compliance-ready report based on a reporting schema.
     * @param string $taxonomyId  // e.g., 'SSMxT_2022'
     * @param array $trialBalanceData // Data from Nexus\Finance
     * @return string // Returns the structured XBRL/XML output
     */
    public function generate(string $taxonomyId, array $trialBalanceData): string;
}
```

-----

## 2\. 🇲🇾 The Exclusive Compliance Module (The Marketable Item)

This module is your **profit center** and the direct answer to the SSM compliance requirement. It should live exclusively within your orchestrator application, `Nexus\Atomy`.

  * **Name Idea:** `App\Compliance\SSM` (or `App\Services\SSMBRSTaxonomyAdapter`).
  * **Role:** This module contains the specialized, proprietary logic (the "heavy lifting") for mapping the user's data to the SSMxT\_2022 format.
  * **Action:** It **implements** the atomic `TaxonomyReportGeneratorInterface` and uses the necessary external libraries (like an XBRL parser/generator library) to produce the final compliant file.

### Integration Flow

1.  **`Nexus\Accounting`** requests a report.
2.  The request goes to the injected **`TaxonomyReportGeneratorInterface`** (from `Nexus\Statutory`).
3.  The request is handled by the **`SSMBRSTaxonomyAdapter`** (the proprietary implementation in `Nexus\Atomy`).
4.  The Adapter pulls the trial balance data from **`Nexus\Finance`**, applies the SSM tagging logic, and outputs the XBRL file.

-----

## 3\. The Final Verdict on Components

| Package | Role in Compliance | Status |
| :--- | :--- | :--- |
| **`Nexus\Finance`** | Provides the raw, debits/credits data (Trial Balance). | **Ready** |
| **`Nexus\Accounting`** | Requests the compliant report (the initiator). | **Ready** |
| **`Nexus\Period`** | Validates the closing date of the financial report. | **Ready** |
| **`Nexus\Statutory`** | **New Atomic Contract.** Defines the interface for all government reporting schemas. | **Missing** (Must be added) |
| **`App\Compliance\SSM`** | **Proprietary Implementation.** Contains the actual SSMxT\_2022 mapping and XBRL generation logic. | **Missing** (Your marketable item in `Atomy`) |

By adding the **`Nexus\Statutory`** package, you create a dedicated atomic spot for all future country-specific compliance tools (e.g., Singapore ACRA, Indonesian Tax Reports) while keeping your valuable **SSM compliance logic** secure and exclusive within `Nexus\Atomy`.

___

This is a fantastic approach. It balances the purity of your **Atomic Architecture** with the practical necessity of **Monetization and Feature Gating**.

You are defining a **Plug-and-Pay** architecture, and it is absolutely achievable within your Nexus structure, using your plan to leverage `Nexus\Atomy` for the exclusive implementation.

Here is the breakdown of how to implement this system, focusing on the two layers:

-----

## 1\. The Installation and Composer Layer (The "Download" Gate)

The goal here is to ensure the **proprietary code** for SSM compliance (`SSMBRSTaxonomyAdapter` and its dependencies) is only downloaded when the user pays or enables the feature.

### A. Private Composer Repository

  * The **`Nexus\Statutory`** package remains in your **public/free monorepo**.
  * The actual SSM implementation package (e.g., `nexus/statutory-malaysia`) must be hosted in a **private Composer repository** (using a tool like Satis, Private Packagist, or a private GitHub/GitLab repository with SSH keys).

### B. Conditional `composer.json` Management

  * When a small proprietary user (who doesn't need SSM) installs your ERP, their `Nexus\Atomy`'s root `composer.json` **does not list** `nexus/statutory-malaysia` in its `require` section.
  * When a large corporation purchases the compliance feature, your deployment pipeline (or a custom Artisan command):
    1.  Adds the private repository definition (with credentials) to `composer.json`.
    2.  Adds `nexus/statutory-malaysia` to the `require` section.
    3.  Runs `composer update nexus/statutory-malaysia`.

This ensures the code is **not even present** in the file system for users who haven't enabled it, maintaining security and a smaller codebase.

-----

## 2\. The Application Layer (The "Turn On" Gate)

Once the package is downloaded, you must ensure its code is only loaded and registered when the user flips a switch in the UI.

### A. `Nexus\Statutory` (The Contract)

This package remains clean and generic. It defines the interface:

```php
// Nexus\Statutory\Contracts\TaxonomyReportGeneratorInterface.php
interface TaxonomyReportGeneratorInterface { /* ... */ }
```

### B. `Nexus\Atomy` (The Orchestrator)

The core logic of `Atomy` must provide a **Default (No-Op) Implementation** of the contract.

1.  **Default/Fallback Service:** Create a basic implementation that is always present and registered:

    ```php
    // apps/Atomy/app/Services/DefaultReportGenerator.php
    class DefaultReportGenerator implements TaxonomyReportGeneratorInterface
    {
        public function generate(string $taxonomyId, array $trialBalanceData): string
        {
            // Returns an error or throws an exception clearly stating:
            // "Statutory Reporting feature is not enabled."
        }
    }
    ```

2.  **Conditional Registration (The Switch):** In the `Atomy` Service Provider, use a configuration check to determine which implementation to bind to the contract:

    ```php
    // apps/Atomy/app/Providers/AppServiceProvider.php
    public function register()
    {
        if (config('features.statutory_ssm_enabled') && class_exists(SSMBRSTaxonomyAdapter::class)) {
            // HIGH-VALUE FEATURE ENABLED: Bind the proprietary class
            $this->app->singleton(
                TaxonomyReportGeneratorInterface::class, 
                SSMBRSTaxonomyAdapter::class 
            );
        } else {
            // DEFAULT/FREE: Bind the fallback class
            $this->app->singleton(
                TaxonomyReportGeneratorInterface::class, 
                DefaultReportGenerator::class
            );
        }
    }
    ```

## Conclusion

This strategy perfectly achieves your goals:

  * **Small Enterprise:** The code is **not downloaded** (Composer-level), and the `DefaultReportGenerator` is active, allowing P\&L/CF but preventing statutory submission, as intended.
  * **Large Corporation:** The code is **downloaded** (private repo), the feature flag is set to `true`, and the **`SSMBRSTaxonomyAdapter`** is bound, enabling full compliance.

This is a sound, secure, and commercially smart architecture.

-----

Would you like to detail the next steps in creating the `Nexus\Statutory` package, or move on to defining the core services within `Nexus\Receivable` (AR)?

____

That's a smart approach—building the architecture now, even if the premium components aren't ready. This ensures **zero refactoring debt** later.

The key to being "architecturally ready" is to define the **interface contract** and place the **conditional binding logic** within `Nexus\Atomy` now, using a **Null Object** pattern for the premium feature.

Here is the plan to make `Nexus\Atomy` ready for the future `Nexus\Statutory` paid package, focusing on the **Payroll Statutory** example you provided.

-----

## 🏗️ Architectural Readiness Plan

### 1\. 📦 Create the Atomic `Nexus\Statutory` Package (Free)

This package will contain only the **contracts** for *all* statutory reporting features—both free (Payroll Malaysia) and future paid ones (MBRS, Payroll Singapore).

| File | Content | Purpose |
| :--- | :--- | :--- |
| `StatutoryReportGeneratorInterface.php` | The MBRS/XBRL contract (Future Paid). | Defines the target interface for the MBRS paid package. |
| `PayrollStatutoryInterface.php` | The contract for *all* country-specific payroll deductions. | Defines the interface for both `PayrollMysStatutory` (Free) and `PayrollSgStatutory` (Paid). |
| `NullPayrollStatutoryAdapter.php` | A concrete class implementing `PayrollStatutoryInterface` that does nothing or throws an exception. | **The Null Object Pattern.** Used as the default binding when the paid feature is disabled. |

### 2\. 🇲🇾 Implement the Free Package (`Nexus\PayrollMysStatutory`)

The free package should be treated as a standard atomic package dependency, not an exclusive premium feature.

  * This package contains the concrete **`MYSStatutoryCalculator`** that implements `PayrollStatutoryInterface`.

### 3\. 🚀 Ready `Nexus\Atomy` for the Future (The Switch)

The `Nexus\Atomy` orchestrator must now be set up to handle the "free or paid or default" logic for all statutory contracts.

#### A. The Free Feature Binding (Payroll)

Since your Malaysian payroll statutory calculations are **free and open source**, you should bind the concrete implementation directly if the package is present.

```php
// apps/Atomy/app/Providers/StatutoryServiceProvider.php

use Nexus\Statutory\Contracts\PayrollStatutoryInterface;
use Nexus\PayrollMysStatutory\MYSStatutoryCalculator; 

// Bind the FREE Malaysian Payroll Calculator directly
$this->app->singleton(
    PayrollStatutoryInterface::class,
    MYSStatutoryCalculator::class 
);
```

*(This is a standard binding, assuming the `Nexus\PayrollMysStatutory` package is a free dependency in `composer.json`.)*

#### B. The Future Paid Feature Binding (MBRS/SSM or Payroll Singapore)

For the premium features that are not yet implemented, you use the **Null Object Pattern** with a placeholder configuration check.

```php
// apps/Atomy/app/Providers/StatutoryServiceProvider.php (cont.)

use Nexus\Statutory\Contracts\StatutoryReportGeneratorInterface;
use Nexus\Statutory\NullPayrollStatutoryAdapter; // The Null Object

// Configuration Check for FUTURE PAID FEATURE
$isMbrsFeatureEnabled = config('features.ssm_mrs_enabled', false);

if ($isMbrsFeatureEnabled) {
    // 🚨 FUTURE CODE HERE: Bind the paid package
    // $this->app->singleton(
    //     StatutoryReportGeneratorInterface::class,
    //     SSMBRSTaxonomyAdapter::class 
    // );
} else {
    // 🆓 CURRENTLY ACTIVE: Bind the Null Object for the MBRS contract
    $this->app->singleton(
        StatutoryReportGeneratorInterface::class, 
        NullPayrollStatutoryAdapter::class 
    );
}
```

### Conclusion

By defining all contracts in `Nexus\Statutory` now, and binding a **`NullPayrollStatutoryAdapter`** for the paid features, any module that requires the paid feature (like a report generator in `Accounting`) will currently receive the Null Object.

  * If the module calls the Null Object, it either returns a safe empty value or throws an exception that says, "Feature not enabled."
  * When you release the paid package, you only need to update the binding logic in `Atomy`, and all dependent packages instantly gain the premium functionality without any code changes.

  ___

  That is a brilliant, highly advanced architectural concept. You are defining a **Compliance Orchestration Layer** that manages feature composition, which is a significant step beyond simple feature toggles.

To answer your first question: **Yes, replacing the "Null Object" with a "Default Implementation" is the correct and more robust architectural choice** for a free/default component.

Your proposal to create a high-level **`Nexus\Compliance`** package to manage the registration and configuration of these deep compliance requirements (like ISO 14001) is the perfect atomic home for this complex logic.

Here is how the architecture evolves with the new **`Nexus\Compliance`** orchestrator package.

---

## 🛠️ 1. Replacing Null with Default (The Foundation)

We absolutely should replace the **Null Object** with a **Default Implementation** for any *free* feature.

* **Default Implementation:** This is a concrete class (e.g., `DefaultReportGenerator`) that lives within the public package (e.g., `Nexus\Statutory`). It implements the interface but returns a generic, safe result (like a blank report template). This is the **free, baseline service**.
* **The Benefit:** It ensures the code path for the **free user** is fully tested and functional, rather than relying on an object whose primary job is to fail or do nothing.

---

## 2. 🛡️ Nexus\\Compliance: The Feature Orchestrator

The new **`Nexus\Compliance`** package becomes the single source of truth for complex compliance rules. It handles both **Product Feature Management** (enabling code) and **Business Process Enforcement** (checking configurations).

### A. Core Functions

| Function | Description | Implementation Detail |
| :--- | :--- | :--- |
| **Compliance Definition** | Defines the contracts for each compliance scheme (e.g., `ISO9001ComplianceInterface`, `SSMComplianceInterface`). | The contract exposes methods like `isFeatureRequired(string $featureKey)` and `getRequiredConfiguration()`. |
| **Feature Composition** | Manages which underlying atomic package features must be present and enabled to satisfy the compliance scheme. | This is handled by a concrete **`ISO14001ComplianceManager`** (the adapter) that implements the contract. |
| **Binding Orchestration** | Provides a service that generates the necessary IoC bindings (which concrete class to use) based on the current scheme. | This service is called by `Nexus\Atomy`'s main service provider. |
| **Configuration Audit** | **Crucial:** Once enabled, it checks operational configurations across the ERP (e.g., in `Nexus\Settings`) to ensure the user has set up the mandatory items (e.g., that `Compulsory Mgmt Review Meeting Auto planning feature` is ON). | Uses injected services from `Nexus\Settings` and other packages to audit the active configuration. |

### B. Example Flow: Turning on ISO 14001

| Step | Action | Package Responsibility |
| :--- | :--- | :--- |
| 1. **User Action** | User flips the `ISO14001Compliance` switch in the `Atomy` UI. | `Nexus\Atomy` (User Interface) |
| 2. **Code Download** | `Atomy`'s deployment pipeline is triggered, downloading the private `nexus/iso-14001-compliance` package. | `Nexus\Atomy` (Composer/DevOps) |
| 3. **Binding** | `Atomy`'s main Service Provider registers the new package. The `ComplianceServiceProvider` calls the new `ISO14001ComplianceManager`. | **`Nexus\Compliance`** (Contract) / **`Atomy`** (Binding) |
| 4. **Feature Check** | The `ISO14001ComplianceManager` checks the state of the system against its internal rules. | **`ISO14001ComplianceManager`** (The Adapter) |
| 5. **Configuration Audit** | The Manager injects the `SettingsManager` and validates: `$settings->isFeatureEnabled('edm_document_retention')`. If false, it logs a mandatory setup error. | **`ISO14001ComplianceManager`** |
| 6. **Package Wiring** | The Manager tells the IoC container: "For the `AuditLogGeneratorInterface`, use the **`ISO14001CompliantAuditGenerator`** from my package, not the default one." | **`ISO14001ComplianceManager`** |

---

## 3. 🎯 Impact on Domain Packages (Pluggable Compliance)

The domain packages do not change. They simply inject a generic service interface:

* **`Nexus\AuditLogger`** injects `AuditLogGeneratorInterface`.
* **`Nexus\Accounting`** injects `TaxonomyReportGeneratorInterface`.

The **`Nexus\Compliance`** orchestrator decides, based on the enabled scheme, *which concrete implementation* to bind to that interface, effectively hijacking the default implementation and replacing it with the highly specialized, compliant version.

This structure allows you to market and sell specific **Compliance Schemas** while keeping your core ERP functional and atomic.

___

My apologies for the confusion regarding the separation of concerns between
That is a critical point of confusion, but one that is common in advanced modular design. You are right to seek clarity, as the separation of concerns between **`Nexus\Compliance`** and **`Nexus\Statutory`** is purely architectural, but essential for future maintainability.

The confusion arises because **"Statutory" is a *type* of "Compliance."**

In your ERP, we will strictly define their roles:

---

## 🛡️ 1. `Nexus\Compliance` (The Orchestrator)

* **Role:** The **Policy Manager** and **Feature Composer**. It is responsible for *governance*, *configuration*, and *enforcement* of **entire compliance schemes** (which can be internal, regulatory, or statutory).
* **Focus:** **Why** the system must be configured a certain way, and **What** combination of features is required.
* **Input/Output:** Takes a high-level scheme ID (`ISO14001`) and outputs a **list of required IoC bindings and configuration checks** that must be satisfied across the whole ERP.

| Function | Example | Atomic Justification |
| :--- | :--- | :--- |
| **Composition & Enforcement** | When ISO 14001 is ON, it *forces* the binding of the `ISO14001AuditGenerator` (the specific implementation) and validates that the `Meeting` package has mandatory features enabled. | Isolates the complex *inter-package dependency* logic from the core packages. |
| **Configuration Audit** | **Checks** that the user has set the "Compulsory Mgmt Review Meeting Auto planning feature" to **ON** in `Nexus\Settings`. | Deals with the interaction between multiple domains and user-level policy settings. |
| **High-Level Contracts** | Defines interfaces like `ComplianceSchemeInterface` and `SegregationOfDutiesInterface` (SOD). | Abstracts the rules for *governing* the system, not *reporting* from it. |

## 💰 2. `Nexus\Statutory` (The Reporter)

* **Role:** The **Data Formatter** and **Legal Reporting Contract Hub**. It is responsible for creating a **document** or **data file** that meets a government's mandatory reporting specification.
* **Focus:** **How** to format the data for a specific legal body.
* **Input/Output:** Takes raw data (e.g., a Trial Balance) and outputs a **standardized, legally mandated report** (e.g., XBRL file, EPF contribution summary).

| Function | Example | Atomic Justification |
| :--- | :--- | :--- |
| **Reporting Contracts** | Defines `TaxonomyReportGeneratorInterface` and `PayrollSummaryGeneratorInterface`. | Isolates the specific, ever-changing rules of *government reporting schemas* (like SSM's XBRL tags) from the compliance layer. |
| **Default Implementations** | Houses the **`DefaultSSMReportGenerator`** (which produces a blank/uncompliant report) and the **`MYSStatutoryCalculator`** (your free, open-source calculator). | Provides the baseline (free) service that the Compliance Orchestrator can choose to *replace* with a paid adapter. |
| **Schema Management** | Provides contracts for accessing the current version of the SSM Taxonomy or the EPF filing structure. | Centralizes all legal data structure knowledge. |

---

## 🧩 Separation of Concerns (The Analogy)

| Scenario | `Nexus\Compliance` | `Nexus\Statutory` |
| :--- | :--- | :--- |
| **Payroll:** EPF Filing | **Audit:** Is the employee contribution rate configured correctly for a full-time vs. part-time employee? | **Format:** Generate the final **EPF Form A** XML file for submission. |
| **Accounting:** SSM Filing | **Enforce:** Did the user close the fiscal period using a *compliant* closing procedure? | **Generate:** Create the **XBRL file** with all financial statements properly tagged. |
| **Quality:** ISO 14001 | **Composition:** If ISO 14001 is ON, ensure the **`AuditLogger`** is using the **`CompliantLogFormatter`** adapter. | **N/A** (ISO 14001 doesn't require a specific statutory *report*, only internal processes). |

### Conclusion

* **`Nexus\Compliance`** = The **Rule Book** (Check configurations, enforce process, manage feature composition).
* **`Nexus\Statutory`** = The **Forms and Filing** (Generate the legal document/file, define reporting contracts).

This separation is clean and scalable, allowing you to sell a premium "ISO 14001 Compliance Scheme" that relies on the "SSM Statutory Reporting" feature when required.

Would you like to start defining the contracts within the new **`Nexus\Compliance`** package, focusing on the configuration audit feature?


___

You've hit on a crucial concept. Yes, "Statutory" is essentially a container for reporting schemas, and they are almost never just a simple data structure.

The challenge is that every governmental body invents its own reporting format. To handle this, your **`Nexus\Statutory`** package must define a robust, unified contract that forces all specific compliance packages (the "plugins") to provide this critical metadata.

The key to standardization isn't finding one universal format (it doesn't exist), but creating a single, atomic **Contract** that requires all necessary metadata.

---

## 🏛️ Standardizing the Statutory Reporting Contract

The `Nexus\Statutory` atomic package should define a contract, let's call it the **`StatutoryReportMetadataInterface`**, which requires the following properties/methods from every statutory reporting package (like the one for SSM).

### 1. ⚙️ Report Schema and Content

| Metadata Requirement | Purpose | Example Value (SSM) |
| :--- | :--- | :--- |
| **`getSchemaIdentifier()`** | A unique ID for the specific set of data tags required. | `'SSMxT_2022'` |
| **`getSchemaVersion()`** | Ensures the ERP knows exactly which legal version is being used. | `'v1.0'` |
| **`getMappingTemplate()`** | The actual instructions for mapping your GL accounts to the regulatory tags (often a proprietary structure). | `Array of {GL_CODE: TAXONOMY_TAG}` |

### 2. 🗓️ Submission Logistics (Frequency & Recipient)

| Metadata Requirement | Purpose | Example Value (SSM) |
| :--- | :--- | :--- |
| **`getSubmissionFrequency()`** | Defines the required submission intervals. | `Annual`, `Monthly`, `Quarterly` |
| **`getReportingBody()`** | The recipient (legal entity) of the report. | `'Suruhanjaya Syarikat Malaysia (SSM)'` |
| **`getRecipientSystemURL()`** | The API endpoint or portal URL for direct submission/reference. | `https://mbrs.ssm.com.my` |

### 3. 💾 Output Format

| Metadata Requirement | Purpose | Example Value (SSM) |
| :--- | :--- | :--- |
| **`getOutputFormat()`** | The required file type for submission. | `XBRL` |
| **`getMimeType()`** | The required file type for submission. | `application/xml` |
| **`getValidationRules()`** | The rules required to pass the government's pre-submission check (e.g., minimum cash value cannot be negative). | `Closure or RuleSet` |

## 🧩 Why This Approach is Atomic

By defining this comprehensive **`StatutoryReportMetadataInterface`** in the **`Nexus\Statutory`** package, you ensure that:

1.  **Domain Packages are Clean:** Your `Nexus\Accounting` package never needs to hardcode the fact that SSM reports are due **Annually** or are in **XBRL**. It simply asks the injected contract: `$metadata->getSubmissionFrequency()`.
2.  **Compliance Packages are Complete:** Every country-specific implementation (e.g., `SSMBRSTaxonomyAdapter`) is **forced** by the contract to provide all necessary details, preventing partial implementations that lead to user errors.

This architectural pattern is what allows you to support vastly different schemes—from a simple **PDF Form** requirement to a complex **XBRL Schema**—all while keeping the integration point consistent.

____

That's a fantastic real-world example. It clearly illustrates the power of decoupling and feature-gating in your Nexus ERP.

Here is the breakdown of responsibilities and the exact flow when the **"MY Payroll Statutory Compliance"** is enabled, using the example of EPF and SOCSO filing.

---

## 1. 📦 `Nexus\Payroll` (The Generic Core)

**Responsibility:** **Calculate Gross Pay, apply generic deductions, and call the necessary contract.**

The generic `Nexus\Payroll` package remains **completely agnostic** of Malaysia, EPF, SOCSO, or specific contribution rates. It only knows that a **Statutory Deduction** must occur.

| Component | Action |
| :--- | :--- |
| **`PayrollManager` Service** | 1. Calculates the employee's **Gross Salary**. 2. Injects the generic `PayrollStatutoryInterface` (defined in `Nexus\Statutory`). 3. Calls the contract method: `$deductions = $statutoryCalculator->calculate($grossSalaryPayload);`. |
| **Data Payload** | Defines what data is required for *any* country: `GrossSalary`, `EmploymentStatus`, `EmployeeId`. **It does NOT contain country-specific fields** like 'EPF Number' or 'SOCSO Status'. |

---

## 2. 🇲🇾 The Orchestration (The Moment of Truth)

This is where the magic happens in **`Nexus\Atomy`** when the feature is enabled. The effect is immediate and twofold: **Validation** (Front-End) and **Implementation** (Back-End).

### A. API/UX Impact (Mandatory Field Submission)

**Yes, the Atomy API will now require mandatory fields.**

1.  **Orchestration Trigger:** When the user turns on "MY Payroll Statutory Compliance" in `Nexus\Settings`, the **`Nexus\Compliance`** package is notified.
2.  **Configuration Check:** The Compliance service checks its own rules: "If Malaysia Payroll is ON, then the `Hrm` package must submit `EPF Number` and `SOCSO Number` for all employees."
3.  **Hrm Validation Hijack:** `Nexus\Atomy` has an adapter that listens for these compliance rules. It **adds new validation rules** to the `Hrm` module's API endpoints (e.g., the `POST /employees` endpoint).
4.  **Result:** When a user now tries to create or update an employee, the API validation (handled by `Atomy`) will **fail** unless the mandatory, Malaysia-specific fields are submitted, even though the core `Nexus\Hrm` package never defined those fields.

### B. IoC Container Binding

The **`StatutoryServiceProvider`** in `Nexus\Atomy` switches the implementation for the core payroll manager:

| Scenario | Binding |
| :--- | :--- |
| **Feature OFF (Default)** | `PayrollStatutoryInterface` $\rightarrow$ `DefaultStatutoryCalculator` (Returns zero deductions). |
| **Feature ON (Paid)** | `PayrollStatutoryInterface` $\rightarrow$ `MYSStatutoryCalculator` (Calculates actual EPF/SOCSO). |

---

## 3. 📄 `Nexus\Statutory` (The Schema & Filing Contract)

**Responsibility:** **Standardize reporting metadata, formats, and schemas for regulatory bodies.**

This package deals with the final output required for submission, which is crucial for digital filing.

| Component | Responsibility | Example Output |
| :--- | :--- | :--- |
| **`PayrollFilingInterface`** | Defines the contract for generating the actual submission file. | `$filingService->generateFiling(ReportType::EPF_BORANG_A)` |
| **`ReportMetadataInterface`** | Defines the output properties needed for digital submission. | **Frequency:** `Monthly`, **Recipient:** `KWSP/EPF`, **Output Format:** `XML` (or other prescribed digital format). |

## 4. 🔄 Full Process Flow

The compliance feature creates a bridge between the **Generic Logic** and the **Regulatory Requirement**:

1.  **Trigger:** An event (`PayRunCompletedEvent`) is dispatched.
2.  **Deduction:** `Nexus\Payroll` calls the generic `$statutoryCalculator->calculate()`. The call is handled by the **`MYSStatutoryCalculator`** (the adapter in `Atomy`), which calculates the deductions using Malaysia's rules and returns them.
3.  **Posting:** The net pay is finalized, and a Journal Entry is posted to `Nexus\Finance`.
4.  **Filing:** A separate process calls the **`Nexus\Statutory`** package: `$payrollFiling->generate(EPF_BORANG_A)`.
5.  **Output:** The call is handled by the **`MYSFilingAdapter`** (in `Atomy`), which retrieves the required data (including the mandatory EPF numbers confirmed in step 2A) and formats it into the required **XML** structure for digital submission.

___

That is a fantastic, comprehensive list. You've mapped out a very advanced and commercially viable strategy for your ERP's statutory and compliance features.

Before validating the list, let me consolidate and refine the package names to maintain the strictest separation of concerns, particularly addressing your concern about coupling the **Default** implementations.

---

## 🏗️ Refined Atomic Package Structure

Your initial concern is valid: we must **NEVER** put the default implementation directly into the core package (e.g., `Nexus\Payroll`).

The best place for the default implementation is within the **Contract Hub** package (`Nexus\Statutory`). We will rename the default packages and use a cleaner naming convention for the country-specific implementations.

### 1. 🌐 The Orchestrators & Contracts (Horizontal)

| Package | Refined Role and Naming | Rationale |
| :--- | :--- | :--- |
| **`Nexus\Compliance`** | **Compliance Orchestrator.** Manages feature composition (ISO check, etc.). | **Correct.** Handles *why* and *what* features are enabled. |
| **`Nexus\Statutory`** | **Contracts & Default Adapters.** Contains all interfaces (`PayrollStatutoryInterface`, `TaxonomyReportGeneratorInterface`) and their **free default implementations**. | **Crucial:** Keeps the default logic decoupled from the core Payroll/Accounting engine and provides a fallback for the IoC container. |

### 2. 💰 The Payroll Compliance Plugins (Vertical)

The naming should follow a consistent pattern: `[Domain].[CountryCode]`.

| Your Name | Refined Name | Status & Notes |
| :--- | :--- | :--- |
| `Nexus\StatutoryPayrollDefault` | **Removed.** Logic moves to `Nexus\Statutory\DefaultPayrollAdapter`. | **Cleanest Solution.** The core package binds the default from `Statutory` when no plugin is active. |
| `Nexus\StatutoryPayrollMys` | **`Nexus\Statutory.Payroll.MYS`** | **Free/Open Source.** The concrete implementation of the contracts. |
| `Nexus\StatutoryPayrollSg` | **`Nexus\Statutory.Payroll.SGP`** | **Paid/Private Repo.** The premium implementation. |

### 3. 📈 The Accounting Compliance Plugins (Vertical)

The splitting of SSM by company type is excellent for **monetization** but can be **simplified** architecturally.

| Your Name | Refined Name | Status & Notes |
| :--- | :--- | :--- |
| `Nexus\StatutoryAccountingDefault` | **Removed.** Logic moves to `Nexus\Statutory\DefaultAccountingAdapter`. | **The P&L/BS Generator.** This default implementation *is* the P&L and Balance Sheet generator you mentioned. |
| `Nexus\StatutoryAccountingSSMPvt` + `Pub` + `For` + `LLP` | **`Nexus\Statutory.Accounting.SSM`** (One Package) | **Recommendation:** Unify these into one large **Gated/Paid** package. The differences between Pvt/Pub/LLP are mainly **configuration/disclosures** handled by internal feature flags within this single adapter, not separate codebases. This simplifies maintenance and deployment. |
| `Nexus\StatutoryAccountingSSMPro` | **`Nexus\Statutory.Accounting.MYS.Prop`** | **Open Source.** This is fundamentally different (simplified, often non-XBRL) and deserves its own implementation package. |

---

## ✅ Summary of Validated Architecture

Your proposed structure is **architecturally sound** and ready for this mission.

The key to its success is the **strict separation** achieved by the central **`Nexus\Statutory`** package, which acts as the **Universal Contract Hub** that all specific implementations (free or paid) must adhere to.

| Package | Core Function | Responsibility Flow |
| :--- | :--- | :--- |
| **`Nexus\Statutory`** | **Contract Hub & Defaults** | Defines the interfaces and provides the base, free P&L/BS/Zero-Deduction service. |
| **`Nexus\Statutory.[Domain].[Geo]`** | **Specific Implementation** | Implements the contracts with all the complex rules, rates, and filing formats (the "plugin"). |
| **`Nexus\Compliance`** | **Feature Orchestrator** | Tells `Nexus\Atomy` **which** specific implementation to bind to the contracts, based on user payment/feature flags. |

___

This is a perfect example to showcase the difference between the **Compliance Orchestrator** and the **Statutory Reporter**.

ISO 14001 is a framework for an **Environmental Management System (EMS)**. It cares about internal processes, measurement, and documentation—not generating a single legal filing for a government agency.

Here is how ISO 14001 fits into the architecture, confirming why it relies almost entirely on `Nexus\Compliance`.

-----

## 🌳 The ISO 14001 Compliance Scheme

We will define a new paid package: **`Nexus\Compliance.ISO14001`**.

| Package | Role in ISO 14001 Compliance | Key Actions |
| :--- | :--- | :--- |
| **`Nexus\Statutory`** | **ZERO ROLE.** | ISO 14001 requires internal records and audits, but no specific external statutory filing (like an SSM XBRL file). Therefore, this package is not involved. |
| **`Nexus\Compliance`** (The Orchestrator) | **Rulebook & Enforcer.** | Manages the composition of features needed to meet the ISO 14001 framework (e.g., Audit logs, KPIs, Training records). |
| **`Nexus\Compliance.ISO14001`** (The Adapter) | **The Proprietary Implementation.** | This is the package that contains the specific ISO 14001 logic, checklists, and integration adapters. |

### 1\. ⚙️ `Nexus\Compliance.ISO14001` Features (The Proprietary Code)

When this package is enabled in `Nexus\Atomy`, it overrides default behaviors across multiple domains:

| ISO Requirement | Atomic Package Impacted | `ISO14001` Adapter Action |
| :--- | :--- | :--- |
| **Environmental Aspects & Impacts** | `Nexus\Inventory`, `Nexus\Asset` | **Adds mandatory fields** to assets/materials (e.g., "Hazardous Material Flag," "Disposal Protocol ID") through `Atomy`'s validation layer. |
| **Operational Control** | `Nexus\Process` (Planned) | **Binds a compliant process checker.** Replaces the `DefaultProcessController` with `ISO14001EnvironmentalController` to enforce checks on water/energy usage inputs during process execution. |
| **Monitoring & Measurement** | `Nexus\Analytics` (Planned) | **Injects mandatory KPIs.** Binds a `PerformanceMetricGeneratorInterface` that includes required ISO KPIs like "Waste Diversion Rate (%)" or "Energy Consumption per Unit of Production." |
| **Internal Audits** | `Nexus\AuditLogger` | **Binds a specialized Audit Logger.** Replaces the `DefaultAuditLogFormatter` with an `ISO14001AuditLogFormatter` to ensure all logs include mandatory compliance tags (e.g., specific clause reference, root cause analysis fields). |
| **Management Review** | `Nexus\Meeting` (Planned) | **Enforces configuration.** Checks (via `Nexus\Compliance`) that the compulsory Management Review Meeting schedule is configured and tracked. |

### 2\. 📝 How `Nexus\Compliance` Orchestrates

The **`Nexus\Compliance`** package's core logic now manages this complexity:

```php
// In Nexus\Compliance\Providers\ComplianceServiceProvider (in Atomy)
public function register()
{
    // If the paid feature is downloaded AND enabled via config...
    if (config('features.iso_14001_enabled') && class_exists(ISO14001ComplianceManager::class)) {
        
        // 1. Bind the high-level manager
        $this->app->singleton(
            ComplianceSchemeInterface::class, 
            ISO14001ComplianceManager::class 
        );

        // 2. Override default services with compliant ones
        $this->app->singleton(
            AuditLogFormatterInterface::class, 
            ISO14001AuditLogFormatter::class 
        );
        // ... and so on for all relevant interfaces
    }
}
```

This model confirms the distinction:

  * **SSM Statutory:** Focused on **External Data Formatting** (The Report).
  * **ISO 14001 Compliance:** Focused on **Internal Process Enforcement** and **System Behavior Modification** (The Rules).

  ___

  That is absolutely the key insight, and you have defined the distinction perfectly. In the industry, the terms you are circling are often formalized as:

1.  **Your "Output Compliance"** $\rightarrow$ **Statutory Reporting Compliance** (or simply Regulatory Reporting).
2.  **Your "Operational Compliance"** $\rightarrow$ **Process Compliance** (or **Compliance Operations**).

You are **correct**: The architectural challenge is to separate these two concerns cleanly into their own atomic packages, which we have done:

| Your Term | Industry Term | Atomic Package | Primary Focus |
| :--- | :--- | :--- | :--- |
| **Output Compliance** | **Statutory Reporting** | **`Nexus\Statutory`** | **Data Format & Filing:** What the government **sees**. (SSM, EPF filing). |
| **Operational Compliance** | **Process Compliance** | **`Nexus\Compliance`** | **Workflow & Configuration:** What the company **does**. (ISO 14001, Segregation of Duties). |

This separation is critical because:

* **Statutory** requires a specific **data structure** that changes every few years (new XBRL taxonomy).
* **Compliance** requires specific **workflow enforcement** that changes based on internal policy or auditing standards.

Your architecture is now perfectly aligned to support both streams of business value: the mandatory filing feature and the premium process management feature.