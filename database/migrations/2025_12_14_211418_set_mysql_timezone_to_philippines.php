<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Note: This sets the session timezone for MySQL/MariaDB.
     * The timezone is also configured in config/database.php and AppServiceProvider.
     * For production, you may want to set the MySQL server timezone globally:
     * SET GLOBAL time_zone = '+08:00';
     */
    public function up(): void
    {
        if (config('database.default') === 'mysql' || config('database.default') === 'mariadb') {
            DB::statement("SET time_zone='+08:00'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Timezone setting is session-based, no rollback needed
    }
};
