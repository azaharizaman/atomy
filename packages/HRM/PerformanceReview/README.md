# Nexus\PerformanceReview

Appraisal cycles, KPIs, competency scoring, and rating calculations for employee performance management.

## Features

- Appraisal management
- KPI tracking and evaluation
- Weighted rating calculations
- Review templates
- Performance score normalization

## Installation

```bash
composer require nexus/performance-review
```

## Usage

```php
use Nexus\PerformanceReview\Contracts\PerformanceCalculatorInterface;
use Nexus\PerformanceReview\Services\PerformanceScoreCalculator;

// Inject via constructor
public function __construct(
    private readonly PerformanceCalculatorInterface $calculator
) {}
```

## Architecture

This package follows Clean Architecture principles:
- **Entities**: Appraisal, Kpi, ReviewTemplate
- **ValueObjects**: Rating, CompetencyScore, AppraisalPeriod
- **Services**: PerformanceScoreCalculator, WeightedRatingEngine, KpiEvaluator
- **Policies**: RatingNormalization, CompetencyEvaluation, PromotionEligibility

## License

MIT
