<?php

namespace Biigle\PulseQueueSizeCard\Recorders;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use Laravel\Pulse\Events\SharedBeat;
use Laravel\Pulse\Facades\Pulse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Queue\Events\JobPopped;
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
        JobPopped::class,
        SharedBeat::class
    ];

    /**
     * Record the job.
     */
    public function record(JobQueued|JobPopped|SharedBeat $event): void
    {
        //TOOO: use only sharedBeat to save records; use other events only to get the queue and connection names
        
        if ($event instanceof SharedBeat && $event->time->second % 60 == 0 && Cache::has('queue_size')) {
            $types = Cache::get('queue_size');
            foreach ($types as $type => $timestamp) {
                if (CarbonInterval::diff($timestamp, Carbon::now())->minutes == 1) {
                    $queue = explode(':', $type)[1];
                    $value = Queue::size($queue);
                    Pulse::record($type, 'queue_size', $value);
                    Log::info("shared beat $type: $value");
                    $types[$type] = Carbon::now();
                    Cache::put('queue_size', $types);
                }
            }
            return;
        }

        if ($event->connectionName === 'sync' || $event instanceof SharedBeat) {
            return;
        }

        $newQueue = false;

        if (!Cache::has('queue_size')) {
            Cache::put('queue_size', []);
        }

        $value = 0;

        if ($event instanceof JobQueued) {
            $queue = $event->queue ?: 'default';
            $type = "$event->connectionName:$queue";
            $value = Queue::size($queue);
        } else {
            $queue = $event->job->getQueue() ?: 'default';
            $connection = $event->job->getConnectionName();
            $type = "$connection:$queue";
            $value = Queue::size($queue) - 1;
        }

        if (!isset(Cache::get('queue_size')[$type])) {
            $types = Cache::get('queue_size');
            $types[$type] = Carbon::now();
            Cache::put('queue_size', $types);
            $newQueue = true;
        }

        $queue_timestamp = Cache::get('queue_size')[$type];
        $now = Carbon::now();
        if ($newQueue || CarbonInterval::diff($queue_timestamp, $now)->minutes == 1) {
            Log::info("new record $type: $value");
            Pulse::record($type, 'queue_size', $value);
            $types = Cache::get('queue_size');
            $types[$type] = Carbon::now();
            Cache::put('queue_size', $types);
        }

    }
}
