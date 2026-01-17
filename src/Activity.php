<?php

namespace Gottvergessen\Activity;

use Illuminate\Support\Str;

class Activity {
    public static function batch(callable $callback): mixed
    {
        app()->instance('activity.batch', (string) Str::uuid());

        try {
            return $callback();
        } finally {
            app()->forgetInstance('activity.batch');
        }
    }
}
