<?php

namespace Biigle\PulseQueueSizeCard;


use Livewire\Livewire;
use Illuminate\Support\ServiceProvider;

use Biigle\PulseQueueSizeCard\Http\Livewire\QueueSize;

class PulseQueueSizeCardServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Livewire::component('pulse-queue-size-card.queue-size', QueueSize::class);

        $this->loadViewsFrom(__DIR__ . '/../resources/views', "pulse-queue-size-card");

    }

}
