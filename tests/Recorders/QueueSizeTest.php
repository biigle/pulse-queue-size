<?php

namespace Biigle\PulseQueueSizeCard\Tests;

use Mockery;
use Laravel\Pulse\Facades\Pulse;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Support\Facades\Queue;
use Illuminate\Queue\Events\JobPopped;
use Illuminate\Queue\Events\JobQueued;
use Illuminate\Foundation\Testing\TestCase;
use Biigle\PulseQueueSizeCard\Recorders\QueueSize;


class QueueSizeTest extends TestCase
{
    public function testRecorderEnqueuedJob()
    {
        $recorder = new QueueSize;
        $someJob = function () {};

        Queue::shouldReceive('size')->times(3)->andReturnValues([1, 2, 3]);

        Pulse::shouldReceive('set')->with('queue_size', 'default', 1)->once()->ordered();
        Pulse::shouldReceive('set')->with('queue_size', 'default', 2)->once()->ordered();
        Pulse::shouldReceive('set')->with('queue_size', 'default', 3)->once()->ordered();

        $recorder->record(new JobQueued("database", "default", 1, $someJob, "test", 0));
        $recorder->record(new JobQueued("database", "default", 2, $someJob, "test", 0));
        $recorder->record(new JobQueued("database", "default", 3, $someJob, "test", 0));

    }

    public function testRecorderPoppedJob()
    {
        $recorder = new QueueSize;

        $job = Mockery::mock(Job::class);
        $job->shouldReceive('getQueue')->andReturn('default')->times(3);

        Queue::shouldReceive('size')->times(3)->andReturnValues([3, 2, 1]);

        Pulse::shouldReceive('set')->with('queue_size', 'default', 2)->once()->ordered();
        Pulse::shouldReceive('set')->with('queue_size', 'default', 1)->once()->ordered();
        Pulse::shouldReceive('set')->with('queue_size', 'default', 0)->once()->ordered();

        $recorder->record(new JobPopped("", $job));
        $recorder->record(new JobPopped("", $job));
        $recorder->record(new JobPopped("", $job));
    }

}
