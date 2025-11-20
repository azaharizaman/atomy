# Nexus\Localization

Framework-agnostic localization and formatting engine for the Nexus ERP system.

## Overview

The `Nexus\Localization` package provides locale-aware formatting for numbers, dates, times, and currency amounts. It establishes a user preference layering system (user → tenant → system default) and integrates with PHP's native `Intl` extension for CLDR-authoritative formatting rules.

## Features

- **Locale Management**: IETF BCP 47 compliant locale codes with parent locale fallback chains
- **Number Formatting**: Locale-specific decimal and thousands separators using PHP `NumberFormatter`
- **Date/Time Formatting**: CLDR-based date and time patterns using PHP `IntlDateFormatter`
- **Currency Formatting**: Locale-aware currency symbol placement and formatting
- **Timezone Conversion**: UTC to user timezone conversion
- **Draft Locale Workflow**: Safe rollout of new locales via draft/active/deprecated status
- **Cycle-Safe Fallback**: Application-level circular reference detection in locale chains
- **Immutable Value Objects**: Readonly properties ensuring cache safety

## Requirements

- PHP 8.2 or higher
- PHP `intl` extension

### Installing the Intl Extension

**Ubuntu/Debian:**
```bash
apt-get install php8.2-intl
```

**macOS (Homebrew):**
```bash
brew install php@8.2
# Intl is included by default
```

**Windows:**
Uncomment `extension=intl` in your `php.ini` file.

## Architecture

### Core Principles

1. **Stateless**: No session or configuration storage in the package
2. **Framework Agnostic**: Pure PHP with only PSR dependencies
3. **CLDR Authoritative**: Formatting rules derived from Unicode CLDR v44+
4. **Contract-Driven**: All persistence via injected interfaces
5. **Immutable VOs**: Readonly value objects prevent mutation bugs

### Package Structure

```
packages/Localization/
├── src/
│   ├── Contracts/          # Repository and resolver interfaces
│   ├── ValueObjects/       # Immutable locale, timezone, settings VOs
│   ├── Enums/              # Text direction, locale status, currency position
│   ├── Services/           # Formatters and locale manager
│   └── Exceptions/         # Domain-specific exceptions
└── composer.json
```

## Value Objects

### Locale

Represents an IETF BCP 47 locale code (e.g., `en_US`, `ms_MY`, `zh_CN`).

```php
use Nexus\Localization\ValueObjects\Locale;

$locale = new Locale('ms_MY');
$locale->code(); // "ms_MY"
$locale->language(); // "ms"
$locale->region(); // "MY"
```

**Validation**: Strict regex `^[a-z]{2}(_[A-Z]{2})?$` - throws `InvalidLocaleCodeException` for malformed codes.

### Timezone

Backed enum with ~50 curated IANA timezone identifiers, plus factory method for all valid IANA codes.

```php
use Nexus\Localization\ValueObjects\Timezone;

$tz = Timezone::AsiaKualaLumpur;
$tz = Timezone::fromString('America/New_York'); // Factory for any valid IANA code
```

### LocaleSettings

Immutable readonly object containing all CLDR formatting rules for a locale.

```php
readonly class LocaleSettings
{
    public string $decimalSeparator;
    public string $thousandsSeparator;
    public string $dateFormat;
    public string $timeFormat;
    public CurrencyPosition $currencyPosition;
    public array $metadata; // Contains currency_symbols, etc.
}
```

### FallbackChain

Represents a locale fallback chain with cycle detection.

```php
$chain = FallbackChain::create(new Locale('ms_MY'));
$chain->addLocale(new Locale('ms'));
$chain->addLocale(new Locale('en_US'));

echo $chain; // "ms_MY → ms → en_US"
json_encode($chain); // ["ms_MY", "ms", "en_US"]
```

## Services

### NumberFormatter

Formats numbers with locale-specific separators.

```php
use Nexus\Localization\Services\NumberFormatter;

$formatter = new NumberFormatter($localeRepository);
$formatter->format(1234.56, new Locale('en_US')); // "1,234.56"
$formatter->format(1234.56, new Locale('de_DE')); // "1.234,56"
```

### DateTimeFormatter

Formats dates and times using CLDR patterns.

```php
use Nexus\Localization\Services\DateTimeFormatter;

$formatter = new DateTimeFormatter($localeRepository);
$formatter->format(new DateTime(), new Locale('ms_MY'), 'medium');
```

