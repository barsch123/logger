<?php

use Illuminate\Database\Eloquent\Model;
use Gottvergessen\Activity\Models\Activity;
use Gottvergessen\Activity\Traits\TracksModelActivity;

class ConfigDummyModel extends Model
{
    use TracksModelActivity;

    protected $table = 'dummy_models';
    protected $guarded = [];
}

it('respects global enabled config', function () {
    config(['activity.enabled' => false]);
    
    ConfigDummyModel::create(['name' => 'Test']);
    
    expect(Activity::count())->toBe(0);
    
    config(['activity.enabled' => true]);
});

it('respects global ignored attributes config', function () {
    config(['activity.ignore_attributes' => ['name']]);
    
    $model = ConfigDummyModel::create(['name' => 'Test']);
    
    Activity::query()->delete();
    
    $model->update(['name' => 'Updated']);
    
    // Should not log since name is ignored
    expect(Activity::count())->toBe(0);
});

it('merges model-level and global ignored attributes', function () {
    config(['activity.ignore_attributes' => ['created_at', 'updated_at']]);
    
    $model = new class extends Model {
        use TracksModelActivity;
        
        protected $table = 'dummy_models';
        protected $guarded = [];
        protected array $ignoredAttributes = ['secret'];
    };
    
    $ignored = $model->getIgnoredAttributes();
    
    expect($ignored)->toContain('created_at')
        ->and($ignored)->toContain('updated_at')
        ->and($ignored)->toContain('secret');
});

it('uses default log from config', function () {
    config(['activity.default_log' => 'custom_default']);
    
    ConfigDummyModel::create(['name' => 'Test']);
    
    $activity = Activity::first();
    expect($activity->log)->toBe('custom_default');
});
