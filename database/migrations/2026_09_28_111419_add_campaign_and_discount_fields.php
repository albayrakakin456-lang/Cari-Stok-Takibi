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
        // Canlı sunucuda bir migration yarıda kalmışsa tabloyu tekrar oluşturmaya çalışma.
        if (! Schema::hasTable('categories')) {
            Schema::create('categories', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('name');
                $table->timestamps();

                $table->index('user_id');
            });
        }

        if (! Schema::hasColumn('products', 'category_id')) {
            Schema::table('products', function (Blueprint $table) {
                // Bazı paylaşımlı sunucularda eski tabloların motoru FK oluşturmaya
                // uygun olmayabilir. İlişki Eloquent seviyesinde çalışmaya devam eder.
                $table->unsignedBigInteger('category_id')->nullable()->after('user_id');
                $table->index('category_id');
            });
        }

        if (! Schema::hasColumn('contacts', 'discount_rate')) {
            Schema::table('contacts', function (Blueprint $table) {
                $table->decimal('discount_rate', 5, 2)->default(0);
            });
        }

        if (! Schema::hasColumn('sales', 'subtotal')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->decimal('subtotal', 12, 2)->default(0);
            });
        }
        if (! Schema::hasColumn('sales', 'campaign_discount')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->decimal('campaign_discount', 12, 2)->default(0);
            });
        }
        if (! Schema::hasColumn('sales', 'customer_discount')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->decimal('customer_discount', 12, 2)->default(0);
            });
        }

        if (! Schema::hasColumn('sale_items', 'original_price')) {
            Schema::table('sale_items', function (Blueprint $table) {
                $table->decimal('original_price', 12, 2)->default(0);
            });
        }
        if (! Schema::hasColumn('sale_items', 'discount_amount')) {
            Schema::table('sale_items', function (Blueprint $table) {
                $table->decimal('discount_amount', 12, 2)->default(0);
            });
        }
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
        if (Schema::hasColumn('products', 'category_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('category_id');
            });
        }
        Schema::dropIfExists('categories');
    }
};
