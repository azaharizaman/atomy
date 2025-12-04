# Nexus\Attendance

Framework-agnostic attendance tracking and management package for HR systems.

## Features

- ✅ Attendance check-in/check-out recording
- ✅ Location-based validation (geofencing)
- ✅ Attendance anomaly detection
- ✅ Attendance history tracking
- ✅ Work schedule integration
- ✅ Overtime calculation support

## Installation

```bash
composer require nexus/attendance
```

## Usage

### Record Attendance

```php
use Nexus\Attendance\Contracts\AttendanceManagerInterface;

$attendanceManager->checkIn(
    employeeId: 'emp-123',
    timestamp: new \DateTimeImmutable(),
    location: ['latitude' => 3.1390, 'longitude' => 101.6869]
);
```

### Validate Attendance

```php
use Nexus\Attendance\Contracts\AttendanceValidatorInterface;

$result = $attendanceValidator->validate(
    employeeId: 'emp-123',
    checkInTime: $timestamp
);
```

## Contracts

- `AttendanceManagerInterface` - Core attendance management operations
- `AttendanceQueryInterface` - Read operations (CQRS)
- `AttendancePersistInterface` - Write operations (CQRS)
- `AttendanceValidatorInterface` - Attendance validation logic

## Enums

- `AttendanceType` - CHECK_IN, CHECK_OUT, BREAK_START, BREAK_END
- `AttendanceStatus` - PENDING, APPROVED, REJECTED, AMENDED

## Architecture

This package follows Nexus architectural principles:
- ✅ Framework-agnostic (pure PHP 8.3+)
- ✅ Contract-driven design
- ✅ Stateless architecture
- ✅ CQRS pattern for repositories

## License

MIT
