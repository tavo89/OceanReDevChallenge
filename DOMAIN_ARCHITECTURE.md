# Domain-Driven Design Architecture

## Overview

This Laravel application follows Domain-Driven Design (DDD) principles to separate business concerns into distinct bounded contexts. The primary domains are **Accounting** and **Sales**.

## Domain Structure

### Accounting Domain
**Location:** `app/Domain/Accounting/`

The Accounting domain handles all financial accounting operations including period management, journal entries, and account balances.

#### Models
- **AccountingPeriod** - Represents accounting periods (months) with status and locking
- **Account** - Chart of accounts with account codes and types
- **JournalEntry** - Financial transactions linked to accounting periods
- **JournalEntryLine** - Individual debit/credit lines within journal entries

#### Contracts (Interfaces)
- **PeriodClosingServiceInterface** - Defines period closing operations
- **PeriodReopeningServiceInterface** - Defines period reopening operations
- **BalanceCalculatorInterface** - Defines balance calculation operations
- **AccountingPeriodRepositoryInterface** - Defines data access for periods

#### Services
- **PeriodClosingService** - Implements period closing business logic
- **PeriodReopeningService** - Implements period reopening business logic
- **BalanceCalculatorService** - Calculates account balances for periods

#### Repositories
- **AccountingPeriodRepository** - Data access layer for accounting periods

#### Commands
- **ClosePeriodCommand** - CLI command for closing accounting periods
- **ReopenPeriodCommand** - CLI command for reopening closed accounting periods

### Sales Domain
**Location:** `app/Domain/Sales/`

The Sales domain handles customer management and sales transactions (invoices and receipts).

#### Models
- **Customer** - Customer master data with codes and contact information
- **Invoice** - Sales invoices linked to customers and accounting periods
- **Receipt** - Payment receipts linked to accounting periods

#### Future Expansion
The Sales domain is prepared for additional functionality:
- Contracts for sales-specific business rules
- Services for invoice processing and receipt handling
- Repositories for complex sales queries

## Domain Relationships

### Cross-Domain References
- **Invoice** and **Receipt** models reference `AccountingPeriod` from the Accounting domain
- This represents the legitimate dependency: sales transactions must be recorded in accounting periods
- Domain boundaries are respected through clear model relationships

### Shared Models
- **User** - Remains in `app/Models/` as it's a framework-level concern, not domain-specific

## Dependency Injection

All domain services are registered in `AppServiceProvider` using interface bindings:

```php
$this->app->bind(
    PeriodClosingServiceInterface::class,
    PeriodClosingService::class
);
```

This follows the **Dependency Inversion Principle** (SOLID), allowing easy testing and future implementation changes.

## Testing Strategy

### Feature Tests
- **ClosePeriodCommandTest** - Integration tests for the period closing command
- Tests use mocked services to verify command behavior

### Unit Tests
- **PeriodClosingServiceTest** - Unit tests for period closing service
- Tests use mocked repositories and calculators
- Verify business logic in isolation

All tests are updated to reference the new domain namespaces.

## Benefits of Domain Separation

1. **Clear Boundaries** - Each domain has a well-defined responsibility
2. **Maintainability** - Changes in one domain don't affect the other
3. **Testability** - Domain logic can be tested independently
4. **Team Scalability** - Different teams can work on different domains
5. **Business Alignment** - Code structure mirrors business organization
6. **SOLID Principles** - Especially Single Responsibility Principle

## Migration Path

### Files Moved to Domains

**From `app/Contracts/` to `app/Domain/Accounting/Contracts/`:**
- PeriodClosingServiceInterface.php
- BalanceCalculatorInterface.php
- AccountingPeriodRepositoryInterface.php

**From `app/Services/` to `app/Domain/Accounting/Services/`:**
- PeriodClosingService.php
- BalanceCalculatorService.php

**From `app/Repositories/` to `app/Domain/Accounting/Repositories/`:**
- AccountingPeriodRepository.php

**From `app/Models/` to `app/Domain/Accounting/Models/`:**
- AccountingPeriod.php
- Account.php
- JournalEntry.php
- JournalEntryLine.php

**Created in `app/Domain/Sales/Models/`:**
- Customer.php
- Invoice.php
- Receipt.php

### Updated Files
- `app/Providers/AppServiceProvider.php` - Updated service bindings
- `app/Console/Commands/ClosePeriodCommand.php` - Updated imports
- `tests/Feature/Commands/ClosePeriodCommandTest.php` - Updated imports
- `tests/Unit/Unit/Services/PeriodClosingServiceTest.php` - Updated imports

### Removed
- `app/Contracts/` directory (moved to domain)
- `app/Services/` directory (moved to domain)
- `app/Repositories/` directory (moved to domain)
- Old model files from `app/Models/`

## Test Results

All 21 tests pass with period closing and reopening functionality:
```
Tests:    21 passed (31 assertions)
Duration: 1.54s
```

### Test Coverage
- **Unit Tests (11 tests)**:
  - PeriodClosingServiceTest (6 tests)
  - PeriodReopeningServiceTest (5 tests)
  - ExampleTest (1 test)

- **Feature Tests (9 tests)**:
  - ClosePeriodCommandTest (4 tests)
  - ReopenPeriodCommandTest (4 tests)
  - ExampleTest (1 test)

## Future Considerations

### Additional Domains
Consider creating separate domains for:
- **Reporting** - Financial reports and analytics
- **Inventory** - If inventory management is needed
- **Multi-currency** - Currency exchange and conversion logic

### Domain Events
Implement domain events for cross-domain communication:
- `PeriodClosedEvent` - Notify other domains when a period is closed
- `InvoiceCreatedEvent` - Trigger accounting entries from sales

### Aggregate Roots
Identify and enforce aggregate boundaries:
- `AccountingPeriod` is an aggregate root in Accounting domain
- `Customer` is an aggregate root in Sales domain

## Commands Reference

### Close Accounting Period
```bash
php artisan accounting:close 2025-11
```

This command:
- Validates that the period exists and is open
- Calculates all account balances for the period
- Verifies debits equal credits (balanced)
- Updates period status to 'closed' and sets locked_at timestamp
- Displays balance summary table

### Reopen Accounting Period
```bash
php artisan accounting:reopen 2025-11
```

This command:
- Validates that the period exists and is closed
- Prompts for confirmation before reopening
- Updates period status to 'open' and clears locked_at timestamp
- Allows modifications to previously closed transactions

**Warning:** Reopening a closed period should be done carefully as it allows modifications to finalized transactions.

### Run Tests
```bash
php artisan test
```

### Run Specific Domain Tests
```bash
php artisan test --filter=PeriodClosing
php artisan test --filter=PeriodReopening
```

## Additional Resources

- See `SOLID_IMPLEMENTATION.md` for SOLID principles implementation
- See `TESTING_GUIDE.md` for comprehensive testing strategies
