<?php
return [

    'enabled' => true,

    'table' => 'activity_logs',

    'default_log' => 'default',

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

];
