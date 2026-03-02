# Implementation Summary - Canary Atomy TUI

## Overview
A Python-based Terminal User Interface (TUI) for system administrators to manage the Atomy Canary application bundle. Built using the `Textual` framework to provide a rich, agile, and "micasa-style" experience.

## Architecture
- **Tech Stack:** Python 3.12, Textual, HTTPX (Async API client).
- **Communication:** Interfaces with `canary-atomy-api` (PHP/Symfony) via RESTful endpoints.
- **Security:** Uses JWT Bearer authentication.

## Features Implemented
- **Login Screen:** Authenticate as a system admin.
- **Sidebar Navigation:** Quickly switch between management modules.
- **Tenant Management:**
    - List all tenants with status.
    - Keyboard actions: `s` (Suspend), `a` (Activate), `x` (Archive).
- **User Management:** List users associated with the current tenant/system.
- **Module Installer:**
    - Scans local `orchestrators/` directory for available modules.
    - Interfaces with API to install selected modules.
- **Feature Flags:** List and view status of system feature flags.

## Key Shortcuts
- `q`: Quit
- `ctrl+s`: Focus Sidebar
- `r`: Refresh current view
- `s/a/x`: Tenant lifecycle actions (Suspend/Activate/Archive)
- `Enter`: Install module (in Module Installer)

## Setup
1. Run `./apps/canary-atomy-tui/setup.sh` to create venv and install dependencies.
2. Run `./apps/canary-atomy-tui/run.sh` to start the TUI.
