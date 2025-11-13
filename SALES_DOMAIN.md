# Sales Domain Implementation

## Overview
The Sales domain has been enhanced with business logic to control transactions on closed accounting periods. This ensures data integrity by preventing modifications to invoices and receipts after an accounting period has been closed.

## Architecture

### Contracts (Interfaces)
Located in `app/Domain/Sales/Contracts/`

1. **InvoiceServiceInterface** - Service contract for invoice operations
2. **ReceiptServiceInterface** - Service contract for receipt operations  
3. **InvoiceRepositoryInterface** - Repository contract for invoice data access
4. **ReceiptRepositoryInterface** - Repository contract for receipt data access

### Repositories
Located in `app/Domain/Sales/Repositories/`

1. **InvoiceRepository** - Handles invoice CRUD operations
   - `create(array $data): Invoice`
   - `update(Invoice $invoice, array $data): Invoice`
   - `findById(int $id): ?Invoice`
   - `findByNumber(string $invoiceNumber): ?Invoice`

2. **ReceiptRepository** - Handles receipt CRUD operations
   - `create(array $data): Receipt`
   - `update(Receipt $receipt, array $data): Receipt`
   - `findById(int $id): ?Receipt`
   - `findByNumber(string $receiptNumber): ?Receipt`

### Services
Located in `app/Domain/Sales/Services/`

1. **InvoiceService** - Business logic for invoice management
   - Validates accounting period status before creation
   - Validates accounting period status before updates
   - Prevents creating/updating invoices in closed periods
   - Uses database transactions for data integrity
   - Returns consistent response format: `['success' => bool, 'message' => string, 'data' => mixed]`

2. **ReceiptService** - Business logic for receipt management
   - Validates accounting period status before creation
   - Validates accounting period status before updates
   - Prevents creating/updating receipts in closed periods
   - Uses database transactions for data integrity
   - Returns consistent response format: `['success' => bool, 'message' => string, 'data' => mixed]`

## Business Rules

### Invoice/Receipt Creation
✅ **Allowed**: Create in periods with status = 'open'  
❌ **Blocked**: Create in periods with status = 'closed', 'locking', or 'validating'

### Invoice/Receipt Updates
✅ **Allowed**: Update records in open periods  
❌ **Blocked**: 
- Update records in closed periods
- Move records from closed period to another period
- Move records to a closed period

### Validation Flow

#### Create Operation
1. Check if `period_id` is provided → Fail if missing
2. Check if period exists → Fail if not found
3. Check if period status is 'open' → Fail if not open
4. Create record in transaction
5. Log success/failure

#### Update Operation
1. Check if record exists → Fail if not found
2. Check if original period is open → Fail if closed (cannot modify existing record)
3. If changing period_id:
   - Check if target period exists → Fail if not found
   - Check if target period is open → Fail if closed
4. Update record in transaction
5. Log success/failure

## Error Messages

### Creation Errors
- `"Period ID is required."` - Missing period_id
- `"Accounting period not found."` - Invalid period_id
- `"Cannot create invoice/receipt. Accounting period {code} is {status}. Only open periods allow transactions."` - Period not open

### Update Errors
- `"Invoice/Receipt not found."` - Invalid ID
- `"Accounting period not found."` - Invalid period_id
- `"Cannot update invoice/receipt. Original accounting period {code} is {status}. Only invoices/receipts in open periods can be modified."` - Original period closed
- `"Cannot move invoice/receipt to period {code}. Target period is {status}."` - Target period not open

## Service Provider Bindings

All interfaces are bound in `app/Providers/AppServiceProvider.php`:

```php
// Sales Domain bindings
$this->app->bind(InvoiceServiceInterface::class, InvoiceService::class);
$this->app->bind(ReceiptServiceInterface::class, ReceiptService::class);
$this->app->bind(InvoiceRepositoryInterface::class, InvoiceRepository::class);
$this->app->bind(ReceiptRepositoryInterface::class, ReceiptRepository::class);
```

## Test Coverage

### InvoiceServiceTest (6 tests, 23 assertions)
✅ Can create invoice in open period  
✅ Cannot create invoice in closed period  
✅ Cannot create invoice without period_id  
✅ Cannot create invoice with invalid period  
✅ Can update invoice in open period  
✅ Cannot update invoice in closed period

### ReceiptServiceTest (6 tests, 23 assertions)
✅ Can create receipt in open period  
✅ Cannot create receipt in closed period  
✅ Cannot create receipt without period_id  
✅ Cannot create receipt with invalid period  
✅ Can update receipt in open period  
✅ Cannot update receipt in closed period

## Dependencies

### External Dependencies
- `AccountingPeriodRepositoryInterface` - To validate period status
- `Illuminate\Support\Facades\DB` - For database transactions
- `Illuminate\Support\Facades\Log` - For audit logging

### Accounting Domain Integration
The Sales domain integrates with the Accounting domain through:
- `AccountingPeriod` model relationship (belongsTo)
- Period status validation via `AccountingPeriodRepository::find()`
- Period lifecycle management (closing/reopening affects Sales transactions)

## Usage Example

```php
use App\Domain\Sales\Contracts\InvoiceServiceInterface;

$invoiceService = app(InvoiceServiceInterface::class);

// Create invoice
$result = $invoiceService->createInvoice([
    'invoice_number' => 'INV-2025-001',
    'issue_date' => '2025-11-15',
    'due_date' => '2025-12-15',
    'customer_id' => 1,
    'total_amount' => 1000.00,
    'currency' => 'USD',
    'period_id' => 2, // Must be open
    'exchange_rate' => 1.0,
    'base_currency_amount' => 1000.00,
]);

if ($result['success']) {
    $invoice = $result['data'];
    echo "Invoice {$invoice->invoice_number} created successfully!";
} else {
    echo "Error: {$result['message']}";
}

// Update invoice
$result = $invoiceService->updateInvoice($invoice->id, [
    'total_amount' => 1500.00,
    'base_currency_amount' => 1500.00,
]);
```

## Transaction Safety

Both services use database transactions (`DB::transaction()`) to ensure:
- **Atomicity**: All operations succeed or all fail
- **Data Integrity**: No partial updates
- **Automatic Rollback**: Any exception reverts all changes

## Logging

All operations are logged for audit purposes:
- Invoice creation: `"Invoice {number} created successfully in period {code}"`
- Invoice update: `"Invoice {number} updated successfully"`
- Receipt creation: `"Receipt {number} created successfully in period {code}"`
- Receipt update: `"Receipt {number} updated successfully"`
- Errors: `"Error creating/updating invoice/receipt: {message}"`

## SOLID Principles Applied

1. **Single Responsibility**: Each class has one reason to change
   - Repositories handle data access only
   - Services handle business logic only
   - Models represent data structures only

2. **Open/Closed**: Extendable through interfaces without modifying existing code

3. **Liskov Substitution**: Any implementation of interface can replace another

4. **Interface Segregation**: Small, focused interfaces (service vs repository)

5. **Dependency Inversion**: Depend on abstractions (interfaces) not concretions

## Future Enhancements

Potential improvements:
- Add validation rules for invoice/receipt data (amounts, dates, etc.)
- Implement invoice payment tracking (paid/unpaid status)
- Add invoice line items support
- Implement receipt allocation to invoices
- Add customer credit limit checking
- Implement invoice cancellation workflow
- Add PDF generation for invoices/receipts
- Implement email notifications
