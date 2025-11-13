# Accounting System - Laravel 11

Domain-Driven Design accounting system with period management, invoice/receipt processing, and balance calculations.

See [ARCHITECTURE.md](ARCHITECTURE.md) for detailed architecture documentation.

## Prerequisites

- PHP 8.2+
- Composer
- MySQL 5.7+ / MariaDB 10.3+

## Setup

```bash
# Install dependencies
composer install
npm install

# Configure environment
cp .env.example .env
php artisan key:generate

# Edit .env with database credentials
DB_CONNECTION=mysql
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Run migrations and seed data
php artisan migrate
php artisan db:seed

# Install Laravel Sanctum (API authentication)
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

# Build assets
npm run build

# Start server
php artisan serve
```

## Testing

```bash
# Run all tests (46 tests, 243 assertions)
php artisan test

# Run specific test
php artisan test --filter=InvoiceServiceTest
```

## CLI Commands

```bash
# Close accounting period (calculates and saves balances)
php artisan accounting:close 2025-11

# Reopen closed period (soft deletes balances for audit trail)
php artisan accounting:reopen 2025-11
```

## API Endpoints

Base URL: `http://localhost:8000/api`

### Create Invoice
```bash
curl -X POST http://localhost:8000/api/sales/invoices \
  -H "Content-Type: application/json" \
  -d '{
    "invoice_number": "INV-2025-001",
    "issue_date": "2025-11-15",
    "due_date": "2025-12-15",
    "customer_id": 1,
    "total_amount": 1500.00,
    "currency": "USD",
    "period_id": 2,
    "exchange_rate": 1.0,
    "base_currency_amount": 1500.00
  }'
```

### Cancel Invoice
```bash
curl -X POST http://localhost:8000/api/sales/invoices/15/cancel \
  -H "Content-Type: application/json" \
  -d '{
    "credit_note_number": "CN-2025-001",
    "period_id": 2,
    "issue_date": "2025-11-20",
    "reason": "Customer request"
  }'
```

### Create Receipt
```bash
curl -X POST http://localhost:8000/api/sales/receipts \
  -H "Content-Type: application/json" \
  -d '{
    "receipt_number": "RCP-2025-001",
    "payment_date": "2025-11-20",
    "amount": 750.00,
    "currency": "USD",
    "period_id": 2,
    "exchange_rate": 1.0,
    "base_currency_amount": 750.00
  }'
```

### Get Period Balances
```bash
# All balances
curl http://localhost:8000/api/accounting/period-balances

# Filter by period
curl http://localhost:8000/api/accounting/period-balances?period_id=2

# Filter by account
curl http://localhost:8000/api/accounting/period-balances?account_id=1
```

## Business Rules

- Transactions only allowed in **open** periods
- Invoices can be cancelled anytime, but credit notes require **open period**
- Balance calculations exclude **cancelled** invoices
- Closed periods can be **reopened** (preserves audit trail via soft deletes)
