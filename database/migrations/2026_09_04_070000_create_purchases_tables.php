<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. ALIŞ FATURALARI (Başlık)
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            // Tedarikçiye bağlanır (contacts tablosu)
            $table->foreignId('contact_id')->constrained('contacts')->onDelete('cascade');
            $table->string('invoice_number')->unique();
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->timestamps();
        });

        // 2. ALIŞ FATURA KALEMLERİ
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Önce bağımlı alt tablo, sonra ana tablo silinir (Foreign Key kuralı)
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
    }
};

