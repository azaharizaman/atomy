<?php

declare(strict_types=1);

namespace Tests\Feature\Intelligence;

use App\Models\CustomerInvoice;
use App\Models\PaymentReceipt;
use App\Models\Tenant;
use App\Repositories\Intelligence\PaymentHistoryRepository;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('intelligence')]
#[Group('repositories')]
#[Group('receivable')]
final class PaymentHistoryRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private PaymentHistoryRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tenant = Tenant::factory()->create();
        $this->repository = new PaymentHistoryRepository($this->tenant->id);
    }

    #[Test]
    public function it_calculates_average_payment_delay_days_from_materialized_view(): void
    {
        // Arrange: Create test data in materialized view
        $customerId = 'CUST-001';
        $avgDelay = 5.8;
        
        \DB::table('mv_customer_payment_analytics')->insert([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customerId,
            'avg_payment_delay_days' => $avgDelay,
            'std_dev_payment_delay_days' => 2.5,
            'on_time_payment_rate' => 0.82,
            'late_payment_rate' => 0.18,
            'avg_days_to_pay' => 32.3,
            'invoice_count_30d' => 5,
            'invoice_count_90d' => 15,
            'invoice_count_365d' => 60,
            'paid_invoice_count_90d' => 14,
            'overdue_invoice_count' => 1,
            'total_outstanding_amount' => 12000.00,
            'overdue_amount' => 2000.00,
            'credit_limit' => 50000.00,
            'credit_utilization_ratio' => 0.24,
            'customer_tenure_days' => 365,
            'lifetime_value' => 180000.00,
            'last_payment_date' => now()->subDays(10),
            'has_disputed_invoices' => false,
            'avg_invoice_amount' => 5200.00,
            'payment_method_stability' => 0.90,
            'last_refreshed_at' => now(),
        ]);

        // Act
        $result = $this->repository->getAveragePaymentDelayDays($customerId);

        // Assert
        $this->assertSame($avgDelay, $result);
    }

    #[Test]
    public function it_returns_zero_for_non_existent_customer(): void
    {
        $result = $this->repository->getAveragePaymentDelayDays('CUST-NONEXISTENT');

        $this->assertSame(0.0, $result);
    }

    #[Test]
    public function it_retrieves_payment_behavior_metrics(): void
    {
        $customerId = 'CUST-002';
        
        \DB::table('mv_customer_payment_analytics')->insert([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customerId,
            'avg_payment_delay_days' => 3.2,
            'std_dev_payment_delay_days' => 1.8,
            'on_time_payment_rate' => 0.95,
            'late_payment_rate' => 0.05,
            'avg_days_to_pay' => 28.5,
            'invoice_count_30d' => 8,
            'invoice_count_90d' => 24,
            'invoice_count_365d' => 96,
            'paid_invoice_count_90d' => 23,
            'overdue_invoice_count' => 0,
            'total_outstanding_amount' => 8000.00,
            'overdue_amount' => 0.00,
            'credit_limit' => 100000.00,
            'credit_utilization_ratio' => 0.08,
            'customer_tenure_days' => 730,
            'lifetime_value' => 500000.00,
            'last_payment_date' => now()->subDays(5),
            'has_disputed_invoices' => false,
            'avg_invoice_amount' => 6800.00,
            'payment_method_stability' => 0.98,
            'last_refreshed_at' => now(),
        ]);

        // Act & Assert: Payment behavior
        $this->assertSame(3.2, $this->repository->getAveragePaymentDelayDays($customerId));
        $this->assertSame(1.8, $this->repository->getStdDevPaymentDelayDays($customerId));
        $this->assertSame(0.95, $this->repository->getOnTimePaymentRate($customerId));
        $this->assertSame(0.05, $this->repository->getLatePaymentRate($customerId));
        $this->assertSame(28.5, $this->repository->getAverageDaysToPay($customerId));
    }

    #[Test]
    public function it_retrieves_credit_health_metrics(): void
    {
        $customerId = 'CUST-003';
        
        \DB::table('mv_customer_payment_analytics')->insert([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customerId,
            'avg_payment_delay_days' => 8.5,
            'std_dev_payment_delay_days' => 4.2,
            'on_time_payment_rate' => 0.70,
            'late_payment_rate' => 0.30,
            'avg_days_to_pay' => 38.0,
            'invoice_count_30d' => 4,
            'invoice_count_90d' => 12,
            'invoice_count_365d' => 48,
            'paid_invoice_count_90d' => 10,
            'overdue_invoice_count' => 3,
            'total_outstanding_amount' => 25000.00,
            'overdue_amount' => 8000.00,
            'credit_limit' => 50000.00,
            'credit_utilization_ratio' => 0.50,
            'customer_tenure_days' => 180,
            'lifetime_value' => 80000.00,
            'last_payment_date' => now()->subDays(20),
            'has_disputed_invoices' => true,
            'avg_invoice_amount' => 4500.00,
            'payment_method_stability' => 0.75,
            'last_refreshed_at' => now(),
        ]);

        // Act & Assert: Credit health
        $this->assertSame(25000.00, $this->repository->getTotalOutstandingAmount($customerId));
        $this->assertSame(8000.00, $this->repository->getOverdueAmount($customerId));
        $this->assertSame(50000.00, $this->repository->getCreditLimit($customerId));
        $this->assertSame(0.50, $this->repository->getCreditUtilizationRatio($customerId));
        $this->assertSame(3, $this->repository->getOverdueInvoiceCount($customerId));
    }

    #[Test]
    public function it_retrieves_relationship_metrics(): void
    {
        $customerId = 'CUST-004';
        $lastPaymentDate = now()->subDays(15);
        
        \DB::table('mv_customer_payment_analytics')->insert([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customerId,
            'avg_payment_delay_days' => 6.0,
            'std_dev_payment_delay_days' => 3.0,
            'on_time_payment_rate' => 0.80,
            'late_payment_rate' => 0.20,
            'avg_days_to_pay' => 33.0,
            'invoice_count_30d' => 6,
            'invoice_count_90d' => 18,
            'invoice_count_365d' => 72,
            'paid_invoice_count_90d' => 16,
            'overdue_invoice_count' => 2,
            'total_outstanding_amount' => 15000.00,
            'overdue_amount' => 3000.00,
            'credit_limit' => 75000.00,
            'credit_utilization_ratio' => 0.20,
            'customer_tenure_days' => 1095,
            'lifetime_value' => 1200000.00,
            'last_payment_date' => $lastPaymentDate,
            'has_disputed_invoices' => false,
            'avg_invoice_amount' => 7500.00,
            'payment_method_stability' => 0.92,
            'last_refreshed_at' => now(),
        ]);

        // Act & Assert: Relationship metrics
        $this->assertSame(1095, $this->repository->getCustomerTenureDays($customerId));
        $this->assertSame(1200000.00, $this->repository->getLifetimeValue($customerId));
        
        $retrievedDate = $this->repository->getLastPaymentDate($customerId);
        $this->assertInstanceOf(DateTimeImmutable::class, $retrievedDate);
        $this->assertSame($lastPaymentDate->format('Y-m-d'), $retrievedDate->format('Y-m-d'));
        
        $this->assertFalse($this->repository->hasDisputedInvoices($customerId));
        $this->assertSame(0.92, $this->repository->getPaymentMethodStability($customerId));
    }

    #[Test]
    public function it_marks_customer_analytics_for_refresh(): void
    {
        $customerId = 'CUST-005';
        
        // Arrange: Ensure dirty_records table is clean
        \DB::table('dirty_customer_payment_analytics')->where([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customerId,
        ])->delete();

        // Act
        $this->repository->refreshCustomerAnalytics($customerId);

        // Assert: Verify dirty record was inserted
        $dirtyRecord = \DB::table('dirty_customer_payment_analytics')
            ->where([
                'tenant_id' => $this->tenant->id,
                'customer_id' => $customerId,
            ])
            ->first();

        $this->assertNotNull($dirtyRecord);
        $this->assertSame($this->tenant->id, $dirtyRecord->tenant_id);
        $this->assertSame($customerId, $dirtyRecord->customer_id);
    }

    #[Test]
    public function it_handles_null_last_payment_date_gracefully(): void
    {
        $customerId = 'CUST-006';
        
        \DB::table('mv_customer_payment_analytics')->insert([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customerId,
            'avg_payment_delay_days' => 0.0,
            'std_dev_payment_delay_days' => 0.0,
            'on_time_payment_rate' => 0.0,
            'late_payment_rate' => 0.0,
            'avg_days_to_pay' => 0.0,
            'invoice_count_30d' => 0,
            'invoice_count_90d' => 0,
            'invoice_count_365d' => 0,
            'paid_invoice_count_90d' => 0,
            'overdue_invoice_count' => 0,
            'total_outstanding_amount' => 5000.00,
            'overdue_amount' => 0.00,
            'credit_limit' => 25000.00,
            'credit_utilization_ratio' => 0.20,
            'customer_tenure_days' => 0,
            'lifetime_value' => 0.00,
            'last_payment_date' => null,
            'has_disputed_invoices' => false,
            'avg_invoice_amount' => 0.00,
            'payment_method_stability' => 0.0,
            'last_refreshed_at' => now(),
        ]);

        // Act
        $result = $this->repository->getLastPaymentDate($customerId);

        // Assert: Should return null for new customers
        $this->assertNull($result);
    }

    #[Test]
    public function it_respects_tenant_isolation(): void
    {
        $otherTenant = Tenant::factory()->create();
        $customerId = 'CUST-007';
        
        // Arrange: Create analytics for different tenant
        \DB::table('mv_customer_payment_analytics')->insert([
            'tenant_id' => $otherTenant->id,
            'customer_id' => $customerId,
            'avg_payment_delay_days' => 999.9,
            'std_dev_payment_delay_days' => 0.0,
            'on_time_payment_rate' => 0.0,
            'late_payment_rate' => 0.0,
            'avg_days_to_pay' => 0.0,
            'invoice_count_30d' => 0,
            'invoice_count_90d' => 0,
            'invoice_count_365d' => 0,
            'paid_invoice_count_90d' => 0,
            'overdue_invoice_count' => 0,
            'total_outstanding_amount' => 0.00,
            'overdue_amount' => 0.00,
            'credit_limit' => 0.00,
            'credit_utilization_ratio' => 0.0,
            'customer_tenure_days' => 0,
            'lifetime_value' => 0.00,
            'last_payment_date' => null,
            'has_disputed_invoices' => false,
            'avg_invoice_amount' => 0.00,
            'payment_method_stability' => 0.0,
            'last_refreshed_at' => now(),
        ]);

        // Act: Query with current tenant repository
        $result = $this->repository->getAveragePaymentDelayDays($customerId);

        // Assert: Should not see other tenant's data
        $this->assertSame(0.0, $result);
        $this->assertNotSame(999.9, $result);
    }
}
