<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For SQLite, we need to recreate the table with the new CHECK constraint
        if (config('database.default') === 'sqlite') {
            // Drop the old CHECK constraint and recreate the table
            DB::statement('PRAGMA foreign_keys=off;');
            
            // Create new table with updated constraint
            DB::statement("
                CREATE TABLE branch_financial_transactions_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    branch_id INTEGER NOT NULL,
                    user_id INTEGER NOT NULL,
                    type TEXT NOT NULL CHECK(type IN ('expense', 'replenish', 'transaction')),
                    description TEXT NOT NULL,
                    amount DECIMAL(15, 2) NOT NULL,
                    transaction_date DATE NOT NULL,
                    created_at TIMESTAMP,
                    updated_at TIMESTAMP,
                    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )
            ");
            
            // Copy data
            DB::statement('INSERT INTO branch_financial_transactions_new SELECT * FROM branch_financial_transactions');
            
            // Drop old table
            DB::statement('DROP TABLE branch_financial_transactions');
            
            // Rename new table
            DB::statement('ALTER TABLE branch_financial_transactions_new RENAME TO branch_financial_transactions');
            
            // Recreate indexes
            DB::statement('CREATE INDEX branch_financial_transactions_branch_id_transaction_date_index ON branch_financial_transactions(branch_id, transaction_date)');
            DB::statement('CREATE INDEX branch_financial_transactions_type_index ON branch_financial_transactions(type)');
            
            DB::statement('PRAGMA foreign_keys=on;');
        } elseif (config('database.default') === 'mysql' || config('database.default') === 'mariadb') {
            // For MySQL/MariaDB, modify the enum
            DB::statement("ALTER TABLE branch_financial_transactions MODIFY COLUMN type ENUM('expense', 'replenish', 'transaction') NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=off;');
            
            DB::statement("
                CREATE TABLE branch_financial_transactions_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    branch_id INTEGER NOT NULL,
                    user_id INTEGER NOT NULL,
                    type TEXT NOT NULL CHECK(type IN ('expense', 'replenish')),
                    description TEXT NOT NULL,
                    amount DECIMAL(15, 2) NOT NULL,
                    transaction_date DATE NOT NULL,
                    created_at TIMESTAMP,
                    updated_at TIMESTAMP,
                    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )
            ");
            
            // Only copy data that matches old constraint
            DB::statement("INSERT INTO branch_financial_transactions_new SELECT * FROM branch_financial_transactions WHERE type IN ('expense', 'replenish')");
            
            DB::statement('DROP TABLE branch_financial_transactions');
            DB::statement('ALTER TABLE branch_financial_transactions_new RENAME TO branch_financial_transactions');
            
            DB::statement('CREATE INDEX branch_financial_transactions_branch_id_transaction_date_index ON branch_financial_transactions(branch_id, transaction_date)');
            DB::statement('CREATE INDEX branch_financial_transactions_type_index ON branch_financial_transactions(type)');
            
            DB::statement('PRAGMA foreign_keys=on;');
        } elseif (config('database.default') === 'mysql' || config('database.default') === 'mariadb') {
            DB::statement("ALTER TABLE branch_financial_transactions MODIFY COLUMN type ENUM('expense', 'replenish') NOT NULL");
        }
    }
};
