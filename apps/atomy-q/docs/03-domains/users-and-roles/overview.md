# Users And Roles Overview

Users and Roles is in alpha only as a minimal tenant-scoped administrative surface:
- list users
- invite user
- suspend user
- reactivate user
- read roles

This is not full identity administration.

Nexus dependencies:
- Layer 1: `packages/Identity`
- Layer 2: `orchestrators/IdentityOperations`
- Layer 3: `adapters/Laravel/Identity` and app-local user controllers
