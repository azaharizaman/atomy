# Requirements: Receivable

Total Requirements: 128

| Package Namespace | Requirements Type | Code | Requirement Statements | Files/Folders | Status | Notes on Status | Date Last Updated |
|-------------------|-------------------|------|------------------------|---------------|--------|-----------------|-------------------|
| `Nexus\Receivable` | Business Requirements | BUS-ACC-0134 | Customer payments MUST be allocated to specific invoices for proper aging tracking |  |  |  |  |
| `Nexus\Receivable` | Business Requirements | BUS-ACC-0134 | Customer payments MUST be allocated to specific invoices for proper aging tracking |  |  |  |  |
| `Nexus\Receivable` | Business Requirements | BUS-ACC-0134 | Customer payments MUST be allocated to specific invoices for proper aging tracking |  |  |  |  |
| `Nexus\Receivable` | Functional Requirement | FUN-ACC-2218 | Generate aging reports (AR and AP) |  |  |  |  |
| `Nexus\Receivable` | Performance Requirement | PER-ACC-0362 | Aging report generation (30/60/90 days) < 2 seconds |  |  |  |  |
| `Nexus\Receivable` | Architechtural Requirement | ARC-REC-4001 | Package MUST be framework-agnostic with no Laravel dependencies in core services |  |  |  |  |
| `Nexus\Receivable` | Architechtural Requirement | ARC-REC-4002 | All data structures defined via interfaces (InvoiceInterface, ReceiptInterface, CreditMemoInterface) |  |  |  |  |
| `Nexus\Receivable` | Architechtural Requirement | ARC-REC-4003 | All persistence operations via repository interfaces (InvoiceRepositoryInterface, ReceiptRepositoryInterface) |  |  |  |  |
| `Nexus\Receivable` | Architechtural Requirement | ARC-REC-4004 | Business logic concentrated in service layer (ReceivableManager, CollectionsService) |  |  |  |  |
| `Nexus\Receivable` | Architechtural Requirement | ARC-REC-4005 | All database migrations in application layer (apps/Atomy) |  |  |  |  |
| `Nexus\Receivable` | Architechtural Requirement | ARC-REC-4006 | All Eloquent models in application layer implementing package interfaces |  |  |  |  |
| `Nexus\Receivable` | Architechtural Requirement | ARC-REC-4007 | Repository implementations in application layer |  |  |  |  |
| `Nexus\Receivable` | Architechtural Requirement | ARC-REC-4008 | Package composer.json MUST NOT depend on laravel/framework |  |  |  |  |
| `Nexus\Receivable` | Architechtural Requirement | ARC-REC-4009 | MUST inject FinanceInterface from Nexus\Finance for journal entry posting |  |  |  |  |
| `Nexus\Receivable` | Architechtural Requirement | ARC-REC-4010 | MUST inject CrmInterface from Nexus\Crm for customer data |  |  |  |  |
| `Nexus\Receivable` | Architechtural Requirement | ARC-REC-4011 | MUST inject TaxCalculatorInterface for tax calculations |  |  |  |  |
| `Nexus\Receivable` | Architechtural Requirement | ARC-REC-4012 | MUST inject NotifierInterface from Nexus\Notifier for invoice delivery |  |  |  |  |
| `Nexus\Receivable` | Architechtural Requirement | ARC-REC-4013 | MUST inject ConnectorInterface from Nexus\Connector for payment gateway integration |  |  |  |  |
| `Nexus\Receivable` | Architechtural Requirement | ARC-REC-4014 | Use Value Objects for InvoiceAmount, PaymentTerm, InvoiceNumber |  |  |  |  |
| `Nexus\Receivable` | Architechtural Requirement | ARC-REC-4015 | Separate Core/ folder for internal engine (AgingCalculator, CollectionScheduler) |  |  |  |  |
| `Nexus\Receivable` | Architechtural Requirement | ARC-REC-4016 | Define internal contracts in Core/Contracts/ (InvoiceGeneratorInterface, AllocationEngineInterface) |  |  |  |  |
| `Nexus\Receivable` | Business Requirements | BUS-REC-4101 | Invoices MUST be linked to sales order or contract for tracking |  |  |  |  |
| `Nexus\Receivable` | Business Requirements | BUS-REC-4102 | Revenue recognition follows delivery/service completion date |  |  |  |  |
| `Nexus\Receivable` | Business Requirements | BUS-REC-4103 | Partial payments allocated to specific invoice line items |  |  |  |  |
| `Nexus\Receivable` | Business Requirements | BUS-REC-4104 | Payment terms MUST be validated against customer master data |  |  |  |  |
| `Nexus\Receivable` | Business Requirements | BUS-REC-4105 | Credit limit checks required before invoice creation |  |  |  |  |
| `Nexus\Receivable` | Business Requirements | BUS-REC-4106 | Overdue invoices automatically flagged for collections |  |  |  |  |
| `Nexus\Receivable` | Business Requirements | BUS-REC-4107 | Write-off authorization required for bad debt above threshold |  |  |  |  |
| `Nexus\Receivable` | Business Requirements | BUS-REC-4108 | Credit memos require reason code and approval |  |  |  |  |
| `Nexus\Receivable` | Business Requirements | BUS-REC-4109 | Cash receipts MUST be allocated to specific invoices for aging accuracy |  |  |  |  |
| `Nexus\Receivable` | Business Requirements | BUS-REC-4110 | Unallocated receipts held in suspense account |  |  |  |  |
| `Nexus\Receivable` | Business Requirements | BUS-REC-4111 | Multi-currency invoices recorded with exchange rate at invoice date |  |  |  |  |
| `Nexus\Receivable` | Business Requirements | BUS-REC-4112 | Support recurring invoices with configurable frequency |  |  |  |  |
| `Nexus\Receivable` | Business Requirements | BUS-REC-4113 | Invoice amendments tracked with version history |  |  |  |  |
| `Nexus\Receivable` | Business Requirements | BUS-REC-4114 | Support milestone billing for long-term projects |  |  |  |  |
| `Nexus\Receivable` | Business Requirements | BUS-REC-4115 | Small business: Basic invoicing, simple payment tracking (< 100 customers) |  |  |  |  |
| `Nexus\Receivable` | Business Requirements | BUS-REC-4116 | Medium business: Automated collections, credit management, recurring billing (100-1000 customers) |  |  |  |  |
| `Nexus\Receivable` | Business Requirements | BUS-REC-4117 | Large enterprise: Advanced collections, bad debt reserves, multi-entity consolidation (1000+ customers, 50K+ invoices/month) |  |  |  |  |
| `Nexus\Receivable` | Business Requirements | BUS-REC-4118 | Support customer statement generation and auto-delivery |  |  |  |  |
| `Nexus\Receivable` | Business Requirements | BUS-REC-4119 | Implement dunning process with escalating reminders |  |  |  |  |
| `Nexus\Receivable` | Business Requirements | BUS-REC-4120 | Support contract-based revenue recognition (ASC 606/IFRS 15 compliance) |  |  |  |  |
| `Nexus\Receivable` | Functional Requirement | FUN-REC-4201 | Create and manage customer invoices with line item detail |  |  |  |  |
| `Nexus\Receivable` | Functional Requirement | FUN-REC-4202 | Support invoice status lifecycle: Draft, Sent, Partially Paid, Paid, Overdue, Written Off, Cancelled |  |  |  |  |
| `Nexus\Receivable` | Functional Requirement | FUN-REC-4203 | Post invoices to GL: Debit Accounts Receivable, Credit Sales Revenue |  |  |  |  |
| `Nexus\Receivable` | Functional Requirement | FUN-REC-4204 | Calculate and record tax liabilities (GST/VAT output tax) on invoices |  |  |  |  |
| `Nexus\Receivable` | Functional Requirement | FUN-REC-4205 | Generate invoice numbers using Nexus\Sequencing with pattern support |  |  |  |  |
| `Nexus\Receivable` | Functional Requirement | FUN-REC-4206 | Send invoices to customers via email with PDF attachment |  |  |  |  |
| `Nexus\Receivable` | Functional Requirement | FUN-REC-4207 | Record cash receipts with allocation to specific invoices |  |  |  |  |
| `Nexus\Receivable` | Functional Requirement | FUN-REC-4208 | Post cash receipts to GL: Debit Cash, Credit Accounts Receivable |  |  |  |  |
| `Nexus\Receivable` | Functional Requirement | FUN-REC-4209 | Support partial payment allocation with remaining balance tracking |  |  |  |  |
| `Nexus\Receivable` | Functional Requirement | FUN-REC-4210 | Apply payment discounts automatically based on terms |  |  |  |  |
| `Nexus\Receivable` | Functional Requirement | FUN-REC-4211 | Generate and apply customer credit memos with reason tracking |  |  |  |  |
| `Nexus\Receivable` | Functional Requirement | FUN-REC-4212 | Process refunds with GL posting and payment reversal |  |  |  |  |
| `Nexus\Receivable` | Functional Requirement | FUN-REC-4213 | Generate aging reports (30/60/90/120+ days) by customer |  |  |  |  |
| `Nexus\Receivable` | Functional Requirement | FUN-REC-4214 | Calculate bad debt reserves based on aging buckets |  |  |  |  |
| `Nexus\Receivable` | Functional Requirement | FUN-REC-4215 | Generate cash forecast based on expected collection dates |  |  |  |  |
| `Nexus\Receivable` | Functional Requirement | FUN-REC-4216 | Support recurring invoice templates with auto-generation |  |  |  |  |
| `Nexus\Receivable` | Functional Requirement | FUN-REC-4217 | Implement automated collections workflow with dunning letters |  |  |  |  |
| `Nexus\Receivable` | Functional Requirement | FUN-REC-4218 | Assign collection tasks to agents based on workload |  |  |  |  |
| `Nexus\Receivable` | Functional Requirement | FUN-REC-4219 | Track collection activities and outcomes |  |  |  |  |
| `Nexus\Receivable` | Functional Requirement | FUN-REC-4220 | Generate customer statements with transaction detail |  |  |  |  |
| `Nexus\Receivable` | Functional Requirement | FUN-REC-4221 | Support customer self-service portal for invoice inquiry and payment |  |  |  |  |
| `Nexus\Receivable` | Functional Requirement | FUN-REC-4222 | Integrate with payment gateways via ConnectorInterface (credit card, ACH) |  |  |  |  |
| `Nexus\Receivable` | Functional Requirement | FUN-REC-4223 | Implement credit limit checks before invoice posting |  |  |  |  |
| `Nexus\Receivable` | Functional Requirement | FUN-REC-4224 | Support payment plans for overdue invoices with installment tracking |  |  |  |  |
| `Nexus\Receivable` | Functional Requirement | FUN-REC-4225 | Write off uncollectible invoices with approval workflow |  |  |  |  |
| `Nexus\Receivable` | Functional Requirement | FUN-REC-4226 | Recover previously written-off amounts with reinstatement process |  |  |  |  |
| `Nexus\Receivable` | Functional Requirement | FUN-REC-4227 | Support milestone billing with percentage completion tracking |  |  |  |  |
| `Nexus\Receivable` | Functional Requirement | FUN-REC-4228 | Small business: Simple invoicing, basic collections reminders |  |  |  |  |
| `Nexus\Receivable` | Functional Requirement | FUN-REC-4229 | Medium business: Automated dunning, credit management, recurring billing |  |  |  |  |
| `Nexus\Receivable` | Functional Requirement | FUN-REC-4230 | Large enterprise: Advanced collections, reserve calculations, customer portal, payment gateway integration |  |  |  |  |
| `Nexus\Receivable` | Functional Requirement | FUN-REC-4231 | Generate revenue recognition schedules for deferred revenue |  |  |  |  |
| `Nexus\Receivable` | Functional Requirement | FUN-REC-4232 | Support invoice factoring with third-party financing |  |  |  |  |
| `Nexus\Receivable` | Functional Requirement | FUN-REC-4233 | Implement lockbox processing for bulk payment imports |  |  |  |  |
| `Nexus\Receivable` | Performance Requirement | PER-REC-4301 | Invoice creation and validation < 200ms |  |  |  |  |
| `Nexus\Receivable` | Performance Requirement | PER-REC-4302 | Invoice posting to GL < 300ms |  |  |  |  |
| `Nexus\Receivable` | Performance Requirement | PER-REC-4303 | Cash receipt allocation < 500ms per payment |  |  |  |  |
| `Nexus\Receivable` | Performance Requirement | PER-REC-4304 | Aging report generation < 3s for 10K outstanding invoices |  |  |  |  |
| `Nexus\Receivable` | Performance Requirement | PER-REC-4305 | Customer statement generation < 2s per customer |  |  |  |  |
| `Nexus\Receivable` | Performance Requirement | PER-REC-4306 | Collections dashboard load < 1s for 1K overdue accounts |  |  |  |  |
| `Nexus\Receivable` | Performance Requirement | PER-REC-4307 | Bulk invoice generation: 1000 invoices per minute |  |  |  |  |
| `Nexus\Receivable` | Performance Requirement | PER-REC-4308 | Small business: Support 100 customers, 1K invoices/month with < 2s response |  |  |  |  |
| `Nexus\Receivable` | Performance Requirement | PER-REC-4309 | Medium business: Support 1K customers, 10K invoices/month with < 5s response |  |  |  |  |
| `Nexus\Receivable` | Performance Requirement | PER-REC-4310 | Large enterprise: Support 10K+ customers, 100K+ invoices/month with < 10s response using partitioning |  |  |  |  |
| `Nexus\Receivable` | Reliability Requirement | REL-REC-4401 | Invoice posting uses database transactions (ACID compliance) |  |  |  |  |
| `Nexus\Receivable` | Reliability Requirement | REL-REC-4402 | Failed payment allocations MUST rollback completely |  |  |  |  |
| `Nexus\Receivable` | Reliability Requirement | REL-REC-4403 | Concurrent payment allocation uses pessimistic locking |  |  |  |  |
| `Nexus\Receivable` | Reliability Requirement | REL-REC-4404 | Duplicate payment detection using idempotency keys |  |  |  |  |
| `Nexus\Receivable` | Reliability Requirement | REL-REC-4405 | Support payment reconciliation with bank statement import |  |  |  |  |
| `Nexus\Receivable` | Reliability Requirement | REL-REC-4406 | Invoice generation is idempotent (repeated calls yield same result) |  |  |  |  |
| `Nexus\Receivable` | Reliability Requirement | REL-REC-4407 | Maintain referential integrity between invoices, receipts, and GL entries |  |  |  |  |
| `Nexus\Receivable` | Reliability Requirement | REL-REC-4408 | Archive historical invoice data with retention policies |  |  |  |  |
| `Nexus\Receivable` | Reliability Requirement | REL-REC-4409 | Support point-in-time recovery for receivables data |  |  |  |  |
| `Nexus\Receivable` | Reliability Requirement | REL-REC-4410 | Retry transient failures for payment gateway integration |  |  |  |  |
| `Nexus\Receivable` | Security and Compliance Requirement | SEC-REC-4501 | Implement audit logging for all invoice and payment operations |  |  |  |  |
| `Nexus\Receivable` | Security and Compliance Requirement | SEC-REC-4502 | Enforce tenant isolation for all AR data |  |  |  |  |
| `Nexus\Receivable` | Security and Compliance Requirement | SEC-REC-4503 | Support RBAC for invoice creation, credit memos, write-offs |  |  |  |  |
| `Nexus\Receivable` | Security and Compliance Requirement | SEC-REC-4504 | Encrypt customer payment information at rest |  |  |  |  |
| `Nexus\Receivable` | Security and Compliance Requirement | SEC-REC-4505 | Mask credit card numbers in UI and logs (PCI-DSS compliance) |  |  |  |  |
| `Nexus\Receivable` | Security and Compliance Requirement | SEC-REC-4506 | Implement dual authorization for high-value write-offs |  |  |  |  |
| `Nexus\Receivable` | Security and Compliance Requirement | SEC-REC-4507 | Support SOX compliance with maker-checker for credit memos |  |  |  |  |
| `Nexus\Receivable` | Security and Compliance Requirement | SEC-REC-4508 | Log all failed payment gateway transactions |  |  |  |  |
| `Nexus\Receivable` | Security and Compliance Requirement | SEC-REC-4509 | Implement fraud detection for payment anomalies |  |  |  |  |
| `Nexus\Receivable` | Security and Compliance Requirement | SEC-REC-4510 | Support GDPR compliance with customer data retention policies |  |  |  |  |
| `Nexus\Receivable` | Security and Compliance Requirement | SEC-REC-4511 | Implement rate limiting for payment API endpoints |  |  |  |  |
| `Nexus\Receivable` | Security and Compliance Requirement | SEC-REC-4512 | Support PCI-DSS compliance for credit card processing |  |  |  |  |
| `Nexus\Receivable` | Security and Compliance Requirement | SEC-REC-4513 | Implement invoice tampering detection with digital signatures |  |  |  |  |
| `Nexus\Receivable` | Integration Requirement | INT-REC-4601 | MUST integrate with Nexus\Finance for journal entry posting via FinanceInterface |  |  |  |  |
| `Nexus\Receivable` | Integration Requirement | INT-REC-4602 | MUST integrate with Nexus\Crm for customer data via CrmInterface |  |  |  |  |
| `Nexus\Receivable` | Integration Requirement | INT-REC-4603 | MUST integrate with Nexus\Workflow for approval routing |  |  |  |  |
| `Nexus\Receivable` | Integration Requirement | INT-REC-4604 | MUST integrate with Nexus\Connector for payment gateway integration |  |  |  |  |
| `Nexus\Receivable` | Integration Requirement | INT-REC-4605 | MUST integrate with Nexus\Notifier for invoice delivery and collections reminders |  |  |  |  |
| `Nexus\Receivable` | Integration Requirement | INT-REC-4606 | MUST integrate with Nexus\AuditLogger for comprehensive audit trails |  |  |  |  |
| `Nexus\Receivable` | Integration Requirement | INT-REC-4607 | MUST integrate with Nexus\Sequencing for invoice number generation |  |  |  |  |
| `Nexus\Receivable` | Integration Requirement | INT-REC-4608 | Expose ReceivableInterface for consumption by reporting systems |  |  |  |  |
| `Nexus\Receivable` | Integration Requirement | INT-REC-4609 | Support webhook notifications for payment status changes |  |  |  |  |
| `Nexus\Receivable` | Integration Requirement | INT-REC-4610 | Provide REST API for customer invoice inquiry |  |  |  |  |
| `Nexus\Receivable` | Integration Requirement | INT-REC-4611 | Support GraphQL queries for flexible receivables data access |  |  |  |  |
| `Nexus\Receivable` | Integration Requirement | INT-REC-4612 | Optional integration with Nexus\EventStream for invoice lifecycle event sourcing (large enterprise only) |  |  |  |  |
| `Nexus\Receivable` | Integration Requirement | INT-REC-4613 | Publish invoice events to EventStream: InvoiceCreatedEvent, PaymentReceivedEvent, InvoiceWrittenOffEvent |  |  |  |  |
| `Nexus\Receivable` | Usability Requirement | USA-REC-4701 | Provide invoice entry wizard with real-time validation |  |  |  |  |
| `Nexus\Receivable` | Usability Requirement | USA-REC-4702 | Support invoice templates with pre-filled customer data |  |  |  |  |
| `Nexus\Receivable` | Usability Requirement | USA-REC-4703 | Display customer credit status during invoice creation |  |  |  |  |
| `Nexus\Receivable` | Usability Requirement | USA-REC-4704 | Provide collections dashboard with priority indicators |  |  |  |  |
| `Nexus\Receivable` | Usability Requirement | USA-REC-4705 | Support bulk payment allocation with auto-matching |  |  |  |  |
| `Nexus\Receivable` | Usability Requirement | USA-REC-4706 | Display aging visualization with color-coded buckets |  |  |  |  |
| `Nexus\Receivable` | Usability Requirement | USA-REC-4707 | Provide customer lookup with autocomplete |  |  |  |  |
| `Nexus\Receivable` | Usability Requirement | USA-REC-4708 | Show payment history timeline for each customer |  |  |  |  |
