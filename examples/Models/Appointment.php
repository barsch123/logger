<?php

namespace Examples\Models;

use Illuminate\Database\Eloquent\Model;
use Gottvergessen\Activity\Traits\TracksModelActivity;
use Gottvergessen\Activity\Traits\InteractsWithActivity;

/**
 * Example: Appointment with Activity Tracking
 * 
 * Demonstrates custom descriptions that include model data for richer audit trails.
 */
class Appointment extends Model
{
    use TracksModelActivity, InteractsWithActivity;

    protected $fillable = ['title', 'scheduled_at', 'customer_id', 'notes'];

    protected $dates = ['scheduled_at'];

    /**
     * Custom description that includes appointment details
     */
    public function activityDescription(string $event): string
    {
        return match ($event) {
            'created' => "Appointment '{$this->title}' scheduled for {$this->scheduled_at->format('Y-m-d H:i')}",
            'updated' => "Appointment '{$this->title}' updated for {$this->scheduled_at->format('Y-m-d H:i')}",
            'deleted' => "Appointment '{$this->title}' was cancelled",
            'restored' => "Appointment '{$this->title}' was restored",
            default => $event,
        };
    }

    /**
     * Organize appointments in their own log
     */
    public function activityLog(): string
    {
        return 'appointments';
    }
}
