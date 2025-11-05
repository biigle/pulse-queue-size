<?php

namespace Biigle\PulseQueueSizeCard\Recorders;


use Laravel\Pulse\Facades\Pulse;
use Laravel\Pulse\Events\SharedBeat;
use Illuminate\Support\Facades\Cache;
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
        // Ensures that recorder is used only by a single machine
        // if (Cache::has('queue_size_recorder')) {
        //     $eventID = Cache::get('queue_size_recorder');
        //     if ($event->instance != $eventID) {
        //         return;
        //     }
        // } else {
        //     Cache::put('queue_size_recorder', $event->instance);
        // }

        // Default: 60 seconds
        $interval = config('pulse-ext.record_interval');
        // Record queue sizes
        $this->throttle(10, $event, function () {
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
