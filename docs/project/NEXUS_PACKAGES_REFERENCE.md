# Nexus First-Party Packages Reference

**Version:** 2.0  
**Last Updated:** 2026-04-22  
**Target Audience:** Coding agents and Nexus developers  
**Purpose:** Prevent duplicated package work and architecture violations by documenting every first-party package under `packages/`, what it owns, and what it does not own.

## Golden Rule

Before implementing a feature, check this guide first. If a first-party Nexus package already owns the capability, consume its contract through dependency injection instead of creating a duplicate implementation.

Layer 1 packages stay framework-agnostic. Layer 2 orchestrators coordinate packages. Layer 3 adapters own Laravel, HTTP, queue, database, filesystem, and vendor SDK integration.

## How To Read Each Package Entry

Each row provides the required package guidance:

| Section | Meaning |
|---------|---------|
| **Brief Introduction** | What the package is in one sentence. |
| **Main Purpose / Main Feature** | The central capability the package owns. |
| **What It Is Not** | Nearby responsibilities that belong to another package or layer. |
| **Use It When** | The trigger for choosing the package in implementation. |

## Category Order

Packages are ordered from platform foundations, then security and privacy, then shared technical capabilities, then business domains.

1. Foundational Platform
2. Security, Privacy, Risk, and Compliance
3. Integration, Data Movement, Content, Communication, and Insights
4. Geography, Routing, and Location
5. Party, Organization, and Master Data
6. Finance and Accounting
7. Payments, Banking, and Settlement
8. Procurement, Sourcing, Inventory, and Supply Chain
9. Sales, CRM, and Loyalty
10. Project, Delivery, and Field Operations
11. Workforce and HR
12. ESG and Sustainability
13. Blockchain

## Foundational Platform

These packages provide platform primitives, tenant isolation, command safety, eventing, workflow, and configuration. Check these before creating shared helpers.

| Package | Brief Introduction | Main Purpose / Main Feature | What It Is Not | Use It When |
|---------|--------------------|-----------------------------|----------------|-------------|
| `Nexus\Common` | Shared primitives and small cross-package contracts. | ULIDs, clocks, operation results, and low-level value objects. | Not a dumping ground for business services; business rules belong in domain packages. | You need a reusable primitive that is genuinely package-neutral. |
| `Nexus\Tenant` | Multi-tenancy context and tenant isolation package. | Resolve tenant context, tenant persistence/query contracts, impersonation storage, and tenant-aware events. | Not identity, RBAC, or authorization policy evaluation; use `Identity` and `PolicyEngine` for those. | Any operation must be scoped to a tenant or needs tenant context. |
| `Nexus\Setting` | Hierarchical settings engine. | Resolve settings across application, tenant, and user scopes with schema and cache contracts. | Not feature rollout; use `FeatureFlags`. Not secrets storage; use adapter-level secret management and `Crypto` as needed. | You need configurable behavior by scope. |
| `Nexus\FeatureFlags` | Feature flag and rollout engine. | Context-based flag evaluation, percentage rollout, kill switches, audit records, and custom evaluators. | Not permanent application settings; use `Setting`. Not authorization; use `Identity` or `PolicyEngine`. | You need controlled rollout, temporary gates, or emergency disablement. |
| `Nexus\Sequencing` | Pattern-based auto-numbering system. | Generate sequence numbers with counters, reservations, gaps, pattern versions, and audit trails. | Not identity generation for domain objects; use `Common` ULIDs where opaque IDs are enough. | You need human-facing numbers such as invoice, RFQ, PO, or journal numbers. |
| `Nexus\Scheduler` | Future-dated instruction and job scheduling domain. | Schedule jobs, queue due work, export calendars, and resolve handlers. | Not queue execution infrastructure; Layer 3 workers run scheduled jobs. Not notification delivery; use `Notifier`. | You need domain-owned delayed or recurring execution rules. |
| `Nexus\Idempotency` | Domain-level command deduplication and replay package. | Begin, complete, and fail mutating operations using tenant-scoped keys, request fingerprints, attempt tokens, and replay-safe result envelopes. | Not an outbox, queue, HTTP retry layer, or persistence adapter. Use `Outbox` for durable fan-out after commit. | You need safe retries for mutating APIs, imports, callbacks, or commands. |
| `Nexus\Outbox` | Transactional outbox domain model and publish lifecycle. | Enqueue messages, claim pending rows, mark sent or failed, and schedule retries using lease and claim tokens. | Not an event store or HTTP client; use `EventStream` for event sourcing and Layer 3 for transport. | You need durable post-commit integration fan-out. |
| `Nexus\EventStream` | Event sourcing package for critical domains. | Event store contracts, event publishing, serialization, upcasting, and cursor pagination. | Not a queue, outbox, or analytics engine; use `Outbox` for delivery and `QueryEngine` for query aggregation. | You need append-only domain event history or replayable event streams. |
| `Nexus\PolicyEngine` | Deterministic policy evaluation engine. | Compile, validate, decode, register, and evaluate typed policies for authorization, workflow, and thresholds. | Not user management or role assignment; use `Identity`. Not workflow execution; use `Workflow`. | You need reusable rule evaluation with explicit inputs and deterministic decisions. |
| `Nexus\Workflow` | Framework-agnostic workflow engine. | Workflow activities, approvals, conditions, delegations, definitions, and history. | Not policy authoring itself; use `PolicyEngine` for rule decisions. Not notifications; use `Notifier`. | You need stateful approval or business-process progression. |

