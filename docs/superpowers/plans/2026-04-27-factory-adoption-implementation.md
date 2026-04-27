# Factory Adoption Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement 21 Laravel Model Factories across 6 phases, enabling test-first development while refactoring PetrochemicalTenantSeeder to use factories with identical output.

**Architecture:** 
- Phase 1: Foundation factories (Tenant, User, Project, Rfq) - establish base entities with status states and relationship modifiers
- Phase 2: Core workflow factories (Vendor, QuoteSubmission, VendorInvitation, RfqLineItem) - procurement flow
- Phase 3: Approval flow factories (Approval, ComparisonRun, Award) - workflow chain with pipeline realism
- Phase 4: Supporting factories (ProjectAcl, ScoringModel, VendorEvidence, VendorFinding) - permissions and compliance
- Phase 5: Specialized factories (NormalizationSourceLine, RfqTemplate, Task, ApprovalHistory, Scenario, NormalizationConflict) - ingestion and misc
- Phase 6: Seeder refactor - rewrite PetrochemicalTenantSeeder using all factories

**Tech Stack:** Laravel Model Factories, HasFactory trait, PHP 8.2, test-first development

---

## Task 1: Phase 1 - Foundation Factories

### Task 1.1: Add HasFactory Trait to Tenant, Project, Rfq Models

**Files:**
- Modify: `apps/atomy-q/API/app/Models/Tenant.php`
- Modify: `apps/atomy-q/API/app/Models/Project.php`
- Modify: `apps/atomy-q/API/app/Models/Rfq.php`

- [ ] **Step 1: Add HasFactory trait to Tenant model**

```php
use Illuminate\Database\Eloquent\Factories\HasFactory;

// In Tenant class:
use HasFactory;
```

- [ ] **Step 2: Add HasFactory trait to Project model**

```php
use Illuminate\Database\Eloquent\Factories\HasFactory;

// In Project class:
use HasFactory;
```

- [ ] **Step 3: Add HasFactory trait to Rfq model**

```php
use Illuminate\Database\Eloquent\Factories\HasFactory;

// In Rfq class:
use HasFactory;
```

- [ ] **Step 4: Commit**

```bash
git add apps/atomy-q/API/app/Models/Tenant.php apps/atomy-q/API/app/Models/Project.php apps/atomy-q/API/app/Models/Rfq.php
git commit -m "feat: add HasFactory trait to Tenant, Project, Rfq models"
```

### Task 1.2: Create TenantFactory

**Files:**
- Create: `apps/atomy-q/API/database/factories/TenantFactory.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Unit/Database/Factories/TenantFactoryTest.php
<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories;

use App\Models\Tenant;
use Tests\TestCase;

final class TenantFactoryTest extends TestCase
{
    public function test_tenant_factory_creates_valid_instance(): void
    {
        $tenant = Tenant::factory()->create();
        
        $this->assertInstanceOf(Tenant::class, $tenant);
        $this->assertNotEmpty($tenant->code);
        $this->assertNotEmpty($tenant->name);
    }
    
    public function test_tenant_active_state(): void
    {
        $tenant = Tenant::factory()->active()->create();
        
        $this->assertSame('active', $tenant->status);
    }
    
    public function test_tenant_suspended_state(): void
    {
        $tenant = Tenant::factory()->suspended()->create();
        
        $this->assertSame('suspended', $tenant->status);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter TenantFactoryTest`
Expected: FAIL (TenantFactory not found)

- [ ] **Step 3: Write TenantFactory**

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

final class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('????')),
            'name' => fake()->company(),
            'email' => fake()->companyEmail(),
            'status' => 'pending',
            'timezone' => fake()->randomElement(['UTC', 'Europe/Oslo', 'America/New_York']),
            'locale' => 'en',
            'currency' => fake()->randomElement(['USD', 'NOK', 'EUR']),
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
            'max_users' => fake()->randomElement([10, 25, 50, 100]),
            'storage_quota' => fake()->randomElement([1073741824, 5368709120, 10737418240]), // 1GB, 5GB, 10GB
            'storage_used' => 0,
            'rate_limit' => 60,
            'is_readonly' => false,
            'onboarding_progress' => 0,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'onboarding_progress' => 100,
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended',
        ]);
    }

    public function trial(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'trial',
            'trial_ends_at' => now()->addDays(14),
        ]);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter TenantFactoryTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add apps/atomy-q/API/database/factories/TenantFactory.php
git commit -m "feat: create TenantFactory with active/suspended/trial states"
```

### Task 1.3: Extend UserFactory with States

**Files:**
- Modify: `apps/atomy-q/API/database/factories/UserFactory.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Unit/Database/Factories/UserFactoryTest.php
<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories;

use App\Models\User;
use Tests\TestCase;

final class UserFactoryTest extends TestCase
{
    public function test_user_active_state(): void
    {
        $user = User::factory()->active()->create();
        
        $this->assertSame('active', $user->status);
    }
    
    public function test_user_locked_state(): void
    {
        $user = User::factory()->locked()->create();
        
        $this->assertSame('locked', $user->status);
    }
    
    public function test_user_admin_state(): void
    {
        $user = User::factory()->admin()->create();
        
        $this->assertSame('admin', $user->role);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter UserFactoryTest`
Expected: FAIL (active/locked/admin methods not defined)

- [ ] **Step 3: Add states to UserFactory**

```php
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    public function locked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'locked',
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
            'status' => 'active',
        ]);
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter UserFactoryTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add apps/atomy-q/API/database/factories/UserFactory.php
git commit -m "feat: extend UserFactory with active/locked/admin states"
```

### Task 1.4: Create ProjectFactory

**Files:**
- Create: `apps/atomy-q/API/database/factories/ProjectFactory.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Unit/Database/Factories/ProjectFactoryTest.php
<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories;

use App\Models\Project;
use App\Models\Tenant;
use App\Models\User;
use Tests\TestCase;

final class ProjectFactoryTest extends TestCase
{
    public function test_project_factory_creates_valid_instance(): void
    {
        $project = Project::factory()->create();
        
        $this->assertInstanceOf(Project::class, $project);
        $this->assertNotEmpty($project->name);
    }
    
    public function test_project_active_state(): void
    {
        $project = Project::factory()->active()->create();
        
        $this->assertSame('active', $project->status);
    }
    
    public function test_project_planning_state(): void
    {
        $project = Project::factory()->planning()->create();
        
        $this->assertSame('planning', $project->status);
    }
    
    public function test_project_completed_state(): void
    {
        $project = Project::factory()->completed()->create();
        
        $this->assertSame('completed', $project->status);
    }
    
