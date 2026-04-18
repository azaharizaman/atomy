# Awards Overview

Award handling is the final business outcome of the Atomy-Q alpha flow:
- create award from final comparison evidence
- persist award and debrief-related state
- sign off the award
- expose award and decision-trail evidence back to the WEB

Nexus dependencies:
- Layer 1: `packages/Sourcing`, `packages/Notifier`
- Layer 2: `orchestrators/SourcingOperations`
- Layer 3: Atomy-Q API award controllers/services and WEB award UI/hooks
