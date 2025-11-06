<?php

namespace Biigle\PulseQueueSizeCard;


use Livewire\Livewire;
use Illuminate\Support\ServiceProvider;

use Illuminate\Console\Scheduling\Schedule;
use Biigle\PulseQueueSizeCard\Http\Livewire\QueueSize;
use Biigle\PulseQueueSizeCard\Console\Commands\PruneOldRecords;

class PulseQueueSizeCardServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/config/pulse-ext.php', 'pulse-ext');
        $this->loadMigrationsFrom(__DIR__.'/Database/mirgrations');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Livewire::component('pulse-queue-size-card.queue-size', QueueSize::class);

        $this->loadViewsFrom(__DIR__ . '/../resources/views', "pulse-queue-size-card");

        $this->publishes([__DIR__ . '/config/pulse-ext.php' => config_path('pulse-ext.php')]);

        if ($this->app->runningInConsole()) {
            $this->commands([
                PruneOldRecords::class
            ]);

            $this->app->booted(function () {
                $schedule = app(Schedule::class);

                $schedule->command(PruneOldRecords::class)
                    ->daily()
                    ->onOneServer();
            });
        }
    }

}
