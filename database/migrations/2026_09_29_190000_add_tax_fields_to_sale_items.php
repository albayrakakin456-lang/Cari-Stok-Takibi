<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table): void {
            // Oran satış anında saklanır; ürünün oranı sonradan değişse de eski fatura bozulmaz.
            $table->unsignedTinyInteger('tax_rate')->nullable()->after('discount_amount');
            // Toplam değişmez: bu alan KDV dahil net tutarın içindeki KDV payıdır.
            $table->decimal('tax_amount', 15, 2)->default(0)->after('tax_rate');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table): void {
            $table->dropColumn(['tax_rate', 'tax_amount']);
        });
    }
};
