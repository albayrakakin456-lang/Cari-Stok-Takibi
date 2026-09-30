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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('user_id')
                ->constrained()->nullOnDelete();
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->decimal('discount_rate', 5, 2)->default(0);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('campaign_discount', 12, 2)->default(0);
            $table->decimal('customer_discount', 12, 2)->default(0);
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('original_price', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['original_price', 'discount_amount']);
        });
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['subtotal', 'campaign_discount', 'customer_discount']);
        });
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('discount_rate');
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });
        Schema::dropIfExists('categories');
    }
};
