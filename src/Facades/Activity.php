<?php

namespace Gottvergessen\Activity\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Gottvergessen\Activity\Activity
 */
class Activity extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Gottvergessen\Activity\Activity::class;
    }
}
