# Integration Guide: FeatureFlags

## Laravel Integration

See [Getting Started Guide](getting-started.md) for complete Laravel setup.

### Quick Setup

1. Create migration
2. Create Eloquent model
3. Create repository
4. Bind in service provider
5. Use in controllers

### Blade Directive (Optional)

```php
// In AppServiceProvider
Blade::directive('feature', function ($expression) {
    return "<?php if(app(FeatureFlagManagerInterface::class)->evaluate($expression, app(EvaluationContext::class))): ?>";
});

Blade::directive('endfeature', function () {
    return "<?php endif; ?>";
});
```

**Usage:**
```blade
@feature('feature.new_ui')
    <div>New UI</div>
@endfeature
```

---

## Symfony Integration

### Step 1: Configure Services

```yaml
# config/services.yaml
services:
    Nexus\FeatureFlags\Contracts\FlagRepositoryInterface:
        class: App\Repository\DoctrineFlag Repository
        
    Nexus\FeatureFlags\Contracts\FeatureFlagManagerInterface:
        class: Nexus\FeatureFlags\Services\FeatureFlagManager
```

### Step 2: Use in Controller

```php
use Nexus\FeatureFlags\Contracts\FeatureFlagManagerInterface;

class DashboardController extends AbstractController
{
    public function __construct(
        private readonly FeatureFlagManagerInterface $flags
    ) {}
    
    public function index(): Response
    {
        if ($this->flags->evaluate('feature.new_ui', $context)) {
            return $this->render('dashboard/new.html.twig');
        }
        
        return $this->render('dashboard/legacy.html.twig');
    }
}
```

---

## Common Patterns

### Pattern 1: Middleware for Feature Gating

```php
class FeatureGateMiddleware
{
    public function __construct(
        private readonly FeatureFlagManagerInterface $flags
    ) {}
    
    public function handle($request, Closure $next, string $feature)
    {
        $context = new EvaluationContext(
            tenantId: $request->user()->tenant_id,
            userId: $request->user()->id
        );
        
        if (!$this->flags->evaluate($feature, $context)) {
            abort(403, 'Feature not available');
        }
        
        return $next($request);
    }
}
```

**Route:**
```php
Route::get('/beta-feature')->middleware('feature:feature.beta');
```

---

**Last Updated:** November 24, 2025
