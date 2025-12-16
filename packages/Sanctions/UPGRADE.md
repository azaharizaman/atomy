# Upgrade Guide

This document provides instructions for upgrading between major versions of `nexus/sanctions`.

## Table of Contents

- [Upgrading to 1.x from 0.x](#upgrading-to-1x-from-0x)
- [General Upgrade Steps](#general-upgrade-steps)

---

## Upgrading to 1.x from 0.x

**Version 1.0.0 is the initial release.** There are no breaking changes to migrate from.

If you're integrating this package for the first time, please see the [README.md](README.md) for installation and usage instructions.

---

## General Upgrade Steps

When upgrading to a new major version:

1. **Review the CHANGELOG:**
   Check [CHANGELOG.md](CHANGELOG.md) for a complete list of changes, including breaking changes, new features, and bug fixes.

2. **Update composer.json:**
   ```bash
   composer require nexus/sanctions:^2.0  # Example for v2.x
   ```

3. **Run tests:**
   After upgrading, run your test suite to ensure compatibility:
   ```bash
   vendor/bin/phpunit
   ```

4. **Review deprecation warnings:**
   Check logs for any deprecation warnings and update your code accordingly.

5. **Update documentation:**
   Review inline code comments and update any references to changed APIs.

---

## Breaking Changes by Version

### Version 1.x

No breaking changes (initial release).

---

## Support

For upgrade assistance or questions:

- **Documentation:** See README.md and inline docblocks
- **Issues:** Report upgrade issues on the project repository
- **Architecture:** Consult ARCHITECTURE.md for design patterns

---

**Last Updated:** December 16, 2025  
**Maintained By:** Nexus Architecture Team
