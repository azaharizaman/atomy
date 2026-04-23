# Atomy-Q AI Investment Baseline Report

**Audience:** Board and Technical Committee  
**Date:** April 23, 2026  
**Scope:** AI/LLM investment baseline for Atomy-Q alpha launch, scale-up path, market offerings, and architectural fit

## 1. Decision Summary

Atomy-Q should not invest in a single "AI model strategy." The correct alpha investment is a capability stack:

1. Document AI / OCR for quote-file ingestion and table extraction
2. One general-purpose LLM for structured reasoning, recommendation explanations, and user-facing insights
3. Deterministic normalization as the operational source of truth
4. Optional sanctions/compliance API if vendor screening is part of the live alpha promise

### Recommended Alpha Baseline

- OCR: `Azure AI Document Intelligence` or `Google Document AI`
- LLM: `OpenAI`
- Operational backbone: existing deterministic Atomy-Q normalization and readiness pipeline
- Optional compliance: dedicated screening API such as `sanctions.io`

### Board-Level Conclusion

This is a moderate, staged investment, not a large AI-platform build. The minimal credible alpha can ship with two core external AI services plus the existing orchestration and review flows already present in Atomy-Q.

## 2. Why This Fits Atomy-Q

This recommendation aligns directly with the current product and codebase:

- `apps/atomy-q/docs/01-product/decisions/product-decision-log.md` states that deterministic quote intelligence is the supported alpha runtime mode until LLM provider wiring is production-ready.
- `apps/atomy-q/docs/01-product/scope/alpha-scope.md` defines the alpha workflow as RFQ creation -> quote intake -> normalization -> comparison -> award.
- `apps/atomy-q/docs/03-domains/normalization/overview.md` makes clear that normalization is currently deterministic and non-LLM and should fail loud in live mode.

Inference: for alpha, AI should improve ingestion, summaries, and recommendations, but must not replace deterministic control surfaces.

## 3. Existing Atomy-Q AI Touchpoints

The current system already exposes the right integration seams.

### Quote Ingestion

- `apps/atomy-q/API/app/Http/Controllers/Api/V1/QuoteSubmissionController.php`
- `ProcessQuoteSubmissionJob`
- `QuoteIngestionOrchestrator`
- Touchpoint: OCR, table extraction, field extraction, confidence scoring, retry/failure handling

### Normalization

- `apps/atomy-q/API/app/Http/Controllers/Api/V1/NormalizationController.php`
- Touchpoint: semantic assist for line mapping, taxonomy suggestion, confidence support
- Constraint: deterministic workflow remains final authority

### Vendor Recommendation

- `apps/atomy-q/API/app/Http/Controllers/Api/V1/VendorRecommendationController.php`
- Existing response already includes `fit_score`, `confidence_band`, `deterministic_reasons`, `llm_insights`, `warning_flags`
- Touchpoint: ranking explanation, rationale generation, candidate summarization

### AI Insights

- `apps/atomy-q/WEB/src/components/workspace/rfq-insights-sidebar.tsx`
- Touchpoint: RFQ-level summaries, comparison narrative, risk summarization

### Vendor Governance / Sanctions

- `apps/atomy-q/API/app/Http/Controllers/Api/V1/VendorGovernanceController.php`
- Current sanctions flow is effectively manual placeholder logic
- Touchpoint: external screening, evidence enrichment, governance signal aggregation

## 4. Model Types Atomy-Q Actually Needs

### A. Document OCR / Document Understanding

Purpose:

- Parse PDFs, scans, tables, forms, invoices, and vendor quote sheets
- Extract line items and document structure

Business value:

- Reduces manual data entry
- Creates the source-line payload used by normalization and comparison

Alpha recommendation:

- Mandatory if live quote-upload is in scope

### B. Structured Reasoning / Extraction LLM

Purpose:

- Convert OCR output into strict JSON
- Generate recommendation explanations and user-facing summaries
- Provide fallback interpretation where OCR structure is incomplete

Business value:

- Powers AI insights without becoming system-of-record logic

Alpha recommendation:

- Use for assistive reasoning, not authoritative state mutation

### C. Classification / Normalization Assist

Purpose:

- Suggest mapping between vendor lines and RFQ lines
- Suggest taxonomy codes or UOM normalization

Business value:

- Accelerates reviewer workflows

Alpha recommendation:

