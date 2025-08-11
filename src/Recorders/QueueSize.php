<?php

namespace Biigle\PulseQueueSizeCard\Recorders;

use Carbon\Carbon;
use Laravel\Pulse\Facades\Pulse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Queue\Events\JobQueued;


class QueueSize
{
    /**
     * The events to listen for.
     *
     * @var list<class-string>
     */
    public array $listen = [
        JobQueued::class,
    ];

    /**
     * Record the job.
     */
    public function record(JobQueued $event): void
    {
        if ($event->connectionName === 'sync') {
            return;
        }

        $queue = $event->queue ?: 'default';
        $key = "queue_size.$queue";
        if (Cache::has($key)) {
            $value = 1;
        } else {
            Cache::put($key, True);
            $value = Queue::size($queue);
        }

        Pulse::record('queue_size', $queue, $value)->sum();
    }
}
