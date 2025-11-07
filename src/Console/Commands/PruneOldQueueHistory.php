<?php

namespace Biigle\PulseQueueSizeCard\Console\Commands;

use Biigle\PulseQueueSizeCard\PulseQueueHistory;
use Illuminate\Console\Command;

class PruneOldQueueHistory extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'pulse-queue-size-card:prune';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Delete pulse queue history records older than 24h.";

    /**
     * Execute the command.
     *
     * @return void
     */
    public function handle()
    {
        PulseQueueHistory::where(
            'timestamp',
            '<',
            now()->subHours(config('pulse-ext.prune_after'))
        )
            ->delete();
    }
}
