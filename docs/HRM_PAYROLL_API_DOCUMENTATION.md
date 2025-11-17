# Nexus HRM & Payroll API Documentation

This document provides comprehensive API documentation for the Human Resource Management (HRM) and Payroll modules of the Nexus ERP system.

## Table of Contents

1. [Authentication](#authentication)
2. [HRM API Endpoints](#hrm-api-endpoints)
   - [Employee Management](#employee-management)
   - [Leave Management](#leave-management)
   - [Attendance Management](#attendance-management)
   - [Performance Reviews](#performance-reviews)
   - [Disciplinary Management](#disciplinary-management)
   - [Training Management](#training-management)
3. [Payroll API Endpoints](#payroll-api-endpoints)
   - [Payroll Components](#payroll-components)
   - [Payroll Processing](#payroll-processing)
   - [Payslip Management](#payslip-management)
4. [Error Responses](#error-responses)

---

## Authentication

All API endpoints require authentication using Laravel Sanctum bearer tokens and must include the `tenant.scope` middleware for multi-tenant data isolation.

**Required Headers:**

```http
Authorization: Bearer {your-api-token}
Content-Type: application/json
Accept: application/json
X-Tenant-Id: {tenant-id}
```

---

## HRM API Endpoints

### Employee Management

#### List Employees

**Endpoint:** `GET /api/hrm/employees`

**Description:** Retrieve a paginated list of employees with optional filters.

**Query Parameters:**
- `status` (string, optional): Filter by employee status (e.g., `active`, `probation`, `terminated`)
- `employment_type` (string, optional): Filter by employment type
- `department_id` (string, optional): Filter by department ULID
- `office_id` (string, optional): Filter by office ULID
- `manager_id` (string, optional): Filter by manager's employee ULID
- `search` (string, optional): Search by name, email, or employee code
- `per_page` (integer, optional): Items per page (default: 15, max: 100)
- `page` (integer, optional): Page number (default: 1)

**Example Request:**

```bash
curl -X GET "https://api.example.com/api/hrm/employees?status=active&per_page=20&page=1" \
  -H "Authorization: Bearer your-api-token" \
  -H "X-Tenant-Id: 01234567-89ab-cdef-0123-456789abcdef"
```

**Example Response:**

```json
{
  "data": [
    {
      "id": "01234567-89ab-cdef-0123-456789abcdef",
      "employee_code": "EMP-001",
      "first_name": "John",
      "last_name": "Doe",
      "email": "john.doe@example.com",
      "status": "active",
      "employment_type": "full_time",
      "hire_date": "2024-01-15",
      "department_id": "98765432-10ab-cdef-0123-456789abcdef",
      "office_id": "87654321-09ab-cdef-0123-456789abcdef"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 150
  }
}
```

---

#### Create Employee

**Endpoint:** `POST /api/hrm/employees`

**Description:** Create a new employee record.

**Request Body:**

```json
{
  "employee_code": "EMP-001",
  "first_name": "John",
  "last_name": "Doe",
  "email": "john.doe@example.com",
  "date_of_birth": "1990-05-15",
  "phone_number": "+60123456789",
  "address": "123 Main Street, Kuala Lumpur",
  "emergency_contact": "Jane Doe",
  "emergency_phone": "+60129876543",
  "employment_type": "full_time",
  "job_title": "Software Engineer",
  "department_id": "98765432-10ab-cdef-0123-456789abcdef",
  "office_id": "87654321-09ab-cdef-0123-456789abcdef",
  "manager_id": "76543210-08ab-cdef-0123-456789abcdef",
  "basic_salary": 5000.00,
  "hire_date": "2024-01-15"
}
```

**Validation Rules:**
- `employee_code`: Required, unique per tenant, max 50 characters
- `first_name`: Required, string, max 255 characters
- `last_name`: Required, string, max 255 characters
- `email`: Required, email format, unique per tenant
- `date_of_birth`: Required, date, must be before today
- `employment_type`: Required, enum (`full_time`, `part_time`, `contract`, `intern`)
- `basic_salary`: Required, numeric, min 0

**Example Request:**

```bash
curl -X POST "https://api.example.com/api/hrm/employees" \
  -H "Authorization: Bearer your-api-token" \
  -H "X-Tenant-Id: 01234567-89ab-cdef-0123-456789abcdef" \
  -H "Content-Type: application/json" \
  -d '{
    "employee_code": "EMP-001",
    "first_name": "John",
    "last_name": "Doe",
    "email": "john.doe@example.com",
    "date_of_birth": "1990-05-15",
    "employment_type": "full_time",
    "basic_salary": 5000.00
  }'
```

**Example Response:**

```json
{
  "message": "Employee created successfully",
  "data": {
    "employee_id": "01234567-89ab-cdef-0123-456789abcdef"
  }
}
```

---

#### Update Employee

**Endpoint:** `PUT /api/hrm/employees/{employee_id}`

**Description:** Update an existing employee record.

**Request Body:** Same as Create Employee, but all fields are optional.

**Example Request:**

```bash
curl -X PUT "https://api.example.com/api/hrm/employees/01234567-89ab-cdef-0123-456789abcdef" \
  -H "Authorization: Bearer your-api-token" \
  -H "X-Tenant-Id: 01234567-89ab-cdef-0123-456789abcdef" \
  -H "Content-Type: application/json" \
  -d '{
    "job_title": "Senior Software Engineer",
    "basic_salary": 6000.00
  }'
```

---

#### Confirm Employee (Complete Probation)

**Endpoint:** `POST /api/hrm/employees/{employee_id}/confirm`

**Description:** Confirm employee after probation period.

**Request Body:**

```json
{
  "confirmation_date": "2024-04-15",
  "notes": "Successfully completed probation period"
}
```

**Example Request:**

```bash
curl -X POST "https://api.example.com/api/hrm/employees/01234567-89ab-cdef-0123-456789abcdef/confirm" \
  -H "Authorization: Bearer your-api-token" \
  -H "X-Tenant-Id: 01234567-89ab-cdef-0123-456789abcdef" \
  -H "Content-Type: application/json" \
  -d '{
    "confirmation_date": "2024-04-15",
    "notes": "Successfully completed probation period"
  }'
```

---

#### Terminate Employee

**Endpoint:** `POST /api/hrm/employees/{employee_id}/terminate`

**Description:** Terminate an employee.

**Request Body:**

```json
{
  "termination_date": "2024-12-31",
  "termination_reason": "Resignation",
  "notes": "Employee resigned for personal reasons"
}
```

---

### Leave Management

#### Create Leave Request

**Endpoint:** `POST /api/hrm/leaves`

**Description:** Create a new leave request.

**Request Body:**

```json
{
  "employee_id": "01234567-89ab-cdef-0123-456789abcdef",
  "leave_type_id": "98765432-10ab-cdef-0123-456789abcdef",
  "start_date": "2025-02-10",
  "end_date": "2025-02-12",
  "reason": "Annual leave for family vacation",
  "half_day": false,
  "contact_number": "+60123456789"
}
```

**Validation Rules:**
- `employee_id`: Required, exists in employees table
- `leave_type_id`: Required, exists in leave_types table
- `start_date`: Required, date, must be today or later
- `end_date`: Required, date, must be on or after start_date
- `reason`: Required, string, max 500 characters

**Example Request:**

```bash
curl -X POST "https://api.example.com/api/hrm/leaves" \
  -H "Authorization: Bearer your-api-token" \
  -H "X-Tenant-Id: 01234567-89ab-cdef-0123-456789abcdef" \
  -H "Content-Type: application/json" \
  -d '{
    "employee_id": "01234567-89ab-cdef-0123-456789abcdef",
    "leave_type_id": "98765432-10ab-cdef-0123-456789abcdef",
    "start_date": "2025-02-10",
    "end_date": "2025-02-12",
    "reason": "Annual leave"
  }'
```

---

#### Approve Leave Request

**Endpoint:** `POST /api/hrm/leaves/{leave_id}/approve`

**Description:** Approve a pending leave request.

**Request Body:**

```json
{
  "approver_id": "76543210-08ab-cdef-0123-456789abcdef",
  "remarks": "Approved"
}
```

---

#### Reject Leave Request

**Endpoint:** `POST /api/hrm/leaves/{leave_id}/reject`

**Description:** Reject a pending leave request.

**Request Body:**

```json
{
  "approver_id": "76543210-08ab-cdef-0123-456789abcdef",
  "rejection_reason": "Insufficient leave balance"
}
```

---

### Attendance Management

#### Clock In

**Endpoint:** `POST /api/hrm/attendance/clock-in`

**Description:** Record employee clock-in.

**Request Body:**

```json
{
  "employee_id": "01234567-89ab-cdef-0123-456789abcdef",
  "clock_in_time": "2025-01-15 08:00:00",
  "latitude": 3.1390,
  "longitude": 101.6869,
  "location_name": "Kuala Lumpur Office",
  "notes": "On time"
}
```

**Validation Rules:**
- `employee_id`: Required
- `clock_in_time`: Required, datetime format (Y-m-d H:i:s)
- `latitude`: Optional, numeric, between -90 and 90
- `longitude`: Optional, numeric, between -180 and 180

**Example Request:**

```bash
curl -X POST "https://api.example.com/api/hrm/attendance/clock-in" \
  -H "Authorization: Bearer your-api-token" \
  -H "X-Tenant-Id: 01234567-89ab-cdef-0123-456789abcdef" \
  -H "Content-Type: application/json" \
  -d '{
    "employee_id": "01234567-89ab-cdef-0123-456789abcdef",
    "clock_in_time": "2025-01-15 08:00:00",
    "latitude": 3.1390,
    "longitude": 101.6869
  }'
```

---

#### Clock Out

**Endpoint:** `POST /api/hrm/attendance/clock-out`

**Description:** Record employee clock-out.

**Request Body:**

```json
{
  "attendance_id": "01234567-89ab-cdef-0123-456789abcdef",
  "clock_out_time": "2025-01-15 17:00:00",
  "latitude": 3.1390,
  "longitude": 101.6869,
  "notes": "Completed tasks"
}
```

---

### Performance Reviews

#### Create Performance Review

**Endpoint:** `POST /api/hrm/reviews`

**Description:** Initiate a new performance review.

**Request Body:**

```json
{
  "employee_id": "01234567-89ab-cdef-0123-456789abcdef",
  "reviewer_id": "76543210-08ab-cdef-0123-456789abcdef",
  "review_type": "annual",
  "review_period_start": "2024-01-01",
  "review_period_end": "2024-12-31",
  "goals": ["Complete 5 projects", "Improve team collaboration"],
  "kpis": {"project_completion": 100, "customer_satisfaction": 95}
}
```

**Validation Rules:**
- `review_type`: Required, enum (`annual`, `probation`, `mid_year`, `quarterly`)
- `review_period_end`: Required, date, must be after review_period_start

---

#### Submit Review

**Endpoint:** `POST /api/hrm/reviews/{review_id}/submit`

**Description:** Submit review for final approval.

---

### Disciplinary Management

#### Create Disciplinary Case

**Endpoint:** `POST /api/hrm/disciplinary`

**Description:** Open a new disciplinary case.

**Request Body:**

```json
{
  "employee_id": "01234567-89ab-cdef-0123-456789abcdef",
  "reported_by": "76543210-08ab-cdef-0123-456789abcdef",
  "incident_date": "2025-01-10",
  "severity": "moderate",
  "category": "attendance",
  "description": "Late for work 3 times in a week",
  "witnesses": ["John Smith", "Jane Doe"]
}
```

**Validation Rules:**
- `severity`: Required, enum (`minor`, `moderate`, `major`, `critical`)
- `incident_date`: Required, date, must be today or earlier

---

### Training Management

#### Create Training Program

**Endpoint:** `POST /api/hrm/training`

**Description:** Create a new training program.

**Request Body:**

```json
{
  "title": "Advanced Laravel Development",
  "description": "Deep dive into Laravel 12 features",
  "category": "Technical Training",
  "trainer_id": "76543210-08ab-cdef-0123-456789abcdef",
  "start_date": "2025-03-01",
  "end_date": "2025-03-05",
  "location": "Training Room A",
  "max_participants": 20,
  "duration_hours": 40,
  "cost": 5000.00
}
```

---

#### Enroll Employee in Training

**Endpoint:** `POST /api/hrm/training/{training_id}/enroll`

**Description:** Enroll an employee in a training program.

**Request Body:**

```json
{
  "employee_id": "01234567-89ab-cdef-0123-456789abcdef",
  "enrollment_notes": "Approved by manager"
}
```

---

## Payroll API Endpoints

### Payroll Components

#### Create Payroll Component

**Endpoint:** `POST /api/payroll/components`

**Description:** Create a new payroll component (earning or deduction).

**Request Body:**

```json
{
  "code": "BASIC",
  "name": "Basic Salary",
  "type": "earning",
  "calculation_method": "fixed",
  "description": "Monthly basic salary",
  "fixed_amount": 5000.00,
  "is_taxable": true,
  "is_statutory": false,
  "gl_account_code": "5100"
}
```

**Validation Rules:**
- `code`: Required, unique per tenant, max 50 characters
- `type`: Required, enum (`earning`, `deduction`)
- `calculation_method`: Required, enum (`fixed`, `percentage`, `formula`)
- `percentage_value`: Required if calculation_method is `percentage`, numeric 0-100
- `fixed_amount`: Required if calculation_method is `fixed`, numeric >= 0

**Example Request:**

```bash
curl -X POST "https://api.example.com/api/payroll/components" \
  -H "Authorization: Bearer your-api-token" \
  -H "X-Tenant-Id: 01234567-89ab-cdef-0123-456789abcdef" \
  -H "Content-Type: application/json" \
  -d '{
    "code": "BASIC",
    "name": "Basic Salary",
    "type": "earning",
    "calculation_method": "fixed",
    "fixed_amount": 5000.00,
    "is_taxable": true
  }'
```

---

#### List Payroll Components

**Endpoint:** `GET /api/payroll/components`

**Description:** Retrieve all payroll components.

**Query Parameters:**
- `type` (string, optional): Filter by type (`earning` or `deduction`)
- `is_statutory` (boolean, optional): Filter statutory components
- `is_active` (boolean, optional): Filter active components

---

### Payroll Processing

#### Process Payroll Period

**Endpoint:** `POST /api/payroll/process-period`

**Description:** Process payroll for all or selected employees for a given period.

**Request Body:**

```json
{
  "period_start": "2025-01-01",
  "period_end": "2025-01-31",
  "pay_date": "2025-02-05",
  "description": "January 2025 Payroll",
  "employee_ids": ["01234567-89ab-cdef-0123-456789abcdef"],
  "department_id": "98765432-10ab-cdef-0123-456789abcdef",
  "office_id": "87654321-09ab-cdef-0123-456789abcdef"
}
```

**Validation Rules:**
- `period_start`: Required, date
- `period_end`: Required, date, must be after period_start
- `pay_date`: Required, date, must be on or after period_end
- `employee_ids`: Optional, array of employee ULIDs

**Example Request:**

```bash
curl -X POST "https://api.example.com/api/payroll/process-period" \
  -H "Authorization: Bearer your-api-token" \
  -H "X-Tenant-Id: 01234567-89ab-cdef-0123-456789abcdef" \
  -H "Content-Type: application/json" \
  -d '{
    "period_start": "2025-01-01",
    "period_end": "2025-01-31",
    "pay_date": "2025-02-05",
    "description": "January 2025 Payroll"
  }'
```

**Example Response:**

```json
{
  "message": "Payroll period processed successfully",
  "data": {
    "period_id": "01234567-89ab-cdef-0123-456789abcdef",
    "employees_processed": 150,
    "total_gross_pay": 750000.00,
    "total_deductions": 125000.00,
    "total_net_pay": 625000.00,
    "total_employer_cost": 825000.00
  }
}
```

---

#### Process Individual Employee Payroll

**Endpoint:** `POST /api/payroll/process-employee`

**Description:** Process payroll for a single employee (off-cycle payment or correction).

**Request Body:**

```json
{
  "employee_id": "01234567-89ab-cdef-0123-456789abcdef",
  "period_start": "2025-01-01",
  "period_end": "2025-01-31",
  "pay_date": "2025-02-05",
  "description": "Bonus payment",
  "earnings": [
    {
      "component_id": "BONUS",
      "amount": 2000.00
    }
  ]
}
```

---

### Payslip Management

#### List Payslips

**Endpoint:** `GET /api/payroll/payslips`

**Description:** Retrieve payslips with filters.

**Query Parameters:**
- `employee_id` (string, optional): Filter by employee ULID
- `period_start` (date, optional): Filter by period start date
- `period_end` (date, optional): Filter by period end date
- `status` (string, optional): Filter by status
- `is_approved` (boolean, optional): Filter approved payslips
- `is_paid` (boolean, optional): Filter paid payslips

---

#### Get Payslip Details

**Endpoint:** `GET /api/payroll/payslips/{payslip_id}`

**Description:** Retrieve detailed payslip breakdown.

**Example Response:**

```json
{
  "data": {
    "id": "01234567-89ab-cdef-0123-456789abcdef",
    "employee": {
      "id": "01234567-89ab-cdef-0123-456789abcdef",
      "name": "John Doe",
      "employee_code": "EMP-001"
    },
    "period": {
      "start": "2025-01-01",
      "end": "2025-01-31",
      "pay_date": "2025-02-05"
    },
    "earnings": [
      {
        "code": "BASIC",
        "name": "Basic Salary",
        "amount": 5000.00
      },
      {
        "code": "ALLOWANCE",
        "name": "Transport Allowance",
        "amount": 500.00
      }
    ],
    "deductions": [
      {
        "code": "EPF_EMPLOYEE",
        "name": "EPF Employee Contribution",
        "amount": 605.00
      },
      {
        "code": "SOCSO_EMPLOYEE",
        "name": "SOCSO Employee Contribution",
        "amount": 22.25
      },
      {
        "code": "EIS_EMPLOYEE",
        "name": "EIS Employee Contribution",
        "amount": 7.90
      },
      {
        "code": "PCB",
        "name": "Income Tax (PCB)",
        "amount": 125.00
      }
    ],
    "statutory_deductions": {
      "epf_employee": 605.00,
      "socso_employee": 22.25,
      "eis_employee": 7.90,
      "pcb": 125.00
    },
    "employer_contributions": {
      "epf_employer": 715.00,
      "socso_employer": 76.25,
      "eis_employer": 7.90
    },
    "summary": {
      "gross_pay": 5500.00,
      "total_deductions": 760.15,
      "net_pay": 4739.85,
      "total_employer_cost": 6299.15
    },
    "status": "approved",
    "is_paid": false
  }
}
```

---

#### Approve Payslip

**Endpoint:** `POST /api/payroll/payslips/{payslip_id}/approve`

**Description:** Approve a payslip for payment.

**Request Body:**

```json
{
  "comments": "Approved for January 2025 payment cycle"
}
```

**Example Request:**

```bash
curl -X POST "https://api.example.com/api/payroll/payslips/01234567-89ab-cdef-0123-456789abcdef/approve" \
  -H "Authorization: Bearer your-api-token" \
  -H "X-Tenant-Id: 01234567-89ab-cdef-0123-456789abcdef" \
  -H "Content-Type: application/json" \
  -d '{
    "comments": "Approved"
  }'
```

---

#### Mark Payslip as Paid

**Endpoint:** `POST /api/payroll/payslips/{payslip_id}/mark-paid`

**Description:** Mark a payslip as paid after bank transfer.

**Request Body:**

```json
{
  "payment_date": "2025-02-05",
  "payment_method": "Bank Transfer",
  "payment_reference": "TRX-202502051234",
  "remarks": "Paid via Maybank"
}
```

---

## Error Responses

All endpoints return standard error responses:

### Validation Error (422)

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email has already been taken."],
    "basic_salary": ["The basic salary must be at least 0."]
  }
}
```

### Unauthorized (401)

```json
{
  "message": "Unauthenticated."
}
```

### Forbidden (403)

```json
{
  "message": "This action is unauthorized."
}
```

### Not Found (404)

```json
{
  "message": "Resource not found."
}
```

### Server Error (500)

```json
{
  "message": "Server error occurred.",
  "error": "Detailed error message for debugging"
}
```

---

## Postman Collection

A Postman collection with all endpoints and example requests is available at:
`docs/postman/Nexus-HRM-Payroll-API.postman_collection.json`

---

## Support

For API support or questions, please contact: support@nexus-erp.com
