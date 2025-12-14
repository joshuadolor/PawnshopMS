# MySQL Setup Guide for Philippines Timezone

This application is configured to use MySQL/MariaDB with Philippines timezone (Asia/Manila, UTC+8).

## Configuration Changes Made

1. **Application Timezone** (`config/app.php`)
   - Set to `Asia/Manila` (Philippines timezone)
   - Can be overridden with `APP_TIMEZONE` in `.env`

2. **Database Timezone** (`config/database.php`)
   - MySQL/MariaDB connections configured with timezone `+08:00`
   - Automatically sets timezone on connection via `PDO::MYSQL_ATTR_INIT_COMMAND`

3. **AppServiceProvider** (`app/Providers/AppServiceProvider.php`)
   - Sets PHP timezone on application boot
   - Sets MySQL session timezone for all connections

## Environment Variables for MySQL

Add these to your `.env` file when switching to MySQL:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pawnshopms
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Optional: Override timezone if needed
APP_TIMEZONE=Asia/Manila
DB_TIMEZONE=+08:00
```

## Setting MySQL Server Timezone (Optional but Recommended)

For production, you may want to set the MySQL server timezone globally:

```sql
-- Set global timezone (requires SUPER privilege)
SET GLOBAL time_zone = '+08:00';

-- Or set in MySQL configuration file (my.cnf or my.ini)
-- Add: default-time-zone = '+08:00'
```

## Migration

A migration has been created (`2025_12_14_211418_set_mysql_timezone_to_philippines.php`) that sets the session timezone when running migrations. This ensures all database operations use Philippines timezone.

## Testing Timezone

To verify the timezone is set correctly:

```php
// In tinker or controller
php artisan tinker

// Check application timezone
config('app.timezone'); // Should return 'Asia/Manila'

// Check current time
now()->timezone; // Should show Asia/Manila
now()->format('Y-m-d H:i:s T'); // Should show Philippines time

// Check database timezone (if using MySQL)
DB::select("SELECT @@session.time_zone"); // Should return '+08:00'
```

## Notes

- The application will work with SQLite (current default) and automatically switch to Philippines timezone when you change to MySQL
- All timestamps in the database will be stored in UTC, but displayed in Philippines timezone
- Laravel's `now()`, `Carbon::now()`, and date formatting will automatically use the configured timezone

