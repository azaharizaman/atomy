<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\CustomerInvoice;
use App\Repositories\EloquentCustomerInvoiceRepository;
use App\Repositories\EloquentPaymentReceiptRepository;
use App\Repositories\EloquentReceivableScheduleRepository;
use App\Repositories\EloquentUnappliedCashRepository;
use App\Services\Receivable\AgingCalculator;
use App\Services\Receivable\CreditLimitChecker;
use App\Services\Receivable\DunningManager;
use App\Services\Receivable\FifoAllocationStrategy;
use App\Services\Receivable\PaymentProcessor;
use App\Services\Receivable\ReceivableManager;
use Illuminate\Support\ServiceProvider;
use Nexus\Receivable\Contracts\AgingCalculatorInterface;
use Nexus\Receivable\Contracts\CreditLimitCheckerInterface;
use Nexus\Receivable\Contracts\CustomerInvoiceRepositoryInterface;
use Nexus\Receivable\Contracts\DunningManagerInterface;
use Nexus\Receivable\Contracts\PaymentAllocationStrategyInterface;
use Nexus\Receivable\Contracts\PaymentProcessorInterface;
use Nexus\Receivable\Contracts\PaymentReceiptRepositoryInterface;
use Nexus\Receivable\Contracts\ReceivableManagerInterface;
use Nexus\Receivable\Contracts\ReceivableScheduleRepositoryInterface;
use Nexus\Receivable\Contracts\UnappliedCashRepositoryInterface;

/**
 * Receivable Service Provider
 *
 * Binds all Receivable interfaces to concrete implementations.
 */
class ReceivableServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind repositories
        $this->app->singleton(
            CustomerInvoiceRepositoryInterface::class,
            EloquentCustomerInvoiceRepository::class
        );

        $this->app->singleton(
            PaymentReceiptRepositoryInterface::class,
            EloquentPaymentReceiptRepository::class
        );

        $this->app->singleton(
            ReceivableScheduleRepositoryInterface::class,
            EloquentReceivableScheduleRepository::class
        );

        $this->app->singleton(
            UnappliedCashRepositoryInterface::class,
            EloquentUnappliedCashRepository::class
        );

        // Bind services
        $this->app->singleton(
            ReceivableManagerInterface::class,
            ReceivableManager::class
        );

        $this->app->singleton(
            PaymentProcessorInterface::class,
            PaymentProcessor::class
        );

        $this->app->singleton(
            CreditLimitCheckerInterface::class,
            CreditLimitChecker::class
        );

        $this->app->singleton(
            AgingCalculatorInterface::class,
            AgingCalculator::class
        );

        $this->app->singleton(
            DunningManagerInterface::class,
            DunningManager::class
        );

        // Bind default payment allocation strategy
        $this->app->bind(
            PaymentAllocationStrategyInterface::class,
            FifoAllocationStrategy::class
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
