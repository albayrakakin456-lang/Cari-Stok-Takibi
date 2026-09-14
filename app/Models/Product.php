<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use BelongsToUser;

    protected $fillable = ['user_id', 'name', 'code', 'barcode', 'purchase_price', 'sale_price', 'tax_rate', 'stock', 'min_stock'];

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }
}
