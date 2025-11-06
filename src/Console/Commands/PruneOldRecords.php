<?php

namespace Biigle\PulseQueueSizeCard\Console\Commands;


use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneOldRecords extends Command
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
    protected $description = "Delete queue size records older than 24h.";

    /**
     * Execute the command.
     *
     * @return void
     */
    public function handle()
    {
        DB::table(config('pulse-ext.queue_size_table'))
            ->where(
                'timestamp',
                '<',
                now()->subHours(config('pulse-ext.prune_after'))
            )
            ->delete();
    }
}
