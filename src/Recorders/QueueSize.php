<?php

namespace Biigle\PulseQueueSizeCard\Recorders;


use Laravel\Pulse\Facades\Pulse;
use Laravel\Pulse\Events\IsolatedBeat;
use Illuminate\Support\Facades\Artisan;

class QueueSize
{

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
        $config = "pulse.recorders." . $this::class;
        $interval = config("$config.record_interval");

        // Record the queue sizes
        if ($event->time->second % $interval === 0) {
            $states = ['pending', 'delayed', 'reserved'];
            $queues = config("$config.queues");
            $defaultConnection = config('queue.default');

            foreach ($queues as $queue) {
                if (!str_contains($queue, ":")) {
                    $queue = "$defaultConnection:$queue";
                }

                Artisan::call("queue:monitor $queue --json");
                $output = json_decode(Artisan::output(), true)[0];

                foreach ($states as $state) {
                    Pulse::record($state, $queue, $output[$state], $event->time)->max();
                }
            }
        }
    }
}
