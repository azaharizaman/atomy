# Laravel Sourcing adapter (`nexus/laravel-sourcing-adapter`)

Layer 3 persistence for `nexus/sourcing`: `EloquentQuotation` / `EloquentAward`, migrations `2026_03_24_000002` (quotations) and `2026_03_24_000003` (awards), and `EloquentQuotationRepository` implementing `QuotationQueryInterface` with `tenant_id` on all queries. `SourcingAdapterServiceProvider` registers the query repository as a singleton and loads migrations.
