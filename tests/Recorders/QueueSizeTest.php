<?php

namespace Biigle\PulseQueueSizeCard\Tests;

use Mockery;
use Laravel\Pulse\Entry;
use Carbon\CarbonImmutable;
use Laravel\Pulse\Facades\Pulse;
use Laravel\Pulse\Events\IsolatedBeat;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Testing\TestCase;
use Biigle\PulseQueueSizeCard\Recorders\QueueSize;


class QueueSizeTest extends TestCase
{
    private $config = 'pulse.recorders.' . QueueSize::class;

    public function testRecorder()
    {
        config([$this->config . ".queues" => ['default']]);
        $defaultConnection = config('queue.default');
        $queue = "$defaultConnection:default";
        $recorder = new QueueSize;

        $value = '[{"pending":1,"delayed":2,"reserved":3}]';

        Artisan::shouldReceive('call')->once();
        Artisan::shouldReceive('output')->once()->andReturns($value);

        $eventTime = CarbonImmutable::now()->startOfMinute();
        $recorder = new QueueSize;

        $mock = Mockery::mock(Entry::class);
        $mock->shouldReceive('max')->times(3);

        Pulse::shouldReceive('record')
            ->with('pending', $queue, 1, $eventTime)
            ->andReturn($mock)
            ->ordered()
            ->once();

        Pulse::shouldReceive('record')
            ->with('delayed', $queue, 2, $eventTime)
            ->andReturn($mock)
            ->ordered()
            ->once();

        Pulse::shouldReceive('record')
            ->with('reserved', $queue, 3, $eventTime)
            ->andReturn($mock)
            ->ordered()
            ->once();

        $recorder->record(new IsolatedBeat($eventTime));
    }
}
