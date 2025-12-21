<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('additional_charge_configs', function (Blueprint $table) {
            $table->id();
            $table->integer('start_day')->comment('Start day (e.g., 4)');
            $table->integer('end_day')->comment('End day (e.g., 31)');
            $table->decimal('percentage', 5, 2)->comment('Percentage to multiply with principal (e.g., 2.00)');
            $table->enum('type', ['LD', 'EC'])->comment('LD = Late Days, EC = Exceeded Charge');
            $table->enum('transaction_type', ['renewal', 'tubos'])->comment('Transaction type this applies to');
            $table->timestamps();
            
            // Index for faster lookups
            $table->index(['transaction_type', 'type', 'start_day', 'end_day']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('additional_charge_configs');
    }
};
