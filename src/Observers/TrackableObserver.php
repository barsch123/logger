<?php

namespace Gottvergessen\Activity\Observers;

use Illuminate\Database\Eloquent\Model;
use Gottvergessen\Activity\Services\ActivityTracker;
use Gottvergessen\Activity\Support\ActivityContext;

class TrackableObserver
{
    public function created(Model $model): void
    {
        $this->track($model, 'created');
    }

    public function updated(Model $model): void
    {
        $this->track($model, 'updated');
    }

    public function deleted(Model $model): void
    {
        $this->track($model, 'deleted');
    }

    protected function track(Model $model, string $event): void
    {
        if (! ActivityContext::enabled()) {
            return;
        }
        if (! method_exists($model, 'shouldTrackEvent')) {
            return;
        }

        if (! $model->shouldTrackEvent($event)) {
            return;
        }

        app(ActivityTracker::class)->track($model, $event);
    }
}
