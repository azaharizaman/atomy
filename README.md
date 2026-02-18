# Nexus - Framework-Agnostic ERP Infrastructure

Nexus is a modular, **three-layer monorepo** containing 50+ atomic PHP packages designed for building scalable Enterprise Resource Planning (ERP) systems. It enforces a strict separation between business logic, workflow coordination, and framework implementation.

---

## 🏗️ Core Architecture

Nexus is built on a "Logic in Packages, Implementation in Applications" philosophy, structured into three distinct layers:

1.  **Atomic Packages (`packages/`)**: Pure, framework-agnostic business logic. Self-contained and publishable units that define domain contracts (interfaces) but contain no persistence or framework logic.
2.  **Orchestrators (`orchestrators/`)**: Cross-package workflow coordination. Pure PHP components that manage the "flow" of business processes using the **Advanced Orchestrator Pattern**.
3.  **Adapters (`adapters/`)**: Framework-specific implementations (Laravel/Eloquent). This is the only place where database migrations, models, and framework controllers reside.

---

## 🚀 Getting Started

### Prerequisites
- PHP 8.3+
- Composer

### Installation
1. **Clone and Install:**
    ```bash
    git clone <repository-url> nexus
    cd nexus
    composer install
    ```
2. **Explore the Ecosystem:**
    ```bash
    ls packages/        # Browse core business domains
    ls orchestrators/   # Browse workflow coordinators
    ```

---

## 📖 Documentation Hub

To keep the codebase maintainable, documentation is structured as follows:

- **[ARCHITECTURE.md](ARCHITECTURE.md)**: **Mandatory reading.** Covers architectural layers, coding standards, the Advanced Orchestrator Pattern, and authorization rules.
- **[docs/ORCHESTRATOR_INTERFACE_SEGREGATION.md](docs/ORCHESTRATOR_INTERFACE_SEGREGATION.md)**: **Mandatory for orchestrator development.** Defines how orchestrators interact with atomic packages through interface segregation.
- **[docs/NEXUS_PACKAGES_REFERENCE.md](docs/NEXUS_PACKAGES_REFERENCE.md)**: Inventory and capabilities of all 51+ internal packages.
- **[docs/NEXUS_SYSTEM_OVERVIEW.md](docs/NEXUS_SYSTEM_OVERVIEW.md)**: Deep-dive reference for AI assistants and senior architects.

---

## 🤝 Contributing

Before contributing, please review the **[ARCHITECTURE.md](ARCHITECTURE.md)** to ensure your code follows our strict standards:
1. **Framework Agnosticism**: No framework-specific code in packages or orchestrators.
2. **Contract-Driven**: Define requirements via interfaces; use primary dependency injection.
3. **Stateless Logic**: Externalize all long-term state via storage interfaces.
4. **Modern PHP**: Strict types, readonly properties, and native enums are mandatory.

---

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
