# Logger

Logger is a lightweight, opinionated activity logging package for Laravel that automatically tracks model changes and records who did what, to which model, and when — without polluting your domain logic.

**Features:**
-  Automatic tracking of model changes (create, update, delete, restore)
-  Privacy-first with explicit opt-in for sensitive data
-  Flexible configuration per model or globally
-  Query scopes for easy filtering and analysis
-  Batch operations to group related changes
-  Lightweight and performant
-  Built-in pruning command for cleanup
-  Comprehensive audit trails


## Installation

You can install the package via composer:

```bash
composer require gottvergessen/activity
```

### Publish Assets

Publish the configuration file and migrations:

```bash
php artisan activity:install
```

This will create:
- `config/activity.php` - Configuration file
- `database/migrations/[timestamp]_create_logger_table.php` - Database migration

You can also publish assets separately:

```bash
# Publish just the config file
php artisan vendor:publish --provider="Gottvergessen\Activity\ActivityServiceProvider" --tag="config"

# Publish just the migrations
php artisan vendor:publish --provider="Gottvergessen\Activity\ActivityServiceProvider" --tag="migrations"
```

### Run Migrations

After publishing, run the migrations to create the `activity_logs` table:

```bash
php artisan migrate
```

### Pruning Old Logs

To keep your database clean, you can prune old activity logs:

```bash
# Keep only the last 90 days (default)
php artisan activity:prune

# Keep only the last 30 days
php artisan activity:prune --days=30
```


### config/activity.php

```php
<?php
return [
    /*
    |--------------------------------------------------------------------------
    | Activity Logging Enabled
    |--------------------------------------------------------------------------
    |
    | This option controls whether activity logging is enabled globally.
    | You can disable this to turn off all activity logging.
    |
    */
    'enabled' => true,

    /*
    |--------------------------------------------------------------------------
    | Activity Logs Table Name
    |--------------------------------------------------------------------------
    |
    | The database table name where activity logs will be stored.
    |
    */
    'table' => 'activity_logs',

    /*
    |--------------------------------------------------------------------------
    | Default Log Category
    |--------------------------------------------------------------------------
    |
    | The default log category to use when a model doesn't specify one.
    |
    */
    'default_log' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Tracked Events
    |--------------------------------------------------------------------------
    |
    | The default events that will be tracked for all models.
    | Models can override this using the $trackEvents property.
    |
    */
    'events' => [
        'created',
        'updated',
        'deleted',
        'restored',
    ],

    /*
    |--------------------------------------------------------------------------
    | Ignored Attributes
    |--------------------------------------------------------------------------
    |
    | Attributes that should be ignored when logging changes globally.
    | Models can add more via the $ignoredAttributes property.
    |
    */
    'ignore_attributes' => [
        'created_at',
        'updated_at',
        'deleted_at',
        'remember_token',
    ],

    /*
    |--------------------------------------------------------------------------
    | Capture Causer
    |--------------------------------------------------------------------------
    |
    | Automatically capture the authenticated user who made the change.
    |
    */
    'capture_causer' => true,

    /*
    |--------------------------------------------------------------------------
    | Capture Request Metadata
    |--------------------------------------------------------------------------
    |
    | Capture HTTP request metadata (method, host).
    | Only applies to web requests, not console commands.
    |
    */
    'capture_request_meta' => false,

    /*
    |--------------------------------------------------------------------------
    | Capture IP Address
    |--------------------------------------------------------------------------
    |
    | Capture the IP address of the user making the change.
    | Only applies to web requests. Requires explicit opt-in due to privacy.
    |
    */
    'capture_ip' => false,

    /*
    |--------------------------------------------------------------------------
    | Auto Batch
    |--------------------------------------------------------------------------
    |
    | Automatically assign a unique batch ID to each request's activities.
    | When enabled, all activities within a single request share a batch_id.
    |
    */
    'auto_batch' => false,

];
```

## Basic usage

```php
use Gottvergessen\Activity\Traits\TracksModelActivity;

class User extends Authenticatable
{
    use TracksModelActivity;
}
```

Now all changes to the User model are automatically logged:

```php
$user = User::create(['name' => 'John', 'email' => 'john@example.com']);
// ✓ Creates activity log with event='created'

$user->update(['name' => 'Jane']);
// ✓ Creates activity log with event='updated' showing the change

$user->delete();
// ✓ Creates activity log with event='deleted'
```

### Ignore Specific Fields per Model

```php
use Gottvergessen\Activity\Traits\TracksModelActivity;

class User extends Authenticatable
{
    use TracksModelActivity;

    protected array $ignoredAttributes = [
        'password',
        'remember_token',
    ];

    /*
    * Changes to the listed attributes will not appear in the activity properties
    * If only ignored attributes change, no activity log entry is created
    */
}
```

## Database Schema

