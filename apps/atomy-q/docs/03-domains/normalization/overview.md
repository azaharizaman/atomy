# Normalization Overview

Normalization turns quote source lines into comparable RFQ-aligned data and exposes conflicts that must be resolved before comparison finalization.

Current alpha decision:
- the supported production-like runtime is deterministic, honestly named, and documented as non-LLM
- malformed or unavailable live data must fail loudly rather than silently degrade

Nexus dependencies:
- Layer 1: `packages/Sourcing`, `packages/MachineLearning`
- Layer 2: `orchestrators/QuotationIntelligence`, `orchestrators/QuoteIngestion`
- Layer 3: Atomy-Q API quotation-intelligence adapters and providers
