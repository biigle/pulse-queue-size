<h1>Pulse Queue Size Card</h1>
Displays the queue sizes.

<h2>Requirements</h2>

Requires Laravel Pulse to be installed.

```
composer require laravel/pulse
```

<h2>Installation</h2>

Install the package by running the following command.

```
composer require biigle/pulse-queue-size-card:dev-pulse-queue-size-card
```

<h3>Set up</h3>

Note: This Laravel package is auto-discovered.

1. Add the new recorder to the recorder array in the `config/pulse.php`

```
Biigle\PulseQueueSizeCard\Recorders\QueueSize::class => [
            'enabled' => env('PULSE_QUEUE_SIZE_ENABLED', true),
            'record_interval' => 60, // time interval between records
            'queues' => ['default'] // queues to monitor
            ],
```

2. Add the pulse card to the `resources/views/vendor/pulse/dashboard.blade.php` as follows

```
    <livewire:pulse-queue-size-card.queue-size cols="4"/>
```

3. Start Laravel Pulse by running `php artisan pulse:check`.