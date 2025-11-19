<?php

namespace Biigle\PulseQueueSizeCard\Tests;

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
        $id = config($this->config . ".id");
        $defaultConnection = config('queue.default');
        $recorder = new QueueSize;

        $value = '[{"pending":1,"delayed":2,"reserved":3}]';

        $getQueueID = fn($q, $s) => "$defaultConnection:$q$$s";

        Artisan::shouldReceive('call')->once();
        Artisan::shouldReceive('output')->once()->andReturns($value);

        $eventTime = CarbonImmutable::now()->startOfMinute();
        $recorder = new QueueSize;

        Pulse::shouldReceive('record')
            ->with($getQueueID('default', 'pending'), $id, 1, $eventTime)
            ->ordered()
            ->once();

        Pulse::shouldReceive('record')
            ->with($getQueueID('default', 'delayed'), $id, 2, $eventTime)
            ->ordered()
            ->once();

        Pulse::shouldReceive('record')
            ->with($getQueueID('default', 'reserved'), $id, 3, $eventTime)
            ->ordered()
            ->once();

        $recorder->record(new IsolatedBeat($eventTime));
    }
}
