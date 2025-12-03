# Nexus\TrainingManagement

Training courses, enrollments, trainers, and certification tracking for employee development.

## Features

- Course management
- Enrollment tracking
- Trainer allocation
- Attendance tracking
- Certification issuance

## Installation

```bash
composer require nexus/training-management
```

## Usage

```php
use Nexus\TrainingManagement\Contracts\CourseRepositoryInterface;
use Nexus\TrainingManagement\Contracts\EnrollmentRepositoryInterface;

// Inject via constructor
public function __construct(
    private readonly CourseRepositoryInterface $courseRepository,
    private readonly EnrollmentRepositoryInterface $enrollmentRepository
) {}
```

## Architecture

This package follows Clean Architecture principles:
- **Entities**: Course, Enrollment, Trainer
- **ValueObjects**: CourseId, EnrollmentStatus, Certification
- **Services**: CertificationGrantService, AttendanceTrackingService, CourseEligibilityChecker
- **Policies**: TrainingApproval, Certification, Reimbursement

## License

MIT
