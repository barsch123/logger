<?php

use Illuminate\Database\Eloquent\Model;
use Gottvergessen\Logger\Traits\TracksModelActivity;
use Gottvergessen\Logger\Models\Activity;


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
