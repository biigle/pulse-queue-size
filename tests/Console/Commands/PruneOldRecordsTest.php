<?php

namespace Biigle\PulseQueueSizeCard\Tests\Console\Commands;

use TestCase;
use Illuminate\Support\Facades\DB;

class PruneOldRecordsTest extends TestCase
{
    public function testHandle()
    {
        config(['pulse-ext.prune_after' => 1]);

        $values = ['test' => 'test'];

        $table = DB::table(config('pulse-ext.queue_size_table'));

        $table->insert([
            'queue' => 'test1',
            'values' => json_encode($values),
            'timestamp' => now()->subMinutes(61)
        ]);

        $table->insert([
            'queue' => 'test2',
            'values' => json_encode($values),
        ]);

        $this->assertCount(2, $table->get());

        $this->artisan('pulse-queue-size-card:prune')->assertExitCode(0);

        $entry = $table->first();
        $this->assertCount(1, $table->get());
        $this->assertEquals('test2', $entry->queue);
        $this->assertEquals(json_encode($values), $entry->values);
    }
}
