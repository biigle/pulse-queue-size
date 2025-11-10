<?php

return [

    'record_interval' => 60,

    'queue_status' => ['pending', 'delayed', 'reserved'],

    'queue_size_card_id' => 'queue_size',

    // Add queues to monitor
    'queues' => ['default', 'high'],
];
