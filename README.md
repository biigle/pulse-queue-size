<h1>Pulse Queue Size Card</h1>
Displays for each queue the number of jobs that are currently enqueued.

<h2>Installation</h2>

```
composer require biigle/pulse-queue-size-card
```

<h3>Laravel</h3>

The service provider is auto-discovered by Laravel.


<h3>Set up</h3>

Add this to the providers array in the config/app.php.

```
Biigle\PulseQueueSizeCard\PulseQueueSizeCardServiceProvider::class
```

<br>


Add the recorder to the recorder array in the config/pulse.php.

```
Biigle\PulseQueueSizeCard\Recorders\QueueSize::class => [
            'enabled' => env('PULSE_QUEUE_SIZE_ENABLED', true),
            'sample_rate' => env('PULSE_QUEUES_SAMPLE_RATE', 1),
            ],
```
