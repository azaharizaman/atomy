## Why

The current production lifecycle is fragmented across multiple domains (Manufacturing, Inventory, Quality). To maintain a decoupled and scalable architecture, a stateless Orchestrator is required to coordinate these workflows via abstract contracts, allowing for independent deployment and framework agnosticism.

## What Changes

- Creation of the `ManufacturingOperations` orchestrator package (Layer 2).
- **Architecture**: Strict "Interface First" approach. The package will define `Contracts/` for all external interactions (Stock, Production, Quality, Scheduling).
- **Independence**: Zero hard dependencies on Layer 1 atomic packages (except `Nexus\Common`). Layer 1 packages will be moved to `suggest` in `composer.json`.
- **Statelessness**: All operations will be context-driven, relying on provided DataTransferObjects or DataProviders.

## Capabilities

### New Capabilities
- `production-order-lifecycle`: Coordinates the transition from a production order to shop floor execution by interacting with abstract Stock and Quality contracts.
- `bom-reconciliation`: Logic to resolve complex Bill of Materials into requirements, checking availability through a `StockProviderInterface`.
- `manufacturing-cost-aggregation`: Aggregates financial data points (labor, material) via provider interfaces to calculate real-time production COGS.

### Modified Capabilities
- (None)

## Impact

- **New Package**: `orchestrators/ManufacturingOperations`.
- **Integration**: Requires Layer 3 Adapters (to be implemented later) to bridge these Orchestrator contracts to `Nexus\Manufacturing`, `Nexus\Inventory`, etc.
- **Portability**: This package will be publishable to Packagist independently.
- **Standards**: PSR compliance for interfaces where applicable.
