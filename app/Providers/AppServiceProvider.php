<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\PeriodClosingServiceInterface;
use App\Contracts\AccountingPeriodRepositoryInterface;
use App\Contracts\BalanceCalculatorInterface;
use App\Services\PeriodClosingService;
use App\Repositories\AccountingPeriodRepository;
use App\Services\BalanceCalculatorService;

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
