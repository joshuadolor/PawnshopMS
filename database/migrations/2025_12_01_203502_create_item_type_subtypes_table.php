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
        Schema::create('item_type_subtypes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_type_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->timestamps();
            
            $table->unique(['item_type_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_type_subtypes');
    }
};
