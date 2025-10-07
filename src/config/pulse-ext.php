<?php

return [

    'record_interval' => 60,

    'queue_list' => 'pulse_queues',

    // Add queue to monitor
    'queues' => [ 'default', 'high', 'gpu'],
];