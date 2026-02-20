# Nexus\Recruitment

Job posting, applicant tracking, interviews, and hiring decision engine for talent acquisition.

## Features

- Job posting management
- Applicant tracking system (ATS)
- Interview scheduling and evaluation
- Applicant scoring engine
- Hiring decision automation

## Installation

```bash
composer require nexus/recruitment
```

## Usage

```php
use Nexus\Recruitment\Contracts\HiringDecisionEngineInterface;
use Nexus\Recruitment\Services\ApplicantScoringEngine;

// Inject via constructor
public function __construct(
    private readonly HiringDecisionEngineInterface $hiringEngine,
    private readonly ApplicantScoringEngine $scoringEngine
) {}
```

## Architecture

This package follows Clean Architecture principles:
- **Entities**: JobPosting, Applicant, Interview
- **ValueObjects**: ApplicantScore, InterviewResult, JobCode
- **Services**: ApplicantScoringEngine, InterviewEvaluationService, HiringDecisionEngine
- **Policies**: EligibilityCheck, DiversityCompliance, BackgroundCheck

## License

MIT
