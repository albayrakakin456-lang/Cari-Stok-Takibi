<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tables = ['contacts', 'products', 'sales', 'purchases', 'cash_transactions', 'stock_movements'];

        // 1. user_id sütununu tüm ilgili tablolara ekle
        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->onDelete('cascade');
            });
        }

        // 2. Canlı veritabanındaki mevcut kayıtları sistemdeki ilk kullanıcıya (Admin / Akın Albayrak) zimmetle
        $firstUserId = DB::table('users')->min('id');
        if ($firstUserId) {
            foreach ($tables as $tableName) {
                DB::table($tableName)->whereNull('user_id')->update(['user_id' => $firstUserId]);
            }
        }

        // 3. Unique kısıtlamalarını kullanıcı bazlı (composite unique) hale getir
        // Ürün Kodu: Farklı kullanıcılar aynı stok kodunu kullanabilmeli
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->unique(['user_id', 'code']);
        });

        // Satış Fatura Numarası
        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique(['invoice_number']);
            $table->unique(['user_id', 'invoice_number']);
        });

        // Alış Fatura Numarası
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropUnique(['invoice_number']);
            $table->unique(['user_id', 'invoice_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'invoice_number']);
            $table->unique('invoice_number');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'invoice_number']);
            $table->unique('invoice_number');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'code']);
            $table->unique('code');
        });

        $tables = ['contacts', 'products', 'sales', 'purchases', 'cash_transactions', 'stock_movements'];
        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            });
        }
    }
};