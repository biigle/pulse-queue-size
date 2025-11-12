<?php

return [

    'record_interval' => 60,

    // Must be a key from the queue:monitor output pointing to an integer
    'queue_status' => ['pending', 'delayed', 'reserved'],

    'queue_size_card_id' => 'queue_size',

    // Add queues to monitor, e.g, <connection>:<queue> or <queue>
    'queues' => ['default'],
];
