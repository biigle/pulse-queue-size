<?php

namespace Biigle\PulseQueueSizeCard\Http\Livewire;

use Livewire\Livewire;
use Illuminate\Support\Carbon;
use Laravel\Pulse\Livewire\Card;
use Illuminate\Support\Facades\DB;

/**
 * @internal
 */
#[Lazy]
class QueueSize extends Card
{
    public function render()
    {
        $tz = config('app.timezone');

        [$queues, $time, $runAt] = $this->remember(
            function () use ($tz) {
                $queues = collect([]);
                $types = config('pulse-ext.queues');

                $query = DB::table('pulse_entries')
                    ->where(
                        'timestamp',
                        '>=',
                        Carbon::now()->subHours($this->periodAsInterval()->hours)->timestamp
                    )
                    ->whereIn('type', $types)
                    ->select('type', 'value', 'timestamp')
                    ->orderBy('id');

                foreach ($query->lazy() as $queue) {
                    $name = ucfirst($queue->type);
                    $date = Carbon::createFromTimestamp($queue->timestamp, $tz)->toDateTimeString();

                    if (!isset($queues[$name])) {
                        $queues[$name] = collect([]);
                    }

                    $queues[$name][$date] = $queue->value;
                }

                return $queues;
            }
        );

        if (Livewire::isLivewireRequest()) {
            $this->dispatch(
                'queues-sizes-chart-update',
                queues: $queues,
            );
        }

        return view('pulse-queue-size-card::queue-size', [
            'queues' => $queues,
            'time' => $time,
            'runAt' => $runAt,
        ]);
    }
}
