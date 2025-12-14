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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number')->unique();
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type')->default('sangla'); // sangla, tubos, renew, partial
            $table->string('first_name');
            $table->string('last_name');
            $table->text('address');
            $table->decimal('appraised_value', 15, 2);
            $table->decimal('loan_amount', 15, 2);
            $table->decimal('interest_rate', 5, 2);
            $table->string('interest_rate_period'); // per_annum, per_month, others
            $table->date('maturity_date');
            $table->date('expiry_date');
            $table->foreignId('item_type_id')->constrained()->onDelete('cascade');
            $table->foreignId('item_type_subtype_id')->nullable()->constrained('item_type_subtypes')->onDelete('set null');
            $table->string('custom_item_type')->nullable();
            $table->text('item_description');
            $table->string('item_image_path');
            $table->string('pawner_id_image_path');
            $table->decimal('grams', 8, 1)->nullable();
            $table->string('orcr_serial')->nullable();
            $table->decimal('service_charge', 10, 2)->default(0);
            $table->decimal('net_proceeds', 15, 2);
            $table->string('status')->default('active'); // active, redeemed, expired
            $table->timestamps();
        });

        Schema::create('transaction_item_type_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->onDelete('cascade');
            $table->foreignId('item_type_tag_id')->constrained('item_type_tags')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['transaction_id', 'item_type_tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_item_type_tags');
        Schema::dropIfExists('transactions');
    }
};
