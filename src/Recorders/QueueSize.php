<?php

namespace Biigle\PulseQueueSizeCard\Recorders;


use Laravel\Pulse\Events\IsolatedBeat;
use Laravel\Pulse\Facades\Pulse;
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
        IsolatedBeat::class
    ];

    /**
     * Record the job.
     */
    public function record(IsolatedBeat $event): void
    {
        // Default: 60 seconds
        $interval = config('pulse-ext.record_interval');

        // Record the queue sizes
        $this->throttle($interval, $event, function () {
            $status = config('pulse-ext.queue_status');
            $id = config('pulse-ext.queue_size_card_id');
            $queues = config('pulse-ext.queues');
            $defaultConnection = config('queue.default');

            foreach ($queues as $queue) {
                if (!str_contains($queue, ":")) {
                    $queue = "$defaultConnection:$queue";
                }

                Artisan::call("queue:monitor $queue --json");
                $output = json_decode(Artisan::output(), true)[0];

                foreach ($status as $state) {
                    Pulse::record($queue . "$$state", $id, $output[$state]);
                }
            }
        });
    }
}