## Security, Privacy, Risk, and Compliance

Use these packages before writing custom auth, audit, screening, privacy, cryptography, or regulatory checks.

| Package | Brief Introduction | Main Purpose / Main Feature | What It Is Not | Use It When |
|---------|--------------------|-----------------------------|----------------|-------------|
| `Nexus\Identity` | Framework-agnostic identity and access management. | Authentication context, users, password/MFA flows, sessions, permissions, roles, and RBAC data access contracts. | Not SAML/OIDC federation; use `SSO`. Not generic policy rules; use `PolicyEngine`. | You need login, MFA, users, roles, permissions, or auth context. |
| `Nexus\SSO` | Single Sign-On package. | SAML, OAuth, OIDC provider contracts, callback state validation, attribute mapping, and SSO manager flows. | Not local password/MFA management; use `Identity`. | You need enterprise identity provider federation. |
| `Nexus\Crypto` | Cryptographic abstraction layer. | Hashing, signing, anonymization, masking, key generation, and post-quantum-ready crypto contracts. | Not audit logging or data privacy workflow; use `Audit`, `AuditLogger`, and `DataPrivacy`. | You need encryption, signing, hashing, masking, or signature verification primitives. |
| `Nexus\Audit` | Cryptographically verified immutable audit engine. | Hash-chained audit records, signatures, sequence management, verification, and audit storage. | Not general activity feed logging; use `AuditLogger`. Not telemetry metrics; use `Telemetry`. | You need tamper-evident audit evidence. |
| `Nexus\AuditLogger` | General audit logging package. | Track CRUD and system activities through audit log repositories and configuration. | Not cryptographic audit proof; use `Audit`. Not application metrics; use `Telemetry`. | You need business/application audit entries and user action history. |
| `Nexus\Compliance` | Operational compliance and SOD package. | Rule engine, compliance schemes, segregation-of-duties rules, and violations. | Not AML/KYC/sanctions screening; use `AmlCompliance`, `KycVerification`, and `Sanctions`. | You need operational controls or SOD enforcement. |
| `Nexus\DataPrivacy` | Data privacy core package. | Consent, data subject rights, retention policies, breach records, privacy audit hooks, and encryption provider contracts. | Not jurisdiction-specific GDPR or PDPA extensions; use `GDPR` or `PDPA`. | You need privacy lifecycle, consent, DSR, retention, or breach workflow. |
| `Nexus\GDPR` | GDPR extension over data privacy. | GDPR-specific breach, data subject request, and compliance services. | Not the generic privacy foundation; use `DataPrivacy`. Not Malaysia PDPA; use `PDPA`. | You need EU GDPR-specific privacy behavior. |
| `Nexus\PDPA` | Malaysia PDPA compliance extension. | PDPA-specific data subject request and compliance services. | Not generic privacy foundation; use `DataPrivacy`. Not EU GDPR; use `GDPR`. | You need Malaysia PDPA-specific privacy behavior. |
| `Nexus\AmlCompliance` | Anti-Money Laundering package. | AML risk assessment, transaction monitoring, SAR management, and risk-level rules. | Not identity verification itself; use `KycVerification`. Not sanctions list matching; use `Sanctions`. | You need AML risk scoring or suspicious activity monitoring. |
| `Nexus\KycVerification` | Know Your Customer verification package. | Customer due diligence, document/address verification providers, beneficial ownership tracking, and KYC profile persistence contracts. | Not AML transaction monitoring; use `AmlCompliance`. Not sanctions screening; use `Sanctions`. | You need verify customer identity or ownership structure. |
| `Nexus\Sanctions` | Regulatory sanctions and PEP screening package. | Screen parties against sanctions and PEP lists using fuzzy matching and periodic screening. | Not KYC workflow or AML scoring; use `KycVerification` and `AmlCompliance`. | You need sanctions, watchlist, or PEP checks. |

## Integration, Data Movement, Content, Communication, and Insights

These packages own external connectivity, import/export, storage, documents, messages, notifications, reporting, analytics, telemetry, and ML.

