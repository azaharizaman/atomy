# Upgrade Guide

This document provides guidance for upgrading between major versions of `Nexus\Payment`.

## Upgrading from 0.x to 1.0

### Breaking Changes

This section will be updated when version 1.0 is released.

### Migration Steps

1. Update your `composer.json`:
   ```json
   {
       "require": {
           "nexus/payment": "^1.0"
       }
   }
   ```

2. Run composer update:
   ```bash
   composer update nexus/payment
   ```

3. Review and update any custom implementations of package interfaces.

### Deprecated Features

No deprecations at this time.

## Version Compatibility Matrix

| Payment Version | PHP Version | Common Version |
|-----------------|-------------|----------------|
| 0.1.x           | ^8.3        | ^1.0           |
| 1.0.x           | ^8.3        | ^1.0           |

## Getting Help

If you encounter issues during upgrade:

1. Check the [CHANGELOG.md](CHANGELOG.md) for detailed changes
2. Review the [REQUIREMENTS.md](REQUIREMENTS.md) for interface changes
3. Open an issue on the repository if needed
