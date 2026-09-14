<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    protected $fillable = ['purchase_id', 'product_id', 'quantity', 'unit_price', 'total'];

    /**
     * Kalemin ait olduğu Alış Faturası
     */
    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    /**
     * Kalemdeki Ürün
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

