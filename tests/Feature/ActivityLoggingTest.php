<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Gottvergessen\Activity\Models\Activity;
use Gottvergessen\Activity\Traits\TracksModelActivity;
use Gottvergessen\Activity\Traits\InteractsWithActivity;
use Gottvergessen\Activity\Support\ActivityContext;
use Gottvergessen\Activity\Activity as ActivityFacade;

class DummyModel extends Model
{
    use TracksModelActivity;

    protected $table = 'dummy_models';
    protected $guarded = [];
}

class DummyModelWithIgnored extends Model
{
    use TracksModelActivity;

    protected $table = 'dummy_models';
    protected $guarded = [];
    
    protected array $ignoredAttributes = ['secret'];
}

class DummyModelWithSoftDeletes extends Model
{
    use TracksModelActivity, SoftDeletes;

    protected $table = 'dummy_models';
    protected $guarded = [];
}

class ActivityRelationModel extends Model
{
    use TracksModelActivity, InteractsWithActivity;

    protected $table = 'dummy_models';
    protected $guarded = [];
}

class CustomTrackingModel extends Model
{
    use TracksModelActivity;

    protected $table = 'dummy_models';
    protected $guarded = [];
    
    protected array $trackEvents = ['created'];
    
    public function activityAction(string $event): string
    {
        return match ($event) {
            'created' => 'custom_created',
            default => $event,
        };
    }
    
    public function activityLog(): string
    {
        return 'custom_log';
    }
    
    public function activityDescription(string $event): string
    {
        return "Custom description for {$event}";
    }
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

it('ignores specified attributes on per-model basis', function () {
    $model = DummyModelWithIgnored::create([
        'name' => 'Test',
    ]);

    $activity = Activity::first();
    expect($activity->properties)->toHaveKey('name');
    
    // Now update with secret to test it's ignored
    Activity::query()->delete();
    $model->update(['name' => 'Updated', 'secret' => 'hidden']);
    
    $updateActivity = Activity::first();
    expect($updateActivity->properties)->toHaveKey('name')
        ->and($updateActivity->properties)->not->toHaveKey('secret');
});

it('does not log update when only ignored attributes change', function () {
    $model = DummyModelWithIgnored::create(['name' => 'Test', 'secret' => 'hidden']);
    
    Activity::query()->delete(); // Clear creation log
    
    $model->update(['secret' => 'new_secret']);
    
    expect(Activity::count())->toBe(0);
});

it('logs deletion events', function () {
    $model = DummyModel::create(['name' => 'Test']);
    
    Activity::query()->delete(); // Clear creation log
    
    $model->delete();
    
    $activity = Activity::first();
    expect($activity->event)->toBe('deleted')
        ->and($activity->subject_type)->toBe(DummyModel::class);
});

it('logs restoration events with soft deletes', function () {
    $model = DummyModelWithSoftDeletes::create(['name' => 'Test']);
    $model->delete();
    
    Activity::query()->delete(); // Clear previous logs
    
    $model->restore();
    
    $activity = Activity::first();
    expect($activity->event)->toBe('restored')
        ->and($activity->subject_type)->toBe(DummyModelWithSoftDeletes::class);
});

it('respects custom event tracking configuration', function () {
    CustomTrackingModel::create(['name' => 'Test']);
    
    Activity::query()->delete();
    
    $model = CustomTrackingModel::first();
    $model->update(['name' => 'Updated']);
    
    // Should not log update since trackEvents only includes 'created'
    expect(Activity::count())->toBe(0);
});

it('uses custom action from model', function () {
    CustomTrackingModel::create(['name' => 'Test']);
    
    $activity = Activity::first();
    expect($activity->action)->toBe('custom_created');
});

it('uses custom log category from model', function () {
    CustomTrackingModel::create(['name' => 'Test']);
    
    $activity = Activity::first();
    expect($activity->log)->toBe('custom_log');
});

it('uses custom description from model', function () {
    CustomTrackingModel::create(['name' => 'Test']);
    
    $activity = Activity::first();
    expect($activity->description)->toBe('Custom description for created');
});

it('can disable activity logging temporarily', function () {
    ActivityContext::withoutLogging(function () {
        DummyModel::create(['name' => 'Test']);
    });
    
    expect(Activity::count())->toBe(0);
});

it('re-enables logging after withoutLogging callback', function () {
    ActivityContext::withoutLogging(function () {
        DummyModel::create(['name' => 'Test 1']);
    });
    
    DummyModel::create(['name' => 'Test 2']);
    
    expect(Activity::count())->toBe(1);
});

it('can disable and enable logging manually', function () {
    ActivityContext::disable();
    DummyModel::create(['name' => 'Test 1']);
    
    ActivityContext::enable();
    DummyModel::create(['name' => 'Test 2']);
    
    expect(Activity::count())->toBe(1);
});

it('groups activities in a batch', function () {
    $batchId = null;
    
    ActivityFacade::batch(function () use (&$batchId) {
        DummyModel::create(['name' => 'Test 1']);
        DummyModel::create(['name' => 'Test 2']);
        
        $batchId = Activity::first()->batch_id;
    });
    
    expect(Activity::count())->toBe(2)
        ->and(Activity::pluck('batch_id')->unique())->toHaveCount(1)
        ->and($batchId)->not->toBeNull();
});

it('scopes activities by event', function () {
    $model = DummyModel::create(['name' => 'Test']);
    $model->update(['name' => 'Updated']);
    
    $created = Activity::forEvent('created')->get();
    $updated = Activity::forEvent('updated')->get();
    
    expect($created)->toHaveCount(1)
        ->and($updated)->toHaveCount(1);
});

it('scopes activities by subject', function () {
    $model1 = DummyModel::create(['name' => 'Test 1']);
    $model2 = DummyModel::create(['name' => 'Test 2']);
    
    $activities = Activity::forSubject($model1)->get();
    
    expect($activities)->toHaveCount(1)
        ->and($activities->first()->subject_id)->toBe($model1->id);
});

it('scopes activities by batch', function () {
    $batchId = null;
    
    ActivityFacade::batch(function () use (&$batchId) {
        DummyModel::create(['name' => 'Test 1']);
        $batchId = Activity::first()->batch_id;
    });
    
    DummyModel::create(['name' => 'Test 2']);
    
    $batchActivities = Activity::inBatch($batchId)->get();
    
    expect($batchActivities)->toHaveCount(1);
});

it('provides latestActivity helper', function () {
    $model = ActivityRelationModel::create(['name' => 'Test']);
    
    sleep(1); // Ensure different timestamps
    
    $model->update(['name' => 'Updated']);
    
    $latest = $model->fresh()->latestActivity();
    
    expect($latest)->toBeInstanceOf(Activity::class)
        ->and($latest->event)->toBe('updated');
});

it('provides activitiesCount helper', function () {
    $model = ActivityRelationModel::create(['name' => 'Test']);
    $model->update(['name' => 'Updated']);
    
    expect($model->activitiesCount())->toBe(2);
});

it('provides recentActivities helper', function () {
    $model = ActivityRelationModel::create(['name' => 'Test']);
    
    for ($i = 0; $i < 5; $i++) {
        $model->update(['name' => "Update {$i}"]);
    }
    
    $recent = $model->recentActivities(3);
    
    expect($recent)->toHaveCount(3);
});

it('provides hasActivities helper', function () {
    $model = ActivityRelationModel::create(['name' => 'Test']);
    
    expect($model->hasActivities())->toBeTrue();
    
    Activity::query()->delete();
    
    expect($model->fresh()->hasActivities())->toBeFalse();
});
