# Vendors Domain Overview

Vendor handling in Atomy-Q alpha is buyer-side only:
- maintain RFQ invitation records
- show tenant-scoped vendor information relevant to quote comparison

Nexus dependencies:
- Layer 1: `packages/Vendor`
- Layer 2: vendor behavior is coordinated through sourcing and related app adapters
- Layer 3: `adapters/Laravel/Vendor`
