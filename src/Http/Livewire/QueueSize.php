<?php

namespace Biigle\PulseQueueSizeCard\Http\Livewire;

use Biigle\PulseQueueSizeCard\Recorders\QueueSize as Recorder;
use Illuminate\Support\Str;
use Laravel\Pulse\Livewire\Card;
use Livewire\Livewire;

#[Lazy]
class QueueSize extends Card
{
    public function render()
    {
        [$queues, $time, $runAt] = $this->remember(
            fn() => $this->graph(['pending', 'delayed', 'reserved'], 'max')
        );

        // Show queues in the same order than they are defined in the config.
        $sortIndex = array_flip(Recorder::getQueuesToRecord());
        $queues = $queues->sortKeysUsing(
            fn ($a, $b) => ($sortIndex[$a] ?? INF) - ($sortIndex[$b] ?? INF)
        );

        // Show only queues with values.
        $queues = $queues->filter(function ($q) {
            return $q['pending']->contains(fn ($v) => floatval($v) !== 0.0) ||
                $q['delayed']->contains(fn ($v) => floatval($v) !== 0.0) ||
                $q['reserved']->contains(fn ($v) => floatval($v) !== 0.0);
        });

        if (Livewire::isLivewireRequest()) {
            $this->dispatch('queues-sizes-chart-update', queues: $queues);
        }

        $defaultConnection = config('queue.default');
        $showConnection = $queues->keys()->contains(fn ($q) => !str_starts_with($q, $defaultConnection.':'));

        return view('pulse-queue-size-card::queue-size', [
            'queues' => $queues,
            'time' => $time,
            'runAt' => $runAt,
            'showConnection' => $showConnection,
        ]);
    }
}