- Advisory only; keep human-confirmed or deterministic path final

### D. Ranking / Reranking

Purpose:

- Improve relevance of vendor candidates, related evidence, or recommendation ordering

Business value:

- Matters once recommendation quality becomes product-critical

Alpha recommendation:

- Not essential on day one

### E. Compliance / Screening Intelligence

Purpose:

- Sanctions/watchlist screening and evidence updates

Business value:

- Needed for defensible vendor governance

Alpha recommendation:

- Use dedicated compliance API, not LLM inference

## 5. Minimal Alpha Baseline

The smallest credible production-ready AI stack is:

- 1 OCR provider
- 1 LLM provider
- Existing deterministic normalization and readiness logic
- Manual review for low-confidence cases
- Audit trail, stored raw outputs, and fail-loud UX

That gives Atomy-Q these alpha capabilities:

- Upload quote files
- Extract line items and key fields
- Surface confidence and review blockers
- Suggest mappings and generate AI summaries
- Explain vendor recommendations
- Preserve deterministic comparison and award logic

This avoids the common alpha mistake of overbuying platform capability before workflow reliability is proven.

## 6. Recommended Vendor Stack

### Option 1: Best Overall Alpha Fit

- OCR: `Azure AI Document Intelligence`
- LLM: `OpenAI`
- Compliance: `sanctions.io` or equivalent only if screening is in the promised alpha workflow

Why:

- Strong enterprise document extraction
- Reliable typed-output LLM path
- Cleanest fit for current fail-loud, tenant-scoped, review-heavy architecture

### Option 2: Strong Alternative

- OCR: `Google Document AI`
- LLM: `OpenAI`
- Compliance: dedicated screening API

Why:

- Strong OCR and document classification
- Strong scale path if later document routing and custom processors are required

### Option 3: Consolidation-First Experiment

- OCR/document reasoning: `Gemini`
- LLM summaries: `Gemini` or `OpenAI`
- Deterministic normalization unchanged

Why:

- Fewer services
- Viable if speed of experimentation is prioritized over strict service separation

Primary recommendation remains Option 1.

## 7. Market Scan and Fit

### OpenAI

Fit:

- Best current fit for structured outputs, recommendation rationale, and insight generation

Strengths:

- Strong schema adherence
- Multimodal input support
- Broad deployment options

Board note:

- Use as the reasoning and insight layer, not the only extraction layer

### Azure AI Document Intelligence

Fit:

- Best fit for enterprise-grade quote-file ingestion

Strengths:

- Prebuilt extraction
- Invoice/document handling
- Custom classification path

Board note:

- Preferred if reliability and governance matter more than experimentation speed

### Google Document AI

Fit:

- Strong alternative for extraction and future document routing

Strengths:

- OCR, form parsing, classifier/processors, and scale story

Board note:

- Especially strong if document volume and routing complexity are expected to rise quickly

### AWS Textract

Fit:

- Valid if Atomy-Q infrastructure strategy becomes AWS-centric

Strengths:

- Forms, tables, queries, expense extraction

Board note:

- Not the lead recommendation unless AWS alignment is strategic

### Anthropic

Fit:

- Viable alternate LLM for long-context and document reasoning

Strengths:

- Strong reasoning quality and vision support

Board note:

- Good second-choice LLM, but OpenAI remains the priority for strict schema workflows

### Cohere Rerank

Fit:

- Later-stage enhancement for recommendation and retrieval quality

Board note:

- Not needed for alpha funding

### Mistral OCR

Fit:

- Interesting lower-cost or sovereignty-oriented option

Board note:

- Not the recommended primary OCR for alpha unless control or cost is the primary concern

### Compliance APIs

Fit:

- Best path for sanctions and watchlist screening

Board note:

- Do not frame screening as an LLM feature; it is a compliance data-service requirement

## 8. Investment Baseline for Alpha

### Budget Category 1: Must Fund Now

- OCR / document AI
- LLM API usage
- Secure prompt/output logging
- Evaluation and confidence thresholds
- Queue-based retry, storage, and auditability
- Human-review UX for low-confidence cases

### Budget Category 2: Should Fund Next
  
- Compliance screening API (see Section 11: Conditional Approval—funding depends on whether governance screening is in the live alpha commitment)
- Prompt/version registry
- Automated eval dataset and regression testing
- Reranking if vendor recommendation quality becomes central

### Budget Category 3: Do Not Fund Yet