| Package | Brief Introduction | Main Purpose / Main Feature | What It Is Not | Use It When |
|---------|--------------------|-----------------------------|----------------|-------------|
| `Nexus\Connector` | External API integration hub. | HTTP client, retry, rate limiting, circuit breaker, credential provider, and connector contracts. | Not a domain-specific gateway such as payments; use `PaymentGateway` for payment processors. | You need resilient outbound third-party API communication. |
| `Nexus\Import` | Data import engine. | Import parsing, mapping, duplicate detection, context authorization, repositories, and processors. | Not export rendering; use `Export`. Not document OCR; use `DataProcessor`. | You need structured data ingestion into Nexus. |
| `Nexus\Export` | Export and output rendering engine. | Export definitions, formatters, template engine, and export generator. | Not scheduled report composition; use `Reporting`. Not raw storage; use `Storage`. | You need produce CSV, PDF, spreadsheet, or other output artifacts. |
| `Nexus\Storage` | File storage abstraction. | Storage drivers and public URL generation contracts. | Not document lifecycle, metadata, or retention; use `Document`. | You need store, fetch, or expose binary objects through a storage driver. |
| `Nexus\Document` | Document management engine. | Document metadata, relationships, audit payloads, content processing, disposal certification, and async upload contracts. | Not raw object storage; use `Storage`. Not OCR/ETL recognition; use `DataProcessor`. | You need document lifecycle, metadata, governance, or relationships. |
| `Nexus\DataProcessor` | OCR, ETL, and document recognition contracts. | Document recognizer contracts for extracting structured information. | Not document management; use `Document`. Not import orchestration; use `Import`. | You need recognize or extract data from documents. |
| `Nexus\Content` | Knowledge base and content management package. | Content repositories, search, versioning, workflow, and multilingual content. | Not regulated document storage; use `Document`. Not notifications; use `Notifier`. | You need manage articles, knowledge content, or CMS-like records. |
| `Nexus\Messaging` | Channel-agnostic communication record package. | Immutable message records, templates, connectors, and rate limiting for email, SMS, chat, and webhooks. | Not user-facing notification preference and delivery orchestration; use `Notifier`. | You need store or model communication messages independent of delivery workflow. |
| `Nexus\Notifier` | Multi-channel notification engine. | Notification manager, notifiables, channels, preferences, queue, history, and delivery status tracking. | Not a generic message archive; use `Messaging`. Not job scheduling; use `Scheduler`. | You need send or queue email, SMS, push, or in-app notifications. |
| `Nexus\QueryEngine` | High-performance query and aggregation engine. | Query definitions, execution, aggregation, authorization context, and result models. | Not report scheduling/presentation; use `Reporting`. Not telemetry; use `Telemetry`. | You need analytical or cross-domain query execution. |
| `Nexus\Reporting` | Report presentation and distribution package. | Report jobs, rendering through export managers, scheduled distribution, authorization, and retention. | Not the underlying query engine; use `QueryEngine`. Not raw export formatting only; use `Export`. | You need scheduled or user-facing reports. |
| `Nexus\Telemetry` | Monitoring, metrics, health, and alerting package. | Metrics, health checks, alert dispatch, exporters, and cardinality guards. | Not audit logs; use `AuditLogger` or `Audit`. | You need observability, metrics, health checks, or alerts. |
| `Nexus\MachineLearning` | Framework-agnostic ML platform package. | Feature extraction, feature versioning, inference, anomaly detection, provider HTTP contracts, and model-facing services. | Not procurement-specific ML features; use `ProcurementML`. Not reporting dashboards; use `Reporting`. | You need generic model inference, feature sets, or anomaly detection. |

## Geography, Routing, and Location

Use these packages for location math and route optimization instead of embedding geospatial logic inside business packages.

| Package | Brief Introduction | Main Purpose / Main Feature | What It Is Not | Use It When |
|---------|--------------------|-----------------------------|----------------|-------------|
| `Nexus\Geo` | Geographic and location services package. | Geocoding, distance, bearing, geofence, polygon simplification, and travel-time contracts. | Not vehicle route optimization; use `Routing`. | You need geospatial calculations or geocoding. |
| `Nexus\Routing` | Route optimization and VRP solver package. | Route optimization, distance/travel-time integration, constraint validation, and route caching. | Not general geocoding or geofence math; use `Geo`. | You need optimized delivery, service, or vehicle routes. |

## Party, Organization, and Master Data

These packages own reference and master data used across domains.

| Package | Brief Introduction | Main Purpose / Main Feature | What It Is Not | Use It When |
|---------|--------------------|-----------------------------|----------------|-------------|
| `Nexus\Party` | Universal party master data package. | Individuals, organizations, addresses, contact methods, party relationships, and repositories. | Not customer CRM pipeline; use `CRM`. Not vendors specifically; use `Vendor`. | You need canonical people or organization identity shared across domains. |
| `Nexus\Backoffice` | Company structure and organizational unit package. | Companies, offices, departments, staff organizational units, validation, persistence, and queries. | Not HR employee profile; use `EmployeeProfile`. Not tenant management; use `Tenant`. | You need internal company structure or departments. |
| `Nexus\Vendor` | Vendor master domain package. | Vendor entities, status transitions, persistence, query, and vendor repository contracts. | Not procurement transaction flow; use `Procurement` or `Sourcing`. | You need supplier/vendor master records. |
| `Nexus\Product` | Product catalog management package. | Product templates, variants, attributes, categories, SKU and barcode handling. | Not stock levels; use `Inventory`. Not vendor sourcing; use `Sourcing` and `Vendor`. | You need product master catalog data. |
| `Nexus\Uom` | Unit of Measurement package. | Units, dimensions, unit systems, conversion rules, and UoM repository contracts. | Not pricing or inventory valuation; use `Sales`, `Inventory`, or `CostAccounting`. | You need unit definitions or conversions. |

## Finance and Accounting

These packages own accounting, financial reporting, budget, cost, cash, tax, treasury, and asset capabilities.

