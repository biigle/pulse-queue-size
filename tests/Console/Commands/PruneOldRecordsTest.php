<?php

namespace Biigle\PulseQueueSizeCard\Tests\Console\Commands;

use Biigle\PulseQueueSizeCard\PulseQueueHistory;
use TestCase;

class PruneOldRecordsTest extends TestCase
{
    public function testHandle()
    {
        config(['pulse-ext.prune_after' => 1]);

        $values = ['test' => 'test'];

        PulseQueueHistory::factory()->create([
            'timestamp' => now()->subHour()->subMinute()
        ]);

        PulseQueueHistory::factory()->create([
            'queue' => 'test2',
            'values' => json_encode($values),
        ]);

        $res = PulseQueueHistory::get()->toArray();

        $this->assertCount(2, $res);

        $this->artisan('pulse-queue-size-card:prune')->assertExitCode(0);

        $res = PulseQueueHistory::get()->toArray();
        $this->assertCount(1, $res);
        $this->assertEquals('test2', $res[0]['queue']);
        $this->assertEquals(json_encode($values), $res[0]['values']);
    }
}
