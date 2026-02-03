<?php

namespace Gottvergessen\Activity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Activity extends Model
{
    protected $table = 'activity_logs';
    protected $fillable = [
        'event',
        'action',
        'log',
        'description',
        'subject_type',
        'subject_id',
        'causer_type',
        'causer_id',
        'properties',
        'meta',
        'batch_id'
    ];

    protected $casts = [
        'properties' => 'array',
        'meta'       => 'array',
    ];

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function causer(): MorphTo
    {
        return $this->morphTo();
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope to filter activities by event type.
     */
    public function scopeForEvent($query, string $event)
    {
        return $query->where('event', $event);
    }

    /**
     * Scope to filter activities for a specific subject model.
     */
    public function scopeForSubject($query, Model $subject)
    {
        return $query->where('subject_type', get_class($subject))
                     ->where('subject_id', $subject->getKey());
    }

    /**
     * Scope to filter activities by batch ID.
     */
    public function scopeInBatch($query, string $batchId)
    {
        return $query->where('batch_id', $batchId);
    }

    /**
     * Scope to filter activities caused by a specific user/model.
     */
    public function scopeCausedBy($query, ?Model $causer)
    {
        if (is_null($causer)) {
            return $query->whereNull('causer_type')
                         ->whereNull('causer_id');
        }

        return $query->where('causer_type', get_class($causer))
                     ->where('causer_id', $causer->getKey());
    }

    /**
     * Scope to filter activities by log category.
     */
    public function scopeInLog($query, string $log)
    {
        return $query->where('log', $log);
    }

    /**
     * Scope to filter activities by action.
     */
    public function scopeWithAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope to get activities within a date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
}
