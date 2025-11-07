<?php

return [

    'record_interval' => 60,

    'queue_status' => ['pending', 'delayed', 'reserved'],

    'prune_after' => 24,

    // Add queues to monitor
    'queues' => ['default'],
];
