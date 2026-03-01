# Nexus\Loyalty

Framework-agnostic loyalty point and tier management engine for the Nexus ERP ecosystem.

## Overview

The **Nexus\Loyalty** package provides a highly granular, atomic loyalty engine designed to manage member point balances, tier qualifications, and redemption constraints. It is built as a Layer 1 package, meaning it contains pure business logic with no framework dependencies.

### Core Principles

1.  **Framework-Agnostic**: Pure PHP 8.3 with no dependencies on Laravel or Symfony.
2.  **Stateless Design**: All persistence is handled via externalized repository interfaces.
3.  **Strict Immutability**: All Value Objects and core services follow strict immutability patterns.
4.  **Multi-Tenancy Ready**: Designed to work within the Nexus multi-tenant ecosystem.

## Features

-   **Point Accrual Engine**: Precise point calculation with rounding strategies and multi-factor multipliers.
-   **Tier Management**: Flexible tier qualification using rolling activity windows and retention logic.
-   **Redemption & Validation**: Rule-based redemption with minimum thresholds and FIFO expiry prioritization.
-   **Point Integrity**: Audit-ready adjustment logging and idempotency support.
-   **Coalition Support**: Multi-brand point sharing and brand-specific balance tracking.

## Architecture

-   **Layer 1 (Atomic Package)**: Contains pure domain logic, entities, and value objects.
-   **Namespace**: `Nexus\Loyalty`
-   **Purity**: No `use Illuminate\*` or framework-specific imports allowed.

## Usage

(Examples to be added as implementation progresses)

## Installation

```bash
composer require nexus/loyalty
```

## License

MIT License - see LICENSE file for details.
