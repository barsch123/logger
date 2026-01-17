<?php

namespace Gottvergessen\Logger\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Gottvergessen\Logger\Models\Activity;
use Gottvergessen\Logger\Support\ActivityContext;

class ActivityTracker
{
    public static function track(Model $model, string $event, ?array $attributes = null): void
    {
        if ($event === 'updated' && ! static::hasMeaningfulChanges($model)) {
            return;
        }

        if (! static::shouldTrack($model, $event)) {
            return;
        }
        $user = Auth::user();

        if (method_exists($model, 'shouldTrackEvent') && ! $model->shouldTrackEvent($event)) {
            return;
        }

        if ($user) {
            ActivityContext::setCauser(
                get_class($user),
                $user->getAuthIdentifier(),
            );
        }
        ActivityContext::addMeta([
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'method' => request()->method(),
            'host' => request()->httpHost(),
        ]);
        Activity::create([
            'event'        => $event,
            'action'       => static::action($model, $event),
            'log'          => static::log($model),
            'description'  => static::description($model, $event),
            'subject_type' => get_class($model),
            'subject_id'   => $model->getKey(),
            'causer_type'  => ActivityContext::causerType(),
            'causer_id'    => ActivityContext::causerId(),
            'properties'   => static::resolveProperties($model, $event, $attributes),
            'meta'         => ActivityContext::meta(),
            'batch_id'     => static::batchId(),
        ]);
    }

    /* -------------------------------- */
    /* Core decisions                   */
    /* -------------------------------- */

    protected static function action(Model $model, string $event): ?string
    {
        // Model-defined semantic action
        if (method_exists($model, 'activityAction')) {
            return $model->activityAction($event);
        }

        return null;
    }

    // protected static function properties(
    //     Model $model,
    //     string $event,
    //     ?array $attributes
    // ): array {
    //     return static::resolveProperties($model, $event, $attributes);
    // }




    protected static function log(Model $model): string
    {
        if (method_exists($model, 'activityLog')) {
            return (string) $model->activityLog();
        }

        return (string) config('logger.default_log', 'default');
    }

    protected static function hasMeaningfulChanges(Model $model): bool
    {
        $ignored = method_exists($model, 'getIgnoredAttributes')
            ? $model->getIgnoredAttributes()
            : [];

        return collect($model->getChanges())
            ->reject(fn($_, $key) => in_array($key, $ignored, true))
            ->isNotEmpty();
    }






    protected static function shouldTrack(Model $model, string $event): bool
    {
        $events = config('logger.events', []);

        $modelKey = get_class($model);

        if (! isset($events[$modelKey])) {
            return true;
        }

        return in_array($event, $events[$modelKey], true);
    }

    /* -------------------------------- */
    /* Data builders                    */
    /* -------------------------------- */

    protected static function resolveProperties(
        Model $model,
        string $event,
        ?array $attributes
    ): array {
        return match ($event) {
            'created' => $attributes ?? $model->getAttributes(),
            'updated' => static::resolveChanges($model),
            default   => [],
        };
    }

    protected static function resolveChanges(Model $model): array
    {
        $ignored = method_exists($model, 'getIgnoredAttributes')
            ? $model->getIgnoredAttributes()
            : config('logger.ignore_attributes', []);

        return collect($model->getChanges())
            ->reject(fn($_, $key) => in_array($key, $ignored, true))
            ->map(fn($value, $key) => [
                'old' => $model->getOriginal($key),
                'new' => $value,
            ])
            ->toArray();
    }



    /* -------------------------------- */
    /* Metadata                         */
    /* -------------------------------- */
    protected static function description(Model $model, string $event): string
    {
        if (method_exists($model, 'activityDescription')) {
            return $model->activityDescription($event);
        }

        return class_basename($model) . ' ' . $event;
    }


    protected static function batchId(): ?string
    {
        return static::explicitBatch()
            ?? static::requestBatch()
            ?? static::autoBatch();
    }

    protected static function explicitBatch(): ?string
    {
        return app()->bound('logger.batch')
            ? app('logger.batch')
            : null;
    }
    protected static function requestBatch(): ?string
    {
        return ActivityContext::batchId();
    }

    protected static function autoBatch(): ?string
    {
        return config('logger.auto_batch', false)
            ? (string) Str::uuid()
            : null;
    }
}
