<?php

use Illuminate\Database\Eloquent\Model;
use Gottvergessen\Activity\Models\Activity;
use Gottvergessen\Activity\Traits\TracksModelActivity;
use Gottvergessen\Activity\Traits\InteractsWithActivity;

class DummyModel extends Model
{
    use TracksModelActivity;

    protected $table = 'dummy_models';
    protected $guarded = [];
}

it('logs model creation', function () {
    DummyModel::create(['name' => 'Test']);

    expect(Activity::count())->toBe(1);

    $log = Activity::first();

    expect($log->event)->toBe('created');
    expect($log->subject_type)->toBe(DummyModel::class);
});


it('logs updated attributes as diffs', function () {
    $model = DummyModel::create([
        'name' => 'Before',
    ]);

    $model->update([
        'name' => 'After',
    ]);

    $activity = Activity::where('event', 'updated')->first();
    expect($activity->event)->toBe('updated')
        ->and($activity->properties)->toHaveKey('name')
        ->and($activity->properties['name']['old'])->toBe('Before')
        ->and($activity->properties['name']['new'])->toBe('After');
});


class ActivityRelationModel extends Model
{
    use TracksModelActivity, InteractsWithActivity;

    protected $table = 'dummy_models';
    protected $guarded = [];
}

it('allows accessing activities via the model relationship', function () {
    $model = ActivityRelationModel::create([
        'name' => 'Test',
    ]);

    expect($model->activities)->toHaveCount(1);

    $activity = $model->activities->first();

    expect($activity)
        ->toBeInstanceOf(Activity::class)
        ->and($activity->event)->toBe('created');
});