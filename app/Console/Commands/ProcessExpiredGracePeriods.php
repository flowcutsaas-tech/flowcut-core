<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PaymentFailureService;

class ProcessExpiredGracePeriods extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:process-expired-grace-periods';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process subscriptions with expired grace periods and suspend them';

    /**
     * Execute the console command.
     */
    public function handle(PaymentFailureService $paymentFailureService)
    {
        $this->info('Processing expired grace periods...');

        try {
            $paymentFailureService->processExpiredGracePeriods();
            $this->info('✅ Expired grace periods processed successfully.');
        } catch (\Exception $e) {
            $this->error('❌ Error processing grace periods: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
