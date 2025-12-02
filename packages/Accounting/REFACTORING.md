


┌─────────────────────────────────────────────────────────────────────────────┐
│                              Common                                    │
│  ┌──────────────────────────────────────────────────────────────────────┐   │
│  │ Nexus\Common\ValueObjects\Money, Nexus\Currency, Common(PercentageVO), ReportingPeriod, PeriodType         │   │
│  └──────────────────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────────────────┘
                                      ▲
                                      │ depends on
          ┌───────────────────────────┼───────────────────────────────┐
          │                           │                               │
          ▼                           ▼                               ▼
┌─────────────────┐       ┌─────────────────────┐       ┌─────────────────────┐
│ FinancialReport │       │    AccountConsolidation    │       │  AccountVarianceAnalysis   │
│     (Atomic)    │       │      (Atomic)       │       │      (Atomic)       │
│                 │       │                     │       │                     │
│ Statement       │       │ Consolidation       │       │ Variance            │
│ structures,     │       │ methods, IC         │       │ calculation,        │
│ templates,      │       │ elimination,        │       │ trend analysis,     |
│ formatting      │       │ NCI calculation     │       │ significance        │
└────────┬────────┘       └──────────┬──────────┘       └──────────┬──────────┘
         │                           │                             │
         │                           │                             │
         └───────────────────────────┼─────────────────────────────┘
                                     │
                                     ▼
          ┌─────────────────────────────────────────────────────────────┐
          │                  AccountingOperations                        │
          │                     (Orchestrator)                           │
          │                                                              │
          │  + Nexus\Finance (GL data, journal entries)                  │
          │  + Nexus\Period (period validation, locking)                 │
          │  + Nexus\Budget (budget data)                                │
          │  + Nexus\AuditLogger (audit trail)                           │
          │                                                              │
          │  Workflows:                                                  │
          │  - Statement Generation (pull GL → build statement)          │
          │  - Period Close (validate → close → closing entries)         │
          │  - Consolidation (fetch entities → consolidate)              │
          │  - Variance Reporting (actuals → budgets → calculate)        │
          └─────────────────────────────────────────────────────────────┘

packages/FinancialStatements (previously FinancialReport)/
├── composer.json
├── README.md
├── LICENSE
├── IMPLEMENTATION_SUMMARY.md
├── REQUIREMENTS.md
├── TEST_SUITE_SUMMARY.md
├── VALUATION_MATRIX.md
├── docs/
│   ├── getting-started.md
│   ├── api-reference.md
│   ├── integration-guide.md
│   └── examples/
├── src/
│   ├── Contracts/
│   │   ├── FinancialStatementInterface.php
│   │   ├── StatementBuilderInterface.php
│   │   ├── StatementTemplateInterface.php
│   │   ├── StatementFormatterInterface.php
│   │   ├── StatementValidatorInterface.php
│   │   └── ComplianceTemplateInterface.php
│   ├── Entities/
│   │   ├── BalanceSheet.php
│   │   ├── IncomeStatement.php
│   │   ├── CashFlowStatement.php
│   │   ├── TrialBalance.php
│   │   └── StatementSection.php
│   ├── ValueObjects/
│   │   ├── LineItem.php
│   │   ├── AccountBalance.php
│   │   ├── StatementMetadata.php
│   │   └── ComplianceStandard.php
│   ├── Enums/
│   │   ├── StatementType.php
│   │   ├── StatementFormat.php
│   │   ├── AccountCategory.php
│   │   ├── CashFlowMethod.php
│   │   └── ComplianceFramework.php
│   ├── Services/
│   │   ├── StatementBuilder.php
│   │   ├── StatementValidator.php
│   │   ├── SectionGrouper.php
│   │   └── ComplianceChecker.php
│   ├── Templates/
│   │   ├── GaapBalanceSheetTemplate.php
│   │   ├── GaapIncomeStatementTemplate.php
│   │   ├── IfrsBalanceSheetTemplate.php
│   │   └── IfrsIncomeStatementTemplate.php
│   └── Exceptions/
│       ├── StatementImbalanceException.php
│       ├── InvalidLineItemException.php
│       ├── InvalidSectionException.php
│       └── TemplateNotFoundException.php
└── tests/


