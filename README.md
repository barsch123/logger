# Logger

Logger is a lightweight, opinionated activity logging package for Laravel that automatically tracks model changes and records who did what, to which model, and when — without polluting your domain logic.


## Installation

You can install the package via composer:

```bash
composer require gott/logger
```

You can publish and run the migrations with:

```bash
php artisan logger:install
```


### config/logger.php

```php
    return [
        'enabled' => env('LOGGER_ENABLED', true),

        'table' => 'activity_logs',

        'default_log' => env('LOGGER_DEFAULT_LOG', 'default'),

        'origin' => env('LOGGER_ORIGIN', 'web'),

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

        'use_diffs' => true,

        'driver' => 'database',
    ];

```

## Basic usage

```php
use Gottvergessen\Logger\Traits\TracksModelActivity;

class User extends Authenticatable
{
    use TracksModelActivity;

}
```

## Ignore Specific Fields per Model
```php
use Gottvergessen\Logger\Traits\TracksModelActivity;

class User extends Authenticatable
{
    use TracksModelActivity;

    protected array $ignoredAttributes = [
        'password',
        'remember_token',
    ];

    /*
    *  Changes to the listed attributes will not appear in the activity properties
    *  If only ignored attributes change, no activity log entry is created
    */
}
```
## Per-Model Event Control
```php
use Gottvergessen\Logger\Traits\TracksModelActivity;

class User extends Authenticatable
{
    use TracksModelActivity;

    protected array $trackEvents = [
        'created',
        'updated',
    ];

    /*
    *  Limit which events are tracked:
    *  
    */
}
```
### Custom description per Model
```php
class Appointment extends Model
{
    use TracksModelActivity;

    public function activityDescription(string $event): string
    {
        return match ($event) {
            'created' => "Appointment scheduled for {$this->scheduled_at}",
            'updated' => "Appointment updated for {$this->scheduled_at}",
            'deleted' => "Appointment cancelled",
            default   => "Appointment {$event}",
        };
    }
}
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
