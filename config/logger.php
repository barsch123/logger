<?php

return [
    'enabled' => env('LOGGER_ENABLED', true),

    'table' => 'activity_logs',

    'default_log' => env('LOGGER_DEFAULT_LOG', 'default'),

    'origin' => env('LOGGER_ORIGIN', 'web'),

    'events' => [
        'created',
        'updated',
        'deleted',
        'restored',
    ],

    'ignore_attributes' => [
        'created_at',
        'updated_at',
        'deleted_at',
        'remember_token',
    ],

    'use_diffs' => true,

    'driver' => 'database',
];
