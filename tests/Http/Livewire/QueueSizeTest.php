<?php

namespace Biigle\PulseQueueSizeCard\Tests\Http\Livewire;

use ApiTestCase;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Biigle\PulseQueueSizeCard\Http\Livewire\QueueSize;

class QueueSizeTest extends ApiTestCase
{

    public function testRender()
    {

        config(['pulse-ext.queue_list' => 'test']);

        $entry = fn($v, $sec) => [
            'type' => 'default',
            'key' => config('pulse-ext.queue_list'),
            'value' => $v,
            'timestamp' => Carbon::now()->addSeconds($sec)->timestamp
        ];

        DB::table('pulse_entries')->insert($entry(100, 0));
        DB::table('pulse_entries')->insert($entry(50, 1));
        DB::table('pulse_entries')->insert($entry(1, 2));

        $controller = new QueueSize();

        $data = $controller->render()->getData();

        $this->assertCount(3, $data['queues']->first());
        $this->assertEquals([100, 50, 1], $data['queues']->flatten()->toArray());
        $this->assertEquals(['Default'], $data['queues']->keys()->toArray());
        $this->assertIsNumeric($data['time']);
        $this->assertIsString($data['runAt']);
    }

    public function testRenderMultipleTypes()
    {

        config([
            'pulse-ext.queue_list' => 'test',
            'pulse-ext.queues' => ['default', 'gpu']
        ]);

        $entry = fn($v, $t, $sec) => [
            'type' => $t,
            'key' => config('pulse-ext.queue_list'),
            'value' => $v,
            'timestamp' => Carbon::now()->addSeconds($sec)->timestamp
        ];

        DB::table('pulse_entries')->insert($entry(100, 'default', 0));
        DB::table('pulse_entries')->insert($entry(50, 'gpu', 1));
        DB::table('pulse_entries')->insert($entry(1, 'testQueue', 2));

        $controller = new QueueSize();

        $data = $controller->render()->getData();

        $this->assertCount(2, $data['queues']->flatten());
        $this->assertEquals([100, 50], $data['queues']->flatten()->toArray());
        $this->assertEquals(['Default', 'Gpu'], $data['queues']->keys()->toArray());
        $this->assertIsNumeric($data['time']);
        $this->assertIsString($data['runAt']);
    }

    public function testRenderPeriod()
    {

        config(['pulse-ext.queue_list' => 'test']);
        $lastXHours = 6;

        $entry = fn($v, $h) => [
            'type' => 'default',
            'key' => config('pulse-ext.queue_list'),
            'value' => $v,
            'timestamp' => Carbon::now()->subHours($h)->timestamp
        ];

        DB::table('pulse_entries')->insert($entry(100, 0));
        DB::table('pulse_entries')->insert($entry(50, $lastXHours));
        DB::table('pulse_entries')->insert($entry(1, 7));

        $controller = new QueueSize();
        $controller->period = strval($lastXHours) . "_hours";

        $data = $controller->render()->getData();

        $this->assertCount(2, $data['queues']->first());
        $this->assertSame([100, 50], $data['queues']->flatten()->toArray());
        $this->assertSame(['Default'], $data['queues']->keys()->toArray());
        $this->assertIsNumeric($data['time']);
        $this->assertIsString($data['runAt']);
    }

    public function testRenderEqualTypes()
    {

        config(['pulse-ext.queue_list' => 'test']);

        DB::table('pulse_entries')->insert([
            'type' => 'default',
            'key' => config('pulse-ext.queue_list'),
            'value' => 100,
            'timestamp' => Carbon::now()->timestamp
        ]);

        // Entry belonging to another card
        DB::table('pulse_entries')->insert([
            'type' => 'default',
            'key' => 'test123',
            'value' => 50000000,
            'timestamp' => Carbon::now()->addSeconds(1)->timestamp
        ]);

        $controller = new QueueSize();

        $data = $controller->render()->getData();

        $this->assertCount(1, $data['queues']->first());
        $this->assertSame([100], $data['queues']->flatten()->toArray());
        $this->assertSame(['Default'], $data['queues']->keys()->toArray());
        $this->assertIsNumeric($data['time']);
        $this->assertIsString($data['runAt']);
    }

}
