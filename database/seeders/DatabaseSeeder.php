<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed in correct order to maintain foreign key relationships
        $this->call([
            CurrencySeeder::class,
            AccountingPeriodSeeder::class,
            AccountSeeder::class,
            CustomerSeeder::class,
            InvoiceSeeder::class,
            ReceiptSeeder::class,
            JournalEntrySeeder::class, // Must be after invoices and receipts
        ]);

        // Create test user
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
