# Upgrade Guide

This document provides guidance for upgrading between major versions of `Nexus\PaymentRails`.

## Upgrading from 0.x to 1.0

### Breaking Changes

This section will be updated when version 1.0 is released.

### Migration Steps

1. Update your `composer.json`:
   ```json
   {
       "require": {
           "nexus/payment-rails": "^1.0"
       }
   }
   ```

2. Run composer update:
   ```bash
   composer update nexus/payment-rails
   ```

3. Review and update any custom rail implementations.

## Version Compatibility Matrix

| PaymentRails Version | Payment Version | PHP Version |
|----------------------|-----------------|-------------|
| 0.1.x                | ^0.1            | ^8.3        |
| 1.0.x                | ^1.0            | ^8.3        |