The `activity_logs` table tracks the following information:

| Column | Type | Description |
|--------|------|-------------|
| id | ID | Primary key |
| event | string | The event type (created, updated, deleted, restored). Events can be resolved into custom actions and descriptions|
| action | string | Custom semantic action defined by the model (optional) |
| log | string | Log category for grouping activities |
| description | string | Description of the activity (optional) |
| subject_type | string | The model class that was changed |
| subject_id | int | The ID of the model that was changed |
| causer_type | string | The model class of the user who made the change (optional) |
| causer_id | int | The ID of the user who made the change (optional) |
| properties | json | The model attributes that changed |
| meta | json | Metadata including IP, user agent, HTTP method, and host |
| batch_id | string | Batch ID for grouping related changes (optional) |
| created_at | timestamp | When the activity was recorded |
| updated_at | timestamp | When the activity record was last updated |

## Eloquent Friendly

Add the `InteractsWithActivity` trait to easily query a model's activities:

```php
use Gottvergessen\Activity\Traits\TracksModelActivity;
use Gottvergessen\Activity\Traits\InteractsWithActivity;

class User extends Authenticatable
{
    use TracksModelActivity, InteractsWithActivity;
}
```

Now you can access activities directly from your models:

```php
// Eager load activities
$users = User::with('activities')->get();

// Get all activities for a user
$user->activities()->get();

// Filter activities
$user->activities()->where('event', 'updated')->get();

// Get the most recent activity
$latest = $user->latestActivity();

// Check if user has any activities
if ($user->hasActivities()) {
    echo "This user has activity history";
}
```

The `InteractsWithActivity` trait is optional and only required if you want to access `$model->activities()`.

## Per-Model Event Control

```php
use Gottvergessen\Activity\Traits\TracksModelActivity;

class User extends Authenticatable
{
    use TracksModelActivity;

    protected array $trackEvents = [
        'created',
        'updated',
    ];

    /*
    * Limit which events are tracked for this model
    */
}
```
## Advanced Usage

### Custom Log Name per Model

```php
use Gottvergessen\Activity\Traits\TracksModelActivity;

class Invoice extends Model
{
    use TracksModelActivity;

    public function activityLog(): string
    {
        return 'invoices';
    }
}
```

### Custom Action per Model

```php
use Gottvergessen\Activity\Traits\TracksModelActivity;

class Appointment extends Model
{
    use TracksModelActivity;

    public function activityAction(string $event): string
    {
        return match ($event) {
            'created' => 'blog created',
            'updated' => 'blog updated',
            'deleted' => 'cancelled',
        };
    }
}
```
### Custom Description per Model

```php
use Gottvergessen\Activity\Traits\TracksModelActivity;

class Appointment extends Model
{
    use TracksModelActivity;

    public function activityDescription(string $event): string
    {
        return match ($event) {
            'created' => "Appointment was scheduled for {$this->scheduled_at}",
            'updated' => "Appointment has been updated to {$this->new_appointment_date}",
            default   => $event,
        };
    }
}
```
### Batch Operations

Group multiple model changes under a single batch ID:

```php
use Gottvergessen\Activity\Activity;

Activity::batch(function () {
    $user->update(['name' => 'John']);
    $user->profile()->update(['bio' => 'Updated bio']);
    // Both changes share the same batch_id
});
```

### Temporarily Disable Logging

You can temporarily disable activity logging when needed:

```php
use Gottvergessen\Activity\Support\ActivityContext;

ActivityContext::withoutLogging(function () {
    // These changes won't be logged
    User::create(['name' => 'John']);
    $user->update(['email' => 'john@example.com']);
});

// Or manually control logging
ActivityContext::disable();
User::create(['name' => 'Jane']); // Not logged
ActivityContext::enable();
User::create(['name' => 'Bob']); // Logged
```

### Query Scopes

The Activity model provides convenient query scopes:

```php
use Gottvergessen\Activity\Models\Activity;

// Filter by event type
Activity::forEvent('created')->get();

// Filter by subject model
Activity::forSubject($user)->get();

// Filter by causer
Activity::causedBy($admin)->get();

// Filter by batch
Activity::inBatch($batchId)->get();

// Filter by log category
Activity::inLog('invoices')->get();

// Filter by date range
Activity::betweenDates($startDate, $endDate)->get();
```


###  Audit Trail

```php
$deletions = Activity::forEvent('deleted')
    ->forSubject($user)
    ->with('causer')
    ->latest()
    ->get();
```

###  Document History

```php
class Document extends Model
{
    use TracksModelActivity, InteractsWithActivity;
}

@foreach($document->activities()->latest()->get() as $activity)
    {{ $activity->causer?->name }}: {{ $activity->description }}
@endforeach
```

For more examples and patterns, see [EXAMPLES.md](EXAMPLES.md).

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
