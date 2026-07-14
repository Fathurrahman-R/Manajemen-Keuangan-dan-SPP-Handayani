<?php

namespace App\Console\Commands;

use App\Models\MidtransTransactionLog;
use Illuminate\Console\Command;

class MidtransPruneLogsCommand extends Command
{
    protected $signature = 'midtrans:prune-logs {--days= : Number of days to retain logs}';

    protected $description = 'Delete midtrans_transaction_logs rows older than the specified number of days';

    public function handle(): int
    {
        $days = $this->option('days') ?? config('midtrans.log_retention_days', 180);
        $days = (int) $days;

        $cutoff = now()->subDays($days);

        $deleted = MidtransTransactionLog::where('created_at', '<', $cutoff)->delete();

        $this->info("Pruned {$deleted} midtrans transaction log(s) older than {$days} days.");

        return Command::SUCCESS;
    }
}