| Package | Brief Introduction | Main Purpose / Main Feature | What It Is Not | Use It When |
|---------|--------------------|-----------------------------|----------------|-------------|
| `Nexus\Accounting` | Accounting facade/domain package for statements, close, consolidation, and variance interfaces. | Financial statement generation, accounting manager contracts, period close, consolidation, and variance interfaces. | Not the atomic general ledger source of truth; use `GeneralLedger`. Not standalone tax; use `Tax`. | You need higher-level accounting coordination or statements. |
| `Nexus\ChartOfAccount` | Chart of accounts management package. | Account definitions, account manager, query, and persistence contracts. | Not ledger posting; use `GeneralLedger` and `JournalEntry`. | You need maintain account master structures. |
| `Nexus\GeneralLedger` | Central general ledger transaction hub. | Double-entry ledger account management, transaction recording, balance calculation, and trial balance generation. | Not journal entry authoring UI/workflow; use `JournalEntry`. Not financial statement presentation; use `FinancialStatements` or `Accounting`. | You need post or query authoritative financial entries. |
| `Nexus\JournalEntry` | Journal entry management package. | Journal entries, lines, managers, currency conversion hooks, and ledger query integration. | Not the ledger balance source of truth; use `GeneralLedger`. | You need create, validate, or manage journal entries before posting. |
| `Nexus\Period` | Fiscal period management package. | Period manager, repository, authorization, cache, and audit hooks. | Not full close readiness and reopening workflow; use `AccountPeriodClose`. | You need fiscal/calendar period definitions and status. |
| `Nexus\AccountPeriodClose` | Period close validation and reopening package. | Close readiness checks, closing entry generation, close sequences, and reopen validation. | Not generic fiscal period setup; use `Period`. Not ledger posting itself; use `GeneralLedger` and `JournalEntry`. | You need month-end or year-end close controls. |
| `Nexus\FinancialStatements` | Financial statement structures and validation package. | Statement builders, templates, compliance templates, validators, data providers, and export adapters. | Not raw ledger postings; use `GeneralLedger`. Not ratio calculation; use `FinancialRatios`. | You need balance sheet, income statement, cash flow, or template validation. |
| `Nexus\AccountConsolidation` | Multi-entity consolidation package. | Ownership resolution, currency translation, intercompany eliminations, goodwill, and NCI calculations. | Not single-entity ledger posting; use `GeneralLedger`. | You need group consolidation across entities. |
| `Nexus\AccountVarianceAnalysis` | Financial account variance and trend analysis package. | Variance calculation, attribution, significance evaluation, trend analysis, and rolling forecast comparison. | Not financial ratio calculation; use `FinancialRatios`. Not generic BI queries; use `QueryEngine`. | You need analyze actual-vs-budget or forecast variances. |
| `Nexus\FinancialRatios` | Financial ratio calculation package. | Liquidity, profitability, leverage, efficiency, market, and cash-flow ratio calculations. | Not financial statement building; use `FinancialStatements`. | You need compute financial ratios from accounting data. |
| `Nexus\Budget` | Budget management and control plane. | Budget entities, approvals, forecasts, analytics repositories, and budget manager contracts. | Not project-specific milestone billing; use `Milestone` plus orchestrators. Not ledger postings; use `GeneralLedger`. | You need planned vs actual budget management or approval. |
| `Nexus\CostAccounting` | Cost accounting and allocation package. | Cost centers, cost pools, product costing, activity rates, cost allocations, and audit. | Not general ledger source of truth; use `GeneralLedger`. Not asset depreciation; use `FixedAssetDepreciation`. | You need allocate or analyze operational/product costs. |
| `Nexus\Assets` | Fixed asset management package. | Asset records, categories, verification, depreciation records, maintenance analysis, and asset repositories. | Not depreciation calculation engine itself; use `FixedAssetDepreciation`. | You need manage fixed asset lifecycle and records. |
| `Nexus\FixedAssetDepreciation` | Fixed asset depreciation calculation engine. | Depreciation methods, depreciation manager, revaluation, GL integration providers, and depreciation query/persist contracts. | Not asset master management; use `Assets`. | You need calculate, persist, or query depreciation. |
| `Nexus\CashManagement` | Bank account, statement, reconciliation, and liquidity package. | Bank account records, statement imports, bank transactions, cash-flow forecasts, reconciliation, and liquidity analysis. | Not treasury optimization or cash concentration; use `Treasury`. Not payment execution; use `Payment` and payment extensions. | You need reconcile bank statements or forecast cash. |
| `Nexus\Currency` | ISO 4217 currency and exchange-rate engine. | Currency definitions, currency repositories, exchange-rate providers, rate storage, and currency manager contracts. | Not payment execution; use `Payment`. Not ledger posting; use `GeneralLedger`. | You need currency metadata, exchange rates, or currency conversion support. |
| `Nexus\Treasury` | Treasury management package. | Liquidity pools, cash concentration, working capital optimization, authorization matrix, and treasury analytics. | Not daily bank reconciliation; use `CashManagement`. Not payment rails; use `PaymentRails`. | You need treasury-level liquidity and cash concentration. |
| `Nexus\Tax` | Multi-jurisdiction tax calculation engine. | Tax rates, jurisdiction resolution, nexus, exemptions, reverse charge, place-of-supply, GL integration, and reporting hooks. | Not statutory report generation; use `Statutory`. Not payroll statutory deductions; use `PayrollMysStatutory`. | You need calculate or validate transaction tax. |
| `Nexus\Statutory` | Statutory reporting package. | Country-specific report metadata, schema validation, taxonomy report generation, payroll statutory hooks, and statutory report repositories. | Not tax amount calculation; use `Tax`. Not payroll calculation; use `Payroll` and `PayrollMysStatutory`. | You need produce regulatory or statutory reports. |

