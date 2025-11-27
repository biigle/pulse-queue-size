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
     * Get the queues (with connections) that should be recoeded.
     */
    public static function getQueuesToRecord(): array
    {
        $queues = config("pulse.recorders.".static::class.".queues");
        $defaultConnection = config('queue.default');

        return array_map(fn ($q) =>  str_contains($q, ":") ? $q : "$defaultConnection:$q", $queues);
    }

    /**
     * Record the job.
     */
    public function record(IsolatedBeat $event): void
    {
        $interval = config("pulse.recorders.".static::class.".record_interval");

        if ($event->time->second % $interval === 0) {
            $states = ['pending', 'delayed', 'reserved'];
            $queues = static::getQueuesToRecord();

            foreach ($queues as $queue) {
                Artisan::call("queue:monitor $queue --json");
                $output = json_decode(Artisan::output(), true)[0];

                foreach ($states as $state) {
                    Pulse::record($state, $queue, $output[$state], $event->time)->max();
                }
            }
        }
    }
}