packages/AccountConsolidation/
├── composer.json
├── README.md
├── LICENSE
├── IMPLEMENTATION_SUMMARY.md
├── REQUIREMENTS.md
├── TEST_SUITE_SUMMARY.md
├── VALUATION_MATRIX.md
├── docs/
│   ├── getting-started.md
│   ├── api-reference.md
│   ├── integration-guide.md
│   └── examples/
├── src/
│   ├── Contracts/
│   │   ├── ConsolidationEngineInterface.php
│   │   ├── EliminationRuleInterface.php
│   │   ├── CurrencyTranslatorInterface.php
│   │   ├── NciCalculatorInterface.php
│   │   └── OwnershipResolverInterface.php
│   ├── ValueObjects/
│   │   ├── ConsolidationEntity.php
│   │   ├── OwnershipStructure.php
│   │   ├── EliminationEntry.php
│   │   ├── TranslationAdjustment.php
│   │   ├── ConsolidationResult.php
│   │   └── IntercompanyBalance.php
│   ├── Enums/
│   │   ├── ConsolidationMethod.php
│   │   ├── EliminationType.php
│   │   ├── TranslationMethod.php
│   │   └── ControlType.php
│   ├── Services/
│   │   ├── ConsolidationCalculator.php
│   │   ├── IntercompanyEliminator.php
│   │   ├── CurrencyTranslator.php
│   │   ├── NciCalculator.php
│   │   └── OwnershipResolver.php
│   ├── Rules/
│   │   ├── IntercompanyRevenueRule.php
│   │   ├── IntercompanyReceivableRule.php
│   │   ├── IntercompanyDividendRule.php
│   │   ├── InvestmentEliminationRule.php
│   │   └── UnrealizedProfitRule.php
│   └── Exceptions/
│       ├── ConsolidationException.php
│       ├── CircularOwnershipException.php
│       ├── InvalidOwnershipException.php
│       ├── CurrencyTranslationException.php
│       └── EliminationException.php
└── tests/


packages/AccountVarianceAnalysis/
├── composer.json
├── README.md
├── LICENSE
├── IMPLEMENTATION_SUMMARY.md
├── REQUIREMENTS.md
├── TEST_SUITE_SUMMARY.md
├── VALUATION_MATRIX.md
├── docs/
│   ├── getting-started.md
│   ├── api-reference.md
│   ├── integration-guide.md
│   └── examples/
├── src/
│   ├── Contracts/
│   │   ├── VarianceCalculatorInterface.php
│   │   ├── TrendAnalyzerInterface.php
│   │   ├── SignificanceEvaluatorInterface.php
│   │   └── AttributionAnalyzerInterface.php
│   ├── ValueObjects/
│   │   ├── VarianceResult.php
│   │   ├── TrendData.php
│   │   ├── SignificanceThreshold.php
│   │   ├── VarianceAttribution.php
│   │   └── AccountVariance.php
│   ├── Enums/
│   │   ├── VarianceType.php
│   │   ├── VarianceStatus.php
│   │   ├── TrendDirection.php
│   │   └── SignificanceLevel.php
│   ├── Services/
│   │   ├── VarianceCalculator.php
│   │   ├── TrendAnalyzer.php
│   │   ├── SignificanceEvaluator.php
│   │   ├── AttributionAnalyzer.php
│   │   └── StatisticalCalculator.php
│   └── Exceptions/
│       ├── VarianceCalculationException.php
│       ├── InsufficientDataException.php
│       └── InvalidThresholdException.php
└── tests/


packages/AccountPeriodClose/
├── composer.json
├── README.md
├── LICENSE
├── IMPLEMENTATION_SUMMARY.md
├── REQUIREMENTS.md
├── TEST_SUITE_SUMMARY.md
├── VALUATION_MATRIX.md
├── docs/
│   ├── getting-started.md
│   ├── api-reference.md
│   ├── integration-guide.md
│   └── examples/
├── src/
│   ├── Contracts/
│   │   ├── CloseReadinessValidatorInterface.php
│   │   ├── ClosingEntryGeneratorInterface.php
│   │   ├── ReopenValidatorInterface.php
│   │   └── CloseSequenceInterface.php
│   ├── ValueObjects/
│   │   ├── CloseReadinessResult.php
│   │   ├── CloseValidationIssue.php
│   │   ├── ClosingEntrySpec.php
│   │   ├── ReopenRequest.php
│   │   └── CloseCheckResult.php
│   ├── Enums/
│   │   ├── CloseStatus.php
│   │   ├── CloseType.php
│   │   ├── ValidationSeverity.php
│   │   └── ReopenReason.php
│   ├── Services/
│   │   ├── CloseReadinessValidator.php
│   │   ├── ClosingEntryGenerator.php
│   │   ├── ReopenValidator.php
│   │   ├── PreCloseChecker.php
│   │   ├── RetainedEarningsCalculator.php
│   │   ├── EquityRollForwardGenerator.php
│   │   └── YearEndCloseHandler.php
│   ├── Rules/
│   │   ├── TrialBalanceMustBalanceRule.php
│   │   ├── NoUnpostedEntriesRule.php
│   │   ├── AllSubledgersClosed.php
│   │   ├── ReconciliationCompleteRule.php
│   │   └── ApprovalRequiredRule.php
│   └── Exceptions/
│       ├── PeriodCloseException.php
│       ├── PeriodNotReadyException.php
│       ├── ReopenNotAllowedException.php
│       └── CloseSequenceException.php
└── tests/