## Payments, Banking, and Settlement

These packages own payment domain entities, payment extensions, bank integrations, and AR/AP settlement boundaries.

| Package | Brief Introduction | Main Purpose / Main Feature | What It Is Not | Use It When |
|---------|--------------------|-----------------------------|----------------|-------------|
| `Nexus\Payment` | Core payment domain package. | Payment entities, instruments, allocations, disbursements, disbursement limits, and event-driven integration for AR/AP/payroll. | Not a specific bank, card, wallet, or rail integration; use the payment extension packages. | You need model payment lifecycle, allocation, or disbursement in a channel-neutral way. |
| `Nexus\PaymentBank` | Direct bank integration extension. | Open banking, bank account verification, bank connections, statements, and account data providers. | Not generic cash reconciliation; use `CashManagement`. Not card gateway processing; use `PaymentGateway`. | You need bank connectivity or account verification. |
| `Nexus\PaymentGateway` | Online payment processor extension. | Gateway registry, selection, health, credentials, circuit breaker, and Stripe/PayPal/Square-style gateway contracts. | Not payment domain allocation; use `Payment`. Not ACH/wire/check rails; use `PaymentRails`. | You need card or online processor integration. |
| `Nexus\PaymentRails` | Payment network rails extension. | ACH, wire, check, RTGS, NACHA formatting, rail configuration, rail selection, and rail transaction persistence. | Not card gateway integration; use `PaymentGateway`. | You need move money through payment networks such as ACH, wire, or checks. |
| `Nexus\PaymentRecurring` | Recurring and subscription payment extension. | Subscription billing, usage-based billing, and recurring payment management. | Not one-off payment core; use `Payment`. Not gateway execution; use `PaymentGateway`. | You need recurring charge schedules or subscription billing behavior. |
| `Nexus\PaymentWallet` | Digital wallet and mobile payment extension. | Wallet, mobile payment, and BNPL-style integration surface. | Not core payment allocation; use `Payment`. Not bank account integration; use `PaymentBank`. | You need wallet or alternative payment method integrations. |
| `Nexus\Payable` | Accounts Payable package. | Payables, goods-received matching, matching tolerance, payment allocation, and payable manager contracts. | Not outbound payment execution; use `Payment`. Not procurement PO lifecycle; use `Procurement`. | You need vendor invoice/AP matching and payable management. |
| `Nexus\Receivable` | Accounts Receivable package. | Customer invoices, invoice lines, aging, credit limits, dunning, exchange-rate integration, and payment allocation strategies. | Not payment execution; use `Payment`. Not sales quotation/order lifecycle; use `Sales`. | You need customer invoicing, collections, or AR aging. |

## Procurement, Sourcing, Inventory, and Supply Chain

Use these packages for buy-side operations, supplier sourcing, inventory, warehouses, manufacturing, quality, and supply chain ML.

| Package | Brief Introduction | Main Purpose / Main Feature | What It Is Not | Use It When |
|---------|--------------------|-----------------------------|----------------|-------------|
| `Nexus\Procurement` | Procurement management package. | Purchase requisitions, purchase orders, goods receipts, 3-way matching, product-vendor data, and procurement manager contracts. | Not competitive RFQ/award lifecycle; use `Sourcing`. Not AP settlement; use `Payable`. | You need purchase requisition, PO, receipt, or matching workflows. |
| `Nexus\ProcurementML` | Procurement-specific ML feature package. | Feature extraction for vendor fraud, pricing anomalies, budget overruns, delivery quality, and procurement analytics repositories. | Not generic ML platform; use `MachineLearning`. Not procurement transaction management; use `Procurement`. | You need ML features or analytics specific to procurement. |
| `Nexus\Sourcing` | RFQ, quotation, award, and sourcing lifecycle package. | RFQ status transition policy, quotations, awards, RFQ bulk actions, lifecycle results, normalization lines, and sourcing events. | Not PO execution or goods receipt; use `Procurement`. Not vendor master data; use `Vendor`. | You need request-for-quotation, vendor quotation, or award lifecycle rules. |
| `Nexus\SourcingScoring` | Vendor evaluation and sourcing scoring package. | Scoring engine for sourcing analysis and vendor evaluation. | Not RFQ lifecycle persistence; use `Sourcing`. Not generic policy evaluation; use `PolicyEngine`. | You need score or rank sourcing responses. |
| `Nexus\Inventory` | Inventory and stock management package. | Stock levels, movements, reservations, lots, serials, transfers, and inventory analytics repositories. | Not warehouse bin/picking optimization; use `Warehouse`. Not product master data; use `Product`. | You need stock quantity, reservation, lot, serial, or transfer logic. |
| `Nexus\Warehouse` | Warehouse management package. | Warehouses, bin locations, picking optimization, route optimization results, and warehouse query/manager contracts. | Not inventory valuation or stock ledger; use `Inventory`. Not vehicle routing outside the warehouse; use `Routing`. | You need warehouse locations, picking, or bin-level operations. |
| `Nexus\Manufacturing` | Manufacturing and production management package. | BOMs, routings, work orders, MRP, CRP, capacity planning, and change orders. | Not warehouse picking or inventory stock source of truth; use `Warehouse` and `Inventory`. | You need production planning or manufacturing execution rules. |
| `Nexus\QualityControl` | Inspection and quality decision package. | Inspection contracts, inspection manager, inspection status, and inspection decisions. | Not manufacturing work order management; use `Manufacturing`. Not product master data; use `Product`. | You need quality inspections and pass/fail/hold decisions. |

