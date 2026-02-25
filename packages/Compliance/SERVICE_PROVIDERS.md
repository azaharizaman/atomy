# Service Provider Registration

## Compliance & Statutory Service Providers

The following service providers have been created:

### ComplianceServiceProvider
Location: `consuming application (e.g., Laravel app)app/Providers/ComplianceServiceProvider.php`

Binds:
- `ComplianceSchemeRepositoryInterface` → `DbComplianceSchemeRepository`
- `SodRuleRepositoryInterface` → `DbSodRuleRepository`
- `SodViolationRepositoryInterface` → `DbSodViolationRepository`
- `RuleEngineInterface` → `RuleEngine`
- `ValidationPipeline`, `SodValidator`, `ConfigurationValidator`
- `ComplianceManager`, `SodManager`, `ConfigurationAuditor`

### StatutoryServiceProvider
Location: `consuming application (e.g., Laravel app)app/Providers/StatutoryServiceProvider.php`

Binds:
- `StatutoryReportRepositoryInterface` → `DbStatutoryReportRepository`
- `TaxonomyReportGeneratorInterface` → `DefaultAccountingAdapter`
- `PayrollStatutoryInterface` → `DefaultPayrollStatutoryAdapter`
- `SchemaValidator`, `ReportGenerator`, `FormatConverter`, `FinanceDataExtractor`
- `StatutoryReportManager`

## Registration

These providers should be registered in Laravel's service provider discovery mechanism. Depending on the Laravel version:

### Laravel 11+ (Auto-Discovery via composer.json)
Add to root `composer.json`:
```json
{
    "extra": {
        "laravel": {
            "providers": [
                "App\\Providers\\ComplianceServiceProvider",
                "App\\Providers\\StatutoryServiceProvider"
            ]
        }
    }
}
```

### Laravel 10 and below (config/app.php)
Add to `config/app.php` providers array:
```php
'providers' => [
    // ...
    App\Providers\ComplianceServiceProvider::class,
    App\Providers\StatutoryServiceProvider::class,
],
```

### Manual Registration (bootstrap/providers.php for Laravel 11)
If `bootstrap/providers.php` exists:
```php
return [
    // ...
    App\Providers\ComplianceServiceProvider::class,
    App\Providers\StatutoryServiceProvider::class,
];
```
