# PaymentRails Gap & Enhancement Backlog

- [ ] Align capability model: consolidate on a single `RailCapabilities` shape (Money-based) and update `AbstractPaymentRail`, `RailSelector`, and all rails (ACH/Wire/RTGS/Check/VirtualCard) to stop referencing non-existent `minimumAmountCents`/`maximumAmountCents` fields and mixed named args.
- [ ] Enforce Nexus coding guidelines on rail/services: convert rails and shared services (AbstractPaymentRail, AchRail, WireRail, RtgsRail, VirtualCardRail, CheckRail, RailSelector, RailValidator, NachaFormatter) to `final readonly` with interface-only dependencies and remove mutable state.
- [ ] Replace float math on money (e.g., amount*100) with `Money` operations to avoid precision issues and ensure currency checks precede arithmetic.
- [ ] Flesh out Validators layer (currently empty) or remove the folder; extract reusable Swift/IBAN/routing/account/SecCode validators to keep `RailValidator` slim and testable.
- [ ] Implement sanctions screening via `Nexus\Sanctions` (interface injection) instead of the placeholder blocklist, and add telemetry/audit hooks via Monitoring/AuditLogger where appropriate.
- [ ] Provide in-memory test doubles for `RailConfigurationInterface`, `RailTransactionPersistInterface`, and `RailTransactionQueryInterface` so rails can be integration-tested without adapters.
- [ ] Expand PHPUnit coverage (>85% target): add suites for each rail (ACH batch creation/NACHA roundtrip, wire instruction validation, RTGS high-value enforcement, check issuance, virtual card metadata), `RailSelector` scoring/eligibility, DTO validation, and `NachaFormatter` parse/generate flows.
- [ ] Reconcile PHPUnit version drift (`composer.json` dev requires ^10 but repo root uses ^11); standardize on one version and update `phpunit.xml.dist` accordingly.
- [ ] Document required bindings and usage in README/REQUIREMENTS: configuration expectations (cutoffs, same-day flags, currency lists), persistence/query contract responsibilities, and how consumers should wire sanctions/monitoring adapters.