## Sales, CRM, and Loyalty

These packages cover customer pipeline, quotation-to-order, and loyalty programs.

| Package | Brief Introduction | Main Purpose / Main Feature | What It Is Not | Use It When |
|---------|--------------------|-----------------------------|----------------|-------------|
| `Nexus\CRM` | Customer relationship management package. | Leads, opportunities, activities, pipeline records, and CRM query/persistence contracts. | Not sales order or quotation execution; use `Sales`. Not party master data; use `Party`. | You need manage prospects, opportunities, and sales activities. |
| `Nexus\Sales` | Sales quotation-to-order lifecycle package. | Quotations, price lists, price tiers, credit checks, invoice manager integration, and sales repositories. | Not customer pipeline CRM; use `CRM`. Not receivable collections; use `Receivable`. | You need quote, order, pricing, or sales lifecycle rules. |
| `Nexus\Loyalty` | Loyalty point and tier management package. | Point calculation, tier management, redemption validation, benefits, adjustments, idempotency, and accounting integration hooks. | Not CRM pipeline or sales pricing; use `CRM` and `Sales`. | You need earn, redeem, expire, or adjust loyalty points and tiers. |

## Project, Delivery, and Field Operations

These packages own project atoms, tasking, time, resources, milestones, and field service operations. The deprecated `Projects` directory is documented so agents do not resurrect it.

| Package | Brief Introduction | Main Purpose / Main Feature | What It Is Not | Use It When |
|---------|--------------------|-----------------------------|----------------|-------------|
| `Nexus\Project` | Atomic project entity and lifecycle package. | Project metadata, project manager assignment, status rules, completion rules, client visibility, query, and persistence contracts. | Not task dependencies, time entries, allocations, or milestone billing; use `Task`, `TimeTracking`, `ResourceAllocation`, and `Milestone`. | You need core project identity and lifecycle rules. |
| `packages/Projects` | Deprecated split marker for the former project-management package. | Documents that project management was split into `Project`, `Task`, `TimeTracking`, `ResourceAllocation`, `Milestone`, `Budget`, `Receivable`, and Layer 2 orchestration. | Not an active implementation package. Do not add code here. | You need to understand the historical split or redirect old references. |
| `Nexus\Task` | Reusable task and dependency package. | Task entity, assignments, dependencies, cycle detection, and Gantt-support schedule calculation. | Not project lifecycle or time approval; use `Project` and `TimeTracking`. | You need task management reusable across projects, support, campaigns, or HR goals. |
| `Nexus\Milestone` | Milestone, deliverable, approval, and billing rule package. | Milestone entities, approvals, deliverables, budget reservation interface, and revenue/billing rules. | Not full project lifecycle; use `Project`. Not accounts receivable invoice execution; use `Receivable`. | You need project milestone rules or billing gate checks. |
| `Nexus\ResourceAllocation` | Capacity allocation package. | Allocation percentages or hours per user/period, overallocation checks, double-booking prevention, and allocation query/persist contracts. | Not shift scheduling; use `Shift`. Not time entry approval; use `TimeTracking`. | You need capacity planning or resource booking rules. |
| `Nexus\TimeTracking` | Time entry and timesheet package. | Timesheet entry, hours validation, approval workflow, immutability, and timesheet query/persist contracts. | Not attendance clock-in/clock-out; use `Attendance`. Not payroll calculation; use `Payroll`. | You need project, support, or internal-work time logging. |
| `Nexus\FieldService` | Field service work order and dispatch package. | Work order lifecycle, technician dispatch, SLA/service contracts, mobile job execution, GPS/location, signatures, and checklist/document hooks. | Not generic project task management; use `Task` and `Project`. Not route optimization engine; use `Routing`. | You need technician work orders or mobile field execution. |

## Workforce and HR

These packages own employee records, attendance, leave, shifts, payroll, hiring, onboarding, training, performance, and disciplinary processes.

