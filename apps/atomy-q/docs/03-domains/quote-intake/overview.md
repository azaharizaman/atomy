# Quote Intake Overview

Quote intake covers:
- quote file upload and persistence
- quote submission status progression
- quote parsing and readiness handoff into normalization

Alpha runtime decision:
- deterministic quote intelligence path
- sync queue posture for staging smoke

Nexus dependencies:
- Layer 1: `packages/Sourcing`, `packages/Document`, `packages/Notifier`
- Layer 2: `orchestrators/QuoteIngestion`
- Layer 3: Atomy-Q API quote submission services plus storage integration