    public function test_project_for_tenant_relationship(): void
    {
        $tenant = Tenant::factory()->create();
        $project = Project::factory()->for($tenant)->create();
        
        $this->assertSame($tenant->id, $project->tenant_id);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter ProjectFactoryTest`
Expected: FAIL (ProjectFactory not found)

- [ ] **Step 3: Write ProjectFactory**

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Project;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

final class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->sentence(3),
            'client_id' => null,
            'start_date' => fake()->dateTimeBetween('now', '+1 month'),
            'end_date' => fake()->dateTimeBetween('+2 months', '+12 months'),
            'project_manager_id' => User::factory(),
            'status' => 'active',
            'budget_type' => fake()->randomElement(['fixed', 'time_and_materials', 'cost_plus']),
            'completion_percentage' => 0.0,
        ];
    }

    public function planning(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'planning',
            'completion_percentage' => 0.0,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'completion_percentage' => fake()->randomFloat(2, 1, 75),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'completion_percentage' => 100.0,
        ]);
    }

    public function onHold(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'on_hold',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Project $project): void {
            if ($project->tenant_id && !$project->project_manager_id) {
                $project->project_manager_id = User::factory()
                    ->for($project->tenant, 'tenant')
                    ->create()
                    ->getKey();
                $project->save();
            }
        });
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter ProjectFactoryTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add apps/atomy-q/API/database/factories/ProjectFactory.php
git commit -m "feat: create ProjectFactory with planning/active/completed/on_hold/cancelled states"
```

### Task 1.5: Create RfqFactory

**Files:**
- Create: `apps/atomy-q/API/database/factories/RfqFactory.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Unit/Database/Factories/RfqFactoryTest.php
<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories;

use App\Models\Rfq;
use App\Models\Project;
use App\Models\Tenant;
use App\Models\User;
use Tests\TestCase;

final class RfqFactoryTest extends TestCase
{
    public function test_rfq_factory_creates_valid_instance(): void
    {
        $rfq = Rfq::factory()->create();
        
        $this->assertInstanceOf(Rfq::class, $rfq);
        $this->assertNotEmpty($rfq->rfq_number);
    }
    
    public function test_rfq_draft_state(): void
    {
        $rfq = Rfq::factory()->draft()->create();
        
        $this->assertSame('draft', $rfq->status);
    }
    
    public function test_rfq_published_state(): void
    {
        $rfq = Rfq::factory()->published()->create();
        
        $this->assertSame('published', $rfq->status);
    }
    
    public function test_rfq_closed_state(): void
    {
        $rfq = Rfq::factory()->closed()->create();
        
        $this->assertSame('closed', $rfq->status);
    }
    
    public function test_rfq_awarded_state(): void
    {
        $rfq = Rfq::factory()->awarded()->create();
        
        $this->assertSame('awarded', $rfq->status);
    }
    
    public function test_rfq_for_project_relationship(): void
    {
        $project = Project::factory()->create();
        $rfq = Rfq::factory()->for($project)->create();
        
        $this->assertSame($project->id, $rfq->project_id);
    }
    
    public function test_rfq_owned_by_relationship(): void
    {
        $user = User::factory()->create();
        $rfq = Rfq::factory()->ownedBy($user)->create();
        
        $this->assertSame($user->id, $rfq->owner_id);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter RfqFactoryTest`
Expected: FAIL (RfqFactory not found)

- [ ] **Step 3: Write RfqFactory**

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ComparisonRun;
use App\Models\Approval;
use App\Models\Rfq;
use App\Models\Project;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Award;
use Illuminate\Database\Eloquent\Factories\Factory;

final class RfqFactory extends Factory
{
    protected $model = Rfq::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'rfq_number' => 'RFQ-' . fake()->unique()->numerify('####'),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'category' => fake()->randomElement([
                'Rotating equipment',
                'Instrumentation',
                'Valves & piping',
                'Electrical',
                'Instrumentation',
                'Process equipment',
            ]),
            'department' => fake()->randomElement(['Maintenance', 'Projects', 'Operations']),
            'status' => 'draft',
            'owner_id' => User::factory(),
            'project_id' => Project::factory(),
            'estimated_value' => fake()->randomFloat(2, 10000, 500000),
            'savings_percentage' => fake()->randomFloat(2, 2.5, 18.0),
            'submission_deadline' => fake()->dateTimeBetween('+1 week', '+4 weeks'),
            'closing_date' => fake()->dateTimeBetween('+2 weeks', '+6 weeks'),
            'expected_award_at' => fake()->dateTimeBetween('+3 weeks', '+8 weeks'),
            'technical_review_due_at' => fake()->dateTimeBetween('+2 weeks', '+5 weeks'),
            'financial_review_due_at' => fake()->dateTimeBetween('+2 weeks', '+5 weeks'),
            'payment_terms' => fake()->randomElement(['Net 30', 'Net 45 EOM', 'Net 60']),
            'evaluation_method' => 'weighted',
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'submission_deadline' => fake()->dateTimeBetween('+1 week', '+4 weeks'),
            'closing_date' => fake()->dateTimeBetween('+2 weeks', '+6 weeks'),
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'submission_deadline' => fake()->dateTimeBetween('+3 days', '+2 weeks'),
            'closing_date' => fake()->dateTimeBetween('+1 week', '+3 weeks'),
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'closed',
            'closing_date' => fake()->dateTimeBetween('-2 weeks', '-1 day'),
        ]);
    }

    public function awarded(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'awarded',
            'closing_date' => fake()->dateTimeBetween('-3 weeks', '-1 week'),
        ])->afterCreating(function (Rfq $rfq): void {
            ComparisonRun::factory()
                ->for($rfq)
                ->for($rfq->owner, 'creator')
                ->final()
                ->create();
            
            Approval::factory()
                ->for($rfq)
                ->approved()
                ->create();
                
            Award::factory()
                ->for($rfq)
                ->signedOff()
                ->create();
        });
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'closing_date' => fake()->dateTimeBetween('-4 weeks', '-2 weeks'),
        ]);
    }

    public function forProject(Project $project): static
    {
        return $this->state(fn (array $attributes) => [
            'project_id' => $project->id,
            'tenant_id' => $project->tenant_id,
        ]);
    }

    public function ownedBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'owner_id' => $user->id,
            'tenant_id' => $user->tenant_id,
        ]);
    }

    public function withLineItems(int $count = 3): static
    {
        return $this->has(RfqLineItem::factory()->count($count));
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter RfqFactoryTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add apps/atomy-q/API/database/factories/RfqFactory.php
git commit -m "feat: create RfqFactory with draft/published/closed/awarded/cancelled states"
```

### Task 1.6: Verify Phase 1 Complete

- [ ] **Step 1: Run all Phase 1 tests**

Run: `php artisan test --filter "TenantFactoryTest|ProjectFactoryTest|RfqFactoryTest"`
Expected: ALL PASS

- [ ] **Step 2: Commit Phase completion**

```bash
git add . && git commit -m "feat: complete Phase 1 foundation factories (Tenant, User, Project, Rfq)"
```

---

## Task 2: Phase 2 - Core Workflow Factories

### Task 2.1: Add HasFactory Trait to Vendor, QuoteSubmission, RfqLineItem, VendorInvitation

**Files:**
- Modify: `apps/atomy-q/API/app/Models/Vendor.php`
- Modify: `apps/atomy-q/API/app/Models/QuoteSubmission.php`
- Modify: `apps/atomy-q/API/app/Models/RfqLineItem.php`
- Modify: `apps/atomy-q/API/app/Models/VendorInvitation.php`

- [ ] **Step 1: Add HasFactory trait to Vendor model**

```php
use Illuminate\Database\Eloquent\Factories\HasFactory;

// In Vendor class:
use HasFactory;
```

- [ ] **Step 2: Add HasFactory trait to QuoteSubmission model**

```php
use Illuminate\Database\Eloquent\Factories\HasFactory;

// In QuoteSubmission class:
use HasFactory;
```

- [ ] **Step 3: Add HasFactory trait to RfqLineItem model**

```php
use Illuminate\Database\Eloquent\Factories\HasFactory;

// In RfqLineItem class:
use HasFactory;
```

- [ ] **Step 4: Add HasFactory trait to VendorInvitation model**

```php
use Illuminate\Database\Eloquent\Factories\HasFactory;

// In VendorInvitation class:
use HasFactory;
```

- [ ] **Step 5: Commit**

```bash
git add apps/atomy-q/API/app/Models/Vendor.php apps/atomy-q/API/app/Models/QuoteSubmission.php apps/atomy-q/API/app/Models/RfqLineItem.php apps/atomy-q/API/app/Models/VendorInvitation.php
git commit -m "feat: add HasFactory trait to Vendor, QuoteSubmission, RfqLineItem, VendorInvitation"
```

### Task 2.2: Create VendorFactory

**Files:**
- Create: `apps/atomy-q/API/database/factories/VendorFactory.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Unit/Database/Factories/VendorFactoryTest.php
<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories;

use App\Models\Vendor;
use Tests\TestCase;

final class VendorFactoryTest extends TestCase
{
    public function test_vendor_factory_creates_valid_instance(): void
    {
        $vendor = Vendor::factory()->create();
        
        $this->assertInstanceOf(Vendor::class, $vendor);
        $this->assertNotEmpty($vendor->legal_name);
    }
    
    public function test_vendor_approved_state(): void
    {
        $vendor = Vendor::factory()->approved()->create();
        
        $this->assertSame('approved', $vendor->status);
    }
    
    public function test_vendor_restricted_state(): void
    {
        $vendor = Vendor::factory()->restricted()->create();
        
        $this->assertSame('restricted', $vendor->status);
    }
    
    public function test_vendor_under_review_state(): void
    {
        $vendor = Vendor::factory()->underReview()->create();
        
        $this->assertSame('under_review', $vendor->status);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter VendorFactoryTest`
Expected: FAIL (VendorFactory not found)

- [ ] **Step 3: Write VendorFactory**

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Vendor;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

final class VendorFactory extends Factory
{
    protected $model = Vendor::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'registration_number' => fake()->unique()->numerify('##########'),
            'tax_id' => fake()->unique()->numerify('##########'),
            'legal_name' => fake()->company(),
            'display_name' => fake()->company(),
            'country_of_registration' => fake()->countryCode(),
            'primary_contact_name' => fake()->name(),
            'primary_contact_email' => fake()->companyEmail(),
            'primary_contact_phone' => fake()->phoneNumber(),
            'status' => 'pending',
            'onboarded_at' => null,
            'metadata' => [],
            'approved_by_user_id' => null,
            'approved_at' => null,
            'approval_note' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'onboarded_at' => now(),
            'approved_at' => now(),
        ]);
    }

    public function restricted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'restricted',
        ]);
    }

    public function underReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'under_review',
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended',
        ]);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter VendorFactoryTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add apps/atomy-q/API/database/factories/VendorFactory.php
git commit -m "feat: create VendorFactory with approved/restricted/under_review/suspended states"
```

### Task 2.3: Create QuoteSubmissionFactory

**Files:**
- Create: `apps/atomy-q/API/database/factories/QuoteSubmissionFactory.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Unit/Database/Factories/QuoteSubmissionFactoryTest.php
<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories;

use App\Models\QuoteSubmission;
use Tests\TestCase;

final class QuoteSubmissionFactoryTest extends TestCase
{
    public function test_quote_submission_factory_creates_valid_instance(): void
    {
        $quote = QuoteSubmission::factory()->create();
        
        $this->assertInstanceOf(QuoteSubmission::class, $quote);
        $this->assertNotEmpty($quote->vendor_name);
    }
    
    public function test_quote_uploaded_state(): void
    {
        $quote = QuoteSubmission::factory()->uploaded()->create();
        
        $this->assertSame('uploaded', $quote->status);
    }
    
    public function test_quote_extracting_state(): void
    {
        $quote = QuoteSubmission::factory()->extracting()->create();
        
        $this->assertSame('extracting', $quote->status);
    }
    
    public function test_quote_extracted_state(): void
    {
        $quote = QuoteSubmission::factory()->extracted()->create();
        
        $this->assertSame('extracted', $quote->status);
    }
    
    public function test_quote_normalizing_state(): void
    {
        $quote = QuoteSubmission::factory()->normalizing()->create();
        
        $this->assertSame('normalizing', $quote->status);
    }
    
    public function test_quote_ready_state(): void
    {
        $quote = QuoteSubmission::factory()->ready()->create();
        
        $this->assertSame('ready', $quote->status);
    }
    
    public function test_quote_needs_review_state(): void
    {
        $quote = QuoteSubmission::factory()->needsReview()->create();
        
        $this->assertSame('needs_review', $quote->status);
    }
    
    public function test_quote_failed_state(): void
    {
        $quote = QuoteSubmission::factory()->failed()->create();
        
        $this->assertSame('failed', $quote->status);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter QuoteSubmissionFactoryTest`
Expected: FAIL (QuoteSubmissionFactory not found)

- [ ] **Step 3: Write QuoteSubmissionFactory**

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\QuoteSubmission;
use App\Models\Rfq;
use App\Models\Vendor;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

final class QuoteSubmissionFactory extends Factory
{
    protected $model = QuoteSubmission::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'rfq_id' => Rfq::factory(),
            'vendor_id' => Vendor::factory(),
            'vendor_name' => fake()->company(),
            'uploaded_by' => User::factory(),
            'file_path' => 'quotes/' . fake()->uuid() . '.pdf',
            'file_type' => 'application/pdf',
            'original_filename' => fake()->word() . '.pdf',
            'status' => 'uploaded',
            'submitted_at' => now(),
            'confidence' => null,
            'line_items_count' => 0,
            'warnings_count' => 0,
            'errors_count' => 0,
            'error_code' => null,
            'error_message' => null,
            'processing_started_at' => null,
            'processing_completed_at' => null,
            'parsed_at' => null,
            'retry_count' => 0,
        ];
    }

    public function uploaded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'uploaded',
            'submitted_at' => now(),
        ]);
    }

    public function extracting(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'extracting',
            'processing_started_at' => now(),
        ]);
    }

    public function extracted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'extracted',
            'processing_started_at' => now(),
            'processing_completed_at' => now(),
        ]);
    }

    public function normalizing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'normalizing',
            'processing_started_at' => now(),
            'processing_completed_at' => now(),
        ]);
    }

    public function ready(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'ready',
            'processing_started_at' => now(),
            'processing_completed_at' => now(),
            'parsed_at' => now(),
            'confidence' => fake()->randomFloat(2, 0.85, 1.0),
            'line_items_count' => fake()->numberBetween(3, 25),
            'warnings_count' => fake()->numberBetween(0, 3),
        ]);
    }

    public function needsReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'needs_review',
            'processing_started_at' => now(),
            'processing_completed_at' => now(),
            'parsed_at' => now(),
            'confidence' => fake()->randomFloat(2, 0.5, 0.84),
            'line_items_count' => fake()->numberBetween(3, 25),
            'warnings_count' => fake()->numberBetween(4, 10),
        ]);
    }

    public function failed(string $errorCode = 'EXTRACTION_FAILED'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'error_code' => $errorCode,
            'error_message' => fake()->sentence(),
            'processing_started_at' => now(),
            'processing_completed_at' => now(),
        ]);
    }

    public function forRfq(Rfq $rfq): static
    {
        return $this->state(fn (array $attributes) => [
            'rfq_id' => $rfq->id,
            'tenant_id' => $rfq->tenant_id,
        ]);
    }

    public function forVendor(Vendor $vendor): static
    {
        return $this->state(fn (array $attributes) => [
            'vendor_id' => $vendor->id,
            'vendor_name' => $vendor->legal_name,
            'tenant_id' => $vendor->tenant_id,
        ]);
    }

    public function withQuotes(int $count = 5): static
    {
        return $this->has(NormalizationSourceLine::factory()->count($count));
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter QuoteSubmissionFactoryTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add apps/atomy-q/API/database/factories/QuoteSubmissionFactory.php
git commit -m "feat: create QuoteSubmissionFactory with full state machine"
```

### Task 2.4: Create RfqLineItemFactory

**Files:**
- Create: `apps/atomy-q/API/database/factories/RfqLineItemFactory.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Unit/Database/Factories/RfqLineItemFactoryTest.php
<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories;

use App\Models\RfqLineItem;
use Tests\TestCase;

final class RfqLineItemFactoryTest extends TestCase
{
    public function test_rfq_line_item_factory_creates_valid_instance(): void
    {
        $lineItem = RfqLineItem::factory()->create();
        
        $this->assertInstanceOf(RfqLineItem::class, $lineItem);
        $this->assertNotEmpty($lineItem->description);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter RfqLineItemFactoryTest`
Expected: FAIL

- [ ] **Step 3: Write RfqLineItemFactory**

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\RfqLineItem;
use App\Models\Rfq;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

final class RfqLineItemFactory extends Factory
{
    protected $model = RfqLineItem::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'rfq_id' => Rfq::factory(),
            'description' => fake()->sentence(6),
            'quantity' => fake()->randomFloat(4, 1, 100),
            'uom' => fake()->randomElement(['EA', 'KG', 'L', 'M', 'SET', 'BOX']),
            'unit_price' => fake()->randomFloat(2, 10, 5000),
            'currency' => 'USD',
            'specifications' => [],
            'sort_order' => 0,
        ];
    }

    public function forRfq(Rfq $rfq): static
    {
        return $this->state(fn (array $attributes) => [
            'rfq_id' => $rfq->id,
            'tenant_id' => $rfq->tenant_id,
        ]);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter RfqLineItemFactoryTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add apps/atomy-q/API/database/factories/RfqLineItemFactory.php
git commit -m "feat: create RfqLineItemFactory"
```

### Task 2.5: Create VendorInvitationFactory

**Files:**
- Create: `apps/atomy-q/API/database/factories/VendorInvitationFactory.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Unit/Database/Factories/VendorInvitationFactoryTest.php
<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories;

use App\Models\VendorInvitation;
use Tests\TestCase;

final class VendorInvitationFactoryTest extends TestCase
{
    public function test_vendor_invitation_factory_creates_valid_instance(): void
    {
        $invitation = VendorInvitation::factory()->create();
        
        $this->assertInstanceOf(VendorInvitation::class, $invitation);
    }
    
    public function test_vendor_invitation_pending_state(): void
    {
        $invitation = VendorInvitation::factory()->pending()->create();
        
        $this->assertSame('pending', $invitation->status);
    }
    
    public function test_vendor_invitation_accepted_state(): void
    {
        $invitation = VendorInvitation::factory()->accepted()->create();
        
        $this->assertSame('accepted', $invitation->status);
    }
    
    public function test_vendor_invitation_declined_state(): void
    {
        $invitation = VendorInvitation::factory()->declined()->create();
        
        $this->assertSame('declined', $invitation->status);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter VendorInvitationFactoryTest`
Expected: FAIL

- [ ] **Step 3: Write VendorInvitationFactory**

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\VendorInvitation;
use App\Models\Rfq;
use App\Models\Vendor;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

final class VendorInvitationFactory extends Factory
{
    protected $model = VendorInvitation::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'rfq_id' => Rfq::factory(),
            'vendor_id' => Vendor::factory(),
            'invited_by' => User::factory(),
            'status' => 'pending',
            'invited_at' => now(),
            'responded_at' => null,
            'response_note' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'responded_at' => null,
        ]);
    }

    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'accepted',
            'responded_at' => now(),
        ]);
    }

    public function declined(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'declined',
            'responded_at' => now(),
            'response_note' => fake()->sentence(),
        ]);
    }

    public function forRfq(Rfq $rfq): static
    {
        return $this->state(fn (array $attributes) => [
            'rfq_id' => $rfq->id,
            'tenant_id' => $rfq->tenant_id,
        ]);
    }

    public function forVendor(Vendor $vendor): static
    {
        return $this->state(fn (array $attributes) => [
            'vendor_id' => $vendor->id,
            'tenant_id' => $vendor->tenant_id,
        ]);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter VendorInvitationFactoryTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add apps/atomy-q/API/database/factories/VendorInvitationFactory.php
git commit -m "feat: create VendorInvitationFactory with pending/accepted/declined states"
```

### Task 2.6: Verify Phase 2 Complete

- [ ] **Step 1: Run all Phase 2 tests**

Run: `php artisan test --filter "VendorFactoryTest|QuoteSubmissionFactoryTest|RfqLineItemFactoryTest|VendorInvitationFactoryTest"`
Expected: ALL PASS

- [ ] **Step 2: Commit Phase completion**

```bash
git add . && git commit -m "feat: complete Phase 2 core workflow factories"
```

---

## Task 3: Phase 3 - Approval Flow Factories

### Task 3.1: Add HasFactory Trait to Approval, ComparisonRun, Award

**Files:**
- Modify: `apps/atomy-q/API/app/Models/Approval.php`
- Modify: `apps/atomy-q/API/app/Models/ComparisonRun.php`
- Modify: `apps/atomy-q/API/app/Models/Award.php`

- [ ] **Step 1: Add HasFactory trait to Approval model**

```php
use Illuminate\Database\Eloquent\Factories\HasFactory;

// In Approval class:
use HasFactory;
```

- [ ] **Step 2: Add HasFactory trait to ComparisonRun model**

```php
use Illuminate\Database\Eloquent\Factories\HasFactory;

// In ComparisonRun class:
use HasFactory;
```

- [ ] **Step 3: Add HasFactory trait to Award model**

```php
use Illuminate\Database\Eloquent\Factories\HasFactory;

// In Award class:
use HasFactory;
```

- [ ] **Step 4: Commit**

```bash
git add apps/atomy-q/API/app/Models/Approval.php apps/atomy-q/API/app/Models/ComparisonRun.php apps/atomy-q/API/app/Models/Award.php
git commit -m "feat: add HasFactory trait to Approval, ComparisonRun, Award"
```

### Task 3.2: Create ComparisonRunFactory

**Files:**
- Create: `apps/atomy-q/API/database/factories/ComparisonRunFactory.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Unit/Database/Factories/ComparisonRunFactoryTest.php
<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories;

use App\Models\ComparisonRun;
use Tests\TestCase;

final class ComparisonRunFactoryTest extends TestCase
{
    public function test_comparison_run_factory_creates_valid_instance(): void
    {
        $comparison = ComparisonRun::factory()->create();
        
        $this->assertInstanceOf(ComparisonRun::class, $comparison);
    }
    
    public function test_comparison_draft_state(): void
    {
        $comparison = ComparisonRun::factory()->draft()->create();
        
        $this->assertSame('draft', $comparison->status);
    }
    
    public function test_comparison_final_state(): void
    {
        $comparison = ComparisonRun::factory()->final()->create();
        
        $this->assertSame('final', $comparison->status);
    }
    
    public function test_comparison_preview_state(): void
    {
        $comparison = ComparisonRun::factory()->preview()->create();
        
        $this->assertSame('draft', $comparison->status);
        $this->assertTrue($comparison->is_preview);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter ComparisonRunFactoryTest`
Expected: FAIL

- [ ] **Step 3: Write ComparisonRunFactory**

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ComparisonRun;
use App\Models\Rfq;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

final class ComparisonRunFactory extends Factory
{
    protected $model = ComparisonRun::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'rfq_id' => Rfq::factory(),
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'idempotency_key' => fake()->uuid(),
            'is_preview' => false,
            'created_by' => User::factory(),
            'request_payload' => [],
            'matrix_payload' => [],
            'scoring_payload' => [],
            'approval_payload' => [],
            'response_payload' => [],
            'readiness_payload' => [],
            'status' => 'draft',
            'version' => 1,
            'expires_at' => now()->addDays(30),
            'discarded_at' => null,
            'discarded_by' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'is_preview' => false,
        ]);
    }

    public function final(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'final',
            'is_preview' => false,
            'matrix_payload' => [
                'items' => fake()->numberBetween(3, 10),
            ],
            'scoring_payload' => [
                'model' => 'weighted',
            ],
        ]);
    }

    public function preview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'is_preview' => true,
        ]);
    }

    public function forRfq(Rfq $rfq): static
    {
        return $this->state(fn (array $attributes) => [
            'rfq_id' => $rfq->id,
            'tenant_id' => $rfq->tenant_id,
        ]);
    }

    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user->id,
            'tenant_id' => $user->tenant_id,
        ]);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter ComparisonRunFactoryTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add apps/atomy-q/API/database/factories/ComparisonRunFactory.php
git commit -m "feat: create ComparisonRunFactory with draft/final/preview states"
```

### Task 3.3: Create ApprovalFactory

**Files:**
- Create: `apps/atomy-q/API/database/factories/ApprovalFactory.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Unit/Database/Factories/ApprovalFactoryTest.php
<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories;

use App\Models\Approval;
use Tests\TestCase;

final class ApprovalFactoryTest extends TestCase
{
    public function test_approval_factory_creates_valid_instance(): void
    {
        $approval = Approval::factory()->create();
        
        $this->assertInstanceOf(Approval::class, $approval);
    }
    
    public function test_approval_pending_state(): void
    {
        $approval = Approval::factory()->pending()->create();
        
        $this->assertSame('pending', $approval->status);
    }
    
    public function test_approval_approved_state(): void
    {
        $approval = Approval::factory()->approved()->create();
        
        $this->assertSame('approved', $approval->status);
    }
    
    public function test_approval_rejected_state(): void
    {
        $approval = Approval::factory()->rejected()->create();
        
        $this->assertSame('rejected', $approval->status);
    }
    
    public function test_approval_snoozed_state(): void
    {
        $approval = Approval::factory()->snoozed()->create();
        
        $this->assertSame('snoozed', $approval->status);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter ApprovalFactoryTest`
Expected: FAIL

- [ ] **Step 3: Write ApprovalFactory**

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Approval;
use App\Models\Rfq;
use App\Models\ComparisonRun;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

final class ApprovalFactory extends Factory
{
    protected $model = Approval::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'rfq_id' => Rfq::factory(),
            'comparison_run_id' => ComparisonRun::factory(),
            'type' => 'value_approval',
            'status' => 'pending',
            'requested_by' => User::factory(),
            'requested_at' => now(),
            'amount' => fake()->randomFloat(2, 10000, 500000),
            'currency' => 'USD',
            'level' => 1,
            'notes' => null,
            'approved_at' => null,
            'approved_by' => null,
            'snoozed_until' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'approved_at' => null,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => User::factory(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'approved_at' => now(),
            'approved_by' => User::factory(),
            'notes' => fake()->sentence(),
        ]);
    }

    public function snoozed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'snoozed',
            'snoozed_until' => fake()->dateTimeBetween('+1 day', '+7 days'),
        ]);
    }

    public function forRfq(Rfq $rfq): static
    {
        return $this->state(fn (array $attributes) => [
            'rfq_id' => $rfq->id,
            'tenant_id' => $rfq->tenant_id,
        ]);
    }

    public function forComparisonRun(ComparisonRun $comparisonRun): static
    {
        return $this->state(fn (array $attributes) => [
            'comparison_run_id' => $comparisonRun->id,
            'tenant_id' => $comparisonRun->tenant_id,
        ]);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter ApprovalFactoryTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add apps/atomy-q/API/database/factories/ApprovalFactory.php
git commit -m "feat: create ApprovalFactory with pending/approved/rejected/snoozed states"
```

### Task 3.4: Create AwardFactory

**Files:**
- Create: `apps/atomy-q/API/database/factories/AwardFactory.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Unit/Database/Factories/AwardFactoryTest.php
<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories;

use App\Models\Award;
use Tests\TestCase;

final class AwardFactoryTest extends TestCase
{
    public function test_award_factory_creates_valid_instance(): void
    {
        $award = Award::factory()->create();
        
        $this->assertInstanceOf(Award::class, $award);
    }
    
    public function test_award_pending_state(): void
    {
        $award = Award::factory()->pending()->create();
        
        $this->assertSame('pending', $award->status);
    }
    
    public function test_award_signed_off_state(): void
    {
        $award = Award::factory()->signedOff()->create();
        
        $this->assertSame('signed_off', $award->status);
    }
    
    public function test_award_protested_state(): void
    {
        $award = Award::factory()->protested()->create();
        
        $this->assertSame('protested', $award->status);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter AwardFactoryTest`
Expected: FAIL

- [ ] **Step 3: Write AwardFactory**

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Award;
use App\Models\Rfq;
use App\Models\ComparisonRun;
use App\Models\Vendor;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

final class AwardFactory extends Factory
{
    protected $model = Award::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'rfq_id' => Rfq::factory(),
            'comparison_run_id' => ComparisonRun::factory(),
            'vendor_id' => Vendor::factory(),
            'status' => 'pending',
            'amount' => fake()->randomFloat(2, 10000, 500000),
            'currency' => 'USD',
            'split_details' => [],
            'protest_id' => null,
            'signoff_at' => null,
            'signed_off_by' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'signoff_at' => null,
        ]);
    }

    public function signedOff(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'signed_off',
            'signoff_at' => now(),
            'signed_off_by' => User::factory(),
        ]);
    }

    public function protested(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'protested',
            'protest_id' => fake()->uuid(),
        ]);
    }

    public function forRfq(Rfq $rfq): static
    {
        return $this->state(fn (array $attributes) => [
            'rfq_id' => $rfq->id,
            'tenant_id' => $rfq->tenant_id,
        ]);
    }

    public function forVendor(Vendor $vendor): static
    {
        return $this->state(fn (array $attributes) => [
            'vendor_id' => $vendor->id,
            'tenant_id' => $vendor->tenant_id,
        ]);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter AwardFactoryTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add apps/atomy-q/API/database/factories/AwardFactory.php
git commit -m "feat: create AwardFactory with pending/signed_off/protested states"
```

### Task 3.5: Verify Phase 3 Complete

- [ ] **Step 1: Run all Phase 3 tests**

Run: `php artisan test --filter "ComparisonRunFactoryTest|ApprovalFactoryTest|AwardFactoryTest"`
Expected: ALL PASS

- [ ] **Step 2: Commit Phase completion**

```bash
git add . && git commit -m "feat: complete Phase 3 approval flow factories"
```

---

## Task 4: Phase 4 - Supporting Factories

### Task 4.1: Add HasFactory Trait to ProjectAcl, ScoringModel, VendorEvidence, VendorFinding

**Files:**
- Modify: `apps/atomy-q/API/app/Models/ProjectAcl.php`
- Modify: `apps/atomy-q/API/app/Models/ScoringModel.php`
- Modify: `apps/atomy-q/API/app/Models/VendorEvidence.php`
- Modify: `apps/atomy-q/API/app/Models/VendorFinding.php`

- [ ] **Step 1: Add HasFactory trait to each model**

```php
use Illuminate\Database\Eloquent\Factories\HasFactory;

// In each class, add: use HasFactory;
```

- [ ] **Step 2: Commit**

```bash
git add apps/atomy-q/API/app/Models/ProjectAcl.php apps/atomy-q/API/app/Models/ScoringModel.php apps/atomy-q/API/app/Models/VendorEvidence.php apps/atomy-q/API/app/Models/VendorFinding.php
git commit -m "feat: add HasFactory trait to ProjectAcl, ScoringModel, VendorEvidence, VendorFinding"
```

### Task 4.2: Create ProjectAclFactory

**Files:**
- Create: `apps/atomy-q/API/database/factories/ProjectAclFactory.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Unit/Database/Factories/ProjectAclFactoryTest.php
<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories;

use App\Models\ProjectAcl;
use Tests\TestCase;

final class ProjectAclFactoryTest extends TestCase
{
    public function test_project_acl_factory_creates_valid_instance(): void
    {
        $acl = ProjectAcl::factory()->create();
        
        $this->assertInstanceOf(ProjectAcl::class, $acl);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter ProjectAclFactoryTest`
Expected: FAIL

- [ ] **Step 3: Write ProjectAclFactory**

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ProjectAcl;
use App\Models\Project;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

final class ProjectAclFactory extends Factory
{
    protected $model = ProjectAcl::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'project_id' => Project::factory(),
            'user_id' => User::factory(),
            'role' => 'viewer',
        ];
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter ProjectAclFactoryTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add apps/atomy-q/API/database/factories/ProjectAclFactory.php
git commit -m "feat: create ProjectAclFactory"
```

### Task 4.3: Create ScoringModelFactory

**Files:**
- Create: `apps/atomy-q/API/database/factories/ScoringModelFactory.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Unit/Database/Factories/ScoringModelFactoryTest.php
<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories;

use App\Models\ScoringModel;
use Tests\TestCase;

final class ScoringModelFactoryTest extends TestCase
{
    public function test_scoring_model_factory_creates_valid_instance(): void
    {
        $model = ScoringModel::factory()->create();
        
        $this->assertInstanceOf(ScoringModel::class, $model);
    }
    
    public function test_scoring_model_active_state(): void
    {
        $model = ScoringModel::factory()->active()->create();
        
        $this->assertSame('active', $model->status);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter ScoringModelFactoryTest`
Expected: FAIL

- [ ] **Step 3: Write ScoringModelFactory**

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ScoringModel;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

final class ScoringModelFactory extends Factory
{
    protected $model = ScoringModel::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->sentence(2),
            'description' => fake()->paragraph(),
            'status' => 'pending',
            'created_by' => User::factory(),
            'version' => 1,
            'policy_id' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter ScoringModelFactoryTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add apps/atomy-q/API/database/factories/ScoringModelFactory.php
git commit -m "feat: create ScoringModelFactory with active state"
```

### Task 4.4: Create VendorEvidenceFactory

**Files:**
- Create: `apps/atomy-q/API/database/factories/VendorEvidenceFactory.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Unit/Database/Factories/VendorEvidenceFactoryTest.php
<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories;

use App\Models\VendorEvidence;
use Tests\TestCase;

final class VendorEvidenceFactoryTest extends TestCase
{
    public function test_vendor_evidence_factory_creates_valid_instance(): void
    {
        $evidence = VendorEvidence::factory()->create();
        
        $this->assertInstanceOf(VendorEvidence::class, $evidence);
    }
    
    public function test_vendor_evidence_pending_review_state(): void
    {
        $evidence = VendorEvidence::factory()->pendingReview()->create();
        
        $this->assertSame('pending_review', $evidence->status);
    }
    
    public function test_vendor_evidence_reviewed_state(): void
    {
        $evidence = VendorEvidence::factory()->reviewed()->create();
        
        $this->assertSame('reviewed', $evidence->status);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter VendorEvidenceFactoryTest`
Expected: FAIL

- [ ] **Step 3: Write VendorEvidenceFactory**

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\VendorEvidence;
use App\Models\Vendor;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

final class VendorEvidenceFactory extends Factory
{
    protected $model = VendorEvidence::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'vendor_id' => Vendor::factory(),
            'type' => fake()->randomElement(['certification', 'insurance', 'financial', 'compliance']),
            'name' => fake()->sentence(3),
            'status' => 'pending',
            'file_path' => 'evidence/' . fake()->uuid() . '.pdf',
            'uploaded_by' => User::factory(),
            'reviewed_by' => null,
            'reviewed_at' => null,
            'notes' => null,
        ];
    }

    public function pendingReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending_review',
        ]);
    }

    public function reviewed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'reviewed',
            'reviewed_by' => User::factory(),
            'reviewed_at' => now(),
        ]);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter VendorEvidenceFactoryTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add apps/atomy-q/API/database/factories/VendorEvidenceFactory.php
