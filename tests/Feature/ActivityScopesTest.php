<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Gottvergessen\Activity\Models\Activity;
use Gottvergessen\Activity\Traits\TracksModelActivity;
use Gottvergessen\Activity\Tests\Fixtures\User;

class ScopeDummyModel extends Model
{
    use TracksModelActivity;

    protected $table = 'dummy_models';
    protected $guarded = [];
}

it('scopes activities by causer', function () {
    $user1 = User::create(['name' => 'User 1', 'email' => 'user1@test.com', 'password' => 'password']);
    $user2 = User::create(['name' => 'User 2', 'email' => 'user2@test.com', 'password' => 'password']);
    
    Auth::login($user1);
    ScopeDummyModel::create(['name' => 'Test 1']);
    Auth::logout();
    
    Auth::login($user2);
    ScopeDummyModel::create(['name' => 'Test 2']);
    Auth::logout();
    
    $user1Activities = Activity::causedBy($user1)->get();
    $user2Activities = Activity::causedBy($user2)->get();
    
    expect($user1Activities)->toHaveCount(1)
        ->and($user2Activities)->toHaveCount(1)
        ->and($user1Activities->first()->causer_id)->toBe($user1->id)
        ->and($user2Activities->first()->causer_id)->toBe($user2->id);
});

it('scopes activities without causer', function () {
    ScopeDummyModel::create(['name' => 'Test without auth']);
    
    $user = User::create(['name' => 'User', 'email' => 'user@test.com', 'password' => 'password']);
    Auth::login($user);
    ScopeDummyModel::create(['name' => 'Test with auth']);
    Auth::logout();
    
    $withoutCauser = Activity::causedBy(null)->get();
    
    expect($withoutCauser)->toHaveCount(1)
        ->and($withoutCauser->first()->causer_id)->toBeNull();
});

it('scopes activities by log category', function () {
    config(['activity.default_log' => 'default']);
    
    ScopeDummyModel::create(['name' => 'Test 1']);
    
    $activities = Activity::inLog('default')->get();
    
    expect($activities)->toHaveCount(1);
});

it('scopes activities between dates', function () {
    // Create old activity
    ScopeDummyModel::create(['name' => 'Old Test']);
    $oldActivity = Activity::first();
    $oldActivity->created_at = now()->subDays(10);
    $oldActivity->save();
    
    // Create recent activity
    ScopeDummyModel::create(['name' => 'Recent Test']);
    
    $recent = Activity::betweenDates(now()->subDay(), now()->addDay())->get();
    $old = Activity::betweenDates(now()->subDays(15), now()->subDays(8))->get();
    
    expect($recent)->toHaveCount(1)
        ->and($recent->first()->subject->name)->toBe('Recent Test')
        ->and($old)->toHaveCount(1)
        ->and($old->first()->subject->name)->toBe('Old Test');
});
