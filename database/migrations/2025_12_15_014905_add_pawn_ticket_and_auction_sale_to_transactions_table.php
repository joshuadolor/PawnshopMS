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
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('pawn_ticket_number')->nullable()->after('expiry_date');
            $table->string('pawn_ticket_image_path')->nullable()->after('pawn_ticket_number');
            $table->date('auction_sale_date')->nullable()->after('pawn_ticket_image_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['pawn_ticket_number', 'pawn_ticket_image_path', 'auction_sale_date']);
        });
    }
};
