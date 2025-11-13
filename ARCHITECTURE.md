# System Architecture

## Overview
This is a **Domain-Driven Design (DDD)** Laravel 11 application implementing an accounting system with period management, invoice/receipt processing, and financial reporting capabilities.

## Architecture Pattern

### Domain-Driven Design (DDD)
The application is structured around two bounded contexts:
- **Accounting Domain** - Period management, balance calculations, financial reports
- **Sales Domain** - Invoices, receipts, credit notes

### Layered Architecture
```
┌─────────────────────────────────────┐
│     API Layer (Controllers)         │  ← HTTP Endpoints
├─────────────────────────────────────┤
│     Service Layer                   │  ← Business Logic
├─────────────────────────────────────┤
│     Repository Layer                │  ← Data Access
├─────────────────────────────────────┤
│     Model Layer                     │  ← Eloquent Models
└─────────────────────────────────────┘
```

## Project Structure

```
app/
├── Domain/
│   ├── Accounting/
│   │   ├── Contracts/              # Interfaces
│   │   │   ├── PeriodClosingServiceInterface
│   │   │   ├── PeriodReopeningServiceInterface
│   │   │   ├── BalanceCalculatorInterface
│   │   │   └── AccountingPeriodRepositoryInterface
│   │   ├── Services/               # Business Logic
│   │   │   ├── PeriodClosingService
│   │   │   ├── PeriodReopeningService
│   │   │   └── BalanceCalculatorService
│   │   ├── Repositories/           # Data Access
│   │   │   └── AccountingPeriodRepository
│   │   └── Models/                 # Eloquent Models
│   │       ├── AccountingPeriod
│   │       ├── AccountingPeriodBalance
│   │       ├── Account
│   │       ├── JournalEntry
│   │       └── JournalEntryLine
│   │
│   └── Sales/
│       ├── Contracts/              # Interfaces
│       │   ├── InvoiceServiceInterface
│       │   ├── ReceiptServiceInterface
│       │   ├── InvoiceCancellationServiceInterface
│       │   ├── InvoiceRepositoryInterface
│       │   ├── ReceiptRepositoryInterface
│       │   └── CreditNoteRepositoryInterface
│       ├── Services/               # Business Logic
│       │   ├── InvoiceService
│       │   ├── ReceiptService
│       │   └── InvoiceCancellationService
│       ├── Repositories/           # Data Access
│       │   ├── InvoiceRepository
│       │   ├── ReceiptRepository
│       │   └── CreditNoteRepository
│       └── Models/                 # Eloquent Models
│           ├── Invoice
│           ├── Receipt
│           ├── CreditNote
│           └── Customer
│
├── Http/
│   └── Controllers/
│       ├── Accounting/
│       │   └── AccountingPeriodBalanceController
│       └── Sales/
│           ├── InvoiceController
│           └── ReceiptController
│
├── Console/
│   └── Commands/
│       ├── ClosePeriodCommand
│       └── ReopenPeriodCommand
│
└── Providers/
    └── AppServiceProvider          # Dependency Injection bindings
```

## Core Concepts

### 1. Accounting Periods
**Purpose**: Define time boundaries for financial transactions

**Lifecycle States**:
- `open` - Transactions can be created/modified
- `validating` - Period is being validated
- `locking` - Period is being closed
- `closed` - Period is locked, no modifications allowed

**Key Rules**:
- Transactions can only be created in `open` periods
- Closed periods can be reopened for corrections
- Balance snapshots are saved when closing periods

### 2. Invoice Management
**Purpose**: Track customer invoices with period control

**States**:
- `valid` - Active invoice included in calculations
- `cancelled` - Invoice cancelled via credit note

**Key Rules**:
- Invoices can only be created in open periods
- Invoices can be cancelled even if period is closed
- Cancelled invoices are excluded from balance calculations
- Cancellation requires creating a credit note in an open period

### 3. Credit Notes
**Purpose**: Document invoice cancellations

**Key Rules**:
- Created when cancelling an invoice
- Must be created in an open period (even if invoice period is closed)
- Inherits amount from cancelled invoice
- One credit note per invoice (no duplicate cancellations)

### 4. Balance Calculations
**Purpose**: Calculate account balances from journal entries