### CurrencyFormatter

Formats monetary amounts with locale-specific currency symbols and positions.

```php
use Nexus\Localization\Services\CurrencyFormatter;

$formatter = new CurrencyFormatter($localeRepository);
$formatter->format($money, new Locale('ms_MY'), CurrencyCode::MYR); // "RM 1,234.56"
```

### TimezoneConverter

Converts UTC timestamps to user timezones.

```php
use Nexus\Localization\Services\TimezoneConverter;

$converter = new TimezoneConverter();
$userTime = $converter->toUserTimezone($utcTime, Timezone::AsiaKualaLumpur);
```

## Contracts

### LocaleRepositoryInterface

Retrieves locale metadata and manages fallback chains.

```php
interface LocaleRepositoryInterface
{
    public function getLocaleSettings(Locale $locale): LocaleSettings;
    public function getFallbackChain(Locale $locale): FallbackChain;
    public function getParentLocale(Locale $locale): ?Locale;
    public function getActiveLocales(): array;
    public function getAllLocalesForAdmin(): array;
}
```

### LocaleResolverInterface

Resolves the current user's active locale.

```php
interface LocaleResolverInterface
{
    public function resolve(): Locale;
}
```

**Resolution Precedence**:
1. User-level preference (from `user_locale_preferences` table)
2. Tenant-level default (from `tenants.locale`)
3. System default (from configuration)

**Active Status Validation**: Only locales with `status = 'active'` are returned; draft/deprecated locales trigger fallback to system default.

### TranslationRepositoryInterface

Translation key retrieval (Phase 2 - currently throws `FeatureNotImplementedException`).

```php
interface TranslationRepositoryInterface
{
    public function translate(string $key, Locale $locale, array $replacements = []): string;
}
```

## Enums

### LocaleStatus

```php
enum LocaleStatus: string
{
    case Active = 'active';
    case Draft = 'draft';
    case Deprecated = 'deprecated';
}
```

**Draft Workflow**: Locales with `draft` status are visible in admin UI but excluded from user-facing locale selection dropdowns, enabling safe testing before activation.

### TextDirection

```php
enum TextDirection: string
{
    case LTR = 'ltr';
    case RTL = 'rtl';
}
```

### CurrencyPosition

```php
enum CurrencyPosition: string
{
    case Before = 'before';              // $100
    case After = 'after';                // 100$
    case BeforeWithSpace = 'before_space'; // $ 100
    case AfterWithSpace = 'after_space';   // 100 $
}
```

## Exceptions

All exceptions extend `Nexus\Localization\Exceptions\LocalizationException`:

- `LocaleNotFoundException`: Locale code not found in repository
- `InvalidLocaleCodeException`: Malformed IETF BCP 47 code
- `UnsupportedLocaleException`: Fallback chain exceeds max depth (3 hops)
- `CircularLocaleReferenceException`: Circular reference detected in parent chain
- `InvalidTimezoneException`: Invalid IANA timezone identifier
- `MissingRequirementException`: PHP `intl` extension not loaded
- `FeatureNotImplementedException`: Phase 2 feature not yet implemented
- `TranslationKeyNotFoundException`: Translation key not found (Phase 2)

## Phase 1 vs Phase 2

### Phase 1 (Current) - Formatting Only

✅ Locale management and resolution  
✅ Number formatting  
✅ Date/time formatting  
✅ Currency formatting  
✅ Timezone conversion  
✅ Draft locale workflow  
✅ Fallback chain with cycle detection  

### Phase 2 (Future) - Translation System

⏳ Translation key management  
⏳ Pluralization rules  
⏳ String interpolation  
⏳ Translation database/file storage  
⏳ Integration with `Nexus\Notifier` templates  

## Integration

### With Nexus\Currency

`CurrencyFormatter` integrates with `Nexus\Finance\ValueObjects\Money` for high-precision currency formatting.

### With Nexus\Tenant

Tenant-level locale defaults are retrieved via `TenantInterface::getLocale()` as fallback when user preference is not set.

### With Nexus\Uom

`Quantity::formatLocalized()` uses `NumberFormatter` for locale-aware quantity display.

### With Nexus\Identity

User-level preferences stored in `user_locale_preferences` table, linked to `UserInterface::getId()`.

## License

MIT License - see [LICENSE](LICENSE) file for details.