- Fine-tuning
- Custom-trained classifiers
- Vector database / RAG platform
- Multi-provider routing layer
- Self-hosted model inference infrastructure

## 9. Scalability Requirement

### Alpha Scale

- Low to moderate document volume
- Queue-based asynchronous processing
- Tenant-isolated storage and logs
- One OCR provider and one LLM provider is sufficient

### Growth Scale

- Add provider abstraction at orchestrator boundaries
- Add confidence-based routing
- Add evaluation datasets by document type and tenant profile
- Batch or async non-urgent summary generation

### Enterprise Scale

- Second OCR provider for fallback or regional redundancy
- Reserved capacity / enterprise pricing
- Data residency controls
- Redaction and retention policies
- Usage quotas and cost governance by tenant
- Full AI audit trail per decision and per output version

### Technical Implication for Atomy-Q

The current Laravel queue and orchestrator model is already the right shape for this evolution. The AI investment does not require a rewrite. It requires clean provider adapters, stored artifacts, confidence thresholds, and operational evaluation.

## 10. Key Governance and Risk Controls

The board and technical committee should require these controls before live rollout:

- Tenant-scoped request and artifact isolation
- Stored raw OCR output and transformed output
- Strict schema validation before persistence
- Confidence thresholds and human-review fallback
- Fail-loud behavior for malformed AI payloads
- Prompt/version traceability
- No AI-generated state transitions without deterministic or human confirmation
- Compliance workflows separated from open-ended LLM reasoning
- Redaction policy for vendor and quote documents
- Cost telemetry per workflow and per tenant

## 11. Proposed Approval Path

### Approve Now

- Baseline OCR provider
- Baseline LLM provider
- Provider-integration engineering
- Evaluation harness and audit controls

### Conditional Approval

- Sanctions API only if governance screening is part of the live alpha commitment

### Defer

- Advanced recommendation ML
- Custom model training
- Retrieval platform
- Self-hosted inference

## 12. Final Recommendation

For Atomy-Q alpha, approve a two-service AI foundation:

- `Azure AI Document Intelligence`
- `OpenAI`

Keep:

- Deterministic normalization and readiness as source of truth
- Human review for low-confidence extraction and mapping
- Compliance as a separate API decision

This is the lowest-risk and most board-defensible investment path. It aligns with the actual Atomy-Q product scope, fits the current codebase without architecture disruption, and creates a clear scale-up path without premature platform spend.

## 13. Source References

### Internal Atomy-Q References

- `apps/atomy-q/docs/01-product/decisions/product-decision-log.md`
- `apps/atomy-q/docs/01-product/scope/alpha-scope.md`
- `apps/atomy-q/docs/03-domains/normalization/overview.md`
- `apps/atomy-q/API/app/Http/Controllers/Api/V1/QuoteSubmissionController.php`
- `apps/atomy-q/API/app/Http/Controllers/Api/V1/NormalizationController.php`
- `apps/atomy-q/API/app/Http/Controllers/Api/V1/VendorRecommendationController.php`
- `apps/atomy-q/API/app/Http/Controllers/Api/V1/VendorGovernanceController.php`
- `apps/atomy-q/WEB/src/components/workspace/rfq-insights-sidebar.tsx`

### External References

- OpenAI pricing: https://openai.com/api/pricing/
- OpenAI vision guide: https://developers.openai.com/api/docs/guides/images-vision
- OpenAI structured outputs: https://developers.openai.com/api/docs/guides/structured-outputs
- Azure Document Intelligence overview: https://learn.microsoft.com/en-us/azure/ai-services/document-intelligence/overview?view=doc-intel-3.1.0
- Google Document AI pricing: https://cloud.google.com/document-ai/pricing
- Gemini document processing: https://ai.google.dev/gemini-api/docs/document-processing
- AWS Textract overview: https://docs.aws.amazon.com/textract/latest/dg/what-is.html
- Anthropic vision: https://platform.claude.com/docs/en/build-with-claude/vision
- Anthropic consistency guidance: https://platform.claude.com/docs/en/test-and-evaluate/strengthen-guardrails/increase-consistency
- Cohere Rerank: https://docs.cohere.com/docs/rerank
- Mistral OCR: https://docs.mistral.ai/studio-api/document-processing/basic_ocr
- sanctions.io screening API: https://www.sanctions.io/solutions/screening-api
