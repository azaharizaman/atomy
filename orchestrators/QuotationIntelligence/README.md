# QuotationIntelligence Orchestrator

AI-driven quotation normalization and intelligence orchestrator for the Atomy (Nexus) project.

## Overview

This orchestrator bridges the gap between structured ERP procurement and unstructured vendor quotes (PDF/Excel). It coordinates the following pipeline:
1. **Ingestion**: Upload and validation of unstructured quote documents.
2. **Extraction**: AI-driven extraction of line items and commercial terms.
3. **Normalization**: Semantic mapping to taxonomies (UNSPSC) and unit/currency normalization.
4. **Risk Assessment**: Detection of pricing anomalies and term deviations.
5. **Traceability**: Linking every data point back to its source snippet in the original document.

## Architecture

- **Layer 2 Orchestrator**: Coordinates multiple Layer 1 packages (`Procurement`, `MachineLearning`, `Document`, `Uom`, `Currency`).
- **Async Workflow**: Uses an event-driven pipeline for document processing.
- **Traceable VOs**: Uses `ExtractionEvidence` and `QuoteSnippet` to maintain audit trails.

## Usage

```php
use Nexus\QuotationIntelligence\Contracts\QuotationIntelligenceCoordinatorInterface;

// Orchestrate the full intelligence flow
$coordinator->processQuote($tenantId, $documentId);
```
