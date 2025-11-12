# Testing Guide for ClosePeriodCommand

## Overview
This guide explains how to test the `ClosePeriodCommand` class and related services using Laravel's testing framework.

## Test Structure

```
tests/
├── Feature/
│   └── Commands/
│       └── ClosePeriodCommandTest.php      # Integration tests
└── Unit/
    └── Services/
        ├── PeriodClosingServiceTest.php    # Unit tests
        ├── BalanceCalculatorServiceTest.php
        └── AccountingPeriodRepositoryTest.php
```

## Running Tests

### Run All Tests
```bash
php artisan test
```

### Run Specific Test File
```bash
php artisan test tests/Feature/Commands/ClosePeriodCommandTest.php
```

### Run Specific Test Method
```bash
php artisan test --filter test_command_closes_period_successfully
```

### Run with Coverage
```bash
php artisan test --coverage
```

## Test Types

### 1. Feature Tests (Integration Tests)
**Location:** `tests/Feature/Commands/ClosePeriodCommandTest.php`

Tests the command from an end-user perspective using mocked services:

```php
public function test_command_closes_period_successfully(): void
{
    // Mock the service
    $mockService = Mockery::mock(PeriodClosingServiceInterface::class);
    $mockService->shouldReceive('closePeriod')
        ->once()
        ->with('2025-11')
        ->andReturn([
            'success' => true,
            'message' => 'Period closed.',
            'data' => [...]
        ]);

    // Bind the mock
    $this->app->instance(PeriodClosingServiceInterface::class, $mockService);

    // Run command and assert output
    $this->artisan('accounting:close 2025-11')
        ->expectsOutput('Starting closing process...')
        ->assertExitCode(0);
}
```

**What it tests:**
- Command accepts correct arguments
- Command produces correct output
- Command returns correct exit codes
- Command displays tables correctly

### 2. Unit Tests (Service Tests)
**Location:** `tests/Unit/Services/PeriodClosingServiceTest.php`

Tests business logic in isolation with mocked dependencies:

```php
public function test_closes_period_successfully(): void
{
    $period = new AccountingPeriod(['status' => 'open']);
    
    // Mock repository
    $this->mockRepository->shouldReceive('findByCode')
        ->once()
        ->andReturn($period);
    
    // Mock calculator
    $this->mockCalculator->shouldReceive('calculatePeriodBalances')
        ->once()
        ->andReturn(collect([...]));
    
    // Test the service
    $result = $this->service->closePeriod('2025-11');
    
    $this->assertTrue($result['success']);
}
```

**What it tests:**
- Business logic works correctly
- Service handles errors properly
- Service uses dependencies correctly
- Return values are correct

## Test Scenarios Covered

### ✅ Command Tests
1. **Success scenario** - Period closes successfully
2. **Period not found** - Returns error when period doesn't exist
3. **Already closed** - Prevents closing a closed period
4. **Table display** - Balance table renders correctly

### ✅ Service Tests
1. **Successful closing** - Full closing process works
2. **Period not found** - Handles missing period
3. **Already closed** - Prevents duplicate closing
4. **Unbalanced books** - Rejects unbalanced debits/credits
5. **Can close validation** - Validates period status

## Mocking with Mockery

### Mock a Service
```php
$mockService = Mockery::mock(PeriodClosingServiceInterface::class);
$mockService->shouldReceive('closePeriod')
    ->once()                    // Called exactly once
    ->with('2025-11')          // With this argument
    ->andReturn([...]);        // Returns this

$this->app->instance(PeriodClosingServiceInterface::class, $mockService);
```

### Mock a Repository
```php
$mockRepo = Mockery::mock(AccountingPeriodRepositoryInterface::class);
$mockRepo->shouldReceive('findByCode')
    ->once()
    ->with('2025-11')
    ->andReturn($period);
```

## Assertions Available

### Command Assertions
```php
$this->artisan('accounting:close 2025-11')
    ->expectsOutput('Period closed')        // Expects specific output
    ->doesntExpectOutput('Error')           // Doesn't expect this
    ->expectsTable([...], [...])            // Expects table with data
    ->expectsQuestion('Continue?', 'yes')   // For interactive commands
    ->assertExitCode(0);                    // Exit code 0 = success
```

### Standard Assertions
```php
$this->assertTrue($result['success']);
$this->assertFalse($result['success']);
$this->assertEquals('expected', $result['message']);
$this->assertStringContainsString('closed', $result['message']);
$this->assertNull($result['data']);
$this->assertNotNull($result['data']);
$this->assertArrayHasKey('balances', $result['data']);
```

## Benefits of This Testing Approach

### ✅ Fast Tests
- Mocks eliminate database calls
- Tests run in milliseconds
- Can run hundreds of tests quickly

### ✅ Isolated Tests
- Each test is independent
- No database state pollution
- Easy to debug failures

### ✅ Reliable Tests
- No flaky tests from database issues
- Consistent results every time
- Easy to reproduce failures

### ✅ Easy to Write
- Clear test structure
- Simple mocking syntax
- Good IDE support

## Testing Best Practices

### 1. Follow AAA Pattern
```php
public function test_example(): void
{
    // Arrange - Set up test data and mocks
    $mock = Mockery::mock(...);
    
    // Act - Execute the code being tested
    $result = $service->doSomething();
    
    // Assert - Verify the results
    $this->assertTrue($result);
}
```

### 2. One Concept Per Test
```php
// ✅ Good - Tests one thing
public function test_fails_when_period_not_found(): void
{
    // Test only "not found" scenario
}

// ❌ Bad - Tests multiple things
public function test_various_errors(): void
{
    // Tests not found, already closed, etc.
}
```

### 3. Descriptive Test Names
```php
// ✅ Good
public function test_command_closes_period_successfully(): void

// ❌ Bad
public function test_it_works(): void
```

### 4. Don't Test Framework
```php
// ❌ Don't test Laravel's code
public function test_artisan_command_exists(): void

// ✅ Test your business logic
public function test_closes_period_with_valid_data(): void
```

## Running Tests in CI/CD

### GitHub Actions Example
```yaml
- name: Run Tests
  run: php artisan test --parallel
```

### With Coverage
```yaml
- name: Run Tests with Coverage
  run: php artisan test --coverage --min=80
```

## Next Steps

1. **Add more test cases** for edge cases
2. **Test repositories** with database
3. **Add integration tests** with real database
4. **Measure test coverage** and aim for 80%+
5. **Add mutation testing** for test quality

## Useful Commands

```bash
# Run tests with output
php artisan test --verbose

# Run tests in parallel (faster)
php artisan test --parallel

# Run tests with coverage report
php artisan test --coverage-html coverage

# Run only unit tests
php artisan test --testsuite=Unit

# Run only feature tests
php artisan test --testsuite=Feature

# Stop on first failure
php artisan test --stop-on-failure
```
