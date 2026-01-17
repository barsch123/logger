<?php

namespace Gottvergessen\Activity\Traits;

use Gottvergessen\Activity\Observers\TrackableObserver;
use Gottvergessen\Activity\Models\Activity;

trait TracksModelActivity
{
    public static function bootTracksModelActivity(): void
    {
        if (static::class === Activity::class) {
            return;
        }

        if (! config('activity.enabled', true)) {
            return;
        }

        static::observe(TrackableObserver::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Model-level configuration hooks
    |--------------------------------------------------------------------------
    */

    public function shouldTrackEvent(string $event): bool
    {
        return in_array($event, $this->getTrackableEvents(), true);
    }

    public function getTrackableEvents(): array
    {
        if (property_exists($this, 'trackEvents')) {
            return $this->trackEvents;
        }

        return config('logger.events', [
            'created',
            'updated',
            'deleted',
            'restored',
        ]);
    }

    public function getIgnoredAttributes(): array
    {
        return array_unique(array_merge(
            config('logger.ignore_attributes', []),
            property_exists($this, 'ignoredAttributes') ? $this->ignoredAttributes : []
        ));
    }

}
