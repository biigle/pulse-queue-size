<?php

namespace Biigle\PulseQueueSizeCard\Recorders;

use Laravel\Pulse\Facades\Pulse;
use Illuminate\Support\Facades\Queue;
use Illuminate\Queue\Events\JobPopped;
use Illuminate\Queue\Events\JobQueued;


// #[Lazy]
class QueueSize
{
    /**
     * The events to listen for.
     *
     * @var list<class-string>
     */
    public array $listen = [
        JobQueued::class,
        JobPopped::class,
    ];

    /**
     * Record the job.
     */
    public function record(JobQueued|JobPopped $event): void
    {
        if ($event->connectionName === 'sync') {
            return;
        }

        $queue = null;
        $value = 0;

        if ($event instanceof JobQueued) {
            $queue = $event->queue ?: 'default';
            $value = Queue::size($queue);
        } else {
            $queue = $event->job->getQueue();
            $value = Queue::size($queue) - 1;
        }

        Pulse::set('queue_size', $queue, $value);
    }
}
