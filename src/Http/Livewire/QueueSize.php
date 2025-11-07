<?php

namespace Biigle\PulseQueueSizeCard\Http\Livewire;

use Biigle\PulseQueueSizeCard\PulseQueueHistory;
use Livewire\Livewire;
use Illuminate\Support\Carbon;
use Laravel\Pulse\Livewire\Card;

#[Lazy]
class QueueSize extends Card
{
    public function render()
    {
        $tz = config('app.timezone');

        [$queues, $time, $runAt] = $this->remember(
            function () use ($tz) {
                $queues = collect([]);
                $query = PulseQueueHistory::where(
                    'timestamp',
                    '>=',
                    Carbon::now('UTC')->subHours($this->periodAsInterval()->hours)
                )
                    ->orderBy('id');

                foreach ($query->lazy() as $record) {
                    $date = Carbon::parse($record->timestamp)->setTimezone($tz)->toDateTimeString();
                    $queue = $record->queue;

                    if (!isset($queues[$queue])) {
                        $queues[$queue] = collect([]);
                    }

                    $queues[$queue][$date] = $record->values;
                }

                return $queues;
            }
        );

        if (!sizeof($queues)) {
            $queues = collect([]);
        }

        if (Livewire::isLivewireRequest()) {
            foreach ($queues->keys() as $index => $key) {
                $this->dispatch('queues-sizes-chart-update', queues: [$key => $queues[$key]]);
            }
        }

        return view('pulse-queue-size-card::queue-size', [
            'queues' => $queues,
            'time' => $time,
            'runAt' => $runAt,
        ]);
    }
}
