<?php

namespace Biigle\PulseQueueSizeCard\Http\Livewire;

use Laravel\Pulse\Facades\Pulse;
use Laravel\Pulse\Livewire\Card;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;


class QueueSize extends Card
{
    public function render()
    {
        $queues = $this->aggregate('queue_size', 'sum');
        Log::info($queues);
        return view('pulse-queue-size-card::queue-size', ['queues' => $queues]);
    }
}