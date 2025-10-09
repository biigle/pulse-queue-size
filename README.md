<h1>Pulse Queue Size Card</h1>
Displays the queue sizes of queues.

<h2>Requirements</h2>

Requires Laravel Pulse to be installed.

```
composer require laravel/pulse
```

<h2>Installation</h2>

Install the local Laravel package by following the next steps.

Add package path to composer.json

```
    "repositories": [
    {
        "type": "path",
        "url": "path/to/pulse/queue/size/card"
    }
    ],
```

Run

```
composer require biigle/pulse-queue-size-card:@dev
```

<h3>Set up</h3>

Note: This Laravel package is auto-discovered.

1. Make package path accessible by adding the next line to the app and worker services in the `docker-compose.yml`

```
    volumes:
      - ./vendor/biigle/pulse-queue-size-card:/var/www/vendor/biigle/pulse-queue-size-card
```


2. Add the new recorder to the recorder array in the `config/pulse.php`

```
Biigle\PulseQueueSizeCard\Recorders\QueueSize::class => [
            'enabled' => env('PULSE_QUEUE_SIZE_ENABLED', true),
            'sample_rate' => env('PULSE_QUEUES_SAMPLE_RATE', 1),
            ],
```

3. Add the pulse card to the `resources/views/vendor/pulse/dashboard.blade.php` as follows

```
    <livewire:pulse-queue-size-card.queue-size cols="4"/>
```
4. Add the queue names to the queues array in the `config/pulse-ext.php` to monitor their size