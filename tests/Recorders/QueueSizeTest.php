<?php

namespace Biigle\PulseQueueSizeCard\Tests;

use Carbon\CarbonImmutable;
use Laravel\Pulse\Events\SharedBeat;
use Laravel\Pulse\Facades\Pulse;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\TestCase;
use Biigle\PulseQueueSizeCard\Recorders\QueueSize;


class QueueSizeTest extends TestCase
{
    public function testRecorder()
    {
        config(['pulse-ext.queues' => ['default']]);
        $pulse_key = config('pulse-ext.queue_list');
        $eventTime = CarbonImmutable::createFromTime(0, 0, 0);
        $recorder = new QueueSize;

        Queue::shouldReceive('size')->times(3)->andReturnValues([1, 2, 3]);

        Pulse::shouldReceive('record')->with('default', $pulse_key, 1)->once();
        Pulse::shouldReceive('record')->with('default', $pulse_key, 2)->once();
        Pulse::shouldReceive('record')->with('default', $pulse_key, 3)->once();

        $recorder->record(new SharedBeat($eventTime, "test"));
        $recorder->record(new SharedBeat($eventTime, "test"));
        $recorder->record(new SharedBeat($eventTime, "test"));
    }

    public function testRecorderInvalidTime()
    {
        config(['pulse-ext.queues' => ['default']]);
        $recorder = new QueueSize;

        Queue::shouldReceive('size')->never();
        Pulse::shouldReceive('record')->never();

        $recorder->record(
            new SharedBeat(
                CarbonImmutable::createFromTime(0, 0, 1),
                "test"
            )
        );
    }
}
