---
name: research
description: Performs deep research on industry standards, competitor ERP systems, and modern tech trends.
---

# Research Analysis (Market & Technical)

This skill performs deep research on industry standards, competitor ERP systems, and modern tech trends.

## Usage
Activate this skill when you need a well-researched analysis before making architectural or product decisions.

## Inputs
- **Topic**: The technical or market-related question (e.g., "Comparison of Atomy CRM to Salesforce and SAP Business One").
- **Constraint**: Specific focus areas (e.g., "Performance," "UI/UX," "Multi-tenancy").

## Actions

1.  **Perform Search & Analysis**:
    -   Use `google_web_search` and `web_fetch` to gather data from high-authority sources.
    -   Compare against the **Atomy Core Mandates** (`docs/project/GEMINI.md`).
2.  **Generate Research Document**:
    -   **Path**: `docs/project/research/{TopicName}.md`
    -   **Sections**:
        -   **Overview**: Executive summary.
        -   **Market Analysis**: How other systems handle this (ERP, CRM, SaaS).
        -   **Best Practices**: Industry standards (e.g., ISO, NIST, GAAP).
        -   **Gap Analysis**: Where Atomy current implementation differs or can improve.
        -   **Recommendations**: Actionable insights for architecture or UX.
3.  **Cross-Reference**:
    -   Architecture or Implementation plans should later cite these research findings if relevant.

## Constraints
-   **Data Fidelity**: Use real-world data and cite sources.
-   **ERP Context**: Always relate findings to the "Atomy" (Nexus) mission of high-performance ERP.

## Example Prompt
"Research modern multi-tenant inventory management and compare it to SAP and Odoo."
