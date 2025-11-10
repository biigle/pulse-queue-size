<?php

namespace Biigle\PulseQueueSizeCard\Http\Livewire;

use Livewire\Livewire;
use Illuminate\Support\Carbon;
use Laravel\Pulse\Livewire\Card;
use Illuminate\Support\Facades\DB;

#[Lazy]
class QueueSize extends Card
{
    public function render()
    {
        $tz = config('app.timezone');

        [$queues, $time, $runAt] = $this->remember(
            function () use ($tz) {
                $queues = collect();
                $query = DB::table('pulse_entries')
                    ->where('key', '=', config('pulse-ext.queue_size_card_id'))
                    ->where(
                        'timestamp',
                        '>=',
                        Carbon::now()->subHours($this->periodAsInterval()->hours)->timestamp
                    )
                    ->select('type', 'value', 'timestamp')
                    ->orderBy('id');

                foreach ($query->lazy() as $queue) {
                    $date = Carbon::createFromTimestamp($queue->timestamp, $tz)->toDateTimeString();
                    list($queueID, $status) = explode("$", $queue->type);

                    if (!isset($queues[$queueID])) {
                        $queues[$queueID] = collect();
                    }

                    if (!isset($queues[$queueID][$status])) {
                        $queues[$queueID][$status] = collect();
                    }

                    $queues[$queueID][$status][$date] = $queue->value;
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
        ]);
    }
}
