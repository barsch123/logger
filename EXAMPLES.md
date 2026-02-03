```php
<?php

/**
 * Activity Logger Examples
 * 
 * This file demonstrates common usage patterns for the Activity Logger package.
 */

// ============================================================================
// 1. BASIC SETUP - Add tracking to your models
// ============================================================================

use Illuminate\Database\Eloquent\Model;
use Gottvergessen\Activity\Traits\TracksModelActivity;
use Gottvergessen\Activity\Traits\InteractsWithActivity;

class Post extends Model
{
    use TracksModelActivity, InteractsWithActivity;
    
    protected $fillable = ['title', 'content', 'published'];
}

// Usage:
$post = Post::create(['title' => 'My Post', 'content' => 'Hello']);
// ✓ Automatically logs: Event='created', Properties with all attributes

$post->update(['title' => 'Updated Title']);
// ✓ Automatically logs: Event='updated', Properties={'title': {'old': 'My Post', 'new': 'Updated Title'}}

$post->delete();
// ✓ Automatically logs: Event='deleted'


// ============================================================================
// 2. IGNORE SENSITIVE ATTRIBUTES
// ============================================================================

class User extends Model
{
    use TracksModelActivity, InteractsWithActivity;
    
    protected $fillable = ['name', 'email', 'password'];
    
    // These fields won't appear in activity logs
    protected array $ignoredAttributes = [
        'password',
        'remember_token',
    ];
}

// Usage:
$user = User::create([
    'name' => 'John',
    'email' => 'john@example.com',
    'password' => 'secret123'
]);
// ✓ Logs created event, but password NOT included in properties


// ============================================================================
// 3. CUSTOM ACTION, LOG CATEGORY, AND DESCRIPTION
// ============================================================================

class Invoice extends Model
{
    use TracksModelActivity, InteractsWithActivity;
    
    protected $fillable = ['number', 'amount', 'status'];
    
    // Custom semantic action
    public function activityAction(string $event): string
    {
        return match ($event) {
            'created' => 'invoice_created',
            'updated' => 'invoice_modified',
            'deleted' => 'invoice_cancelled',
            default => $event,
        };
    }
    
    // Group activities by custom log name
    public function activityLog(): string
    {
        return 'invoices';
    }
    
    // Custom readable description
    public function activityDescription(string $event): string
    {
        return match ($event) {
            'created' => "Invoice {$this->number} was created",
            'updated' => "Invoice {$this->number} was updated",
            'deleted' => "Invoice {$this->number} was cancelled",
            default => "Invoice {$this->number} {$event}",
        };
    }
}

// Usage:
$invoice = Invoice::create(['number' => 'INV-001', 'amount' => 1000, 'status' => 'draft']);
// ✓ Logs: action='invoice_created', log='invoices', description='Invoice INV-001 was created'


// ============================================================================
// 4. CONTROL WHICH EVENTS ARE TRACKED
// ============================================================================

class ArchiveEntry extends Model
{
    use TracksModelActivity;
    
    // Only track creation, ignore updates and deletes
    protected array $trackEvents = ['created'];
}

// Usage:
$archive = ArchiveEntry::create(['data' => 'archive data']);
// ✓ Logged

$archive->update(['data' => 'new data']);
// ✗ NOT logged (update not in trackEvents)


// ============================================================================
// 5. BATCH OPERATIONS - Group related changes
// ============================================================================

use Gottvergessen\Activity\Activity;

Activity::batch(function () {
    $user = User::create(['name' => 'Jane', 'email' => 'jane@example.com']);
    $user->update(['name' => 'Jane Doe']);
    
    Post::create(['title' => 'Jane\'s First Post', 'user_id' => $user->id]);
});

// ✓ All 3 activities share the same batch_id
// Usage: Activity::inBatch($batchId)->get()


// ============================================================================
// 6. DISABLE LOGGING TEMPORARILY
// ============================================================================

use Gottvergessen\Activity\Support\ActivityContext;

// Method 1: Using withoutLogging
ActivityContext::withoutLogging(function () {
    User::create(['name' => 'Temporary User', 'email' => 'temp@example.com']);
});
// ✗ NOT logged

// Method 2: Manual control
ActivityContext::disable();
User::create(['name' => 'Another Temp', 'email' => 'temp2@example.com']);
// ✗ NOT logged

ActivityContext::enable();
User::create(['name' => 'Now Logged', 'email' => 'now@example.com']);
// ✓ Logged


// ============================================================================
// 7. QUERYING ACTIVITIES WITH SCOPES
// ============================================================================

use Gottvergessen\Activity\Models\Activity;

// Filter by event type
$createdActivities = Activity::forEvent('created')->get();
$deletedActivities = Activity::forEvent('deleted')->get();

// Filter by subject (model)
$userActivities = Activity::forSubject($user)->get();
$postActivities = Activity::forSubject($post)->get();

// Filter by causer (who made the change)
$adminActivities = Activity::causedBy($adminUser)->get();
$anonymousActivities = Activity::causedBy(null)->get();

// Filter by batch ID
$batchActivities = Activity::inBatch($batchId)->get();

// Filter by log category
$invoiceActivities = Activity::inLog('invoices')->get();

// Filter by date range
$recentActivities = Activity::betweenDates(now()->subDays(7), now())->get();

// Combine scopes
$userInvoiceCreations = Activity::forSubject($user)
    ->forEvent('created')
    ->inLog('invoices')
    ->get();


// ============================================================================
// 8. ACCESSING ACTIVITIES VIA MODEL RELATIONSHIP
// ============================================================================

// Get all activities for a model
$activities = $user->activities()->get();

// Get most recent activity
$latest = $user->latestActivity();

// Get count without loading records
$count = $user->activitiesCount();

// Get recent activities (limited)
$recent = $user->recentActivities(10);

// Check if model has any activities
if ($user->hasActivities()) {
    echo "User has activity history";
}

// Eager load limited activities
$users = User::with('activitiesLimited:10')->get();


// ============================================================================
// 9. WORKING WITH ACTIVITY LOG DATA
// ============================================================================

$activity = Activity::first();

// Access basic information
$event = $activity->event;              // 'created', 'updated', 'deleted', etc.
$action = $activity->action;            // Custom action (if defined)
$log = $activity->log;                  // Log category
$description = $activity->description;  // Human-readable description

// Access the subject (the model that was changed)
$model = $activity->subject;            // Returns the actual model instance
$modelType = $activity->subject_type;   // 'App\Models\Post'
$modelId = $activity->subject_id;       // The ID

// Access who made the change (if captured)
$causer = $activity->causer;            // Returns the user/model that caused it
$causerType = $activity->causer_type;   // 'App\Models\User'
$causerId = $activity->causer_id;       // The ID

// Access change data
$properties = $activity->properties;    // Array of changes
// For updates: ['field' => ['old' => 'old_value', 'new' => 'new_value']]
// For creates: ['field' => 'value', ...]

// Access metadata
$meta = $activity->meta;                // IP, user agent, HTTP method, etc.

// Batch operations
$batchId = $activity->batch_id;         // Null if not in a batch
$createdAt = $activity->created_at;     // When logged


// ============================================================================
// 10. PRUNING OLD LOGS
// ============================================================================

// In your terminal:
// php artisan activity:prune                    # Keep last 90 days (default)
// php artisan activity:prune --days=30          # Keep last 30 days
// php artisan activity:prune --days=365         # Keep last year

// Schedule in app/Console/Kernel.php:
// $schedule->command('activity:prune --days=90')->daily();


// ============================================================================
// 11. CONFIGURATION OPTIONS
// ============================================================================

// In config/activity.php:

return [
    'enabled' => true,                  // Global on/off switch
    'table' => 'activity_logs',         // Database table name
    'default_log' => 'default',         // Default log category
    
    'events' => [                       // Events to track by default
        'created',
        'updated',
        'deleted',
        'restored',
    ],
    
    'ignore_attributes' => [            // Fields to ignore globally
        'created_at',
        'updated_at',
        'deleted_at',
        'remember_token',
    ],
    
    'capture_causer' => true,           // Log authenticated user
    'capture_request_meta' => false,    // Log HTTP method, host
    'capture_ip' => false,              // Log IP address (privacy opt-in)
    'auto_batch' => false,              // Auto-batch all activities
];


// ============================================================================
// 12. REAL-WORLD EXAMPLE: AUDIT TRAIL FOR ADMIN ACTIONS
// ============================================================================

class AdminController extends Controller
{
    public function deleteUser(User $user)
    {
        // Capture who deleted the user
        ActivityContext::enable();
        
        Activity::batch(function () use ($user) {
            // Log soft delete
            $user->delete();
            
            // Log related cleanup
            $user->posts()->delete();
            $user->comments()->delete();
        });
        
        return redirect()->back()->with('success', 'User deleted');
    }
}

// Query the audit trail:
$userDeletions = Activity::forEvent('deleted')
    ->forSubject($user)
    ->where('causer_id', $adminId)
    ->orderBy('created_at', 'desc')
    ->get();

foreach ($userDeletions as $deletion) {
    echo "Admin {$deletion->causer->name} deleted user on {$deletion->created_at}";
}


// ============================================================================
// 13. REAL-WORLD EXAMPLE: TRACK DOCUMENT CHANGES
// ============================================================================

class Document extends Model
{
    use TracksModelActivity, InteractsWithActivity;
    
    protected $fillable = ['title', 'content', 'status'];
    protected array $ignoredAttributes = ['views_count'];
    
    public function activityDescription(string $event): string
    {
        return match ($event) {
            'created' => "Document '{$this->title}' was created",
            'updated' => "Document '{$this->title}' was updated to {$this->status}",
            'deleted' => "Document '{$this->title}' was deleted",
            default => "Document '{$this->title}' was {$event}",
        };
    }
}

// In DocumentController:
public function show(Document $doc)
{
    // Show the document with its change history
    return view('document.show', [
        'document' => $doc,
        'history' => $doc->activities()
            ->latest()
            ->limit(20)
            ->get(),
        'lastModified' => $doc->latestActivity(),
    ]);
}

// In Blade template:
@foreach($history as $activity)
    <div class="activity-item">
        <strong>{{ $activity->causer?->name ?? 'System' }}</strong>
        {{ $activity->description }}
        <small>{{ $activity->created_at->diffForHumans() }}</small>
    </div>
@endforeach
