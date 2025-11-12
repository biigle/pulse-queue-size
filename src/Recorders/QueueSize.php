<?php

namespace Biigle\PulseQueueSizeCard\Recorders;


use Laravel\Pulse\Facades\Pulse;
use Laravel\Pulse\Events\SharedBeat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;


class QueueSize
{

    /**
     * Create a new recorder instance.
     *
     * @param string $key Cache key for the cache lock
     *
     */
    public function __construct($key = 'queue_size')
    {
        $this->lockKey = $key;
    }

    /**
     * Key to save the update timestamps in the cache
     *
     * @var string
     */
    protected $lastUpdatedKey = 'queue_size_updated_at';

    /**
     * Key of the cache lock that can be acquired
     *
     * @var string
     */
    protected $lockKey;

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

            // Record the queue sizes
            if ($lastUpdate === null || $lastUpdate->addSeconds($interval)->isNowOrPast()) {
                Cache::put($this->lastUpdatedKey, now());
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
            }

            $lock->release();
        }
    }
}
