# Quick Start Guide

Get up and running with the Activity Logger package in 5 minutes.

## Installation

```bash
composer require gottvergessen/activity
php artisan activity:install
php artisan migrate
```

## Basic Setup

Add the `TracksModelActivity` trait to any model you want to track:

```php
use Gottvergessen\Activity\Traits\TracksModelActivity;

class User extends Model
{
    use TracksModelActivity;
}
```

Done! All changes to the User model are now logged automatically.

## View Activity History

Add `InteractsWithActivity` to access activity relationships:

```php
use Gottvergessen\Activity\Traits\InteractsWithActivity;

class User extends Model
{
    use TracksModelActivity, InteractsWithActivity;
}

// View activities
$user->activities()->get();              // All activities
$user->latestActivity();                 // Most recent
$user->recentActivities(10);             // Last 10
```

## Common Tasks

### Ignore Sensitive Fields

```php
class User extends Model
{
    use TracksModelActivity;
    
    protected array $ignoredAttributes = ['password'];
}
```

### Custom Description

```php
class Invoice extends Model
{
    use TracksModelActivity;
    
    public function activityDescription(string $event): string
    {
        return "Invoice #{$this->id} was {$event}";
    }
}
```

### Filter Activities

```php
use Gottvergessen\Activity\Models\Activity;

Activity::forEvent('updated')->get();
Activity::forSubject($user)->get();
Activity::causedBy($admin)->get();
Activity::betweenDates($start, $end)->get();
```

### Batch Operations

```php
use Gottvergessen\Activity\Activity;

Activity::batch(function () {
    $user->update(['name' => 'Jane']);
    $user->profile()->update(['bio' => 'New bio']);
    // Both logged with same batch_id
});
```

### Disable Logging

```php
use Gottvergessen\Activity\Support\ActivityContext;

ActivityContext::withoutLogging(function () {
    User::create(['name' => 'Test']);  // Not logged
});
```

### Prune Old Logs

```bash
php artisan activity:prune --days=90
```

## Configuration

Edit `config/activity.php` to customize:

```php
return [
    'enabled' => true,                    // Global on/off
    'capture_causer' => true,             // Log authenticated user
    'ignore_attributes' => [              // Fields to always ignore
        'created_at',
        'updated_at',
        'password',
    ],
];
```

## Next Steps

- Read the [full documentation](README.md)
- Check out [detailed examples](EXAMPLES.md)
- Explore [query scopes](README.md#query-scopes)
- Schedule [log pruning](README.md#pruning-old-logs)

## Need Help?

- Review [EXAMPLES.md](EXAMPLES.md) for real-world patterns
- Check the [database schema](README.md#database-schema)
- See [all available scopes](README.md#query-scopes)
