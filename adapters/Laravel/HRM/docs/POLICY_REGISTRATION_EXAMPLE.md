# Policy Registration Example for HRM Package

This document demonstrates how to register context-aware authorization policies for HRM operations in your Laravel application.

## Overview

The `Nexus\Identity` package provides `PolicyEvaluatorInterface` for context-aware authorization (ABAC). This is used by `Nexus\HumanResourceOperations` for complex authorization scenarios like proxy leave applications.

## Policy Registration in Service Provider

Register HRM policies in your application's service provider:

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Identity\Contracts\PolicyEvaluatorInterface;
use Nexus\Identity\ValueObjects\Policy;
use Nexus\Hrm\Contracts\EmployeeQueryInterface;

class HrmServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerLeaveApplicationPolicies();
    }
    
    /**
     * Register leave application authorization policies
     */
    protected function registerLeaveApplicationPolicies(): void
    {
        $policyEvaluator = $this->app->make(PolicyEvaluatorInterface::class);
        $employeeQuery = $this->app->make(EmployeeQueryInterface::class);
        
        // Policy: User can apply leave on behalf of employees
        $leaveProxyPolicy = Policy::define('hrm.leave.apply_on_behalf')
            ->description('User can apply leave on behalf of employees in same department or as their manager')
            ->check(function($user, $action, $resource, $context) use ($employeeQuery) {
                // Extract target employee from context
                $targetEmployeeId = $context['target_employee_id'] ?? null;
                if (!$targetEmployeeId) {
                    return false;
                }
                
                // Get employee records
                $userEmployee = $employeeQuery->findByUserId($user->getId());
                $targetEmployee = $employeeQuery->findById($targetEmployeeId);
                
                if (!$userEmployee || !$targetEmployee) {
                    return false;
                }
                
                // Authorization logic: Same department OR user is manager
                return $userEmployee->getDepartmentId() === $targetEmployee->getDepartmentId()
                    || $userEmployee->getId() === $targetEmployee->getManagerId();
            });
        
        // Register the policy
        $policyEvaluator->registerPolicy(
            $leaveProxyPolicy->getName(),
            $leaveProxyPolicy->getEvaluator()
        );
    }
    
    public function register(): void
    {
        //
    }
}
```

## Policy Evaluation Flow

When a user tries to apply leave on behalf of another employee:

1. **Orchestrator Rule**: `ProxyApplicationAuthorizedRule` calls `PolicyEvaluator`
2. **Policy Lookup**: PolicyEvaluator finds registered policy `'hrm.leave.apply_on_behalf'`
3. **Context Evaluation**: Policy callable receives:
   - `$user` - The applicant User object
   - `$action` - `'hrm.leave.apply_on_behalf'`
   - `$resource` - `null` (not resource-specific)
   - `$context` - `['target_employee_id' => '...']`
4. **Business Logic**: Policy checks:
   - Does applicant employee exist?
   - Does target employee exist?
   - Are they in same department? OR
   - Is applicant the target's manager?
5. **Authorization Decision**: Returns `true` or `false`

## Alternative: Inline Registration (No Value Object)

If you prefer not to use the Policy value object helper:

```php
protected function registerLeaveApplicationPolicies(): void
{
    $policyEvaluator = $this->app->make(PolicyEvaluatorInterface::class);
    $employeeQuery = $this->app->make(EmployeeQueryInterface::class);
    
    // Direct callable registration
    $policyEvaluator->registerPolicy(
        'hrm.leave.apply_on_behalf',
        function($user, $action, $resource, $context) use ($employeeQuery) {
            $targetEmployeeId = $context['target_employee_id'] ?? null;
            if (!$targetEmployeeId) {
                return false;
            }
            
            $userEmployee = $employeeQuery->findByUserId($user->getId());
            $targetEmployee = $employeeQuery->findById($targetEmployeeId);
            
            if (!$userEmployee || !$targetEmployee) {
                return false;
            }
            
            return $userEmployee->getDepartmentId() === $targetEmployee->getDepartmentId()
                || $userEmployee->getId() === $targetEmployee->getManagerId();
        }
    );
}
```

## Policy Naming Convention

Use dot notation for hierarchical policy names:

- `hrm.leave.apply_on_behalf` - Apply leave for others
- `hrm.leave.approve` - Approve leave requests
- `hrm.employee.view_salary` - View salary information
- `finance.invoice.approve_above_limit` - Approve high-value invoices

## Testing Policies

Test your policies in isolation:

```php
use Tests\TestCase;
use Nexus\Identity\Contracts\PolicyEvaluatorInterface;
use Nexus\Identity\Contracts\UserInterface;
use Nexus\Hrm\Contracts\EmployeeQueryInterface;

