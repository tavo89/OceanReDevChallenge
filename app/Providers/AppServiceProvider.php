<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\Accounting\Contracts\PeriodClosingServiceInterface;
use App\Domain\Accounting\Contracts\AccountingPeriodRepositoryInterface;
use App\Domain\Accounting\Contracts\BalanceCalculatorInterface;
use App\Domain\Accounting\Services\PeriodClosingService;
use App\Domain\Accounting\Repositories\AccountingPeriodRepository;
use App\Domain\Accounting\Services\BalanceCalculatorService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind interfaces to implementations (Dependency Inversion Principle)
        $this->app->bind(
            PeriodClosingServiceInterface::class,
            PeriodClosingService::class
        );

        $this->app->bind(
            AccountingPeriodRepositoryInterface::class,
            AccountingPeriodRepository::class
        );

        $this->app->bind(
            BalanceCalculatorInterface::class,
            BalanceCalculatorService::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
