<?php

namespace Biigle\PulseQueueSizeCard\Recorders;


use Laravel\Pulse\Events\SharedBeat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Biigle\PulseQueueSizeCard\PulseQueueHistory;


class QueueSize
{

    /**
     * Create a new recorder instance.
     */
    public function __construct($key = 'queue_size')
    {
        $this->lockKey = $key;
    }

    /**
     * Key to save update timestamps in cache
     *
     * @var string
     */
    protected string $lastUpdatedKey = 'queue_size_updated_at';

    protected string $lockKey;

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
    public function record(): void
    {
        $lock = Cache::lock($this->lockKey, 60);

        // Ensures only a single machine is executing the code
        if ($lock->get()) {
            // Default: 60 seconds
            $interval = config('pulse-ext.record_interval');
            $lastUpdate = Cache::get($this->lastUpdatedKey);

            // Record queue sizes
            if ($lastUpdate === null || $lastUpdate->addSeconds($interval)->isNowOrPast()) {
                Cache::put($this->lastUpdatedKey, now());
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

                    PulseQueueHistory::create(['queue' => $queue, 'values' => json_encode($values)]);
                }
            }

            $lock->release();
        }
    }
}