| Package | Brief Introduction | Main Purpose / Main Feature | What It Is Not | Use It When |
|---------|--------------------|-----------------------------|----------------|-------------|
| `Nexus\EmployeeProfile` | Employee profile domain package. | Employee repositories and employee profile business logic. | Not user authentication; use `Identity`. Not organizational departments; use `Backoffice`. | You need employee master/profile records. |
| `Nexus\Recruitment` | Talent acquisition package. | Job postings, applicants, interviews, and hiring decision engine. | Not onboarding after hire; use `Onboarding`. Not employee profile management; use `EmployeeProfile`. | You need applicant tracking or hiring workflows. |
| `Nexus\Onboarding` | New employee onboarding package. | Onboarding checklists, onboarding tasks, probation reviews, and milestone tracking. | Not recruitment pipeline; use `Recruitment`. Not training catalog; use `Training`. | You need pre-hire-to-probation onboarding workflows. |
| `Nexus\AttendanceManagement` (`packages/Attendance`) | Attendance domain package. | Attendance records, work schedules, attendance queries/persistence, and attendance manager logic. | Not timesheets for project work; use `TimeTracking`. Not leave balances; use `Leave`. | You need attendance capture or work schedule attendance rules. |
| `Nexus\LeaveManagement` (`packages/Leave`) | Leave management domain package. | Leave policies, leave repositories, balances, accrual strategies, country law repositories, and leave calculation. | Not attendance records; use `Attendance`. Not shift definitions; use `Shift`. | You need annual leave, sick leave, accrual, or entitlement rules. |
| `Nexus\ShiftManagement` (`packages/Shift`) | Shift management domain package. | Shift repository and shift business rules. | Not attendance recording; use `Attendance`. Not resource allocation percentages; use `ResourceAllocation`. | You need model work shifts or rosters. |
| `Nexus\PayrollCore` | Payroll core domain package. | Pure payroll core logic and payslip repository contracts. | Not full country-agnostic payroll component engine; use `Payroll`. Not Malaysia statutory deductions; use `PayrollMysStatutory`. | You need core payroll/payslip primitives. |
| `Nexus\Payroll` | Country-agnostic payroll engine. | Payroll components, employee components, deductions, payroll queries/persistence, and component repositories. | Not Malaysia EPF/SOCSO/EIS/PCB formulas; use `PayrollMysStatutory`. Not payment disbursement; use `Payment`. | You need run payroll calculation independent of country statutory formulas. |
| `Nexus\PayrollMysStatutory` | Malaysia statutory payroll package. | EPF, SOCSO, EIS, PCB statutory calculation payloads and deduction results. | Not generic payroll engine; use `Payroll`. Not statutory reporting output; use `Statutory`. | You need Malaysia payroll statutory deductions. |
| `Nexus\PerformanceReview` | Employee performance management package. | Appraisal cycles, KPIs, competency scoring, templates, review repositories, and rating calculations. | Not training management; use `Training`. Not disciplinary case management; use `Disciplinary`. | You need appraisals, KPIs, or performance scoring. |
| `Nexus\Training` | Employee development and certification package. | Courses, enrollments, trainers, policies, and certification tracking. | Not onboarding tasks; use `Onboarding`. Not performance reviews; use `PerformanceReview`. | You need manage employee training and certifications. |
| `Nexus\Disciplinary` | Employee misconduct and case management package. | Disciplinary cases, policies, evidence repositories, sanctions, and sanction decision engine. | Not compliance SOD rules; use `Compliance`. Not performance reviews; use `PerformanceReview`. | You need misconduct investigations, warnings, or sanctions. |

## ESG and Sustainability

These packages own sustainability data, ESG scoring, and ESG regulatory mappings.

| Package | Brief Introduction | Main Purpose / Main Feature | What It Is Not | Use It When |
|---------|--------------------|-----------------------------|----------------|-------------|
| `Nexus\SustainabilityData` | Raw sustainability event lakehouse package. | Sustainability event contracts, source metadata, and event sampling. | Not ESG scoring or regulatory mapping; use `ESG` and `ESGRegulatory`. | You need ingest or model raw sustainability events. |
| `Nexus\ESG` | Sustainability truth and scoring package. | Carbon normalization and ESG scoring engine contracts. | Not raw event ingestion; use `SustainabilityData`. Not regulatory framework mapping; use `ESGRegulatory`. | You need normalize carbon data or compute ESG scores. |
| `Nexus\ESGRegulatory` | ESG regulatory framework mapping package. | Regulatory registry, framework mapper, and standard mappings. | Not ESG scoring or raw events; use `ESG` and `SustainabilityData`. | You need map ESG metrics to reporting frameworks or standards. |

## Blockchain

| Package | Brief Introduction | Main Purpose / Main Feature | What It Is Not | Use It When |
|---------|--------------------|-----------------------------|----------------|-------------|
| `Nexus\Blockchain` | Framework-agnostic blockchain implementation package. | Blockchain ledger integration and smart-contract coordination surface for the Nexus ecosystem. | Not the accounting general ledger or immutable audit engine; use `GeneralLedger` and `Audit`. | You need blockchain-specific anchoring or smart-contract integration. |

## Common Composition Recipes

### Webhook-Style Integrations

Do not create a monolithic `Nexus\Webhook` package. Compose existing packages:

| Concern | Package |
|---------|---------|
| Tenant scope and signing secret resolution | `Tenant` plus Layer 3 configuration/persistence |
| Signature verification | `Crypto` |
| Duplicate inbound delivery handling | `Idempotency` |
| Durable outbound delivery after commit | `Outbox` |
| Optional domain event history | `EventStream` |
| Audit evidence | `Audit` or `AuditLogger` |
| Metrics and retries visibility | `Telemetry` |
| Actual HTTP controllers, HTTP clients, workers | Layer 3 adapters |

