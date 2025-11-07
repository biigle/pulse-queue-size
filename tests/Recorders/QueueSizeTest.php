<?php

namespace Biigle\PulseQueueSizeCard\Tests;

use Biigle\PulseQueueSizeCard\PulseQueueHistory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Testing\TestCase;
use Biigle\PulseQueueSizeCard\Recorders\QueueSize;
use Illuminate\Foundation\Testing\RefreshDatabase;


class QueueSizeTest extends TestCase
{
    use RefreshDatabase;
    public function testRecorder()
    {
        $defaultConnection = config('queue.default');
        $recorder = new QueueSize;
        $value = '[{"pending":0,"delayed":0,"reserved":0}]';

        Artisan::shouldReceive('call')->twice();
        Artisan::shouldReceive('output')->twice()->andReturnValues([$value, $value]);

        config(['pulse-ext.queues' => ['default']]);
        $recorder->record();

        config(['pulse-ext.queues' => ['high']]);
        Carbon::setTestNow(now()->addSeconds(config('pulse-ext.record_interval')));
        $recorder->record();

        // should be ignored since it is too early to record
        $recorder->record();

        $entries = PulseQueueHistory::get()->toArray();
        $valuesArray = json_decode($value)[0];
        $this->assertCount(2, $entries);
        $this->assertEquals("$defaultConnection:default", $entries[0]['queue']);
        $this->assertEquals($valuesArray, json_decode($entries[0]['values']));
        $this->assertEquals("$defaultConnection:high", $entries[1]['queue']);
        $this->assertEquals($valuesArray, json_decode($entries[1]['values']));
    }

    public function testRecorderLocked()
    {
        $lockKey = "test";
        $recorder = new QueueSize($lockKey);

        // Simulate another machine acquiring the lock
        $lock = Cache::lock($lockKey, 10);
        $lock->get();
        $recorder->record();

        $entries = PulseQueueHistory::get();
        $this->assertEmpty($entries);
        $lock->release();
    }
}