class LeaveProxyPolicyTest extends TestCase
{
    public function test_manager_can_apply_leave_for_subordinate(): void
    {
        // Arrange
        $manager = $this->createManagerUser();
        $subordinate = $this->createSubordinateEmployee($manager);
        
        $policyEvaluator = $this->app->make(PolicyEvaluatorInterface::class);
        
        // Act
        $canApply = $policyEvaluator->evaluate(
            user: $manager,
            action: 'hrm.leave.apply_on_behalf',
            resource: null,
            context: ['target_employee_id' => $subordinate->getId()]
        );
        
        // Assert
        $this->assertTrue($canApply);
    }
    
    public function test_same_department_can_apply_leave(): void
    {
        // Arrange
        $employee1 = $this->createEmployeeInDepartment('HR');
        $employee2 = $this->createEmployeeInDepartment('HR');
        
        $policyEvaluator = $this->app->make(PolicyEvaluatorInterface::class);
        
        // Act
        $canApply = $policyEvaluator->evaluate(
            user: $employee1->getUser(),
            action: 'hrm.leave.apply_on_behalf',
            resource: null,
            context: ['target_employee_id' => $employee2->getId()]
        );
        
        // Assert
        $this->assertTrue($canApply);
    }
    
    public function test_different_department_cannot_apply_leave(): void
    {
        // Arrange
        $hrEmployee = $this->createEmployeeInDepartment('HR');
        $itEmployee = $this->createEmployeeInDepartment('IT');
        
        $policyEvaluator = $this->app->make(PolicyEvaluatorInterface::class);
        
        // Act
        $canApply = $policyEvaluator->evaluate(
            user: $hrEmployee->getUser(),
            action: 'hrm.leave.apply_on_behalf',
            resource: null,
            context: ['target_employee_id' => $itEmployee->getId()]
        );
        
        // Assert
        $this->assertFalse($canApply);
    }
}
```

## Best Practices

1. **Register policies in service providers** - Keep policy logic centralized
2. **Use descriptive policy names** - Follow dot notation hierarchy
3. **Fail securely** - Return `false` when context is missing or invalid
4. **Test policies thoroughly** - Unit test each authorization scenario
5. **Document business rules** - Explain why authorization succeeds/fails
6. **Log policy failures** - PolicyEvaluator logs failures automatically
7. **Avoid heavy queries** - Keep policy checks lightweight

## RBAC vs ABAC Decision Matrix

| Scenario | Use RBAC (PermissionChecker) | Use ABAC (PolicyEvaluator) |
|----------|------------------------------|----------------------------|
| "Can user delete invoices?" | ✅ YES | ❌ NO |
| "Can user approve invoices over $10,000?" | ❌ NO | ✅ YES |
| "Can manager approve subordinate's leave?" | ❌ NO | ✅ YES |
| "Can HR view employee salary?" | ✅ YES | ❌ NO |
| "Can user edit their own profile?" | ❌ NO | ✅ YES |
| "Can accountant post journal entries?" | ✅ YES | ❌ NO |

**Rule of Thumb**: Use RBAC for static permissions, ABAC for context-dependent authorization.

## See Also

- [CODING_GUIDELINES.md - Section 5.1: Authorization & Policy-Based Access Control](/CODING_GUIDELINES.md)
- [Nexus\Identity README](/packages/Identity/README.md)
- [NEXUS_PACKAGES_REFERENCE.md](/docs/NEXUS_PACKAGES_REFERENCE.md)
