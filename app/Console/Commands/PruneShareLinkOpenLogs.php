<?php
// app/Console/Commands/PruneShareLinkOpenLogs.php

namespace App\Console\Commands;

use App\Models\V4ShareLinkLog;
use Illuminate\Console\Command;

class PruneShareLinkOpenLogs extends Command
{
    protected $signature = 'share-links:prune-open-logs';

    protected $description = 'Delete share-link "opened" log rows older than 12 months (audit rows are kept)';

    public function handle(): int
    {
        $deleted = V4ShareLinkLog::where('action', 'opened')
            ->where('created_at', '<', now()->subMonths(12))
            ->delete();

        $this->info("Pruned {$deleted} opened rows");

        return self::SUCCESS;
    }
}
