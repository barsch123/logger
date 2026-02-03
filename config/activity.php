<?php
return [
    /*
    |--------------------------------------------------------------------------
    | Activity Logging Enabled
    |--------------------------------------------------------------------------
    |
    | This option controls whether activity logging is enabled globally.
    | You can disable this to turn off all activity logging.
    |
    */
    'enabled' => true,

    /*
    |--------------------------------------------------------------------------
    | Activity Logs Table Name
    |--------------------------------------------------------------------------
    |
    | The database table name where activity logs will be stored.
    |
    */
    'table' => 'activity_logs',

    /*
    |--------------------------------------------------------------------------
    | Default Log Category
    |--------------------------------------------------------------------------
    |
    | The default log category to use when a model doesn't specify one.
    |
    */
    'default_log' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Tracked Events
    |--------------------------------------------------------------------------
    |
    | The default events that will be tracked for all models.
    | Models can override this using the $trackEvents property.
    |
    */
    'events' => [
        'created',
        'updated',
        'deleted',
        'restored',
    ],

    /*
    |--------------------------------------------------------------------------
    | Ignored Attributes
    |--------------------------------------------------------------------------
    |
    | Attributes that should be ignored when logging changes globally.
    | Models can add more via the $ignoredAttributes property.
    |
    */
    'ignore_attributes' => [
        'created_at',
        'updated_at',
        'deleted_at',
        'remember_token',
    ],

    /*
    |--------------------------------------------------------------------------
    | Capture Causer
    |--------------------------------------------------------------------------
    |
    | Automatically capture the authenticated user who made the change.
    |
    */
    'capture_causer' => true,

    /*
    |--------------------------------------------------------------------------
    | Capture Request Metadata
    |--------------------------------------------------------------------------
    |
    | Capture HTTP request metadata (method, host).
    | Only applies to web requests, not console commands.
    |
    */
    'capture_request_meta' => false,

    /*
    |--------------------------------------------------------------------------
    | Capture IP Address
    |--------------------------------------------------------------------------
    |
    | Capture the IP address of the user making the change.
    | Only applies to web requests. Requires explicit opt-in due to privacy.
    |
    */
    'capture_ip' => false,

    /*
    |--------------------------------------------------------------------------
    | Auto Batch
    |--------------------------------------------------------------------------
    |
    | Automatically assign a unique batch ID to each request's activities.
    | When enabled, all activities within a single request share a batch_id.
    |
    */
    'auto_batch' => false,

];
