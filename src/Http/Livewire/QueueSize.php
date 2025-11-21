<?php

namespace Biigle\PulseQueueSizeCard\Http\Livewire;

use Livewire\Livewire;
use Illuminate\Support\Str;
use Laravel\Pulse\Livewire\Card;

#[Lazy]
class QueueSize extends Card
{
    public function render()
    {
        $config = 'pulse.recorders.' . \Biigle\PulseQueueSizeCard\Recorders\QueueSize::class;
        [$queues, $time, $runAt] = $this->remember(
            fn() =>
            $this->graph(['pending', 'delayed', 'reserved'], 'sum')
        );

        if (!sizeof($queues)) {
            $queues = collect([]);
        }

        if (Livewire::isLivewireRequest()) {
            $this->dispatch('queues-sizes-chart-update', queues: $queues);
        }

        return view('pulse-queue-size-card::queue-size', [
            'queues' => $queues,
            'time' => $time,
            'runAt' => $runAt,
            'showConnection' => $queues->keys()->map(fn ($queue) => Str::before($queue, ':'))->unique()->count() > 1,
        ]);
    }
}
