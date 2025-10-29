<?php

namespace Biigle\PulseQueueSizeCard\Recorders;

use Laravel\Pulse\Facades\Pulse;
use Laravel\Pulse\Events\SharedBeat;
use Illuminate\Support\Facades\Queue;



class QueueSize
{
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
        if ($event->time->second % $interval == 0) {
            $queues = config('pulse-ext.queues');
            $defaultConnection = config('queue.default');
            foreach ($queues as $queue) {
                if (str_contains($queue, ":")) {
                    $queueName = explode(":", $queue)[1];
                } else {
                    $queueName = $queue;
                    $queue = "$defaultConnection:$queue";
                }
                $value = Queue::size($queueName);
                Pulse::record($queue, config('pulse-ext.queue_list'), $value);
            }
        }
    }
}
