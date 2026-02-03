<?php

namespace Gottvergessen\Activity\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Gottvergessen\Activity\Models\Activity;
use Gottvergessen\Activity\Support\ActivityContext;

class ActivityTracker
{
    public function track(Model $model, string $event, ?array $attributes = null): void
    {
        if ($event === 'updated' && ! $this->hasMeaningfulChanges($model)) {
            return;
        }

        if (! $this->shouldTrack($model, $event)) {
            return;
        }

        // Capture causer (opt-in)
        if (config('activity.capture_causer', true)) {
            if ($user = Auth::user()) {
                ActivityContext::setCauser(
                    get_class($user),
                    $user->getAuthIdentifier()
                );
            }
        }

        // Capture request metadata (opt-in & safe)
        if (
            config('activity.capture_request_meta', false)
            && app()->runningInConsole() === false
            && request()
        ) {
            ActivityContext::addMeta(array_filter([
                'method' => request()->method(),
                'host'   => request()->httpHost(),
            ]));
        }

        // Capture IP separately (explicit opt-in)
        if (
            config('activity.capture_ip', false)
            && app()->runningInConsole() === false
            && request()
        ) {
            ActivityContext::addMeta([
                'ip' => request()->ip(),
            ]);
        }

        Activity::create([
            'event'        => $event,
            'action'       => $this->action($model, $event),
            'log'          => $this->log($model),
            'description'  => $this->description($model, $event),
            'subject_type' => get_class($model),
            'subject_id'   => $model->getKey(),
            'causer_type'  => ActivityContext::causerType(),
            'causer_id'    => ActivityContext::causerId(),
            'properties'   => $this->resolveProperties($model, $event, $attributes),
            'meta'         => ActivityContext::meta(),
            'batch_id'     => $this->batchId(),
        ]);

        ActivityContext::flush();
    }


    /* -------------------------------- */
    /* Core decisions                   */
    /* -------------------------------- */

    protected function action(Model $model, string $event): ?string
    {
        // Model-defined semantic action
        if (method_exists($model, 'activityAction')) {
            return $model->activityAction($event);
        }

        return null;
    }


    protected function log(Model $model): string
    {
        if (method_exists($model, 'activityLog')) {
            return (string) $model->activityLog();
        }

        return (string) config('activity.default_log', 'default');
    }

    protected function hasMeaningfulChanges(Model $model): bool
    {
        $ignored = method_exists($model, 'getIgnoredAttributes')
            ? $model->getIgnoredAttributes()
            : [];

        return collect($model->getChanges())
            ->reject(fn($_, $key) => in_array($key, $ignored, true))
            ->isNotEmpty();
    }

    protected function shouldTrack(Model $model, string $event): bool
    {
        // Check if model has custom event tracking
        if (method_exists($model, 'shouldTrackEvent')) {
            return $model->shouldTrackEvent($event);
        }

        return true;
    }

    /* -------------------------------- */
    /* Data builders                    */
    /* -------------------------------- */

    protected function resolveProperties(Model $model, string $event, ?array $attributes): array
    {
        if (method_exists($model, 'activityProperties')) {
            return $model->activityProperties($event, $attributes);
        }

        return match ($event) {
            'created' => $attributes ?? $model->getAttributes(),
            'updated' => $this->resolveChanges($model),
            default   => [],
        };
    }


    protected function resolveChanges(Model $model): array
    {
        $ignored = method_exists($model, 'getIgnoredAttributes')
            ? $model->getIgnoredAttributes()
            : config('activity.ignore_attributes', []);

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
    protected function description(Model $model, string $event): string
    {
        if (method_exists($model, 'activityDescription')) {
            return $model->activityDescription($event);
        }

        return class_basename($model) . ' ' . $event;
    }


    protected function batchId(): ?string
    {
        return $this->explicitBatch()
            ?? $this->requestBatch()
            ?? $this->autoBatch();
    }

    protected function explicitBatch(): ?string
    {
        return app()->bound('activity.batch')
            ? app('activity.batch')
            : null;
    }

    protected function requestBatch(): ?string
    {
        return ActivityContext::batchId();
    }

    protected function autoBatch(): ?string
    {
        return config('activity.auto_batch', false)
            ? (string) Str::uuid()
            : null;
    }
}