**Calculation Rules**:
- Only includes transactions from the specified period
- Only includes **valid** invoices (excludes cancelled)
- Includes all receipts
- Validates debits = credits
- Groups by account

### 5. Soft Deletes for Audit Trail
**Purpose**: Maintain history of balance changes

**Implementation**:
- `AccountingPeriodBalance` uses soft deletes
- When period is reopened, balances are soft-deleted (not permanently removed)
- When period is re-closed, new balances are created
- Complete audit trail of all period closings/reopenings

## Dependencies Between Domains

### Sales → Accounting
The Sales domain depends on the Accounting domain:

```
Sales Domain                    Accounting Domain
┌──────────────┐               ┌──────────────────┐
│ Invoice      │───period_id──→│ AccountingPeriod │
│ Receipt      │───period_id──→│                  │
│ CreditNote   │───period_id──→│                  │
└──────────────┘               └──────────────────┘
```

**Services Integration**:
- `InvoiceService` validates period status via `AccountingPeriodRepository`
- `ReceiptService` validates period status via `AccountingPeriodRepository`
- `InvoiceCancellationService` validates credit note period via `AccountingPeriodRepository`

### Accounting → Sales
The Accounting domain reads from Sales for calculations:

```
Accounting Domain              Sales Domain
┌──────────────────┐          ┌──────────────┐
│BalanceCalculator │─reads──→│ Invoice      │
│                  │─reads──→│ Receipt      │
└──────────────────┘          └──────────────┘
```

**Read Operations**:
- `BalanceCalculatorService` reads valid invoices and receipts
- Period closing calculates balances from Sales transactions
- Journal entries link to invoices/receipts via `source_reference`

## SOLID Principles Implementation

### 1. Single Responsibility Principle (SRP)
Each class has one reason to change:
- **Repositories**: Only handle data access
- **Services**: Only handle business logic
- **Controllers**: Only handle HTTP requests/responses
- **Models**: Only represent data structures

### 2. Open/Closed Principle (OCP)
- System is open for extension through interfaces
- Closed for modification - new implementations don't require changing existing code
- Example: Can add new balance calculation strategies by implementing `BalanceCalculatorInterface`

### 3. Liskov Substitution Principle (LSP)
- Any implementation of an interface can replace another
- Example: `InvoiceRepository` can be replaced with a different implementation without breaking `InvoiceService`

### 4. Interface Segregation Principle (ISP)
- Small, focused interfaces instead of large, monolithic ones
- Example: Separate interfaces for `InvoiceService` and `InvoiceCancellationService`

### 5. Dependency Inversion Principle (DIP)
- High-level modules depend on abstractions (interfaces)
- Low-level modules implement abstractions
- All service dependencies are injected via constructor
- Bindings configured in `AppServiceProvider`

## Business Logic Flow

### Period Closing Flow
```
1. User executes: php artisan accounting:close 2025-11
2. ClosePeriodCommand calls PeriodClosingService
3. PeriodClosingService:
   ├─ Validates period exists and is open
   ├─ Changes status to 'locking'
   ├─ Calls BalanceCalculatorService
   ├─ BalanceCalculatorService:
   │  ├─ Queries journal entries for period
   │  ├─ Includes only VALID invoices
   │  ├─ Includes all receipts
   │  └─ Returns balance by account
   ├─ Validates debits = credits
   ├─ Soft deletes old balances (if re-closing)
   ├─ Saves new balances to accounting_period_balances
   └─ Changes status to 'closed'
4. Returns balance report
```

### Invoice Cancellation Flow
```
1. User posts to: POST /api/sales/invoices/{id}/cancel
2. InvoiceController validates request
3. InvoiceCancellationService:
   ├─ Validates invoice exists and not already cancelled
   ├─ Checks credit note period is OPEN (critical rule)
   ├─ Creates transaction:
   │  ├─ Updates invoice: status='cancelled', cancelled_at=now()
   │  └─ Creates credit note with invoice amount
   └─ Returns invoice + credit note
4. Future balance calculations exclude cancelled invoice
```

