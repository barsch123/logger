<?php

namespace Gottvergessen\Activity\Traits;

use Gottvergessen\Activity\Models\Activity;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait InteractsWithActivity
{
    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject');
    }
}
