<?php

return [
    'simulation' => [
        'delay_min' => env('WEBHOOK_SIMULATION_DELAY_MIN', 5),
        'delay_max' => env('WEBHOOK_SIMULATION_DELAY_MAX', 10),
        'delay_default' => env('WEBHOOK_SIMULATION_DELAY_DEFAULT', 7),
    ],

    'retry' => [
        'max_attempts' => env('WEBHOOK_RETRY_MAX_ATTEMPTS', 3),
        'backoff' => [
            5,
            10,
            30,
        ],
    ],

    'lock' => [
        'timeout' => env('WEBHOOK_LOCK_TIMEOUT', 30),
    ],
];

