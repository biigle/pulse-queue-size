<?php

namespace Biigle\PulseQueueSizeCard\Recorders;


use Illuminate\Support\Facades\DB;
use Laravel\Pulse\Events\SharedBeat;
use Illuminate\Support\Facades\Artisan;
use Laravel\Pulse\Recorders\Concerns\Throttling;

class QueueSize
{
    use Throttling;

    /**
     * The events to listen for.
     *
     * @var list<class-string>
     */
    public array $listen = [
        SharedBeat::class
    ];

    /**
     * Record the job.
     */
    public function record(SharedBeat $event): void
    {
        // Default: 60 seconds
        $interval = config('pulse-ext.record_interval');

        // Record queue sizes
        $this->throttle($interval, $event, function () {
            $status = config('pulse-ext.queue_status');
            $queues = config('pulse-ext.queues');
            $defaultConnection = config('queue.default');

            foreach ($queues as $queue) {
                if (!str_contains($queue, ":")) {
                    $queue = "$defaultConnection:$queue";
                }

                Artisan::call("queue:monitor $queue --json");
                $output = json_decode(Artisan::output(), true)[0];

                $values = [];
                foreach ($status as $state) {
                    $values[$state] = $output[$state];
                }

                DB::table('queue_sizes')->insert([
                    'queue' => $queue,
                    'values' => json_encode($values),
                ]);
            }
        });
    }
}
