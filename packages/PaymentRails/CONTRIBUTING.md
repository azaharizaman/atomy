# Contributing to Nexus\PaymentRails

Thank you for your interest in contributing! Please read the [CONTRIBUTING.md](../Payment/CONTRIBUTING.md) in the core Payment package for guidelines.

## Package-Specific Notes

### Adding a New Payment Rail

1. Create a new class implementing `PaymentRailInterface`
2. Add validation rules for the rail's requirements
3. Implement fee calculation logic
4. Add processing time estimation
5. Write comprehensive unit tests
6. Update the `PaymentRailRegistry` to include the new rail
