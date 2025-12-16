# Nexus\Sanctions

**Production-ready international sanctions screening and Politically Exposed Person (PEP) detection for financial compliance.**

[![PHP Version](https://img.shields.io/badge/php-%5E8.3-blue)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)
[![FATF Compliant](https://img.shields.io/badge/FATF-Compliant-success)](https://www.fatf-gafi.org/)

---

## Overview

Nexus\Sanctions is a truly atomic, framework-agnostic PHP package that provides enterprise-grade sanctions screening and PEP detection capabilities. Designed for financial institutions, compliance teams, and ERP systems, it implements FATF (Financial Action Task Force) guidelines and OFAC requirements.

**Key Features:**
- ✅ Multi-list sanctions screening (OFAC, UN, EU, UK, AU, CA, JP, CH)
- ✅ Advanced fuzzy matching (Levenshtein distance, Soundex, Metaphone, token-based)
- ✅ PEP detection with FATF-compliant Enhanced Due Diligence (EDD) workflows
- ✅ Former PEP identification with 40% risk reduction (>12 months rule)
- ✅ Risk-based periodic re-screening (DAILY to ANNUAL frequencies)
- ✅ Batch processing for high-volume operations
- ✅ Comprehensive validation and error handling
- ✅ Performance tracking and detailed logging
- ✅ Immutable value objects with rich domain methods
- ✅ Type-safe enums with exhaustive match expressions

**Atomic Architecture:**
- **Zero Circular Dependencies**: Only depends on `nexus/common` and PSR interfaces
- **Interface-Based**: Package provides contracts, orchestrators inject implementations
- **Independently Testable**: Can be tested without other atomic packages
- **Framework Agnostic**: Pure PHP 8.3+, works with any framework

---

## Installation

```bash
composer require nexus/sanctions
```

**Requirements:**
- PHP 8.3 or higher
- `nexus/common` ^1.0
- `psr/log` ^3.0

---

## Quick Start

See full documentation in README for detailed examples of:
- Basic sanctions screening
- PEP detection and risk assessment  
- Periodic re-screening
- Advanced fuzzy matching algorithms
- Integration guide

---

## License

MIT License. See [LICENSE](LICENSE) for details.

---

**Maintained by:** Nexus Architecture Team  
**Last Updated:** December 2025
