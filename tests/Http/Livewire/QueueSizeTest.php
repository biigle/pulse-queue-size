<?php

namespace Biigle\PulseQueueSizeCard\Tests\Http\Livewire;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use Laravel\Pulse\Facades\Pulse;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Biigle\PulseQueueSizeCard\Http\Livewire\QueueSize;

class QueueSizeTest extends TestCase
{
    use RefreshDatabase;

    private $config = 'pulse.recorders.' . \Biigle\PulseQueueSizeCard\Recorders\QueueSize::class;

    public function testRender()
    {
        config([$this->config . ".queues" => ['hello:world', 'world:test']]);
        $getDate = fn($d) => Carbon::parse($d)
            ->setTimezone('UTC')
            ->startOfMinute()
            ->toDateTimeString();

        $r1 = ['hello:world', 'pending', 10, now()];
        $r2 = ['hello:world', 'delayed', 9, now()->addSeconds(60)];
        $r3 = ['world:test', 'reserved', 8, now()];
        $records = [$r1, $r2, $r3];
        $this->record($records, 1);

        $controller = new QueueSize();
        $data = $controller->render()->getData();
        $queues = $data['queues']->flatten(1)->filter(fn($q) => $q->countBy()->count() > 1)->values();

        $this->assertCount(5, $data);
        $this->assertSame(config($this->config . ".sample_rate"), $data['sampleRate']);
        $this->assertIsFloat($data['time']);
        $this->assertIsString($data['runAt']);
        $this->assertTrue($data['showConnection']);
        $this->assertCount(2, $data['queues']);
        $this->assertEquals([$r1[0], $r3[0]], $data['queues']->keys()->toArray());
        $this->assertCount(6, $data['queues']->flatten(1));
        foreach ($queues as $idx => $queue) {
            $this->assertCount(1, $queue->filter(fn($v, $k) => $v != null));
            $this->assertEquals($records[$idx][2], $queue[$getDate($records[$idx][3])]);
            $this->assertCount($queue->count() - 1, $queue
                ->filter(fn($v, $k) => $v === null && is_string($k)));
        }
    }

    public function testRenderPeriod()
    {
        $periodHour = 6;
        config([$this->config . ".queues" => ['test:test', 'world:world']]);

        $r1 = ['test:test', 'pending', 10, now()];
        $r2 = ['test:test', 'pending', 9, now()->subHours($periodHour)->addMinutes($periodHour)];
        // should be ignored
        $r3 = ['hello:world', 'pending', 8, now()->subHours($periodHour)];

        $this->record([$r3, $r2, $r1], $periodHour);

        $controller = new QueueSize();
        $controller->period = $periodHour . "_hours";
        $data = $controller->render()->getData();
        $queue = $data['queues']->flatten(1)->filter(fn($q) => $q->countBy()->count() > 1)->values();

        $this->assertCount(5, $data);
        $this->assertIsFloat($data['time']);
        $this->assertIsString($data['runAt']);
        $this->assertFalse($data['showConnection']);
        $this->assertSame(config($this->config . ".sample_rate"), $data['sampleRate']);
        $this->assertCount(1, $data['queues']);
        $this->assertEquals($r1[0], $data['queues']->keys()->first());
        $this->assertCount(1, $queue);
        $this->assertCount(2, $queue[0]->filter(fn($v, $k) => $v != null));
        $this->assertEquals($r1[2], $queue[0]->last());
        $this->assertEquals($r2[2], $queue[0]->first());
        $this->assertCount($queue[0]->count() - 2, $queue[0]
            ->filter(fn($v, $k) => $v === null && is_string($k)));
    }

    public function record($records, $period)
    {
        foreach ($records as $record) {
            list($queue, $status, $value, $timestamp) = $record;
            $maxDataPoints = 60;
            $secondsPerPeriod = (float) ($period * 60 * 60 / $maxDataPoints);
            $currentBucket = (int) (floor($timestamp->getTimestamp() / $secondsPerPeriod) * $secondsPerPeriod);

            DB::table('pulse_aggregates')->insert([
                'type' => $status,
                'key' => $queue,
                'value' => $value,
                'bucket' => $currentBucket,
                'aggregate' => 'avg',
                'period' => $secondsPerPeriod
            ]);
        }
    }
}
