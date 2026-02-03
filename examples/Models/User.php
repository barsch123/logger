<?php

namespace Examples\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Gottvergessen\Activity\Traits\TracksModelActivity;
use Gottvergessen\Activity\Traits\InteractsWithActivity;

/**
 * Example: User Model with Activity Tracking
 * 
 * Demonstrates tracking user account changes with sensitive field protection.
 */
class User extends Authenticatable
{
    use Notifiable, TracksModelActivity, InteractsWithActivity;

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    /**
     * Never log sensitive attributes like passwords.
     * These are ignored even though they're being tracked.
     */
    protected array $ignoredAttributes = [
        'password',
        'remember_token',
        'email_verified_at',
    ];

    /**
     * Only track creation and deletion of user accounts, not updates.
     * This is useful if you have high-frequency user metadata updates.
     */
    protected array $trackEvents = [
        'created',
        'deleted',
    ];

    /**
     * Organize user activities separately
     */
    public function activityLog(): string
    {
        return 'users';
    }

    public function activityDescription(string $event): string
    {
        return match ($event) {
            'created' => "User account '{$this->email}' was created",
            'deleted' => "User account '{$this->email}' was deleted",
            default => $event,
        };
    }
}
