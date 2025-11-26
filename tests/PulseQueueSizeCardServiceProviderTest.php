<?php

namespace Biigle\Tests\PulseQueueSizeCard;

use Illuminate\Foundation\Testing\TestCase;
use Biigle\PulseQueueSizeCard\PulseQueueSizeCardServiceProvider;

class PulseQueueSizeCardServiceProviderTest extends TestCase
{
    public function testServiceProvider()
    {
        $this->assertTrue(class_exists(PulseQueueSizeCardServiceProvider::class));
    }
}
