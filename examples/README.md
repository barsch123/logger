# Activity Logger Examples

This directory contains example Laravel models and implementations showing how to use the gottvergessen/activity package.

## Files

### Models

- **Post.php** - Blog post model with activity tracking
- **User.php** - User model with activity tracking and causer relationship
- **Invoice.php** - Invoice model with custom log categories
- **Appointment.php** - Appointment model with custom actions and descriptions

### Controllers

- **PostController.php** - Controller showing how to view activity history
- **ActivityLogController.php** - Controller showing how to query and display activities

### Views

- **activity-log.blade.php** - Example view for displaying activity timeline
- **post-history.blade.php** - Example view for displaying post change history

### Migrations

- **create_posts_table.php** - Example migration for posts
- **create_invoices_table.php** - Example migration for invoices
- **create_appointments_table.php** - Example migration for appointments

## Quick Start

1. Copy model files to `app/Models/`
2. Copy controller files to `app/Http/Controllers/`
3. Copy view files to `resources/views/`
4. Copy migration files to `database/migrations/`
5. Run migrations: `php artisan migrate`
6. Register routes in `routes/web.php`
7. Start creating and managing models to see activities logged automatically

## See Also

- [EXAMPLES.md](../EXAMPLES.md) - Comprehensive usage patterns
- [QUICKSTART.md](../QUICKSTART.md) - 5-minute setup guide
