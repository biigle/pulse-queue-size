<?php

namespace Biigle\PulseQueueSizeCard\Http\Livewire;

use Livewire\Livewire;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;
use Laravel\Pulse\Livewire\Card;
use Illuminate\Support\Facades\DB;

#[Lazy]
class QueueSize extends Card
{
    public function render()
    {        
        $config = 'pulse.recorders.'. \Biigle\PulseQueueSizeCard\Recorders\QueueSize::class;
        [$queues, $time, $runAt] = $this->remember(
            function () use ($config) {
                $queues = collect();
                $getArrayString = fn($a) => "ARRAY['" . implode("','", $a) . "']";
                $queueIDs = $getArrayString(config('pulse-ext.queues'));
                $interval = $this->periodAsInterval()->hours;

                $query = DB::table('pulse_entries')
                    ->where('key', '=', config("$config.id"))
                    ->where(
                        'timestamp',
                        '>=',
                        Carbon::now()->subHours($interval)->timestamp
                    )
                    ->whereRaw("type ~ ANY($queueIDs)") // filter queue
                    ->select('type', 'value', 'timestamp')
                    ->orderBy('id');

                foreach ($query->lazy() as $queue) {
                    $date = Carbon::createFromTimestamp($queue->timestamp);
                    $currentDate = fn() => $date->copy();
                    list($queueID, $status) = explode("$", $queue->type);

                    if (!isset($queues[$queueID])) {
                        $queues[$queueID] = collect();
                    }

                    if (!isset($queues[$queueID][$status])) {
                        // Fill collection with 60 values beginning at interval start to maintain x-axis scaling
                        $past = collect(CarbonPeriod::create(
                            $currentDate()->subHours($interval),
                            "$interval minutes",
                            $currentDate()->subMinute()
                        ))->mapWithKeys(fn($time) => [$time->toDateTimeString() => 0]);
                        $queues[$queueID][$status] = $past;
                    }

                    $lastKey = $queues[$queueID][$status]->keys()->last();
                    $lastDate = Carbon::createFromTimeString($lastKey, 'UTC');
                    $lastDate->addMinutes($interval);

                    if ($lastDate->isBefore($date) || $lastDate->is($date)) {
                        $queues[$queueID][$status][$date->toDateTimeString()] = $queue->value;
                        // Allow at most 60 entries like other Pulse controllers
                        $queues[$queueID][$status]->shift();
                    }
                }

                return $queues;
            }
        );

        if (!sizeof($queues)) {
            $queues = collect([]);
        }

        if (Livewire::isLivewireRequest()) {
            foreach ($queues->keys() as $key) {
                $this->dispatch('queues-sizes-chart-update', queues: [$key => $queues[$key]]);
            }
        }

        return view('pulse-queue-size-card::queue-size', [
            'queues' => $queues,
            'time' => $time,
            'runAt' => $runAt,
            'sampleRate' => config($config . '.sample_rate')
        ]);
    }
}
