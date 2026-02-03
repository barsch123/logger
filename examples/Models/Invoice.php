<?php

namespace Examples\Models;

use Illuminate\Database\Eloquent\Model;
use Gottvergessen\Activity\Traits\TracksModelActivity;
use Gottvergessen\Activity\Traits\InteractsWithActivity;

/**
 * Example: Invoice with Activity Tracking
 * 
 * Demonstrates how to organize different model types into separate log categories
 * for better audit trail organization.
 */
class Invoice extends Model
{
    use TracksModelActivity, InteractsWithActivity;

    protected $fillable = ['invoice_number', 'amount', 'status', 'customer_id'];

    /**
     * Separate invoices into their own log category.
     * This makes it easy to query only invoice-related activities.
     */
    public function activityLog(): string
    {
        return 'invoices';
    }

    /**
     * Custom action labels for invoice-specific operations.
     */
    public function activityAction(string $event): string
    {
        return match ($event) {
            'created' => 'invoice_issued',
            'updated' => 'invoice_modified',
            'deleted' => 'invoice_cancelled',
            default => $event,
        };
    }

    public function activityDescription(string $event): string
    {
        return match ($event) {
            'created' => "Invoice #{$this->invoice_number} issued for \${$this->amount}",
            'updated' => "Invoice #{$this->invoice_number} updated",
            'deleted' => "Invoice #{$this->invoice_number} cancelled",
            default => $event,
        };
    }

    /**
     * Don't log status updates that don't really change anything
     */
    protected array $ignoredAttributes = [
        'updated_at',
    ];
}
