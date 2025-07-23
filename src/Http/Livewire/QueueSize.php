<?php

namespace Biigle\PulseQueueSizeCard\Http\Livewire;

use Laravel\Pulse\Livewire\Card;

class QueueSize extends Card
{
    public function render()
    {
        $queues = $this->aggregate('queue_size', 'sum');
        return view('pulse-queue-size-card::queue-size', ['queues' => $queues]);
    }
}