### Transaction Creation Flow
```
1. User posts to: POST /api/sales/invoices
2. InvoiceController validates data
3. InvoiceService:
   ├─ Validates period_id exists
   ├─ Checks period status is 'open'
   ├─ If open: creates invoice in transaction
   └─ If closed: returns error
4. Returns created invoice or error
```

## Data Integrity Measures

### Database Transactions
All critical operations use database transactions:
- Period closing (status changes + balance saving)
- Invoice cancellation (status update + credit note creation)
- Invoice/receipt creation (validation + insert)

**Benefit**: Automatic rollback on any failure, no partial data changes

### Foreign Key Constraints
- `invoices.period_id` → `accounting_periods.id` (RESTRICT)
- `receipts.period_id` → `accounting_periods.id` (RESTRICT)
- `credit_notes.invoice_id` → `invoices.id` (CASCADE)
- `credit_notes.period_id` → `accounting_periods.id` (RESTRICT)

**Benefit**: Database-level referential integrity

### Soft Deletes
- `accounting_period_balances` uses soft deletes
- Maintains complete history of balance calculations
- Supports audit requirements

### Status Validation
- Period status checked before all transaction operations
- Invoice status determines inclusion in calculations
- State machine pattern for period lifecycle

## API Endpoints

### Sales Domain
- `POST /api/sales/invoices` - Create invoice
- `POST /api/sales/invoices/{id}/cancel` - Cancel invoice
- `POST /api/sales/receipts` - Create receipt

### Accounting Domain
- `GET /api/accounting/period-balances` - List period balances
  - Query params: `?period_id=X&account_id=Y`

### Response Format
All endpoints return consistent JSON:
```json
{
  "success": true|false,
  "message": "Operation message",
  "data": { ... } | null
}
```

## Console Commands

### Period Management
```bash
php artisan accounting:close {period-code}
php artisan accounting:reopen {period-code}
```

Both commands:
- Accept period code (e.g., "2025-11")
- Validate period existence and current status
- Use transactions for data integrity
- Provide detailed output with balance tables
- Support --help flag

## Testing Strategy

### Test Coverage
- **46 tests, 243 assertions**
- Feature tests for integration scenarios
- API tests for endpoint validation
- Service tests for business logic

### Test Categories
1. **Command Tests** - CLI interaction and validation
2. **Controller Tests** - API endpoint behavior
3. **Service Tests** - Business logic and rules
4. **Integration Tests** - End-to-end scenarios

### Key Test Scenarios
- Period closing/reopening cycle
- Invoice creation in open/closed periods
- Invoice cancellation with credit notes
- Balance calculation with cancelled invoices
- Soft delete audit trail
- Validation and error handling

## Security Considerations

### Input Validation
- All API inputs validated via Laravel's validation rules
- Foreign key existence checked before operations
- Business rule validation in service layer

### Data Protection
- Soft deletes preserve audit trail
- Foreign key constraints prevent orphaned records
- Transactions ensure atomic operations

### Period Locking
- Closed periods reject transaction modifications
- Status-based access control at service layer
- Prevents accidental data manipulation

## Performance Considerations

### Database Optimization
- Indexes on foreign keys
- Unique constraints on business keys (invoice_number, period_code)
- Eager loading for related data (with() in queries)

### Query Optimization
- Balance calculation uses grouped aggregations
- Filters at database level (WHERE clauses)
- Minimal N+1 query issues (eager loading relationships)

## Extensibility Points

### Adding New Transaction Types
1. Create new model in Sales domain
2. Add period_id foreign key
3. Update `BalanceCalculatorService` to include in calculations
4. Follow same service/repository pattern

### Adding New Business Rules
1. Implement new service interface
2. Add validation in service layer
3. Register binding in `AppServiceProvider`
4. Add tests for new behavior

### Adding New Reports
1. Create new controller in Accounting domain
2. Query `accounting_period_balances` table
3. Add filtering/formatting logic
4. Return structured JSON response

## Technology Stack

- **Framework**: Laravel 11
- **PHP**: 8.2+
- **Database**: MySQL (production), SQLite (testing)
- **Testing**: PHPUnit with Laravel testing utilities
- **Architecture**: Domain-Driven Design (DDD)
- **Patterns**: Repository, Service Layer, Dependency Injection
- **ORM**: Eloquent
- **API**: RESTful JSON API
