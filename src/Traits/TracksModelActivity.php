<?php

namespace Gottvergessen\Activity\Traits;

use Gottvergessen\Activity\Models\Activity;
use Gottvergessen\Activity\Observers\TrackableObserver;

trait TracksModelActivity
{
    /**
     * Boot the activity tracking observer.
     */
    public static function bootTracksModelActivity(): void
    {
        // Never track the Activity model itself
        if (is_a(static::class, Activity::class, true)) {
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

    /**
     * Determine whether a given event should be tracked for this model.
     *
     * Acts as an override hook. Global config still applies.
     */
    public function shouldTrackEvent(string $event): bool
    {
        return in_array($event, $this->getTrackableEvents(), true);
    }

    /**
     * Get the list of trackable events for this model.
     */
    public function getTrackableEvents(): array
    {
        if (property_exists($this, 'trackEvents')) {
            return $this->trackEvents;
        }

        return config('activity.events', [
            'created',
            'updated',
            'deleted',
            'restored',
        ]);
    }

    /**
     * Get attributes that should be ignored when resolving changes.
     */
    public function getIgnoredAttributes(): array
    {
        return array_unique(array_merge(
            config('activity.ignore_attributes', []),
            property_exists($this, 'ignoredAttributes')
                ? $this->ignoredAttributes
                : []
        ));
    }
}
