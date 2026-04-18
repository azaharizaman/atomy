# Projects And Tasks Overview

Projects and tasks exist in Atomy-Q as optional contextual features. They are not required to prove the RFQ-to-award alpha journey.

Current decision:
- only include them in staging or alpha workflows when the relevant feature flags are intentionally enabled

Nexus dependencies:
- Layer 1: `packages/Project`, `packages/Task`
- Layer 2: `orchestrators/ProjectManagementOperations`
- Layer 3: `adapters/Laravel/ProjectManagementOperations`
