<?php

namespace Gottvergessen\Activity\Traits;

use Gottvergessen\Activity\Models\Activity;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 *
 * Provides safe, intention-revealing accessors for model activity logs.
 *
 * ⚠️ Note:
 * The underlying activities relationship is unbounded.
 * Prefer helper methods instead of accessing `$model->activities` directly.
 */
trait InteractsWithActivity
{
    /**
     * Base activity relationship.
     *
     * Returns a morph-many relation ordered by most recent first.
     * This relationship is intentionally unbounded.
     */
    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject')
            ->latest('created_at');
    }

    /**
     * Get the most recent activity for the model.
     */
    public function latestActivity(): ?Activity
    {
        return $this->activities()->first();
    }

    /**
     * Get the total number of activities for the model
     * without loading the activity collection.
     */
    public function activitiesCount(): int
    {
        return $this->activities()->count();
    }

    /**
     * Get a limited collection of recent activities.
     *
     * @param  int  $limit
     * @return \Illuminate\Support\Collection<int, Activity>
     */
    public function recentActivities(int $limit = 10): Collection
    {
        return $this->activities()
            ->limit($limit)
            ->get();
    }

    /**
     * Get a constrained activities relationship.
     *
     * Useful for safe eager loading:
     * Model::with(['activitiesLimited'])->get();
     */
    public function activitiesLimited(int $limit = 50): MorphMany
    {
        return $this->activities()->limit($limit);
    }

    /**
     * Determine if the model has any activities without loading them.
     */
    public function hasActivities(): bool
    {
        return $this->activities()->exists();
    }
}
