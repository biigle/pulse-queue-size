<?php

namespace Biigle\PulseQueueSizeCard\Tests\Http\Livewire;

use Carbon\Carbon;
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
        $id = config($this->config . ".id");
        config([$this->config . ".queues" => ['hello:world', 'world:test']]);

        $r1 = ['hello:world', 'pending', $id, 10, now()];
        $r2 = ['hello:world', 'delayed', $id, 9, now()->addSeconds(60)];
        $r3 = ['world:test', 'reserved', $id, 8, now()];
        $records = [$r1, $r2, $r3];

        $this->record($r1);
        $this->record($r3);
        $this->record($r2);

        $controller = new QueueSize();
        $data = $controller->render()->getData();

        $this->assertCount(4, $data);
        $this->assertSame(config($this->config . ".sample_rate"), $data['sampleRate']);
        $this->assertIsFloat($data['time']);
        $this->assertIsString($data['runAt']);
        $this->assertCount(2, $data['queues']);
        $this->assertEquals([$r1[0], $r3[0]], $data['queues']->keys()->toArray());
        $this->assertCount(3, $data['queues']->flatten(1));
        foreach ($data['queues']->flatten(1) as $idx => $queue) {
            $this->assertCount(60, $queue);
            $date = $this->getDate($records[$idx][4]);
            $this->assertEquals($records[$idx][3], $queue[$date]);
            $this->assertCount(59, $queue->filter(fn($v, $k) => $v === 0 && is_string($k)));
        }

    }

    public function testRenderPeriod()
    {
        $periodHour = 6;
        $id = config($this->config . ".id");
        config([$this->config . ".queues" => ['test:test', 'world:world']]);

        $r1 = ['test:test', 'pending', $id, 10, now()];
        $r2 = ['test:test', 'pending', $id, 9, now()->subHours($periodHour)->addMinutes($periodHour)];
        // should be ignored
        $r3 = ['hello:world', 'pending', $id, 8, now()->subHours($periodHour)];
        $records = [$r1, $r2];

        $this->record($r3);
        $this->record($r2);
        $this->record($r1);

        $controller = new QueueSize();
        $controller->period = $periodHour . "_hours";
        $data = $controller->render()->getData();
        $queue = $data['queues']->flatten(1);

        $this->assertCount(4, $data);
        $this->assertIsFloat($data['time']);
        $this->assertIsString($data['runAt']);
        $this->assertSame(config($this->config . ".sample_rate"), $data['sampleRate']);
        $this->assertCount(1, $data['queues']);
        $this->assertEquals($r1[0], $data['queues']->keys()->first());
        $this->assertCount(1, $queue);
        $this->assertCount(60, $queue[0]);
        $this->assertCount(58, $queue[0]->filter(fn($v, $k) => $v === 0 && is_string($k)));
        $this->assertEquals($r1[3], $queue[0][$this->getDate($r1[4])]);
        $this->assertEquals($r2[3], $queue[0][$this->getDate($r2[4])]);
    }

    public function testRenderQueueFilter()
    {
        $id = config($this->config . ".id");
        config([$this->config . ".queues" => ['con:q1', 'con:q3']]);

        $r1 = ['con:q1', 'pending', $id, 10, now()];
        $r2 = ['con:q2', 'delayed', $id, 9, now()];
        $r3 = ['con:q3', 'reserved', $id, 8, now()];
        $records = [$r1, $r3];

        $this->record($r1);
        $this->record($r2);
        $this->record($r3);

        $controller = new QueueSize();
        $data = $controller->render()->getData();

        $this->assertCount(4, $data);
        $this->assertSame(config($this->config . ".sample_rate"), $data['sampleRate']);
        $this->assertIsFloat($data['time']);
        $this->assertIsString($data['runAt']);
        $this->assertCount(2, $data['queues']);
        $this->assertEquals([$r1[0], $r3[0]], $data['queues']->keys()->toArray());
        foreach ($data['queues']->flatten(1) as $idx => $queue) {
            $this->assertCount(60, $queue);
            $date = $this->getDate($records[$idx][4]);
            $this->assertEquals($records[$idx][3], $queue[$date]);
            $this->assertCount(59, $queue->filter(fn($v, $k) => $v === 0 && is_string($k)));
        }
    }

    public function record($values)
    {
        list($queue, $status, $key, $value, $timestamp) = $values;
        $type = $queue . '$' . $status;
        DB::table('pulse_entries')->insert([
            'type' => $type,
            'key' => $key,
            'value' => $value,
            'timestamp' => $timestamp->timezone('UTC')->timestamp
        ]);
    }

    public function getDate($d)
    {
        return Carbon::parse($d)
            ->setTimezone('UTC')
            ->toDateTimeString();
    }
}
