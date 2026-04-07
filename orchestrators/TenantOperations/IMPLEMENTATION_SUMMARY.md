# Nexus\TenantOperations Implementation Summary

## Alpha company onboarding

The alpha release now has a focused company onboarding boundary separate from the broader tenant lifecycle workflow:

- `TenantCompanyOnboardingCoordinator` is the alpha-facing traffic cop.
- `TenantCompanyOnboardingService` composes tenant creation with owner-user creation.
- `TenantCreatorAdapterInterface` now carries tenant email so the tenant row can be created without synthetic placeholder data.
- `AdminCreatorAdapterInterface` carries the owner profile data needed for alpha without pulling in the full IdentityOperations onboarding stack.
- `TenantCompanyOnboardingRequest` carries only the minimal onboarding inputs needed by Alpha.
- `TenantCompanyOnboardingResult` returns the created tenant ID and owner user ID in a compact result contract.

This path intentionally does not include settings initialization, feature configuration, or company-structure creation. Those remain part of the broader tenant lifecycle/orchestration surface and are deferred for later lifecycle work.
