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
                $queues = collect([]);
                $defaultConnection = config('queue.default');
                $types = array_map(
                    fn($q) => str_contains($q, ":") ? $q : "$defaultConnection:$q",
                     config('pulse-ext.queues')
                    );

                $query = DB::table('pulse_entries')
                    ->where('key', '=', config('pulse-ext.queue_list'))
                    ->where(
                        'timestamp',
                        '>=',
                        Carbon::now()->subHours($this->periodAsInterval()->hours)->timestamp
                    )
                    ->whereIn('type', $types)
                    ->select('type', 'value', 'timestamp')
                    ->orderBy('id');

                foreach ($query->lazy() as $queue) {
                    $date = Carbon::createFromTimestamp($queue->timestamp, $tz)->toDateTimeString();

                    if (!isset($queues[$queue->type])) {
                        $queues[$queue->type] = collect([]);
                    }

                    $queues[$queue->type][$date] = $queue->value;
                }

                return $queues;
            }
        );

        if (!sizeof($queues)) {
            $queues = collect([]);
        }

        if (Livewire::isLivewireRequest()) {
            foreach ($queues->keys() as $key => $value) {
                $this->dispatch('queues-sizes-chart-update', queues: [$value => $queues[$value]]);
            }
        }

        return view('pulse-queue-size-card::queue-size', [
            'queues' => $queues,
            'time' => $time,
            'runAt' => $runAt,
        ]);
    }
}