### Approval Workflows

Use `Workflow` for stateful workflow execution, `PolicyEngine` for rule decisions, `Identity` for actors and permissions, `Tenant` for scope, `Notifier` for messages, and `AuditLogger` or `Audit` for traceability.

### Project Management

Use `Project` only for project identity and lifecycle. Use `Task`, `TimeTracking`, `ResourceAllocation`, and `Milestone` for the atomic project capabilities. Cross-package dashboards and workflows belong in a Layer 2 orchestrator.

## Orchestrators Are Not Packages

Layer 2 orchestrators coordinate Layer 1 packages and own workflow-level application contracts. Do not move orchestration concerns into the packages listed above.

| Orchestrator | Typical Packages Coordinated |
|--------------|------------------------------|
| `ProcurementOperations` | `Procurement`, `Inventory`, `Payable`, `Tax`, `Currency`, `Vendor` |
| `AccountingOperations` | `Accounting`, `GeneralLedger`, `JournalEntry`, `Period`, `Tax` |
| `HumanResourceOperations` | `EmployeeProfile`, `Attendance`, `Leave`, `Payroll`, `Identity` |
| `QuotationIntelligence` | `Sourcing`, `Procurement`, `MachineLearning`, `Currency`, `Document` |
| `SupplyChainOperations` | `Inventory`, `Manufacturing`, `Warehouse`, `Procurement`, `QualityControl` |
| `ProjectManagementOperations` | `Project`, `Task`, `TimeTracking`, `ResourceAllocation`, `Milestone`, `Budget`, `Receivable`, `Notifier` |
| `InsightOperations` | `QueryEngine`, `Reporting`, `Notifier`, `Export` |
| `ApprovalOperations` | `Workflow`, `PolicyEngine`, `Identity`, `Tenant`, `Common` |
| `DataExchangeOperations` | `Import`, `Export`, `Storage`, `Document`, `DataProcessor` |
| `ConnectivityOperations` | `Connector`, `Outbox`, `Idempotency`, `Crypto`, `Telemetry` |

## Quick Violation Map

| If You Are About To Build... | Use This Instead |
|------------------------------|------------------|
| Custom tenant context | `Tenant` |
| Custom role/permission checks | `Identity` plus `PolicyEngine` |
| Custom sequence numbers | `Sequencing` |
| Custom command retry deduplication | `Idempotency` |
| Custom durable post-commit delivery table | `Outbox` |
| Custom audit log | `AuditLogger` or `Audit` depending on tamper-evidence needs |
| Custom encryption/signature helper | `Crypto` |
| Custom file storage interface | `Storage` |
| Custom import/export engine | `Import` or `Export` |
| Custom notification system | `Notifier` |
| Custom metrics/health checks | `Telemetry` |
| Custom payment processor abstraction | `PaymentGateway`, coordinated through `Payment` |
| Custom KYC/AML/sanctions screening | `KycVerification`, `AmlCompliance`, `Sanctions` |
| Custom RFQ scoring | `SourcingScoring` |
| Custom project mega-package | `Project`, `Task`, `TimeTracking`, `ResourceAllocation`, and `Milestone` |

## Package Count

This reference covers **104** first-party package directories under `packages/` as of 2026-04-22, including the deprecated `packages/Projects` marker directory.

## Changelog

### 2026-04-22

- Rebuilt the package reference around the live `packages/` tree.
- Added entries for all 104 first-party package directories.
- Reordered categories so foundational platform packages appear first, followed by security/privacy and then business domains.
- Added the required per-package guidance columns: brief introduction, main purpose/main feature, what it is not, and use it when.
- Moved changelog content to the bottom of the file.

### 2026-03-24

- Added webhook-style integration composition guidance: inbound/outbound flows should compose `Idempotency`, `Outbox`, `Crypto`, `Tenant`, `EventStream`, `AuditLogger`, and `Telemetry`; no standalone `Nexus\Webhook` package is required for these mechanisms.

### 2026-03-22

- Added `Nexus\Outbox` as a Layer 1 transactional outbox model with publish lifecycle, tenant-scoped dedup keys, and claim tokens.

### 2026-03-21

- Added `Nexus\Idempotency` as a Layer 1 package for tenant-scoped command idempotency, fingerprint conflict detection, and replay-safe result storage.

### 2026-03-20

- Added `Nexus\PolicyEngine` as a Layer 1 package for deterministic policy evaluation across authorization, workflow, and threshold decisions.

### 2026-03-15

- Added project and delivery Layer 1 packages: `Task`, `TimeTracking`, `Project`, `ResourceAllocation`, and `Milestone`.
- Clarified that project workflows are coordinated by Layer 2 orchestration.

### 2026-03-01

- Added `FieldService`, `Loyalty`, `GeneralLedger`, and `Blockchain`.
- Documented related orchestrator growth in the previous reference version.

### 2026-02-26

- Renamed `Analytics` to `QueryEngine` and `Monitoring` to `Telemetry`.
- Added references to `DataExchangeOperations`, `InsightOperations`, `IntelligenceOperations`, and `ConnectivityOperations` orchestration responsibilities.
