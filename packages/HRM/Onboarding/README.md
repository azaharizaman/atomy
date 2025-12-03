# Nexus\Onboarding

Pre-hire to onboarding to probation milestone tracking for new employee integration.

## Features

- Onboarding task management
- Checklist progress tracking
- Probation period reviews
- Task assignment automation
- Digital onboarding workflows

## Installation

```bash
composer require nexus/onboarding
```

## Usage

```php
use Nexus\Onboarding\Contracts\OnboardingChecklistRepositoryInterface;
use Nexus\Onboarding\Services\OnboardingProgressTracker;

// Inject via constructor
public function __construct(
    private readonly OnboardingChecklistRepositoryInterface $checklistRepository,
    private readonly OnboardingProgressTracker $progressTracker
) {}
```

## Architecture

This package follows Clean Architecture principles:
- **Entities**: OnboardingTask, OnboardingChecklist, ProbationReview
- **ValueObjects**: TaskStatus, ChecklistId, ProbationStatus
- **Services**: OnboardingProgressTracker, TaskAssignmentService, ProbationEvaluationService
- **Policies**: Probation, DocumentSubmission, EquipmentIssuance

## License

MIT
