<?php

namespace Biigle\PulseQueueSizeCard\Http\Livewire;

use Laravel\Pulse\Facades\Pulse;
use Laravel\Pulse\Livewire\Card;

#[Lazy]
class QueueSize extends Card
{
    public function render()
    {
        $queues = Pulse::values('queue_size');
        return view('pulse-queue-size-card::queue-size', ['queues' => $queues]);
    }
}
