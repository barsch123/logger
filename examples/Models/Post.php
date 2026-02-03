<?php

namespace Examples\Models;

use Illuminate\Database\Eloquent\Model;
use Gottvergessen\Activity\Traits\TracksModelActivity;
use Gottvergessen\Activity\Traits\InteractsWithActivity;

/**
 * Example: Blog Post with Activity Tracking
 * 
 * This model demonstrates basic activity tracking with custom log categories
 * and descriptions.
 */
class Post extends Model
{
    use TracksModelActivity, InteractsWithActivity;

    protected $fillable = ['title', 'content', 'published', 'author_id'];

    /**
     * Define which events to track for this model.
     * You can override the global config on a per-model basis.
     */
    protected array $trackEvents = [
        'created',
        'updated',
        'deleted',
        'restored',
    ];

    /**
     * Attributes that should not be logged.
     * These changes will be ignored even if the attribute is modified.
     */
    protected array $ignoredAttributes = [
        'view_count',
    ];

    /**
     * Custom log category for organizing activities.
     * All activities for this model will be stored under this category.
     */
    public function activityLog(): string
    {
        return 'blog_posts';
    }

    /**
     * Define custom action labels based on the event type.
     * This allows for more semantic action tracking.
     */
    public function activityAction(string $event): string
    {
        return match ($event) {
            'created' => 'post_created',
            'updated' => 'post_updated',
            'deleted' => 'post_deleted',
            'restored' => 'post_restored',
            default => $event,
        };
    }

    /**
     * Define human-friendly descriptions for each event.
     * These appear in activity logs and are useful for audit trails.
     */
    public function activityDescription(string $event): string
    {
        return match ($event) {
            'created' => "Post '{$this->title}' was created",
            'updated' => "Post '{$this->title}' was updated",
            'deleted' => "Post '{$this->title}' was deleted",
            'restored' => "Post '{$this->title}' was restored",
            default => $event,
        };
    }
}