git commit -m "feat: create VendorEvidenceFactory with pending_review/reviewed states"
```

### Task 4.5: Create VendorFindingFactory

**Files:**
- Create: `apps/atomy-q/API/database/factories/VendorFindingFactory.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Unit/Database/Factories/VendorFindingFactoryTest.php
<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories;

use App\Models\VendorFinding;
use Tests\TestCase;

final class VendorFindingFactoryTest extends TestCase
{
    public function test_vendor_finding_factory_creates_valid_instance(): void
    {
        $finding = VendorFinding::factory()->create();
        
        $this->assertInstanceOf(VendorFinding::class, $finding);
    }
    
    public function test_vendor_finding_open_state(): void
    {
        $finding = VendorFinding::factory()->open()->create();
        
        $this->assertSame('open', $finding->status);
    }
    
    public function test_vendor_finding_resolved_state(): void
    {
        $finding = VendorFinding::factory()->resolved()->create();
        
        $this->assertSame('resolved', $finding->status);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter VendorFindingFactoryTest`
Expected: FAIL

- [ ] **Step 3: Write VendorFindingFactory**

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\VendorFinding;
use App\Models\Vendor;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

final class VendorFindingFactory extends Factory
{
    protected $model = VendorFinding::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'vendor_id' => Vendor::factory(),
            'severity' => fake()->randomElement(['low', 'medium', 'high', 'critical']),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'status' => 'open',
            'assigned_to' => User::factory(),
            'resolved_by' => null,
            'resolved_at' => null,
            'resolution_notes' => null,
        ];
    }

    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'open',
        ]);
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'resolved',
            'resolved_by' => User::factory(),
            'resolved_at' => now(),
            'resolution_notes' => fake()->sentence(),
        ]);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter VendorFindingFactoryTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add apps/atomy-q/API/database/factories/VendorFindingFactory.php
git commit -m "feat: create VendorFindingFactory with open/resolved states"
```

### Task 4.6: Verify Phase 4 Complete

- [ ] **Step 1: Run all Phase 4 tests**

Run: `php artisan test --filter "ProjectAclFactoryTest|ScoringModelFactoryTest|VendorEvidenceFactoryTest|VendorFindingFactoryTest"`
Expected: ALL PASS

- [ ] **Step 2: Commit Phase completion**

```bash
git add . && git commit -m "feat: complete Phase 4 supporting factories"
```

---

## Task 5: Phase 5 - Specialized Factories

### Task 5.1: Add HasFactory Trait to Remaining Models

**Files:**
- Modify: `apps/atomy-q/API/app/Models/NormalizationSourceLine.php`
- Modify: `apps/atomy-q/API/app/Models/RfqTemplate.php`
- Modify: `apps/atomy-q/API/app/Models/Task.php`
- Modify: `apps/atomy-q/API/app/Models/ApprovalHistory.php`
- Modify: `apps/atomy-q/API/app/Models/Scenario.php`
- Modify: `apps/atomy-q/API/app/Models/NormalizationConflict.php`

- [ ] **Step 1: Add HasFactory trait to each model**

```php
use Illuminate\Database\Eloquent\Factories\HasFactory;

// In each class, add: use HasFactory;
```

- [ ] **Step 2: Commit**

```bash
git add apps/atomy-q/API/app/Models/NormalizationSourceLine.php apps/atomy-q/API/app/Models/RfqTemplate.php apps/atomy-q/API/app/Models/Task.php apps/atomy-q/API/app/Models/ApprovalHistory.php apps/atomy-q/API/app/Models/Scenario.php apps/atomy-q/API/app/Models/NormalizationConflict.php
git commit -m "feat: add HasFactory trait to remaining models"
```

### Task 5.2: Create NormalizationSourceLineFactory

**Files:**
- Create: `apps/atomy-q/API/database/factories/NormalizationSourceLineFactory.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Unit/Database/Factories/NormalizationSourceLineFactoryTest.php
<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories;

use App\Models\NormalizationSourceLine;
use Tests\TestCase;

final class NormalizationSourceLineFactoryTest extends TestCase
{
    public function test_normalization_source_line_factory_creates_valid_instance(): void
    {
        $line = NormalizationSourceLine::factory()->create();
        
        $this->assertInstanceOf(NormalizationSourceLine::class, $line);
    }
    
    public function test_normalization_source_line_with_override_state(): void
    {
        $line = NormalizationSourceLine::factory()->withOverride()->create();
        
        $this->assertNotEmpty($line->override_value);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter NormalizationSourceLineFactoryTest`
Expected: FAIL

- [ ] **Step 3: Write NormalizationSourceLineFactory**

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\NormalizationSourceLine;
use App\Models\QuoteSubmission;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

final class NormalizationSourceLineFactory extends Factory
{
    protected $model = NormalizationSourceLine::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'quote_submission_id' => QuoteSubmission::factory(),
            'source_line_number' => fake()->numberBetween(1, 50),
            'source_value' => fake()->randomFloat(2, 10, 1000),
            'source_description' => fake()->sentence(4),
            'matched_line_id' => null,
            'normalized_value' => null,
            'normalized_description' => null,
            'override_value' => null,
            'override_reason' => null,
            'confidence' => fake()->randomFloat(2, 0.8, 1.0),
            'status' => 'pending',
        ];
    }

