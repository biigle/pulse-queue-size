<?php

namespace Biigle\PulseQueueSizeCard\Tests\Http\Livewire;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Biigle\PulseQueueSizeCard\Http\Livewire\QueueSize;
use TestCase;

class QueueSizeTest extends TestCase
{
    public function testRender()
    {
        $id = config('pulse-ext.queue_size_card_id');
        config(['pulse-ext.queues' => ['hello:world', 'world:test']]);

        $r1 = ['hello:world', 'pending', $id, 10, now()];
        $r2 = ['hello:world', 'delayed', $id, 9, now()->addSeconds(60)];
        $r3 = ['world:test', 'reserved', $id, 8, now()];

        $this->record($r1);
        $this->record($r2);
        $this->record($r3);

        $controller = new QueueSize();
        $data = $controller->render()->getData();

        $this->assertCount(4, $data);
        $this->assertSame(config('pulse-ext.queue_status'), $data['states']);
        $this->assertIsFloat($data['time']);
        $this->assertIsString($data['runAt']);
        $this->assertCount(2, $data['queues']);
        $this->assertEquals([$r1[0], $r3[0]], $data['queues']->keys()->toArray());
        $exp = [
            [
                $r1[1] => [$this->getDate($r1[4]) => $r1[3]],
                $r2[1] => [$this->getDate($r2[4]) => $r2[3]]
            ],
            [
                $r3[1] => [$this->getDate($r3[4]) => $r3[3]]
            ]
        ];
        $this->assertEquals($exp, $data['queues']->values()->toArray());
    }

    public function testRenderPeriod()
    {
        $periodHour = 6;
        $id = config('pulse-ext.queue_size_card_id');
        config(['pulse-ext.queues' => ['test:test', 'world:world']]);

        $r1 = ['test:test', 'pending', $id, 10, now()];
        $r2 = ['test:test', 'pending', $id, 9, now()->subHours($periodHour)];
        // should be ignored
        $r3 = ['hello:world', 'pending', $id, 8, now()->subHours($periodHour)->subMinute()];

        $this->record($r1);
        $this->record($r2);
        $this->record($r3);

        $controller = new QueueSize();
        $controller->period = $periodHour . "_hours";
        $data = $controller->render()->getData();

        $this->assertCount(4, $data);
        $this->assertSame(config('pulse-ext.queue_status'), $data['states']);
        $this->assertIsFloat($data['time']);
        $this->assertIsString($data['runAt']);
        $this->assertCount(1, $data['queues']);
        $this->assertEquals($r1[0], $data['queues']->keys()->first());
        $exp = [
            $r1[1] => [
                $this->getDate($r1[4]) => $r1[3],
                $this->getDate($r2[4]) => $r2[3]
            ]
        ];
        $res = $data['queues']->values()->toArray();
        $this->assertEquals($exp, $res[0]);
    }

    public function testRenderQueueFilter()
    {
        $id = config('pulse-ext.queue_size_card_id');
        config(['pulse-ext.queues' => ['hello:world', 'test:hello']]);

        $r1 = ['hello:world', 'pending', $id, 10, now()];
        $r2 = ['world:test', 'delayed', $id, 9, now()];
        $r3 = ['test:hello', 'reserved', $id, 8, now()];

        $this->record($r1);
        $this->record($r2);
        $this->record($r3);

        $controller = new QueueSize();
        $data = $controller->render()->getData();

        $this->assertCount(4, $data);
        $this->assertSame(config('pulse-ext.queue_status'), $data['states']);
        $this->assertIsFloat($data['time']);
        $this->assertIsString($data['runAt']);
        $this->assertCount(2, $data['queues']);
        $this->assertEquals([$r1[0], $r3[0]], $data['queues']->keys()->toArray());
        $exp = [
            [
                $r1[1] => [$this->getDate($r1[4]) => $r1[3]],
            ],
            [
                $r3[1] => [$this->getDate($r3[4]) => $r3[3]]
            ]
        ];
        $this->assertEquals($exp, $data['queues']->values()->toArray());
    }

    public function testRenderQueueStatusFilter()
    {
        $id = config('pulse-ext.queue_size_card_id');
        config(['pulse-ext.queues' => ['hello:world', 'world:test', 'test:hello']]);
        config(['pulse-ext.queue_status' => ['pending', 'delayed']]);

        $r1 = ['hello:world', 'pending', $id, 10, now()];
        $r2 = ['world:test', 'delayed', $id, 9, now()];
        $r3 = ['test:hello', 'reserved', $id, 8, now()];

        $this->record($r1);
        $this->record($r2);
        $this->record($r3);

        $controller = new QueueSize();
        $data = $controller->render()->getData();

        $this->assertCount(4, $data);
        $this->assertSame(config('pulse-ext.queue_status'), $data['states']);
        $this->assertIsFloat($data['time']);
        $this->assertIsString($data['runAt']);
        $this->assertCount(2, $data['queues']);
        $this->assertEquals([$r1[0], $r2[0]], $data['queues']->keys()->toArray());
        $exp = [
            [
                $r1[1] => [$this->getDate($r1[4]) => $r1[3]],
            ],
            [
                $r2[1] => [$this->getDate($r2[4]) => $r2[3]]
            ]
        ];
        $this->assertEquals($exp, $data['queues']->values()->toArray());
    }

    public function record($values)
    {
        list($queue, $status, $key, $value, $timestamp) = $values;
        $type = $queue . '$' . $status;
        DB::table('pulse_entries')->insert([
            'type' => $type,
            'key' => $key,
            'value' => $value,
            'timestamp' => $timestamp->timestamp
        ]);
    }

    public function getDate($d)
    {
        $tz = config('app.timezone');

        return Carbon::parse($d)
            ->setTimezone($tz)
            ->toDateTimeString();
    }
}
