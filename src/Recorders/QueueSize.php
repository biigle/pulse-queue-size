<?php

namespace Biigle\PulseQueueSizeCard\Recorders;


use Illuminate\Support\Facades\DB;
use Laravel\Pulse\Events\SharedBeat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;


class QueueSize
{

    /**
     * Key to save update timestamps in cache
     *
     * @var string
     */
    protected string $LAST_UPDATE_KEY = 'queue_size_updated_at';

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
        $lock = Cache::lock('queue_size', 60);

        // Ensures only a single machine is executing the code
        if ($lock->get()) {
            // Default: 60 seconds
            $interval = 10;
            $lastUpdate = Cache::get($this->LAST_UPDATE_KEY);

            // Record queue sizes
            if ($lastUpdate === null || $lastUpdate->addSeconds($interval)->isNowOrPast()) {
                Cache::put($this->LAST_UPDATE_KEY, now());
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

                    DB::table(config('pulse-ext.queue_size_table'))->insert([
                        'queue' => $queue,
                        'values' => json_encode($values),
                    ]);
                }
            }

            $lock->release();
        }
    }
}
