<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('external_reference', 100)->nullable()->after('invoice_number');
            $table->string('request_fingerprint', 64)->nullable()->after('external_reference');
            $table->string('status', 20)->default('active')->after('total_amount');
            $table->timestamp('cancelled_at')->nullable()->after('status');
            $table->unique(['user_id', 'external_reference']);
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('sale_id')->nullable()->after('product_id')->constrained('sales')->nullOnDelete();
        });

        Schema::table('cash_transactions', function (Blueprint $table) {
            $table->foreignId('sale_id')->nullable()->after('contact_id')->constrained('sales')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cash_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sale_id');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sale_id');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'external_reference']);
            $table->dropColumn(['external_reference', 'request_fingerprint', 'status', 'cancelled_at']);
        });
    }
};
