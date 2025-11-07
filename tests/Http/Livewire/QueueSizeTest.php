<?php

namespace Biigle\PulseQueueSizeCard\Tests\Http\Livewire;

use ApiTestCase;
use Biigle\PulseQueueSizeCard\PulseQueueHistory;
use Carbon\Carbon;
use Biigle\PulseQueueSizeCard\Http\Livewire\QueueSize;

class QueueSizeTest extends ApiTestCase
{

    public function testRender()
    {
        $tz = config('app.timezone');
        $h1 = PulseQueueHistory::factory()->create()->fresh();
        $h2 = PulseQueueHistory::factory()->create([
            'queue' => $h1->queue,
            'timestamp' => now()->addSeconds(60)
        ])->fresh();
        $h3 = PulseQueueHistory::factory()->create()->fresh();

        $controller = new QueueSize();
        $data = $controller->render()->getData();

        $getDate = fn($d) => Carbon::parse($d)
            ->setTimezone($tz)
            ->toDateTimeString();

        $this->assertCount(3, $data);
        $this->assertIsFloat($data['time']);
        $this->assertIsString($data['runAt']);
        $this->assertCount(2, $data['queues']);
        $this->assertEquals([$h1->queue, $h3->queue], $data['queues']->keys()->toArray());
        $this->assertEquals(
            [
                [$getDate($h1->timestamp) => $h1->values,
                $getDate($h2->timestamp) => $h2->values],
                [$getDate($h3->timestamp) => $h3->values]
            ],
            $data['queues']->values()->toArray()
        );
    }

    public function testRenderPeriod()
    {
        $periodHour = 6;
        $tz = config('app.timezone');

        $h1 = PulseQueueHistory::factory()->create();
        $h2 = PulseQueueHistory::factory()->create([
            'queue' => $h1->queue,
            'timestamp' => now()->subHours($periodHour)->setTimezone('UTC')
        ])->fresh();
        // should be ignored
        PulseQueueHistory::factory()->create([
            'queue' => $h1->queue,
            'timestamp' => now()->subHours($periodHour)->subMinute()->setTimezone('UTC')
        ]);

        $controller = new QueueSize();
        $controller->period = $periodHour . "_hours";
        $data = $controller->render()->getData();

        $getDate = fn($d) => Carbon::parse($d)
            ->setTimezone($tz)
            ->toDateTimeString();

        $this->assertCount(3, $data);
        $this->assertIsFloat($data['time']);
        $this->assertIsString($data['runAt']);
        $this->assertCount(1, $data['queues']);
        $this->assertEquals($h1->queue, $data['queues']->keys()->first());

        $exp = [$getDate($h1->timestamp) => $h1->values,
                $getDate($h2->timestamp) => $h2->values];
        $res = $data['queues']->values()->toArray();
        $this->assertEquals(sort($exp), sort($res));
    }
}