    public function withOverride(): static
    {
        return $this->state(fn (array $attributes) => [
            'override_value' => fake()->randomFloat(2, 10, 1000),
            'override_reason' => fake()->sentence(),
        ]);
    }

    public function forQuote(QuoteSubmission $quote): static
    {
        return $this->state(fn (array $attributes) => [
            'quote_submission_id' => $quote->id,
            'tenant_id' => $quote->tenant_id,
        ]);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter NormalizationSourceLineFactoryTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add apps/atomy-q/API/database/factories/NormalizationSourceLineFactory.php
git commit -m "feat: create NormalizationSourceLineFactory with override state"
```

### Task 5.3: Create RfqTemplateFactory

**Files:**
- Create: `apps/atomy-q/API/database/factories/RfqTemplateFactory.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Unit/Database/Factories/RfqTemplateFactoryTest.php
<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories;

use App\Models\RfqTemplate;
use Tests\TestCase;

final class RfqTemplateFactoryTest extends TestCase
{
    public function test_rfq_template_factory_creates_valid_instance(): void
    {
        $template = RfqTemplate::factory()->create();
        
        $this->assertInstanceOf(RfqTemplate::class, $template);
    }
    
    public function test_rfq_template_active_state(): void
    {
        $template = RfqTemplate::factory()->active()->create();
        
        $this->assertSame('active', $template->status);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter RfqTemplateFactoryTest`
Expected: FAIL

- [ ] **Step 3: Write RfqTemplateFactory**

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\RfqTemplate;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

final class RfqTemplateFactory extends Factory
{
    protected $model = RfqTemplate::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->sentence(2),
            'description' => fake()->paragraph(),
            'category' => fake()->randomElement(['Rotating equipment', 'Instrumentation', 'Valves & piping']),
            'department' => fake()->randomElement(['Maintenance', 'Projects', 'Operations']),
            'default_payment_terms' => 'Net 30',
            'default_evaluation_method' => 'weighted',
            'default_savings_percentage' => 10.0,
            'status' => 'draft',
            'created_by' => User::factory(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter RfqTemplateFactoryTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add apps/atomy-q/API/database/factories/RfqTemplateFactory.php
git commit -m "feat: create RfqTemplateFactory with active state"
```

### Task 5.4: Create TaskFactory

**Files:**
- Create: `apps/atomy-q/API/database/factories/TaskFactory.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Unit/Database/Factories/TaskFactoryTest.php
<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories;

use App\Models\Task;
use Tests\TestCase;

final class TaskFactoryTest extends TestCase
{
    public function test_task_factory_creates_valid_instance(): void
    {
        $task = Task::factory()->create();
        
        $this->assertInstanceOf(Task::class, $task);
    }
    
    public function test_task_completed_state(): void
    {
        $task = Task::factory()->completed()->create();
        
        $this->assertSame('completed', $task->status);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter TaskFactoryTest`
Expected: FAIL

- [ ] **Step 3: Write TaskFactory**

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Task;
use App\Models\Project;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

final class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'project_id' => Project::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'status' => 'pending',
            'assigned_to' => User::factory(),
            'due_at' => fake()->dateTimeBetween('now', '+1 month'),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }

    public function forProject(Project $project): static
    {
        return $this->state(fn (array $attributes) => [
            'project_id' => $project->id,
            'tenant_id' => $project->tenant_id,
        ]);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter TaskFactoryTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add apps/atomy-q/API/database/factories/TaskFactory.php
git commit -m "feat: create TaskFactory with completed state"
```

### Task 5.5: Create ApprovalHistoryFactory

**Files:**
- Create: `apps/atomy-q/API/database/factories/ApprovalHistoryFactory.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Unit/Database/Factories/ApprovalHistoryFactoryTest.php
<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories;

use App\Models\ApprovalHistory;
use Tests\TestCase;

final class ApprovalHistoryFactoryTest extends TestCase
{
    public function test_approval_history_factory_creates_valid_instance(): void
    {
        $history = ApprovalHistory::factory()->create();
        
        $this->assertInstanceOf(ApprovalHistory::class, $history);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter ApprovalHistoryFactoryTest`
Expected: FAIL

- [ ] **Step 3: Write ApprovalHistoryFactory**

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ApprovalHistory;
use App\Models\Approval;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

final class ApprovalHistoryFactory extends Factory
{
    protected $model = ApprovalHistory::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'approval_id' => Approval::factory(),
            'action' => fake()->randomElement(['requested', 'approved', 'rejected', 'snoozed']),
            'performed_by' => User::factory(),
            'notes' => fake()->sentence(),
        ];
    }

    public function forApproval(Approval $approval): static
    {
        return $this->state(fn (array $attributes) => [
            'approval_id' => $approval->id,
            'tenant_id' => $approval->tenant_id,
        ]);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter ApprovalHistoryFactoryTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add apps/atomy-q/API/database/factories/ApprovalHistoryFactory.php
git commit -m "feat: create ApprovalHistoryFactory"
```

### Task 5.6: Create ScenarioFactory

**Files:**
- Create: `apps/atomy-q/API/database/factories/ScenarioFactory.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Unit/Database/Factories/ScenarioFactoryTest.php
<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories;

use App\Models\Scenario;
use Tests\TestCase;

final class ScenarioFactoryTest extends TestCase
{
    public function test_scenario_factory_creates_valid_instance(): void
    {
        $scenario = Scenario::factory()->create();
        
        $this->assertInstanceOf(Scenario::class, $scenario);
    }
    
    public function test_scenario_active_state(): void
    {
        $scenario = Scenario::factory()->active()->create();
        
        $this->assertSame('active', $scenario->status);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter ScenarioFactoryTest`
Expected: FAIL

- [ ] **Step 3: Write ScenarioFactory**

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Scenario;
use App\Models\Rfq;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

final class ScenarioFactory extends Factory
{
    protected $model = Scenario::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'rfq_id' => Rfq::factory(),
            'name' => fake()->sentence(2),
            'description' => fake()->paragraph(),
            'status' => 'draft',
            'payload' => [],
            'created_by' => User::factory(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    public function forRfq(Rfq $rfq): static
    {
        return $this->state(fn (array $attributes) => [
            'rfq_id' => $rfq->id,
            'tenant_id' => $rfq->tenant_id,
        ]);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter ScenarioFactoryTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add apps/atomy-q/API/database/factories/ScenarioFactory.php
git commit -m "feat: create ScenarioFactory with active state"
```

### Task 5.7: Create NormalizationConflictFactory

**Files:**
- Create: `apps/atomy-q/API/database/factories/NormalizationConflictFactory.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Unit/Database/Factories/NormalizationConflictFactoryTest.php
<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories;

use App\Models\NormalizationConflict;
use Tests\TestCase;

final class NormalizationConflictFactoryTest extends TestCase
{
    public function test_normalization_conflict_factory_creates_valid_instance(): void
    {
        $conflict = NormalizationConflict::factory()->create();
        
        $this->assertInstanceOf(NormalizationConflict::class, $conflict);
    }
    
    public function test_normalization_conflict_resolved_state(): void
    {
        $conflict = NormalizationConflict::factory()->resolved()->create();
        
        $this->assertSame('resolved', $conflict->status);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter NormalizationConflictFactoryTest`
Expected: FAIL

- [ ] **Step 3: Write NormalizationConflictFactory**

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\NormalizationConflict;
use App\Models\Rfq;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

final class NormalizationConflictFactory extends Factory
{
    protected $model = NormalizationConflict::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'rfq_id' => Rfq::factory(),
            'line_item_description' => fake()->sentence(4),
            'source_values' => [],
            'resolved_value' => null,
            'resolved_by' => null,
            'status' => 'pending',
            'notes' => null,
        ];
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'resolved',
            'resolved_by' => User::factory(),
            'notes' => fake()->sentence(),
        ]);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter NormalizationConflictFactoryTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add apps/atomy-q/API/database/factories/NormalizationConflictFactory.php
git commit -m "feat: create NormalizationConflictFactory with resolved state"
```

### Task 5.8: Verify Phase 5 Complete

- [ ] **Step 1: Run all Phase 5 tests**

Run: `php artisan test --filter "NormalizationSourceLineFactoryTest|RfqTemplateFactoryTest|TaskFactoryTest|ApprovalHistoryFactoryTest|ScenarioFactoryTest|NormalizationConflictFactoryTest"`
Expected: ALL PASS

- [ ] **Step 2: Commit Phase completion**

```bash
git add . && git commit -m "feat: complete Phase 5 specialized factories"
```

---

## Task 6: Phase 6 - Seeder Refactor

### Task 6.1: Analyze PetrochemicalTenantSeeder Structure

**Files:**
- Read: `apps/atomy-q/API/database/seeders/PetrochemicalTenantSeeder.php`

- [ ] **Step 1: Review the seeder to understand data structure**

Read key sections of the seeder to understand:
- Tenant seeding (lines 89-130)
- User seeding (lines 150-220)
- Project/ACL seeding (lines 230-350)
- Vendor seeding (lines 400-600)
- RFQ/context building loop (lines 70-87)
- Quote/invitation/comparison/approval/award flow (per RFQ)

### Task 6.2: Rewrite PetrochemicalTenantSeeder Using Factories

**Files:**
- Modify: `apps/atomy-q/API/database/seeders/PetrochemicalTenantSeeder.php`

- [ ] **Step 1: Write the failing integration test**

```php
// tests/Integration/Database/Seeders/PetrochemicalTenantSeederTest.php
<?php

declare(strict_types=1);

namespace Tests\Integration\Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Project;
use App\Models\Rfq;
use App\Models\Vendor;
use Tests\TestCase;

final class PetrochemicalTenantSeederTest extends TestCase
{
    protected bool $seed = false;
    
    public function test_seeder_creates_correct_tenant(): void
    {
        $this->seed(PetrochemicalTenantSeeder::class);
        
        $tenant = Tenant::where('code', 'NORDFJORD')->first();
        
        $this->assertNotNull($tenant);
        $this->assertSame('Nordfjord Process Chemicals AS', $tenant->name);
    }
    
    public function test_seeder_creates_8_users(): void
    {
        $this->seed(PetrochemicalTenantSeeder::class);
        
        $tenant = Tenant::where('code', 'NORDFJORD')->first();
        
        $this->assertEquals(8, User::where('tenant_id', $tenant->id)->count());
    }
    
    public function test_seeder_creates_12_projects(): void
    {
        $this->seed(PetrochemicalTenantSeeder::class);
        
        $tenant = Tenant::where('code', 'NORDFJORD')->first();
        
        $this->assertEquals(12, Project::where('tenant_id', $tenant->id)->count());
    }
    
    public function test_seeder_creates_56_rfqs(): void
    {
        $this->seed(PetrochemicalTenantSeeder::class);
        
        $tenant = Tenant::where('code', 'NORDFJORD')->first();
        
        $this->assertEquals(56, Rfq::where('tenant_id', $tenant->id)->count());
    }
    
    public function test_seeder_creates_28_vendors(): void
    {
        $this->seed(PetrochemicalTenantSeeder::class);
        
        $tenant = Tenant::where('code', 'NORDFJORD')->first();
        
        $this->assertEquals(28, Vendor::where('tenant_id', $tenant->id)->count());
    }
}
```

- [ ] **Step 2: Run test to verify baseline**

Run: `php artisan test --filter PetrochemicalTenantSeederTest`
Expected: PASS (current seeder produces expected data)

- [ ] **Step 3: Rewrite seeder using factories**

Replace the raw DB::table()->insert() calls with factory calls. Maintain identical output:
- 1 tenant: "NORDFJORD" (Nordfjord Process Chemicals AS)
- 8 users with realistic names
- 12 projects with varied statuses
- 56 RFQs across all status types
- 28 vendors (including 8 marked risky)
- Full quote pipeline with normalization data
- Comparison runs, approvals, and awards for closed/awarded RFQs

Target: ~150 LOC (from ~1200 LOC)

```php
<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Approval;
use App\Models\Award;
use App\Models\ComparisonRun;
use App\Models\Project;
use App\Models\QuoteSubmission;
use App\Models\Rfq;
use App\Models\RfqLineItem;
use App\Models\ScoringModel;
use App\Models\ScoringPolicy;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorFinding;
use App\Models\VendorInvitation;
use Illuminate\Database\Seeder;

final class PetrochemicalTenantSeeder extends Seeder
{
    private const DEFAULT_TENANT_ID = '01KKH77M4R0V8QZ1M8NB3XWWWQ';

    private string $tenantId = '';

    public function run(): void
    {
        $this->tenantId = env('ATOMY_SEED_TENANT_ID') ?: self::DEFAULT_TENANT_ID;

        if (Rfq::where('tenant_id', $this->tenantId)->exists()) {
            return;
        }

        $tenant = $this->seedTenant();
        $users = $this->seedUsers($tenant);
        $projects = $this->seedProjects($tenant, $users->first());
        $scoringModel = $this->seedScoringModel($tenant, $users->first());
        $vendors = $this->seedVendors($tenant);
        
        $this->seedRfqs($tenant, $projects, $users, $vendors);
    }

    private function seedTenant(): Tenant
    {
        return Tenant::factory()->create([
            'id' => $this->tenantId,
            'code' => 'NORDFJORD',
            'name' => 'Nordfjord Process Chemicals AS',
            'email' => 'procurement@nordfjord.example.com',
            'status' => 'active',
            'timezone' => 'Europe/Oslo',
            'locale' => 'en',
            'currency' => 'NOK',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
            'max_users' => 50,
            'storage_quota' => 5368709120,
            'storage_used' => 0,
            'rate_limit' => 60,
            'is_readonly' => false,
            'onboarding_progress' => 100,
        ]);
    }

    private function seedUsers(Tenant $tenant): \Illuminate\Database\Eloquent\Collection
    {
        $users = [
            ['name' => 'Ingrid Solberg', 'email' => 'ingrid.solberg@nordfjord.example.com'],
            ['name' => 'Erik Haugen', 'email' => 'erik.haugen@nordfjord.example.com'],
            ['name' => 'Sigrid Berg', 'email' => 'sigrid.berg@nordfjord.example.com'],
            ['name' => 'Mats Johannessen', 'email' => 'mats.johannessen@nordfjord.example.com'],
            ['name' => 'Liv Holt', 'email' => 'liv.holt@nordfjord.example.com'],
            ['name' => 'Knut Larsen', 'email' => 'knut.larsen@nordfjord.example.com'],
            ['name' => 'Anna Myhre', 'email' => 'anna.myhre@nordfjord.example.com'],
            ['name' => 'Olav Vik', 'email' => 'olav.vik@nordfjord.example.com'],
        ];

        return User::factory()
            ->count(8)
            ->sequence(...$users)
            ->for($tenant)
            ->create();
    }

    private function seedProjects(Tenant $tenant, User $projectManager): \Illuminate\Database\Eloquent\Collection
    {
        $projectDefinitions = [
            ['name' => 'Nordfjord Plant Expansion', 'status' => 'active'],
            ['name' => 'Bergen Refinery Upgrade', 'status' => 'planning'],
            ['name' => 'Stavanger Terminal', 'status' => 'completed'],
            ['name' => 'Oslo Distribution Hub', 'status' => 'on_hold'],
            ['name' => 'Trondheim Processing', 'status' => 'active'],
            ['name' => 'Kristiansand Maintenance', 'status' => 'active'],
            ['name' => 'Tromsø Storage Facility', 'status' => 'planning'],
            ['name' => 'Drammen Quality Lab', 'status' => 'active'],
            ['name' => 'Fredrikstad Pipeline', 'status' => 'completed'],
            ['name' => 'Sandefjord Equipment', 'status' => 'on_hold'],
            ['name' => 'Haugesund Solar Farm', 'status' => 'planning'],
            ['name' => 'Moss Warehouse', 'status' => 'active'],
        ];

        return Project::factory()
            ->count(12)
            ->sequence(...$projectDefinitions)
            ->for($tenant)
            ->for($projectManager, 'projectManager')
            ->create();
    }

    private function seedScoringModel(Tenant $tenant, User $creator): ScoringModel
    {
        $scoringModel = ScoringModel::factory()
            ->for($tenant)
            ->for($creator, 'creator')
            ->active()
            ->create();

        ScoringPolicy::factory()
            ->for($scoringModel)
            ->create();

        return $scoringModel;
    }

    private function seedVendors(Tenant $tenant): \Illuminate\Database\Eloquent\Collection
    {
        $vendorPool = [];
        $riskyVendorIndices = [3, 7, 11, 15, 19, 21, 25, 27];

        for ($i = 0; $i < 28; $i++) {
            $vendorPool[] = [
                'registration_number' => str_pad((string) ($i + 100000000), 10, '0', STR_PAD_LEFT),
                'legal_name' => fake()->company(),
                'display_name' => fake()->company(),
                'country_of_registration' => fake()->countryCode(),
                'primary_contact_name' => fake()->name(),
                'primary_contact_email' => fake()->companyEmail(),
                'status' => in_array($i, $riskyVendorIndices) ? 'restricted' : 'approved',
            ];
        }

        return Vendor::factory()
            ->count(28)
            ->sequence(...$vendorPool)
            ->for($tenant)
            ->create();
    }

    private function seedRfqs(
        Tenant $tenant,
        \Illuminate\Database\Eloquent\Collection $projects,
        \Illuminate\Database\Eloquent\Collection $users,
        \Illuminate\Database\Eloquent\Collection $vendors
    ): void {
        $statusDistribution = [
            'draft' => 8,
            'published' => 15,
            'closed' => 18,
            'awarded' => 10,
            'cancelled' => 5,
        ];

        $projectIndex = 0;
        $vendorIndex = 0;

        foreach ($statusDistribution as $status => $count) {
            for ($i = 0; $i < $count; $i++) {
                $project = $projects[$projectIndex % $projects->count()];
                $owner = $users[$i % $users->count()];

                $rfq = $this->createRfqForStatus($tenant, $project, $owner, $status);
                $this->createLineItems($rfq);

                if (in_array($status, ['published', 'closed', 'awarded'])) {
                    $this->createInvitationsAndQuotes($rfq, $vendors, $vendorIndex);
                    $vendorIndex += 3;
                }

                if (in_array($status, ['closed', 'awarded'])) {
                    $this->createWorkflowChain($rfq, $owner);
                }

                $projectIndex++;
            }
        }
    }

    private function createRfqForStatus(Tenant $tenant, Project $project, User $owner, string $status): Rfq
    {
        return match ($status) {
            'draft' => Rfq::factory()
                ->for($tenant)
                ->for($project)
                ->ownedBy($owner)
                ->draft()
                ->create(),
            'published' => Rfq::factory()
                ->for($tenant)
                ->for($project)
                ->ownedBy($owner)
                ->published()
                ->create(),
            'closed' => Rfq::factory()
                ->for($tenant)
                ->for($project)
                ->ownedBy($owner)
                ->closed()
                ->create(),
            'awarded' => Rfq::factory()
                ->for($tenant)
                ->for($project)
                ->ownedBy($owner)
                ->awarded()
                ->create(),
            'cancelled' => Rfq::factory()
                ->for($tenant)
                ->for($project)
                ->ownedBy($owner)
                ->cancelled()
                ->create(),
            default => throw new \InvalidArgumentException("Unknown status: $status"),
        };
    }

    private function createLineItems(Rfq $rfq): void
    {
        $lineItemCount = rand(3, 8);
        
        RfqLineItem::factory()
            ->count($lineItemCount)
            ->for($rfq)
            ->create();
    }

    private function createInvitationsAndQuotes(
        Rfq $rfq,
        \Illuminate\Database\Eloquent\Collection $vendors,
        int $startIndex
    ): void {
        $vendorCount = min(3, $vendors->count() - $startIndex);

        for ($i = 0; $i < $vendorCount; $i++) {
            $vendor = $vendors[($startIndex + $i) % $vendors->count()];
            
            VendorInvitation::factory()
                ->for($rfq)
                ->for($vendor)
                ->accepted()
                ->create();

            QuoteSubmission::factory()
                ->for($rfq)
                ->for($vendor)
                ->ready()
                ->create();
        }
    }

    private function createWorkflowChain(Rfq $rfq, User $creator): void
    {
        $comparison = ComparisonRun::factory()
            ->for($rfq)
            ->createdBy($creator)
            ->final()
            ->create();

        Approval::factory()
            ->for($rfq)
            ->forComparisonRun($comparison)
            ->approved()
            ->create();

        Award::factory()
            ->for($rfq)
            ->signedOff()
            ->create();
    }
}
```

- [ ] **Step 4: Run test to verify seeder produces identical output**

Run: `php artisan test --filter PetrochemicalTenantSeederTest`
Expected: PASS

- [ ] **Step 5: Verify LOC reduction**

Run: `wc -l apps/atomy-q/API/database/seeders/PetrochemicalTenantSeeder.php`
Expected: ~150 LOC (down from ~1200)

- [ ] **Step 6: Commit**

```bash
git add apps/atomy-q/API/database/seeders/PetrochemicalTenantSeeder.php
git commit -m "refactor: rewrite PetrochemicalTenantSeeder using factories (~150 LOC from ~1200)"
```

### Task 6.3: Verify Full Seeder Integration

- [ ] **Step 1: Run migrate:fresh with seeder**

Run: `php artisan migrate:fresh --seed --env=testing`

- [ ] **Step 2: Verify all counts**

Expected:
- 1 tenant (NORDFJORD)
- 8 users
- 12 projects
- 56 RFQs
- 28 vendors (8 restricted, 20 approved)
- Proper quote pipeline for published/closed/awarded RFQs

- [ ] **Step 3: Commit Phase completion**

```bash
git add . && git commit -m "feat: complete Phase 6 seeder refactor"
```

---

## Task 7: Final Verification and Acceptance Criteria

### Task 7.1: Run All Factory Tests

- [ ] **Step 1: Run complete test suite**

Run: `php artisan test --filter Factory`
Expected: ALL PASS

### Task 7.2: Verify Acceptance Criteria

- [ ] **Step 1: Test Isolation**

Each factory can create valid model instances without database dependencies beyond schema.

- [ ] **Step 2: State Consistency**

Each status state produces field combinations that pass domain validation rules.

- [ ] **Step 3: Relationship Integrity**

Factory-created models satisfy foreign key constraints.

- [ ] **Step 4: Pipeline Realism**

Workflow states (e.g., `awarded()`) auto-create related entities.

- [ ] **Step 5: Seeder Parity**

Refactored seeder produces identical data to current seeder (verified via integration tests).

- [ ] **Step 6: Performance**

Factory creation adds no more than 50ms overhead per instance vs raw inserts (benchmark if needed).

### Task 7.3: Run Static Analysis

- [ ] **Step 1: Run PHPStan**

Run: `php vendor/bin/phpstan analyse database/factories --level=max`
Expected: NO ERRORS

- [ ] **Step 2: Commit final**

```bash
git add . && git commit -m "feat: complete factory adoption implementation - all acceptance criteria met"
```

---

## Execution Summary

**Recommended: Subagent-Driven Development**
- Dispatch fresh subagent per task (one phase at a time)
- Review each task before proceeding to next
- Fast iteration with verification after each task

**Alternative: Inline Execution**
- Execute in this session using executing-plans
- Batch execution with periodic checkpoints
- Review after each phase completion