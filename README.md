# Logger

Logger is a lightweight, opinionated activity logging package for Laravel that automatically tracks model changes and records who did what, to which model, and when — without polluting your domain logic.


## Installation

You can install the package via composer:

```bash
composer require gottvergessen/activity
```

You can publish and run the migrations with:

```bash
php artisan activity:install
```

### config/activity.php

```php
return [
    'enabled' => true,

    'table' => 'activity_logs',

    'default_log' => 'default',

    'events' => [
        'created',
        'updated',
        'deleted',
        'restored',
    ],

    'ignore_attributes' => [
        'created_at',
        'updated_at',
        'deleted_at',
        'remember_token',
    ],
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

## Ignore Specific Fields per Model

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
| event | string | The event type (created, updated, deleted, restored) |
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

```php
use Gottvergessen\Activity\Traits\TracksModelActivity;
use Gottvergessen\Activity\Traits\InteractsWithActivity;

class User extends Authenticatable
{
    use TracksModelActivity, InteractsWithActivity;
}
```

With the `InteractsWithActivity` trait, you can easily query a model's activities:

```php
$users = User::with('activities')->get();

// Access activities for a specific model
$userActivities = $user->activities()->get();
$userActivities = $user->activities()->where('event', 'updated')->get();
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
            'created' => "Appointment was scheduled for {$this->scheduled_at}",
            'updated' => "Appointment has been updated to {$this->new_appointment_date}",
            default   => $event,
        };
    }
}
```

### Batch Operations

Feature not yet Implement but it will be:
Group multiple model changes under a single batch ID:

```php
use Gottvergessen\Activity\Activity;

Activity::batch(function () {
    $user->update(['name' => 'John']);
    $user->profile()->update(['bio' => 'Updated bio']);
    // Both changes share the same batch_id
});
```



## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Gottvergessen](https://github.com/Gott)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
