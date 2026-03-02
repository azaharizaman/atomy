# Architectural Design (Senior Architect)

This skill generates high-fidelity, highly technical architectural design documents for new features, packages, or system-wide changes. The output is a comprehensive, multi-page document suitable for senior architectural review.

## Usage
Activate this skill when you need to define the technical blueprint for a complex change, major feature, or new capability before implementation begins.

## Inputs
- **Request**: A description of the feature or structural change (e.g., "Centralized ML management across domains").
- **Context**: Any specific constraints, technical preferences, or reference documents (e.g., "See `docs/project/research/loyalty-program-erp-capability.md`").

## Actions

1.  **Analyze Request & Context**:
    -   Map the request against the Three-Layer Architecture (L1, L2, L3).
    -   **CRITICAL**: If a reference document (e.g., Research Analysis) is provided, read and analyze it. Explicitly note which parts are relevant and which are being adapted or discarded.

2.  **Generate Design Document**:
    -   **Path**: `docs/project/architecture/{FeatureName}.md`
    -   **Format**: Comprehensive, 10+ page Markdown document.
    -   **Structure**:

        ### 1. Executive Summary
        -   **Mission Statement**: High-level technical goal.
        -   **Problem Statement**: Specific gaps in current system.
        -   **Enterprise Use Cases**: Bulleted list of real-world scenarios showing how this supports enterprise operations (e.g., "Multi-brand loyalty consolidation").

        ### 2. Feasibility Study
        -   **Readiness Assessment**: "Are we ready now?" (Green/Yellow/Red).
        -   **Mitigations**: Strategies to handle identified gaps.
        -   **Critical Prerequisites**: What *must* exist for this to work (e.g., "Requires new `GeneralLedger` event bus").

        ### 3. Functional Architecture
        -   **New Packages**: Table listing new Layer 1/L2 packages, their Responsibility, and Domain Focus.
        -   **Existing Package Impact**: Table listing existing packages (e.g., `Sales`, `Accounting`) and their specific "Touch Points" (Integration points).
        -   **External Communication**: Diagram or description of external APIs/Webhooks if required.

        ### 4. System Architecture
        -   **Mermaid Diagrams**:
            -   **Component Diagram**: Visualizing L1, L2, and L3 interactions.
            -   **Data Flow**: Sequence diagram of a core operation (e.g., "Point Accrual").
        -   **Modularity & Integration**: specific explanation of shared databases, event-driven patterns, and API boundaries.
        -   **Changes to Layers**: Breakdown of impact on Presentation, Business Logic, and Data Access layers.

        ### 5. Non-Functional Design
        -   **Testing Plan**: Strategy for Unit (L1), Integration (L2), and End-to-End (L3) testing.
        -   **Performance & Scalability**: Complexity analysis (Big O), indexing strategy, caching needs.
        -   **Security**: Tenant isolation, PII handling, Role-Based Access Control (RBAC).
        -   **Risk Management**: Identified risks and mitigation strategies.
        -   **Error Handling**: Strategy for domain exceptions, retries, and dead-letter queues.

3.  **Cross-Reference for Planning**:
    -   **CRITICAL**: When `openspec-propose` or any planning tool is subsequently used, the agent MUST search `docs/project/architecture/` for a matching design and ensure the plan adheres to it.

## Example Prompt
"Make architectural design to introduce centralized ML management across all business domain packages, referencing the research report."
