<?php

namespace Biigle\PulseQueueSizeCard\Recorders;

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
        $type = "$event->connectionName:$queue";


        if(!Cache::has('queue_size')){
            Cache::put('queue_size', []);
        }

        if (in_array($type, Cache::get('queue_size'))) {
            $value = 1;
        } else {
            $types = Cache::get('queue_size');
            $types[] = $type;
            Cache::put('queue_size', $types); // wird das überhaupt gebraucht?
            $value = Queue::size($queue);
        }

        Pulse::record($type, 'queue_size', $value)->sum();
    }
}
