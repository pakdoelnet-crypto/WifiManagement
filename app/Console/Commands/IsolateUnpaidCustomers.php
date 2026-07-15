<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Services\CustomerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class IsolateUnpaidCustomers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:isolate-unpaid';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically isolate customers with unpaid overdue invoices';

    /**
     * Execute the console command.
     */
    public function handle(CustomerService $customerService)
    {
        $this->info('Starting automated isolation scan for unpaid invoices...');

        // Find all unpaid invoices where due date has passed
        $overdueInvoices = Invoice::where('status', 'unpaid')
            ->where('due_date', '<', now()->toDateString())
            ->get();

        $isolatedCount = 0;

        foreach ($overdueInvoices as $invoice) {
            $invoice->update(['status' => 'overdue']);
            
            $customer = $invoice->customer;
            if ($customer && $customer->status === 'active') {
                try {
                    $customerService->toggleStatus($customer->id, 'isolir');
                    $this->info("Customer {$customer->name} ({$customer->pppoe_username}) has been isolated due to unpaid invoice {$invoice->invoice_number}.");
                    Log::info("Automated Billing Isolation: Customer ID {$customer->id} isolated.");
                    $isolatedCount++;
                } catch (\Exception $e) {
                    $this->error("Failed to isolate customer {$customer->name}: " . $e->getMessage());
                    Log::error("Failed to isolate customer {$customer->id} automatically: " . $e->getMessage());
                }
            }
        }

        $this->info("Scan completed. $isolatedCount customers isolated.");
    }
}
