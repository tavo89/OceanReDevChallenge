<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\Accounting\Contracts\PeriodClosingServiceInterface;
use App\Domain\Accounting\Contracts\PeriodReopeningServiceInterface;
use App\Domain\Accounting\Contracts\AccountingPeriodRepositoryInterface;
use App\Domain\Accounting\Contracts\BalanceCalculatorInterface;
use App\Domain\Accounting\Services\PeriodClosingService;
use App\Domain\Accounting\Services\PeriodReopeningService;
use App\Domain\Accounting\Repositories\AccountingPeriodRepository;
use App\Domain\Accounting\Services\BalanceCalculatorService;
use App\Domain\Sales\Contracts\InvoiceServiceInterface;
use App\Domain\Sales\Contracts\ReceiptServiceInterface;
use App\Domain\Sales\Contracts\InvoiceRepositoryInterface;
use App\Domain\Sales\Contracts\ReceiptRepositoryInterface;
use App\Domain\Sales\Contracts\CreditNoteRepositoryInterface;
use App\Domain\Sales\Contracts\InvoiceCancellationServiceInterface;
use App\Domain\Sales\Services\InvoiceService;
use App\Domain\Sales\Services\ReceiptService;
use App\Domain\Sales\Services\InvoiceCancellationService;
use App\Domain\Sales\Repositories\InvoiceRepository;
use App\Domain\Sales\Repositories\ReceiptRepository;
use App\Domain\Sales\Repositories\CreditNoteRepository;

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
            PeriodReopeningServiceInterface::class,
            PeriodReopeningService::class
        );

        $this->app->bind(
            AccountingPeriodRepositoryInterface::class,
            AccountingPeriodRepository::class
        );

        $this->app->bind(
            BalanceCalculatorInterface::class,
            BalanceCalculatorService::class
        );

        // Sales Domain bindings
        $this->app->bind(
            InvoiceServiceInterface::class,
            InvoiceService::class
        );

        $this->app->bind(
            ReceiptServiceInterface::class,
            ReceiptService::class
        );

        $this->app->bind(
            InvoiceRepositoryInterface::class,
            InvoiceRepository::class
        );

        $this->app->bind(
            ReceiptRepositoryInterface::class,
            ReceiptRepository::class
        );

        $this->app->bind(
            CreditNoteRepositoryInterface::class,
            CreditNoteRepository::class
        );

        $this->app->bind(
            InvoiceCancellationServiceInterface::class,
            InvoiceCancellationService::class
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