packages/FinancialRatios/
├── composer.json
├── README.md
├── LICENSE
├── IMPLEMENTATION_SUMMARY.md
├── REQUIREMENTS.md
├── TEST_SUITE_SUMMARY.md
├── VALUATION_MATRIX.md
├── docs/
│   ├── getting-started.md
│   ├── api-reference.md
│   ├── integration-guide.md
│   └── examples/
├── src/
│   ├── Contracts/
│   │   ├── RatioCalculatorInterface.php
│   │   ├── LiquidityRatioInterface.php
│   │   ├── ProfitabilityRatioInterface.php
│   │   ├── LeverageRatioInterface.php
│   │   └── EfficiencyRatioInterface.php
│   ├── ValueObjects/
│   │   ├── RatioResult.php
│   │   ├── RatioBenchmark.php
│   │   ├── DuPontComponents.php
│   │   └── RatioAnalysis.php
│   ├── Enums/
│   │   ├── RatioCategory.php
│   │   ├── RatioType.php
│   │   └── BenchmarkSource.php
│   ├── Services/
│   │   ├── LiquidityRatioCalculator.php
│   │   ├── ProfitabilityRatioCalculator.php
│   │   ├── LeverageRatioCalculator.php
│   │   ├── EfficiencyRatioCalculator.php
│   │   ├── DuPontAnalyzer.php
│   │   └── RatioBenchmarker.php
│   └── Exceptions/
│       ├── RatioCalculationException.php
│       ├── DivisionByZeroException.php
│       └── InsufficientDataException.php
└── tests/


orchestrators/AccountingOperations/
├── composer.json
├── README.md
├── LICENSE
├── IMPLEMENTATION_SUMMARY.md
├── REQUIREMENTS.md
├── TEST_SUITE_SUMMARY.md
├── VALUATION_MATRIX.md
├── docs/
│   ├── getting-started.md
│   ├── api-reference.md
│   ├── integration-guide.md
│   └── examples/
├── src/
│   ├── Workflows/
│   │   ├── PeriodClose/
│   │   │   ├── PeriodCloseWorkflow.php
│   │   │   └── Steps/
│   │   │       ├── ValidateReadinessStep.php
│   │   │       ├── GenerateTrialBalanceStep.php
│   │   │       ├── CreateClosingEntriesStep.php
│   │   │       ├── PostClosingEntriesStep.php
│   │   │       └── LockPeriodStep.php
│   │   ├── StatementGeneration/
│   │   │   ├── BalanceSheetWorkflow.php
│   │   │   ├── IncomeStatementWorkflow.php
│   │   │   └── CashFlowWorkflow.php
│   │   └── Consolidation/
│   │       └── ConsolidationWorkflow.php
│   ├── Coordinators/
│   │   ├── TrialBalanceCoordinator.php
│   │   ├── BalanceSheetCoordinator.php
│   │   ├── IncomeStatementCoordinator.php
│   │   ├── CashFlowCoordinator.php
│   │   ├── PeriodCloseCoordinator.php
│   │   ├── ConsolidationCoordinator.php
│   │   ├── VarianceReportCoordinator.php
│   │   └── FinancialRatioCoordinator.php
│   ├── Listeners/
│   │   ├── CreateClosingEntriesOnPeriodClose.php
│   │   ├── GenerateStatementsOnPeriodClose.php
│   │   ├── AuditLogOnStatementGenerated.php
│   │   └── NotifyOnSignificantVariance.php
│   ├── Contracts/
│   │   ├── AccountingWorkflowInterface.php
│   │   └── AccountingCoordinatorInterface.php
│   ├── DTOs/
│   │   ├── PeriodCloseRequest.php
│   │   ├── StatementGenerationRequest.php
│   │   ├── ConsolidationRequest.php
│   │   └── VarianceReportRequest.php
│   └── Exceptions/
│       ├── WorkflowException.php
│       └── CoordinationException.php
└── tests/




1. FinancialReporting rename to FinancialStatements. Further consideration: can Reporting handed over to Nexus\Reporting package for rendering and distribution?
2. AccountingOperations meant to be an orchestrator package that uses FinancialStatements, AccountConsolidation, AccountVarianceAnalysis, and AccountPeriodClose packages to perform high-level accounting operations.
3. AccountConsolidation does not perform Financial Reporting directly but provides consolidation services that can be used by AccountingOperations.
4. What is the purpose of RuleInterface and does it have further usage outside of the Accounting Operations orchestrator?

Recommendation: Add to AccountPeriodClose:

RetainedEarningsCalculator service
EquityRollForwardGenerator service
These are essential for year-end close and statement of changes in equity generation.

Can this use Nexus\Period for period management and validation? or Period logic and AccountingOperations will coordinate with Nexus\Period for cross package dependencies?