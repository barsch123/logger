<?php

namespace Gottvergessen\Logger\Traits;

use Gottvergessen\Logger\Observers\TrackableObserver;
use Gottvergessen\Logger\Models\Activity;

trait TracksModelActivity
{
    public static function bootTracksModelActivity(): void
    {
        // Never track the Activity model itself
        if (static::class === Activity::class) {
            return;
        }

        // Allow global disable
        if (! config('logger.enabled', true)) {
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
