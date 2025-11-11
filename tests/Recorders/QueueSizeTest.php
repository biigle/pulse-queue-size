<?php

namespace Biigle\PulseQueueSizeCard\Tests;

use Illuminate\Support\Carbon;
use Laravel\Pulse\Facades\Pulse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Testing\TestCase;
use Biigle\PulseQueueSizeCard\Recorders\QueueSize;


class QueueSizeTest extends TestCase
{
    public function testRecorder()
    {
        $id = config('pulse-ext.queue_size_card_id');
        $defaultConnection = config('queue.default');
        $recorder = new QueueSize;
        $value = '[{"pending":1,"delayed":2,"reserved":3}]';

        $getQueueID = fn($q, $s) => "$defaultConnection:$q$$s";

        Artisan::shouldReceive('call')->twice();
        Artisan::shouldReceive('output')->twice()->andReturnValues([$value, $value]);

        $queue = 'default';
        config(['pulse-ext.queues' => [$queue]]);
        Pulse::shouldReceive('record')
            ->once()
            ->ordered()
            ->with($getQueueID($queue, 'pending'), $id, 1);

        Pulse::shouldReceive('record')
            ->once()
            ->ordered()
            ->with($getQueueID($queue, 'delayed'), $id, 2);

        Pulse::shouldReceive('record')
            ->once()
            ->ordered()
            ->with($getQueueID($queue, 'reserved'), $id, 3);

        $recorder->record();

        $queue = 'high';
        config(['pulse-ext.queues' => [$queue]]);
        Pulse::shouldReceive('record')
            ->once()
            ->ordered()
            ->with($getQueueID($queue, 'pending'), $id, 1);

        Pulse::shouldReceive('record')
            ->once()
            ->ordered()
            ->with($getQueueID($queue, 'delayed'), $id, 2);

        Pulse::shouldReceive('record')
            ->once()
            ->ordered()
            ->with($getQueueID($queue, 'reserved'), $id, 3);

        Carbon::setTestNow(now()->addSeconds(config('pulse-ext.record_interval')));
        $recorder->record();

        // should be ignored since it is too early to record
        Pulse::shouldReceive('record')->never();
        $recorder->record();
    }

    public function testRecorderLocked()
    {
        $lockKey = "test";
        $recorder = new QueueSize($lockKey);

        Pulse::shouldReceive('record')->never();

        // Simulate another machine acquiring the lock
        $lock = Cache::lock($lockKey);
        $lock->get();

        $recorder->record();
        $lock->release();
    }
}
