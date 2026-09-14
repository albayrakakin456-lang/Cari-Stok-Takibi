<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. CARİLER (Müşteriler ve Tedarikçiler)
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['customer', 'supplier']);
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->text('note')->nullable();
            $table->decimal('balance', 10, 2)->default(0); // Cari bakiye sütunu
            $table->timestamps();
        });

        // 2. ÜRÜNLER
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique(); // Controller ve modelde beklenen zorunlu stok kodu
            $table->string('barcode')->nullable();
            $table->decimal('purchase_price', 10, 2)->default(0);
            $table->decimal('sale_price', 10, 2)->default(0);
            $table->integer('tax_rate')->default(20); // KDV Oranı (%)
            $table->integer('stock')->default(0);
            $table->integer('min_stock')->default(10); // Kritik stok seviyesi
            $table->timestamps();
        });

        // 3. FATURALAR (Satış Başlıkları)
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained('contacts')->onDelete('cascade');
            $table->string('invoice_number')->unique();
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->timestamps();
        });

        // 4. FATURA KALEMLERİ
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total', 10, 2);
            $table->timestamps();
        });

        // 5. STOK HAREKETLERİ
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->enum('type', ['in', 'out']); 
            $table->integer('quantity');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // 6. KASA HAREKETLERİ
        Schema::create('cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->onDelete('set null');
            $table->enum('type', ['in', 'out']); 
            $table->decimal('amount', 10, 2);
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_transactions');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('products');
        Schema::dropIfExists('contacts');
    }
};