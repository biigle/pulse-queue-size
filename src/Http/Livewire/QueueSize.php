<?php

namespace Biigle\PulseQueueSizeCard\Http\Livewire;

use Livewire\Livewire;
use Laravel\Pulse\Livewire\Card;
use Illuminate\Support\Facades\Cache;

// #[Lazy]
class QueueSize extends Card
{
    public function render()
    {

        $types = Cache::get('queue_size', []);

        [$queues, $time, $runAt] = $this->remember(
            fn() =>
            $this->graph($types, 'sum')
        );

        if (sizeof($queues)) {
            $queues = $queues['queue_size']->map(fn($v, $k) => $v->map(fn($vv, $kk) => intval($vv)));
        } else {
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
